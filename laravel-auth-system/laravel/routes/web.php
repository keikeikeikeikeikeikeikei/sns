<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\FingerprintController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rate Limit Check API (Public)
Route::get('/rate-limit-status', function (Request $request) {
    $ip = $request->ip();
    $attempts = RateLimiter::attempts('register:'.$ip); 
    $maxAttempts = 3;
    
    return response()->json([
        'ip' => $ip,
        'user_agent' => $request->userAgent(),
        'policy' => '3 requests per minute',
        'attempts_made' => $attempts,
        'remaining_estimate' => max(0, $maxAttempts - $attempts),
    ]);
});

// Fingerprint Collection API
Route::post('/api/fingerprint', [FingerprintController::class, 'store']);

// SSO Token Issue
Route::get('/auth/sso/sns', function (Request $request) {
    $user = $request->user();
    if (!$user) {
        return redirect('/login');
    }
    
    // Create a new token for SNS app
    $token = $user->createToken('sns-app-token')->plainTextToken;
    
    // Redirect to SNS app with token
    // Assuming SNS app is at /sns
    return redirect('/sns?token=' . urlencode($token));
})->middleware(['auth']);

// SSO for V2
Route::get('/auth/sso/v2', function (Request $request) {
    $user = $request->user();
    if (!$user) {
        return redirect('/login');
    }
    
    // Create token for V2
    $token = $user->createToken('v2-sso-token')->plainTextToken;
    
    // Redirect to V2 login with token
    // V2 is at /v2
    return redirect('/v2/login.html?token=' . urlencode($token));
})->middleware(['auth']);

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
                ->middleware('throttle:register'); // Custom rate limit

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [VerifyEmailController::class, '__invoke'])
                ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, 'store'])
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');

    // Settings Routes
    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
});
