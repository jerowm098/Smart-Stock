<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->name('register.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Dashboard (requires auth)
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect('/dashboard'));
    // Overview page (renamed from dashboard content)
    Route::get('/dashboard', [InventoryController::class, 'index'])->name('dashboard');
    // Products page
    Route::get('/products', [InventoryController::class, 'products'])->name('products');

    // API routes
    Route::post('/api/inventory/add', [InventoryController::class, 'store'])->name('inventory.add');
    Route::get('/api/inventory/alerts', [InventoryController::class, 'getAlerts'])->name('inventory.alerts');
    Route::get('/api/inventory/products', [InventoryController::class, 'getProducts'])->name('inventory.products');
    Route::delete('/api/inventory/{product}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
});
