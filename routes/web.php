<?php

use App\Http\Controllers\TransactionSuggestionController;
use App\Http\Controllers\UserController;
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
//
// BRD (Account Management) — Out of Scope: "Self-service account registration
// (staff cannot create their own accounts)." Functional Requirement: "The
// system shall allow an Admin to create new accounts with a designated
// username, password, and role."
//
// Public /register routes were therefore REMOVED. Accounts are provisioned
// exclusively by an Admin through the User Management screen
// (see /users + /api/users/* below).
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// API auth endpoints (JSON responses)
Route::post('/api/auth/login', [AuthController::class, 'apiLogin'])->name('api.auth.login')->middleware('guest');
Route::post('/api/auth/logout', [AuthController::class, 'apiLogout'])->name('api.auth.logout')->middleware('auth');

// Public homepage (accessible to guests and authenticated users)
Route::get('/', fn () => redirect()->route('home'));
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Overview / dashboard
    // BRD (Account Management): "Staff Role = POS Access Only." The overview
    // page is an Admin surface, so Staff are redirected to the POS screen
    // instead of landing on a dashboard whose APIs would 403.
    Route::get('/dashboard', [InventoryController::class, 'index'])
        ->name('dashboard')
        ->middleware(EnsureUserIsAdmin::class);

    // POS checkout page (cashier-only)
    Route::get('/pos', [PosCheckoutController::class, 'index'])
        ->name('pos')
        ->middleware(EnsureUserIsCashier::class);

    // BRD (Inventory Management) Security: "The system shall restrict all
    // access to this module; it must be completely inaccessible to accounts with
    // the Staff role." Staff get the POS catalog instead (/api/pos/products).
    Route::get('/products', [InventoryController::class, 'products'])
        ->name('products')
        ->middleware(EnsureUserIsAdmin::class);

    // Stock-In / Receiving page (SS-87)
    Route::get('/stock-in', [InventoryController::class, 'stockIn'])
        ->name('stock-in')
        ->middleware(EnsureUserIsAdmin::class);

    // SS-24: Transaction History page (Admin only per BRD)
    Route::get('/transactions', [DashboardController::class, 'transactions'])
        ->name('transactions')
        ->middleware(EnsureUserIsAdmin::class);

    // BRD (Demand Forecasting): the dedicated "Order Suggestions" dashboard.
    Route::get('/order-suggestions', [TransactionSuggestionController::class, 'page'])
        ->name('order-suggestions')
        ->middleware(EnsureUserIsAdmin::class);

    // BRD (Account Management): Admin-only user management — create accounts
    // with a designated username/password/role, deactivate instead of delete,
    // and reset passwords manually. No self-service registration exists.
    Route::get('/users', [UserController::class, 'index'])
        ->name('users')
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

    // BRD (Account Management / Inventory) Security: "No Staff account shall be
    // able to read or modify Admin dashboard data by any route, including direct
    // URL manipulation." These read endpoints expose pricing and stock levels, so
    // they are Admin-only. Staff use /api/inventory/pos-catalog instead.
    Route::get('/api/inventory/alerts', [InventoryController::class, 'getAlerts'])
        ->name('inventory.alerts')
        ->middleware(EnsureUserIsAdmin::class);
    Route::get('/api/inventory/products', [InventoryController::class, 'getProducts'])
        ->name('inventory.products')
        ->middleware(EnsureUserIsAdmin::class);
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

    // Cashier-safe product catalog for the POS screen.
    // BRD (POS): Staff may "browse or search for available hardware items".
    // This intentionally exposes only the fields a cashier needs (no cost data,
    // no supplier info) and excludes deactivated products.
    Route::get('/api/pos/products', [PosCheckoutController::class, 'catalog'])
        ->name('pos.products')
        ->middleware(EnsureUserIsCashier::class);

    // User management API (Admin only) — BRD (Account Management)
    Route::get('/api/users', [UserController::class, 'list'])->name('users.index')->middleware(EnsureUserIsAdmin::class);
    Route::post('/api/users', [UserController::class, 'store'])->name('users.store')->middleware(EnsureUserIsAdmin::class);
    Route::put('/api/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware(EnsureUserIsAdmin::class);
    Route::post('/api/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate')->middleware(EnsureUserIsAdmin::class);
    Route::post('/api/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate')->middleware(EnsureUserIsAdmin::class);
    // BRD Constraint: "No external email service integration; all password
    // resets must be done manually by the Admin within the system."
    Route::post('/api/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password')->middleware(EnsureUserIsAdmin::class);

    // Order Suggestions API (Admin only) — BRD (Demand Forecasting)
    // BRD Security: "Any attempt to access the suggestion export endpoint
    // without a valid Admin session token shall result in a 403 Forbidden."
    Route::get('/api/dashboard/order-suggestions', [TransactionSuggestionController::class, 'index'])
        ->name('dashboard.orderSuggestions')
        ->middleware(EnsureUserIsAdmin::class);
    Route::post('/api/dashboard/order-suggestions/{id}/ordered', [TransactionSuggestionController::class, 'markOrdered'])
        ->name('dashboard.orderSuggestions.ordered')
        ->middleware(EnsureUserIsAdmin::class);
    Route::post('/api/dashboard/order-suggestions/{id}/dismiss', [TransactionSuggestionController::class, 'dismiss'])
        ->name('dashboard.orderSuggestions.dismiss')
        ->middleware(EnsureUserIsAdmin::class);
    Route::get('/api/dashboard/order-suggestions/export', [TransactionSuggestionController::class, 'export'])
        ->name('dashboard.orderSuggestions.export')
        ->middleware(EnsureUserIsAdmin::class);

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
    // BRD (Account Management / Inventory) Security: "No Staff account shall be
    // able to read or modify Admin dashboard data by any route, including direct
    // URL manipulation." These five endpoints expose revenue, sales counts and
    // supplier activity, so they are Admin-only.
    Route::get('/api/dashboard/stats', [DashboardController::class, 'stats'])
        ->name('dashboard.stats')
        ->middleware(EnsureUserIsAdmin::class);
    Route::get('/api/dashboard/revenue-chart', [DashboardController::class, 'revenueChart'])
        ->name('dashboard.revenueChart')
        ->middleware(EnsureUserIsAdmin::class);
    Route::get('/api/dashboard/top-products', [DashboardController::class, 'topProducts'])
        ->name('dashboard.topProducts')
        ->middleware(EnsureUserIsAdmin::class);
    Route::get('/api/dashboard/recent-stockins', [DashboardController::class, 'recentStockIns'])
        ->name('dashboard.recentStockIns')
        ->middleware(EnsureUserIsAdmin::class);
    Route::get('/api/dashboard/recent-sales', [DashboardController::class, 'recentSales'])
        ->name('dashboard.recentSales')
        ->middleware(EnsureUserIsAdmin::class);

    // BRD (Demand Forecasting): "Forecasting calculations shall run asynchronously
    // (e.g., via a scheduled nightly job)." This endpoint lets the Admin trigger
    // the same job manually without waiting for the nightly schedule.
    Route::post('/api/dashboard/order-suggestions/run', [TransactionSuggestionController::class, 'runForecast'])
        ->name('dashboard.orderSuggestions.run')
        ->middleware(EnsureUserIsAdmin::class);
});
