<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosCheckoutController;
use App\Http\Controllers\SupplierController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsCashier;
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

    // POS checkout page (cashier-only)
    Route::get('/pos', [PosCheckoutController::class, 'index'])
        ->name('pos')
        ->middleware(EnsureUserIsCashier::class);

    // Products page
    Route::get('/products', [InventoryController::class, 'products'])->name('products');

    // Stock-In / Receiving page (SS-87)
    Route::get('/stock-in', [InventoryController::class, 'stockIn'])
        ->name('stock-in')
        ->middleware(EnsureUserIsAdmin::class);

    // SS-24: Transaction History page (Admin only per BRD)
    Route::get('/transactions', [DashboardController::class, 'transactions'])
        ->name('transactions')
        ->middleware(EnsureUserIsAdmin::class);

    // SS-39: Backups page (Admin only per BRD)
    Route::get('/backups', [BackupController::class, 'index'])
        ->name('backups')
        ->middleware(EnsureUserIsAdmin::class);

    // Suppliers directory page
    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->name('suppliers')
        ->middleware(EnsureUserIsAdmin::class);

    // API routes — read-only for cashier, write ops admin-only per BRD
    Route::post('/api/inventory/add', [InventoryController::class, 'store'])->name('inventory.add')->middleware(EnsureUserIsAdmin::class);
    Route::put('/api/inventory/update', [InventoryController::class, 'updateProduct'])->name('inventory.update.product')->middleware(EnsureUserIsAdmin::class);
    Route::put('/api/inventory/{product}', [InventoryController::class, 'update'])->name('inventory.update')->middleware(EnsureUserIsAdmin::class);
    Route::get('/api/inventory/alerts', [InventoryController::class, 'getAlerts'])->name('inventory.alerts');
    Route::get('/api/inventory/products', [InventoryController::class, 'getProducts'])->name('inventory.products');
    Route::delete('/api/inventory/{product}', [InventoryController::class, 'destroy'])->name('inventory.destroy')->middleware(EnsureUserIsAdmin::class);
    Route::post('/api/inventory/adjust', [InventoryController::class, 'adjustStock'])
        ->name('inventory.adjust')
        ->middleware(EnsureUserIsAdmin::class);

    // SS-88: Stock-In / Receiving API endpoint
    Route::post('/api/inventory/stock-in', [InventoryController::class, 'storeStockIn'])
        ->name('inventory.stock-in')
        ->middleware(EnsureUserIsAdmin::class);

    // POS checkout API route (cashier-only)
    Route::post('/api/pos/checkout', [PosCheckoutController::class, 'checkout'])
        ->name('pos.checkout')
        ->middleware(EnsureUserIsCashier::class);

    // Supplier directory API routes
    Route::get('/api/suppliers/active', [SupplierController::class, 'getActive'])
        ->name('suppliers.active')
        ->middleware(EnsureUserIsAdmin::class);
    Route::post('/api/suppliers', [SupplierController::class, 'store'])
        ->name('suppliers.store')
        ->middleware(EnsureUserIsAdmin::class);
    Route::delete('/api/suppliers/{id}', [SupplierController::class, 'destroy'])
        ->name('suppliers.destroy')
        ->middleware(EnsureUserIsAdmin::class);

    // Dashboard API endpoints (admin overview)
    // SS-35: demand-based restocking suggestions (Admin only per BRD)
    Route::get('/api/dashboard/restock-suggestions', [DashboardController::class, 'restockSuggestions'])
        ->name('dashboard.restockSuggestions')
        ->middleware(EnsureUserIsAdmin::class);
    // SS-24: paginated transaction history with staff name (Admin only)
    Route::get('/api/dashboard/transactions', [DashboardController::class, 'transactionHistory'])
        ->name('dashboard.transactions')
        ->middleware(EnsureUserIsAdmin::class);
    // SS-25 / SS-34: downloadable transaction summary CSV (Admin only)
    Route::get('/api/dashboard/transactions/export', [DashboardController::class, 'exportTransactions'])
        ->name('dashboard.transactions.export')
        ->middleware(EnsureUserIsAdmin::class);
    // SS-39: backup management API (Admin only)
    Route::get('/api/backups', [BackupController::class, 'list'])
        ->name('backups.list')
        ->middleware(EnsureUserIsAdmin::class);
    Route::post('/api/backups/run', [BackupController::class, 'run'])
        ->name('backups.run')
        ->middleware(EnsureUserIsAdmin::class);
    Route::get('/api/backups/download/{file}', [BackupController::class, 'download'])
        ->name('backups.download')
        ->middleware(EnsureUserIsAdmin::class);
    Route::delete('/api/backups/{file}', [BackupController::class, 'destroy'])
        ->name('backups.destroy')
        ->middleware(EnsureUserIsAdmin::class);
    Route::get('/api/dashboard/stats', [DashboardController::class, 'stats'])
        ->name('dashboard.stats');
    Route::get('/api/dashboard/revenue-chart', [DashboardController::class, 'revenueChart'])
        ->name('dashboard.revenueChart');
    Route::get('/api/dashboard/top-products', [DashboardController::class, 'topProducts'])
        ->name('dashboard.topProducts');
    Route::get('/api/dashboard/recent-stockins', [DashboardController::class, 'recentStockIns'])
        ->name('dashboard.recentStockIns');
    Route::get('/api/dashboard/recent-sales', [DashboardController::class, 'recentSales'])
        ->name('dashboard.recentSales');
});
