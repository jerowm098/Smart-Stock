<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

// Web auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->name('register.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// API auth and registration endpoints (JSON responses)
Route::post('/api/auth/login', [AuthController::class, 'apiLogin'])->name('api.auth.login')->middleware('guest');
Route::post('/api/auth/logout', [AuthController::class, 'apiLogout'])->name('api.auth.logout')->middleware('auth');
Route::post('/api/users/register', [AuthController::class, 'apiRegister'])->name('api.users.register')->middleware('guest');

// Public homepage (accessible to guests and authenticated users)
Route::get('/', fn () => redirect()->route('home'));
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Overview / dashboard
    Route::get('/dashboard', [InventoryController::class, 'index'])->name('dashboard');

    // Products page
    Route::get('/products', [InventoryController::class, 'products'])->name('products');

    // API routes
    Route::post('/api/inventory/add', [InventoryController::class, 'store'])->name('inventory.add');
    Route::put('/api/inventory/update', [InventoryController::class, 'updateProduct'])->name('inventory.update.product');
    Route::put('/api/inventory/{product}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::get('/api/inventory/alerts', [InventoryController::class, 'getAlerts'])->name('inventory.alerts');
    Route::get('/api/inventory/products', [InventoryController::class, 'getProducts'])->name('inventory.products');
    Route::delete('/api/inventory/{product}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
});
