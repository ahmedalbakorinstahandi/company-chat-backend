<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\StoryController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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



    // get user chats
    Route::get('user-chats', [MessageController::class, 'getUserChats']);

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

// Multi-cluster Pusher test
Route::post('/test-pusher-clusters', function (Request $request) {
    $clusters = ['us1', 'us2', 'us3', 'eu', 'ap1', 'ap2', 'ap3'];
    $results = [];
    
    foreach ($clusters as $cluster) {
        try {
            $pusher = new \Pusher\Pusher(
                'aad0418787f85ad833f7',
                'd82e595a10197a5be4a4',
                '2007760',
                [
                    'cluster' => $cluster,
                    'useTLS' => true,
                ]
            );
            
            $result = $pusher->trigger('test-channel', 'test-event', [
                'message' => "Test from cluster {$cluster}",
                'timestamp' => now()->toISOString()
            ]);
            
            $results[$cluster] = [
                'success' => true,
                'result' => $result
            ];
            
        } catch (Exception $e) {
            $results[$cluster] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return response()->json([
        'success' => true,
        'message' => 'Multi-cluster test results',
        'results' => $results
    ]);
});

// DNS test route
Route::get('/test-dns', function (Request $request) {
    $hosts = [
        'api-us3.pusherapp.com',
        'api-us2.pusherapp.com',
        'api-us1.pusherapp.com',
        'google.com', // Test with a known working domain
    ];
    
    $results = [];
    
    foreach ($hosts as $host) {
        $ip = gethostbyname($host);
        $results[$host] = [
            'ip' => $ip,
            'resolved' => $ip !== $host,
            'ping' => false
        ];
        
        // Try to ping the host
        if ($ip !== $host) {
            $connection = @fsockopen($host, 443, $errno, $errstr, 5);
            $results[$host]['ping'] = $connection !== false;
            if ($connection) {
                fclose($connection);
            }
        }
    }
    
    return response()->json([
        'success' => true,
        'message' => 'DNS test results',
        'results' => $results
    ]);
});

// Direct Pusher test (bypasses config)
Route::post('/test-pusher-direct', function (Request $request) {
    try {
        // Test with hardcoded values to see if credentials work
        $pusher = new \Pusher\Pusher(
            'aad0418787f85ad833f7',
            'd82e595a10197a5be4a4',
            '2007760',
            [
                'cluster' => 'us3',
                'useTLS' => true,
                'host' => 'api-us3.pusherapp.com',
                'port' => 443,
                'scheme' => 'https',
            ]
        );
        
        $result = $pusher->trigger('test-channel', 'test-event', [
            'message' => 'Direct test message',
            'timestamp' => now()->toISOString()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Direct Pusher test successful',
            'result' => $result
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Direct Pusher test failed',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
});

// Test Pusher route
Route::post('/test-pusher', function (Request $request) {
    // Check configuration
    $config = [
        'key' => config('services.pusher.key'),
        'secret' => config('services.pusher.secret'),
        'app_id' => config('services.pusher.app_id'),
        'cluster' => config('services.pusher.cluster'),
        'host' => config('services.pusher.host'),
        'port' => config('services.pusher.port'),
        'scheme' => config('services.pusher.scheme'),
    ];
    
    // Check if any required config is missing
    $missing = [];
    foreach (['key', 'secret', 'app_id', 'cluster'] as $required) {
        if (empty($config[$required])) {
            $missing[] = $required;
        }
    }
    
    if (!empty($missing)) {
        return response()->json([
            'success' => false,
            'message' => 'Missing Pusher configuration: ' . implode(', ', $missing),
            'config' => $config
        ]);
    }
    
    // Create PusherService to see the actual host being used
    $pusher = new \App\Services\PusherService();
    $pusherInstance = $pusher->getPusher();
    
    // Get the actual configuration from the Pusher instance
    $actualConfig = [
        'key' => $config['key'],
        'secret' => $config['secret'],
        'app_id' => $config['app_id'],
        'cluster' => $config['cluster'],
        'host' => $pusherInstance->getSettings()['host'] ?? 'unknown',
        'port' => $config['port'],
        'scheme' => $config['scheme'],
    ];
    
    $result = $pusher->sendMessage('test-channel', 'test-event', [
        'message' => 'Test message from Laravel',
        'timestamp' => now()->toISOString()
    ]);
    
    return response()->json([
        'success' => $result !== false,
        'message' => $result !== false ? 'Pusher test successful' : 'Pusher test failed',
        'result' => $result,
        'config' => $config,
        'actual_config' => $actualConfig
    ]);
});

// Simple Pusher test (no custom host)
Route::post('/test-pusher-simple', function (Request $request) {
    try {
        // Test with minimal configuration
        $pusher = new \Pusher\Pusher(
            'aad0418787f85ad833f7',
            'd82e595a10197a5be4a4',
            '2007760',
            [
                'cluster' => 'us3',
                'useTLS' => true,
            ]
        );
        
        $result = $pusher->trigger('test-channel', 'test-event', [
            'message' => 'Simple test message',
            'timestamp' => now()->toISOString()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Simple Pusher test successful',
            'result' => $result
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Simple Pusher test failed',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
});
