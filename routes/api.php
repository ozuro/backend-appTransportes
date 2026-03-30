<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\OperatingExpenseController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\Sunat\ElectronicDocumentController;
use App\Http\Controllers\Api\Sunat\SunatConfigController;
use App\Http\Controllers\Api\Sunat\SunatDemoController;
use App\Http\Controllers\Api\TransportServiceController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register-owner', [AuthController::class, 'registerCompanyOwner']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/firebase-google', [AuthController::class, 'firebaseGoogleLogin']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/company', [AuthController::class, 'createCompany']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('clients', ClientController::class);
        Route::apiResource('vehicles', VehicleController::class);
        Route::apiResource('drivers', DriverController::class);
        Route::apiResource('services', TransportServiceController::class);
        Route::apiResource('quotations', QuotationController::class);
        Route::apiResource('expenses', OperatingExpenseController::class);

        Route::prefix('sunat')->group(function () {
            Route::get('/config', [SunatConfigController::class, 'show']);
            Route::put('/config', [SunatConfigController::class, 'update']);
            Route::post('/demo/company', [SunatDemoController::class, 'prepareCompany']);
            Route::get('/documents', [ElectronicDocumentController::class, 'index']);
            Route::post('/documents', [ElectronicDocumentController::class, 'store']);
            Route::get('/documents/{document}', [ElectronicDocumentController::class, 'show']);
            Route::post('/documents/{document}/send', [ElectronicDocumentController::class, 'send']);
        });
    });
});
