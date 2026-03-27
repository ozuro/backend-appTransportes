<?php

namespace App\Services\Auth;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
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
}
