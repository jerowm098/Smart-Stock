<?php

use App\Http\Controllers\InputController;
use Illuminate\Support\Facades\Route;

// The single page of this app: inputs
Route::get('/', [InputController::class, 'create']);
Route::get('/inputs', [InputController::class, 'create'])->name('inputs');
Route::post('/inputs', [InputController::class, 'store'])->name('inputs.store');

// Listing page for all submitted inputs
Route::get('/info', [InputController::class, 'index'])->name('info');
