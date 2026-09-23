<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosCheckoutController;
use App\Http\Controllers\SupplierController;
use App\Http\Middleware\EnsureUserIsAdmin;
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

    // POS checkout page
    Route::get('/pos', [PosCheckoutController::class, 'index'])->name('pos');

    // Products page
    Route::get('/products', [InventoryController::class, 'products'])->name('products');

    // Stock-In / Receiving page (SS-87)
    Route::get('/stock-in', [InventoryController::class, 'stockIn'])->name('stock-in');

    // Suppliers directory page
    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->name('suppliers')
        ->middleware(EnsureUserIsAdmin::class);

    // API routes
    Route::post('/api/inventory/add', [InventoryController::class, 'store'])->name('inventory.add');
    Route::put('/api/inventory/update', [InventoryController::class, 'updateProduct'])->name('inventory.update.product');
    Route::put('/api/inventory/{product}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::get('/api/inventory/alerts', [InventoryController::class, 'getAlerts'])->name('inventory.alerts');
    Route::get('/api/inventory/products', [InventoryController::class, 'getProducts'])->name('inventory.products');
    Route::delete('/api/inventory/{product}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    Route::post('/api/inventory/adjust', [InventoryController::class, 'adjustStock'])
        ->name('inventory.adjust')
        ->middleware(EnsureUserIsAdmin::class);

    // SS-88: Stock-In / Receiving API endpoint
    Route::post('/api/inventory/stock-in', [InventoryController::class, 'storeStockIn'])
        ->name('inventory.stock-in')
        ->middleware(EnsureUserIsAdmin::class);

    // POS checkout API route
    Route::post('/api/pos/checkout', [PosCheckoutController::class, 'checkout'])
        ->name('pos.checkout');

    // Supplier directory API routes
    Route::get('/api/suppliers/active', [SupplierController::class, 'getActive'])
        ->name('suppliers.active')
        ->middleware(EnsureUserIsAdmin::class);
    Route::post('/api/suppliers', [SupplierController::class, 'store'])
        ->name('suppliers.store')
        ->middleware(EnsureUserIsAdmin::class);
});
