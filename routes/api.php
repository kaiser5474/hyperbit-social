<?php

use App\Http\Controllers\DataDeletionController;
use Illuminate\Support\Facades\Route;

// Meta data deletion callback
Route::post('/meta/data-deletion', [DataDeletionController::class, 'callback'])
    ->name('api.meta.data-deletion');
