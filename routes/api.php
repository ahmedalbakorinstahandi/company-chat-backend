<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\StoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Company routes
    // Route::apiResource('companies', CompanyController::class);
    Route::get('companies', [CompanyController::class, 'index']);
    Route::get('companies/{id}', [CompanyController::class, 'show']);
    Route::post('companies', [CompanyController::class, 'store']);
    Route::put('companies/{id}', [CompanyController::class, 'update']);
    Route::delete('companies/{id}', [CompanyController::class, 'destroy']);
    Route::post('companies/{company}/employees', [CompanyController::class, 'addEmployee']);
    Route::delete('companies/{company}/employees', [CompanyController::class, 'removeEmployee']);

    // Message routes
    Route::get('messages', [MessageController::class, 'index']);
    Route::post('messages', [MessageController::class, 'store']);
    Route::post('messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::delete('messages/{message}', [MessageController::class, 'destroy']);

    // Story routes
    // Route::apiResource('stories', StoryController::class);
    Route::get('stories', [StoryController::class, 'index']);
    Route::get('stories/{id}', [StoryController::class, 'show']);
    Route::post('stories', [StoryController::class, 'store']);
    Route::delete('stories/{id}', [StoryController::class, 'destroy']);
    Route::post('stories/{id}/view', [StoryController::class, 'view']);
});
