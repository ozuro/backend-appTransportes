<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CreateCompanyRequest;
use App\Http\Requests\Auth\FirebaseGoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterCompanyOwnerRequest;
use App\Http\Resources\Auth\AuthUserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function registerCompanyOwner(RegisterCompanyOwnerRequest $request): JsonResponse
    {
        $result = $this->authService->registerCompanyOwner($request->validated());

        return response()->json([
            'message' => 'Empresa y usuario propietario creados correctamente.',
            'token' => $result['token'],
            'company' => $result['company'],
            'user' => new AuthUserResource($result['user']),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'token' => $result['token'],
            'user' => new AuthUserResource($result['user']),
        ]);
    }

    public function firebaseGoogleLogin(FirebaseGoogleLoginRequest $request): JsonResponse
    {
        $result = $this->authService->loginWithFirebaseGoogle($request->validated());

        return response()->json([
            'message' => 'Inicio de sesión con Google exitoso.',
            'token' => $result['token'],
            'requires_company' => $result['requires_company'],
            'user' => new AuthUserResource($result['user']),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('companies');

        return response()->json([
            'user' => new AuthUserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function createCompany(Request $request, CreateCompanyRequest $companyRequest): JsonResponse
    {
        $result = $this->authService->createCompanyForUser($request->user(), $companyRequest->validated());

        return response()->json([
            'message' => 'Empresa creada correctamente.',
            'company' => $result['company'],
            'user' => new AuthUserResource($result['user']),
        ], 201);
    }
}
