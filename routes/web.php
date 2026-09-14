<?php

use App\Http\Controllers\InputController;
use App\Http\Controllers\SupabaseSchemaController;
use Illuminate\Support\Facades\Route;

// The single page of this app: inputs
Route::get('/', [InputController::class, 'create']);
Route::get('/inputs', [InputController::class, 'create'])->name('inputs');
Route::post('/inputs', [InputController::class, 'store'])->name('inputs.store');

// Listing page for all submitted inputs
Route::get('/info', [InputController::class, 'index'])->name('info');

// Schema management (protected by SUPABASE_SCHEMA_ADMIN_TOKEN)
Route::get('/schema/login', [SupabaseSchemaController::class, 'login'])->name('schema.login');
Route::post('/schema/login', [SupabaseSchemaController::class, 'authenticate'])->name('schema.authenticate');

Route::middleware(\App\Http\Middleware\EnsureSupabaseSchemaAdmin::class)->group(function (): void {
    Route::get('/schema', [SupabaseSchemaController::class, 'index'])->name('schema.index');
    Route::post('/schema/update', [SupabaseSchemaController::class, 'update'])->name('schema.update');
    Route::post('/schema/reset', [SupabaseSchemaController::class, 'reset'])->name('schema.reset');
    Route::post('/schema/logout', [SupabaseSchemaController::class, 'logout'])->name('schema.logout');
});
