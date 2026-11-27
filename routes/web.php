<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\FacebookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataDeletionController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/privacy', [LandingController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LandingController::class, 'terms'])->name('terms');
Route::get('/data-deletion', [LandingController::class, 'dataDeletion'])->name('data-deletion');
Route::get('/data-deletion/status/{code}', [DataDeletionController::class, 'status'])->name('data-deletion.status');

// Locale-prefixed landing routes
Route::get('/en', [LandingController::class, 'indexEn'])->name('home.en');
Route::get('/es', [LandingController::class, 'indexEs'])->name('home.es');

// Language switching
Route::get('/lang/{locale}', [LandingController::class, 'setLocale'])->name('lang.switch');

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => redirect()->route('home'))->name('login');

    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/auth/facebook', [FacebookController::class, 'redirect'])->name('auth.facebook');
        Route::get('/auth/facebook/callback', [FacebookController::class, 'callback'])->name('auth.facebook.callback');
    });
});

Route::post('/logout', [FacebookController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Connected accounts
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('/connect', [AccountController::class, 'connect'])->name('connect');
        Route::post('/store', [AccountController::class, 'store'])->name('store');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
        Route::post('/{account}/refresh', [AccountController::class, 'refresh'])->name('refresh');
    });

    // Posts
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('/create', [PostController::class, 'create'])->name('create');
        Route::post('/', [PostController::class, 'store'])->name('store');
        Route::get('/{post}', [PostController::class, 'show'])->name('show');
        Route::get('/{post}/edit', [PostController::class, 'edit'])->name('edit');
        Route::put('/{post}', [PostController::class, 'update'])->name('update');
        Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
        Route::post('/{post}/cancel', [PostController::class, 'cancel'])->name('cancel');
        Route::post('/{post}/publish-now', [PostController::class, 'publishNow'])->name('publish-now');
        Route::post('/{post}/insights/refresh', [PostController::class, 'refreshInsights'])
            ->middleware('throttle:20,1')
            ->name('insights.refresh');
    });

    // Calendar view
    Route::get('/calendar', [PostController::class, 'calendar'])->name('calendar');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
