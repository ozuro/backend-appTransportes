<?php

namespace App\Services\Auth;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private Client $httpClient;

    public function __construct(?Client $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Client();
    }

    public function registerCompanyOwner(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $companyData = $payload['company'];
            $userData = $payload['user'];

            $company = Company::create([
                'trade_name' => $companyData['trade_name'],
                'legal_name' => $companyData['legal_name'] ?? null,
                'ruc' => $companyData['ruc'] ?? null,
                'email' => $companyData['email'] ?? null,
                'phone' => $companyData['phone'] ?? null,
                'address' => $companyData['address'] ?? null,
                'district' => $companyData['district'] ?? null,
                'province' => $companyData['province'] ?? null,
                'department' => $companyData['department'] ?? null,
                'country_code' => $companyData['country_code'] ?? 'PE',
                'currency_code' => $companyData['currency_code'] ?? 'PEN',
                'timezone' => $companyData['timezone'] ?? 'America/Lima',
            ]);

            $user = User::create([
                'name' => $userData['name'],
                'first_name' => $userData['first_name'] ?? null,
                'last_name' => $userData['last_name'] ?? null,
                'email' => $userData['email'],
                'phone' => $userData['phone'] ?? null,
                'document_type' => $userData['document_type'] ?? null,
                'document_number' => $userData['document_number'] ?? null,
                'password' => Hash::make($userData['password']),
                'auth_provider' => 'password',
                'is_active' => true,
                'last_login_at' => now(),
            ]);

            $ownerRole = Role::where('code', 'owner')->firstOrFail();

            $company->users()->attach($user->id, [
                'role_id' => $ownerRole->id,
                'is_owner' => true,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            $token = $user->createToken($payload['device_name'] ?? 'transportes-app')->plainTextToken;

            return [
                'user' => $user->load('companies'),
                'company' => $company,
                'token' => $token,
            ];
        });
    }

    public function login(array $credentials): array
    {
        $user = User::with('companies')->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales ingresadas no son válidas.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['El usuario se encuentra inactivo.'],
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $token = $user->createToken($credentials['device_name'] ?? 'transportes-app')->plainTextToken;

        return [
            'user' => $user->fresh()->load('companies'),
            'token' => $token,
        ];
    }

    public function loginWithFirebaseGoogle(array $payload): array
    {
        $firebaseUser = $this->fetchFirebaseUser($payload['id_token']);
        $email = $firebaseUser['email'] ?? null;
        $firebaseUid = $firebaseUser['localId'] ?? null;

        if (! $email || ! $firebaseUid) {
            throw ValidationException::withMessages([
                'id_token' => ['No se pudo validar el usuario de Google desde Firebase.'],
            ]);
        }

        $user = User::with('companies')
            ->where('firebase_uid', $firebaseUid)
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $displayName = $firebaseUser['displayName'] ?? $email;
            [$firstName, $lastName] = $this->splitName($displayName);

            $user = User::create([
                'name' => $displayName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'firebase_uid' => $firebaseUid,
                'avatar_url' => $firebaseUser['photoUrl'] ?? null,
                'password' => Hash::make(Str::random(32)),
                'auth_provider' => 'google',
                'is_active' => true,
                'last_login_at' => now(),
            ]);
        } else {
            $displayName = $firebaseUser['displayName'] ?? $user->name;
            [$firstName, $lastName] = $this->splitName($displayName);

            $user->forceFill([
                'name' => $displayName,
                'email' => $email,
                'firebase_uid' => $firebaseUid,
                'avatar_url' => $firebaseUser['photoUrl'] ?? $user->avatar_url,
                'first_name' => $firstName ?: $user->first_name,
                'last_name' => $lastName ?: $user->last_name,
                'auth_provider' => 'google',
                'last_login_at' => now(),
            ])->save();
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['El usuario se encuentra inactivo.'],
            ]);
        }

        $token = $user->createToken($payload['device_name'] ?? 'transportes-app')->plainTextToken;
        $user = $user->fresh()->load('companies');

        return [
            'user' => $user,
            'token' => $token,
            'requires_company' => $user->companies->isEmpty(),
        ];
    }

    public function createCompanyForUser(User $user, array $companyData): array
    {
        return DB::transaction(function () use ($user, $companyData) {
            $company = Company::create([
                'trade_name' => $companyData['trade_name'],
                'legal_name' => $companyData['legal_name'] ?? null,
                'ruc' => $companyData['ruc'] ?? null,
                'email' => $companyData['email'] ?? $user->email,
                'phone' => $companyData['phone'] ?? $user->phone,
                'address' => $companyData['address'] ?? null,
                'district' => $companyData['district'] ?? null,
                'province' => $companyData['province'] ?? null,
                'department' => $companyData['department'] ?? null,
                'country_code' => 'PE',
                'currency_code' => 'PEN',
                'timezone' => 'America/Lima',
            ]);

            $ownerRole = Role::where('code', 'owner')->firstOrFail();

            $company->users()->attach($user->id, [
                'role_id' => $ownerRole->id,
                'is_owner' => true,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            return [
                'company' => $company,
                'user' => $user->fresh()->load('companies'),
            ];
        });
    }

    private function fetchFirebaseUser(string $idToken): array
    {
        $apiKey = env('FIREBASE_WEB_API_KEY');

        if (! $apiKey) {
            throw ValidationException::withMessages([
                'firebase' => ['Falta configurar FIREBASE_WEB_API_KEY en el backend.'],
            ]);
        }

        $response = $this->httpClient->post(
            "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$apiKey}",
            [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => ['idToken' => $idToken],
            ]
        );

        $payload = json_decode((string) $response->getBody(), true);
        $users = $payload['users'] ?? [];

        if (empty($users)) {
            throw ValidationException::withMessages([
                'id_token' => ['No se encontró un usuario válido en Firebase.'],
            ]);
        }

        return $users[0];
    }

    private function splitName(string $displayName): array
    {
        $parts = preg_split('/\s+/', trim($displayName)) ?: [];

        if (count($parts) <= 1) {
            return [$displayName, null];
        }

        $firstName = array_shift($parts);
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName];
    }
}
