@extends('layouts.app')

@section('title', 'POS Checkout - Smart-Stock')

@section('content')
<div class="pos-page">
    <div class="page-header">
        <h1 class="page-title">POS Checkout</h1>
        <p class="page-subtitle">Process sales, manage cart, and handle payments</p>
    </div>

    <!-- SECTION CARD 1: Search & Filter -->
    <div class="section-card">
        <div class="section-card-header">
            <h2 class="section-card-title">Search & Filter</h2>
            <p class="section-card-desc">Find and narrow down products using the controls below</p>
        </div>
        <div class="filter-bar">
            <div class="filter-bar-inner">
                <div class="search-bar">
                    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" class="search-input-full" id="posSearchInput" placeholder="Search products by name, SKU, or category..." oninput="filterPosProducts()" autocomplete="off">
                </div>
                <div class="filter-bar-divider"></div>
                <div class="filter-group">
                    <label class="filter-label">Search In</label>
                    <select class="search-column-select" id="posSearchColumn" onchange="filterPosProducts()">
                        <option value="">All Columns</option>
                        <option value="name">Product Name</option>
                        <option value="sku">SKU</option>
                        <option value="category">Category</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Filter By</label>
                    <select class="category-select" id="posCategoryFilter" onchange="filterPosProducts()">
                        <option value="">All Categories</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select class="sort-select" id="posSortSelect" onchange="filterPosProducts()">
                        <option value="">Default</option>
                        <option value="name-asc">Name (A → Z)</option>
                        <option value="name-desc">Name (Z → A)</option>
                        <option value="price-asc">Price (Low → High)</option>
                        <option value="price-desc">Price (High → Low)</option>
                        <option value="stock-asc">Stock (Low → High)</option>
                        <option value="stock-desc">Stock (High → Low)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION CARD 2: Products & Cart -->
    <div class="section-card">
        <div class="section-card-header">
            <div class="section-card-header-row">
                <div>
                    <h2 class="section-card-title">Products & Cart</h2>
                    <p class="section-card-desc">Browse available products, manage cart, and process payments</p>
                </div>
                <div class="view-toggle">
                    <button class="view-toggle-btn active" id="tableViewBtn" onclick="switchView('table')" title="Table View">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                    </button>
                    <button class="view-toggle-btn" id="gridViewBtn" onclick="switchView('grid')" title="Grid View">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="pos-content-grid">
            <!-- LEFT: Products Display -->
            <div class="pos-products-col">
                <!-- Table View -->
                <div class="table-wrapper pos-table-wrapper" id="tableView">
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="posProductTableBody">
                            <tr><td colspan="6" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading products...</td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- Grid View -->
                <div class="grid-view hidden" id="gridView">
                    <div id="posProductGridBody" class="product-grid">
                        <div class="empty-state" style="grid-column: 1/-1;"><span class="spinner" aria-hidden="true"></span> Loading products...</div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Cart + Payment -->
            <div class="pos-right-col">
                <!-- Cart Sub-card -->
                <div class="pos-cart-card">
                    <div class="pos-cart-card-header">
                        <span class="pos-cart-card-title">Cart</span>
                        <span class="cart-count" id="cartCount">0 items</span>
                    </div>
                    <div class="cart-items" id="cartItems">
                        <div class="cart-empty" id="cartEmpty">
                            <p>No items in cart</p>
                            <p class="cart-empty-hint">Select products from the catalog to add them</p>
                        </div>
                    </div>
                    <div class="cart-summary" id="cartSummary">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="summarySubtotal">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (12%)</span>
                            <span id="summaryTax">₱0.00</span>
                        </div>
                        <div class="summary-row summary-total-row">
                            <span>Total</span>
                            <span id="summaryTotal">₱0.00</span>
                        </div>
                    </div>
                    <div class="cart-footer">
                        <button type="button" class="pos-btn pos-btn-red" onclick="clearCart()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>Clear Cart
                        </button>
                    </div>
                </div>

                <!-- Payment Sub-card -->
                <div class="pos-payment-card">
                    <div class="pos-payment-card-header">Order Summary</div>
                    <div class="pos-payment-form">
                        <form id="pos-form" onsubmit="processCheckout(event)">
                            @csrf
                            <div class="form-group">
                                <label for="payment_amount">Payment Amount (₱)</label>
                                <input type="number" name="payment_amount" id="payment_amount" class="form-control" step="0.01" min="0" placeholder="0.00" required oninput="updateChange()">
                            </div>
                            <div class="summary-row">
                                <span>Change</span>
                                <span id="summaryChange" class="change-amount">₱0.00</span>
                            </div>
                            <button type="submit" class="pos-btn pos-btn-blue" id="checkoutBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>Process Payment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    /* ===== SHARED SECTION CARD STYLES (same as products.blade.php) ===== */
    .pos-page { padding: 0; }
    .page-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 6px; }
    .page-subtitle { color: #64748b; font-size: 14px; margin-bottom: 0; }
    .page-header { margin-bottom: 20px; }
    .section-card {
        background: rgba(255,255,255,0.025);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 14px;
        margin-bottom: 16px;
        overflow: hidden;
    }
    body.light-theme .section-card {
        background: #ffffff;
        border-color: rgba(15,23,42,0.08);
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .section-card-header { padding: 18px 20px 16px 20px; }
    .section-card-header-row {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    }
    .section-card-title { font-size: 15px; font-weight: 700; color: #e2e8f0; margin: 0 0 3px 0; }
    body.light-theme .section-card-title { color: #1e293b; }
    .section-card-desc { font-size: 13px; color: #64748b; margin: 0; }
    body.light-theme .section-card-desc { color: #94a3b8; }
    .section-card .filter-bar { margin: 14px 16px 16px 16px; }

    /* ===== FILTER BAR ===== */
    .filter-bar {
        background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px; padding: 14px 16px;
    }
    body.light-theme .filter-bar { background: #f8fafc; border-color: rgba(15,23,42,0.08); }
    .filter-bar-inner { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
    .filter-bar-divider { width: 1px; height: 28px; background: rgba(255,255,255,0.1); align-self: flex-end; flex-shrink: 0; }
    body.light-theme .filter-bar-divider { background: rgba(15,23,42,0.12); }
    .search-bar { flex: 1 1 200px; min-width: 180px; position: relative; display: flex; align-items: center; }
    .search-icon { position: absolute; left: 12px; color: #64748b; pointer-events: none; z-index: 1; flex-shrink: 0; }
    body.light-theme .search-icon { color: #94a3b8; }
    .search-input-full {
        width: 100%; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px; padding: 0 14px 0 36px; height: 36px; color: #f8fafc;
        font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; box-sizing: border-box;
    }
    .search-input-full:focus { border-color: #3b82f6; }
    .search-input-full::placeholder { color: #64748b; }
    body.light-theme .search-input-full { background: #ffffff; border-color: rgba(15,23,42,0.12); color: #0f172a; }
    body.light-theme .search-input-full::placeholder { color: #94a3b8; }
    .filter-group { display: flex; flex-direction: column; gap: 4px; flex-shrink: 0; }
    .filter-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    body.light-theme .filter-label { color: #94a3b8; }

    /* ===== DROPDOWN SELECTS ===== */
    .search-column-select, .category-select, .sort-select {
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12);
        border-radius: 8px; padding: 0 30px 0 12px; height: 36px; color: #f8fafc;
        font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s;
        cursor: pointer; box-sizing: border-box; appearance: none; -webkit-appearance: none; -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center; background-size: 12px;
    }
    .search-column-select { min-width: 120px; }
    .category-select { min-width: 140px; }
    .sort-select { min-width: 150px; }
    .search-column-select:focus, .category-select:focus, .sort-select:focus { border-color: #3b82f6; }
    .search-column-select option, .category-select option, .sort-select option { background: #1e293b; color: #e2e8f0; }
    body.light-theme .search-column-select, body.light-theme .category-select, body.light-theme .sort-select {
        background-color: #ffffff; border-color: rgba(15,23,42,0.14); color: #0f172a;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center; background-size: 12px;
    }
    body.light-theme .search-column-select option, body.light-theme .category-select option, body.light-theme .sort-select option {
        background: #ffffff; color: #0f172a;
    }

    /* ===== VIEW TOGGLE ===== */
    .view-toggle {
        display: flex; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px; overflow: hidden;
    }
    body.light-theme .view-toggle { background: #f1f5f9; border-color: rgba(15,23,42,0.1); }
    .view-toggle-btn {
        display: flex; align-items: center; justify-content: center;
        width: 36px; height: 34px; background: transparent; border: none;
        color: #64748b; cursor: pointer; transition: all 0.15s;
    }
    .view-toggle-btn:hover { color: #94a3b8; }
    .view-toggle-btn.active { background: rgba(59,130,246,0.15); color: #60a5fa; }
    body.light-theme .view-toggle-btn { color: #94a3b8; }
    body.light-theme .view-toggle-btn:hover { color: #64748b; }
    body.light-theme .view-toggle-btn.active { background: rgba(59,130,246,0.1); color: #3b82f6; }

    /* ===== TABLE ===== */
    .section-card .table-wrapper {
        border: 1px solid rgba(255,255,255,0.08); border-radius: 10px;
        margin: 0 16px 16px 16px; width: calc(100% - 32px); background: rgba(255,255,255,0.02);
    }
    body.light-theme .section-card .table-wrapper { border-color: rgba(15,23,42,0.1); background: #ffffff; }
    table { width: 100%; min-width: 500px; border-collapse: collapse; }
    thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
    tbody tr { border-top: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
    tbody tr:hover { background: rgba(255,255,255,0.02); }
    tbody td { padding: 12px 16px; font-size: 13px; color: #cbd5e1; }
    body.light-theme table thead th { background: #f8fafc; color: #64748b; }
    body.light-theme table tbody td { color: #475569; }
    body.light-theme table tbody tr:hover { background: rgba(15,23,42,0.025); }
    .empty-state { text-align: center; color: #475569; padding: 48px; font-size: 14px; }
    body.light-theme .empty-state { color: #94a3b8; }
    .stock-cell { font-weight: 600; }
    .stock-ok { color: #4ade80; }
    .stock-low { color: #fbbf24; }
    .stock-critical { color: #f87171; }
    body.light-theme .stock-ok { color: #16a34a; }
    body.light-theme .stock-low { color: #ca8a04; }
    body.light-theme .stock-critical { color: #dc2626; }

    /* ===== GRID VIEW ===== */
    .grid-view { padding: 0 16px 16px 16px; }
    .product-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px;
    }
    .product-card {
        background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px; overflow: hidden; transition: all 0.2s; cursor: default;
    }
    .product-card:hover { border-color: rgba(59,130,246,0.3); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
    body.light-theme .product-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
    body.light-theme .product-card:hover { border-color: rgba(59,130,246,0.3); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    .product-card-img { width: 100%; height: 140px; object-fit: cover; background: rgba(255,255,255,0.03); display: block; }
    body.light-theme .product-card-img { background: #f1f5f9; }
    .product-card-body { padding: 14px; }
    .product-card-category {
        display: inline-block; font-size: 11px; color: #94a3b8;
        background: rgba(255,255,255,0.06); padding: 2px 8px; border-radius: 4px; margin-bottom: 8px;
    }
    body.light-theme .product-card-category { background: #f1f5f9; color: #64748b; }
    .product-card-name {
        font-size: 14px; font-weight: 600; color: #e2e8f0; margin: 0 0 4px 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    body.light-theme .product-card-name { color: #1e293b; }
    .product-card-sku { font-size: 12px; color: #64748b; margin: 0 0 8px 0; }
    .product-card-meta { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .product-card-price { font-size: 15px; font-weight: 700; color: #60a5fa; }
    body.light-theme .product-card-price { color: #2563eb; }
    .product-card-stock { font-size: 12px; font-weight: 600; padding: 3px 8px; border-radius: 6px; }
    .product-card-stock.stock-ok { background: rgba(74,222,128,0.12); color: #4ade80; }
    .product-card-stock.stock-low { background: rgba(251,191,36,0.12); color: #fbbf24; }
    .product-card-stock.stock-critical { background: rgba(248,113,113,0.12); color: #f87171; }
    body.light-theme .product-card-stock.stock-ok { background: rgba(22,163,74,0.1); color: #16a34a; }
    body.light-theme .product-card-stock.stock-low { background: rgba(202,138,4,0.1); color: #ca8a04; }
    body.light-theme .product-card-stock.stock-critical { background: rgba(220,38,38,0.1); color: #dc2626; }
    .product-card-actions {
        display: flex; gap: 6px; margin-top: 12px; padding-top: 10px;
        border-top: 1px solid rgba(255,255,255,0.06);
    }
    body.light-theme .product-card-actions { border-top-color: rgba(15,23,42,0.06); }

    /* ===== ACTION LINKS ===== */
    .action-link {
        background: none; border: 1px solid transparent; padding: 4px 10px; cursor: pointer;
        font-size: 13px; font-family: 'Inter', sans-serif; color: #60a5fa; text-decoration: none;
        transition: all 0.15s; margin-right: 8px; display: inline-flex; align-items: center; gap: 5px;
        border-radius: 6px;
    }
    .action-link:last-child { margin-right: 0; }
    .action-link:hover { border-color: rgba(96,165,250,0.4); background: rgba(96,165,250,0.08); color: #93c5fd; }
    .action-link svg { width: 13px; height: 13px; flex-shrink: 0; }
    .action-link:disabled { opacity: 0.4; cursor: not-allowed; transform: none; filter: none; }
    body.light-theme .action-link { color: #1e293b; }
    body.light-theme .action-link:hover { border-color: rgba(37,99,235,0.3); background: rgba(37,99,235,0.06); color: #1d4ed8; }

    /* ===== POS 2-COLUMN GRID ===== */
    .pos-content-grid {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 16px;
        padding: 0 16px 16px 16px;
    }
    .pos-products-col { min-width: 0; }

    /* ===== RIGHT COLUMN (Cart + Payment) ===== */
    .pos-right-col {
        display: flex; flex-direction: column; gap: 12px; min-width: 0;
    }

    /* ===== CART SUB-CARD ===== */
    .pos-cart-card {
        background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
        border-radius: 12px; overflow: hidden; display: flex; flex-direction: column;
    }
    body.light-theme .pos-cart-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
    .pos-cart-card-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.06);
        background: rgba(255,255,255,0.03);
    }
    body.light-theme .pos-cart-card-header { background: #f8fafc; border-bottom-color: rgba(15,23,42,0.08); }
    .pos-cart-card-title { font-size: 14px; font-weight: 700; color: #e2e8f0; }
    body.light-theme .pos-cart-card-title { color: #1e293b; }
    .cart-count { font-size: 12px; color: #64748b; font-weight: 500; }

    .cart-items { flex: 1; overflow-y: auto; padding: 8px 12px; max-height: 320px; }
    .cart-empty { text-align: center; padding: 32px 16px; color: #475569; }
    body.light-theme .cart-empty { color: #94a3b8; }
    .cart-empty-hint { font-size: 12px; margin-top: 4px; }
    .cart-item {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    body.light-theme .cart-item { border-bottom-color: rgba(15,23,42,0.04); }
    .cart-item:last-child { border-bottom: none; }
    .cart-item-info { flex: 1; min-width: 0; }
    .cart-item-name { font-size: 13px; font-weight: 600; color: #e2e8f0; margin-bottom: 2px; }
    body.light-theme .cart-item-name { color: #0f172a; }
    .cart-item-price { font-size: 12px; color: #64748b; }
    .cart-item-controls { display: flex; align-items: center; gap: 6px; }
    .cart-qty-btn {
        width: 28px; height: 28px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.05); color: #e2e8f0; font-size: 16px; font-weight: 600;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: background 0.15s; font-family: 'Inter', sans-serif;
    }
    .cart-qty-btn:hover { background: rgba(255,255,255,0.1); }
    body.light-theme .cart-qty-btn { background: #f1f5f9; border-color: rgba(15,23,42,0.12); color: #334155; }
    body.light-theme .cart-qty-btn:hover { background: #e2e8f0; }
    .cart-qty-value { min-width: 28px; text-align: center; font-size: 14px; font-weight: 600; color: #f8fafc; }
    body.light-theme .cart-qty-value { color: #0f172a; }
    .cart-item-subtotal { min-width: 60px; text-align: right; font-size: 13px; font-weight: 600; color: #cbd5e1; }
    body.light-theme .cart-item-subtotal { color: #334155; }
    .cart-item-remove {
        background: none; border: none; color: #f87171; cursor: pointer; font-size: 16px;
        padding: 4px; margin-left: 4px; transition: opacity 0.15s;
    }
    .cart-item-remove:hover { opacity: 0.8; }

    /* Cart summary inside cart sub-card */
    .cart-summary { padding: 10px 16px; border-top: 1px solid rgba(255,255,255,0.06); }
    body.light-theme .cart-summary { border-top-color: rgba(15,23,42,0.06); }
    .summary-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 5px 0; font-size: 13px; color: #94a3b8;
    }
    body.light-theme .summary-row { color: #64748b; }
    .summary-total-row {
        border-top: 1px solid rgba(255,255,255,0.06); margin-top: 4px; padding-top: 8px;
        font-size: 14px; font-weight: 700; color: #f8fafc;
    }
    body.light-theme .summary-total-row { border-top-color: rgba(15,23,42,0.1); color: #0f172a; }

    /* Cart footer with Clear Cart button */
    .cart-footer { display: flex; justify-content: center; padding: 10px 16px; }

    /* ===== PAYMENT SUB-CARD ===== */
    .pos-payment-card {
        background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
        border-radius: 12px; overflow: hidden; display: flex; flex-direction: column;
    }
    body.light-theme .pos-payment-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
    .pos-payment-card-header {
        padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.06);
        background: rgba(255,255,255,0.03); font-size: 14px; font-weight: 700; color: #e2e8f0;
    }
    body.light-theme .pos-payment-card-header { background: #f8fafc; border-bottom-color: rgba(15,23,42,0.08); color: #1e293b; }
    .pos-payment-form { padding: 16px; }
    .pos-payment-form .form-group { margin-bottom: 12px; }
    .pos-payment-form label { display: block; color: #94a3b8; font-size: 12px; font-weight: 500; margin-bottom: 5px; }
    body.light-theme .pos-payment-form label { color: #64748b; }
    .pos-payment-form .form-control {
        width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.12); border-radius: 7px;
        color: #f8fafc; font-size: 14px; font-family: 'Inter', sans-serif; outline: none;
        transition: border-color 0.2s; box-sizing: border-box;
    }
    .pos-payment-form .form-control:focus { border-color: #3b82f6; }
    .pos-payment-form .form-control::placeholder { color: #475569; }
    body.light-theme .pos-payment-form .form-control { background: #f8fafc; border-color: rgba(15,23,42,0.14); color: #0f172a; }
    body.light-theme .pos-payment-form .form-control:focus { border-color: #3b82f6; }
    body.light-theme .pos-payment-form .form-control::placeholder { color: #94a3b8; }
    .change-amount { color: #4ade80; font-weight: 600; }
    .change-amount.negative { color: #f87171; }

    /* ===== POS BUTTONS ===== */
    .pos-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        padding: 10px 16px; border: none; border-radius: 8px;
        font-size: 13px; font-weight: 600; font-family: 'Inter', sans-serif;
        cursor: pointer; transition: filter 0.1s ease, transform 0.1s ease; width: 100%; text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .pos-btn svg { width: 14px; height: 14px; flex-shrink: 0; }
    .pos-btn:active { filter: brightness(0.85); transform: scale(0.97); }
    .pos-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; filter: none; }
    .pos-btn-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; }
    .pos-btn-blue:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
    .pos-btn-red { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; }
    .pos-btn-red:hover { background: linear-gradient(135deg, #dc2626, #b91c1c); }
    .pos-btn-gray { background: rgba(255,255,255,0.1); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.15); }
    .pos-btn-gray:hover { background: rgba(255,255,255,0.15); }
    body.light-theme .pos-btn-gray { background: #e2e8f0; color: #475569; border-color: rgba(15,23,42,0.1); }
    body.light-theme .pos-btn-gray:hover { background: #cbd5e1; }

    /* ===== HIDDEN UTILITY ===== */
    .hidden { display: none !important; }

    /* ===== CHECKOUT CONFIRMATION MODAL ===== */
    .pos-modal-overlay {
        position: fixed; inset: 0; z-index: 500; display: flex; align-items: center; justify-content: center;
        background: rgba(15,23,42,0.62); padding: 16px;
    }
    .pos-modal {
        width: min(440px, 100%); background: #ffffff; color: #0f172a; border-radius: 12px;
        box-shadow: 0 20px 50px rgba(15,23,42,0.25); overflow: hidden;
    }
    .pos-modal-header {
        display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;
        border-bottom: 1px solid rgba(15,23,42,0.08);
    }
    .pos-modal-header h3 { margin: 0; font-size: 18px; font-weight: 700; }
    .pos-modal-close {
        width: 32px; height: 32px; padding: 0; border: 0; border-radius: 6px; background: transparent;
        color: #64748b; font-size: 24px; line-height: 1; cursor: pointer;
    }
    .pos-modal-close:hover { background: rgba(15,23,42,0.06); color: #0f172a; }
    .pos-modal-body { padding: 20px; }
    .pos-modal-body p { margin: 0 0 16px; color: #475569; font-size: 14px; }
    .pos-modal-summary { background: #f8fafc; border-radius: 8px; padding: 14px 16px; }
    .pos-modal-row {
        display: flex; justify-content: space-between; align-items: center; padding: 7px 0;
        font-size: 14px; font-weight: 500; color: #334155;
    }
    .pos-modal-row span:last-child { font-weight: 600; color: #0f172a; }
    .pos-modal-footer {
        display: flex; justify-content: flex-end; gap: 8px; padding: 12px 16px;
        background: #f8fafc; border-top: 1px solid rgba(15,23,42,0.08);
    }
    .pos-modal-footer .pos-btn { min-width: 120px; }

    /* ===== LIGHT THEME PAGE ===== */
    body.light-theme .page-title { color: #0f172a; }
    body.light-theme .page-subtitle { color: #64748b; }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 900px) {
        .pos-content-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .page-title { font-size: 20px; }
        .page-subtitle { font-size: 13px; margin-bottom: 0; }
        .page-header { margin-bottom: 14px; }
        .section-card { border-radius: 12px; }
        .section-card-header { padding: 14px 14px 0 14px; }
        .section-card .filter-bar { margin: 10px 12px 12px 12px; padding: 12px; }
        .section-card .table-wrapper { margin: 0 12px 12px 12px; width: calc(100% - 24px); }
        .filter-bar-inner { gap: 10px; }
        .filter-bar-divider { display: none; }
        .search-bar { flex-basis: 100%; min-width: 0; }
        .filter-group { flex: 1 1 120px; min-width: 120px; }
        .section-card-header-row { flex-direction: column; }
        .pos-content-grid { padding: 0 12px 12px 12px; gap: 12px; }
        .grid-view { padding: 0 12px 12px 12px; }
        .product-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
        .product-card-img { height: 100px; }
        .product-card-body { padding: 10px; }
        .table-wrapper { border-radius: 10px; }
        table { min-width: 400px; }
        thead th, tbody td { padding: 10px 10px; font-size: 12px; }
        .empty-state { padding: 32px 16px; font-size: 13px; }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    let posProducts = [];
    let cart = [];
    let currentView = 'table';

    // Product image mapping by category/name keywords
    const productImages = {
        'drill': 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=400&h=300&fit=crop',
        'hammer': 'https://images.unsplash.com/photo-1572588256287-71a4661b6b96?w=400&h=300&fit=crop',
        'paint': 'https://images.unsplash.com/photo-1562259929-b4e1fd3aef09?w=400&h=300&fit=crop',
        'wrench': 'https://images.unsplash.com/photo-1581783898377-1c85bf937427?w=400&h=300&fit=crop',
        'pipe': 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=400&h=300&fit=crop',
        'electronics': 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&h=300&fit=crop',
        'electrical': 'https://images.unsplash.com/photo-1544724569-5f6462b2268f?w=400&h=300&fit=crop',
        'default': 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=400&h=300&fit=crop'
    };

    function getProductImage(product) {
        const name = (product.name || '').toLowerCase();
        const category = (product.category || '').toLowerCase();
        const searchStr = name + ' ' + category;
        for (const [key, url] of Object.entries(productImages)) {
            if (key === 'default') continue;
            if (searchStr.includes(key)) return url;
        }
        return productImages['default'];
    }

    function switchView(view) {
        currentView = view;
        document.getElementById('tableViewBtn').classList.toggle('active', view === 'table');
        document.getElementById('gridViewBtn').classList.toggle('active', view === 'grid');
        document.getElementById('tableView').classList.toggle('hidden', view !== 'table');
        document.getElementById('gridView').classList.toggle('hidden', view !== 'grid');
    }

    // Load products for POS
    async function loadPosProducts() {
        const tbody = document.getElementById('posProductTableBody');
        const grid = document.getElementById('posProductGridBody');
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading products...</td></tr>';
        grid.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;"><span class="spinner" aria-hidden="true"></span> Loading products...</div>';
        try {
            const res = await fetch('/api/inventory/products');
            if (!res.ok) throw new Error('Unable to load products');
            posProducts = await res.json();
            populateCategoryFilter();
            filterPosProducts();
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Unable to load products. Please try again.</td></tr>';
            grid.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;">Unable to load products. Please try again.</div>';
            showToast('Failed to load products', 'error');
        }
    }

    function populateCategoryFilter() {
        const select = document.getElementById('posCategoryFilter');
        const selected = select.value;
        const categories = [...new Set(posProducts.map(p => (p.category || '').trim()).filter(c => c))].sort((a, b) => a.localeCompare(b));
        select.innerHTML = '<option value="">All Categories</option>' + categories.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
        if (categories.includes(selected)) select.value = selected;
    }

    // Filter products
    function filterPosProducts() {
        const t = (document.getElementById('posSearchInput')?.value || '').toLowerCase();
        const col = document.getElementById('posSearchColumn')?.value || '';
        const category = document.getElementById('posCategoryFilter')?.value || '';
        const sortVal = document.getElementById('posSortSelect')?.value || '';

        let filtered = posProducts.filter(p => {
            if (category && (p.category || '').trim().toLowerCase() !== category.toLowerCase()) return false;
            if (!t) return true;
            switch (col) {
                case 'name': return p.name && p.name.toLowerCase().includes(t);
                case 'sku': return p.sku && p.sku.toLowerCase().includes(t);
                case 'category': return p.category && p.category.toLowerCase().includes(t);
                default: return (p.name && p.name.toLowerCase().includes(t)) || (p.sku && p.sku.toLowerCase().includes(t)) || (p.category && p.category.toLowerCase().includes(t));
            }
        });

        // Sort
        if (sortVal) {
            const [key, dir] = sortVal.split('-');
            const asc = dir === 'asc';
            filtered.sort((a, b) => {
                let va, vb;
                switch (key) {
                    case 'name': va = (a.name || '').toLowerCase(); vb = (b.name || '').toLowerCase(); return asc ? va.localeCompare(vb) : vb.localeCompare(va);
                    case 'price': va = parseFloat(a.price) || 0; vb = parseFloat(b.price) || 0; return asc ? va - vb : vb - va;
                    case 'stock': va = parseInt(a.current_stock) || 0; vb = parseInt(b.current_stock) || 0; return asc ? va - vb : vb - va;
                    default: return 0;
                }
            });
        }

        renderPosTable(filtered);
        renderPosGrid(filtered);
    }

    function getStockStatus(stock, threshold) {
        if (stock <= 0) return { label: 'Out of Stock', class: 'critical' };
        if (stock <= threshold) return { label: 'Low', class: 'low' };
        return { label: 'OK', class: 'ok' };
    }

    function renderPosTable(products) {
        const tbody = document.getElementById('posProductTableBody');
        if (products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No products found</td></tr>';
            return;
        }
        tbody.innerHTML = products.map(p => {
            const s = getStockStatus(p.current_stock, p.reorder_threshold);
            const isInCart = cart.find(item => item.product_id === p.id);
            const outOfStock = p.current_stock <= 0;
            const actionBtn = outOfStock
                ? '<span style="color:#64748b;font-size:12px;">Out of Stock</span>'
                : `<button class="action-link" onclick="addToCart(${p.id})" ${isInCart ? '' : ''}><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>Add</button>`;
            return `<tr>
                <td><strong>${escapeHtml(p.name)}</strong></td>
                <td>${escapeHtml(p.sku || '—')}</td>
                <td>${escapeHtml(p.category || '—')}</td>
                <td>₱${parseFloat(p.price).toFixed(2)}</td>
                <td><span class="stock-cell stock-${s.class}">${p.current_stock}</span></td>
                <td>${actionBtn}</td>
            </tr>`;
        }).join('');
    }

    function renderPosGrid(products) {
        const grid = document.getElementById('posProductGridBody');
        if (products.length === 0) {
            grid.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;">No products found</div>';
            return;
        }
        grid.innerHTML = products.map(p => {
            const s = getStockStatus(p.current_stock, p.reorder_threshold);
            const img = getProductImage(p);
            const outOfStock = p.current_stock <= 0;
            const addBtn = outOfStock
                ? '<span style="color:#64748b;font-size:12px;">Out of Stock</span>'
                : `<button class="action-link" onclick="addToCart(${p.id})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>Add</button>`;
            return `<div class="product-card">
                <img class="product-card-img" src="${img}" alt="${escapeHtml(p.name)}" loading="lazy" onerror="this.src='${productImages['default']}'">
                <div class="product-card-body">
                    <div class="product-card-category">${escapeHtml(p.category || 'Uncategorized')}</div>
                    <h3 class="product-card-name" title="${escapeHtml(p.name)}">${escapeHtml(p.name)}</h3>
                    <p class="product-card-sku">${escapeHtml(p.sku || '')}</p>
                    <div class="product-card-meta">
                        <span class="product-card-price">₱${parseFloat(p.price).toFixed(2)}</span>
                        <span class="product-card-stock stock-${s.class}">${s.label} · ${p.current_stock}</span>
                    </div>
                    <div class="product-card-actions">${addBtn}</div>
                </div>
            </div>`;
        }).join('');
    }

    // Cart operations
    function addToCart(productId) {
        const product = posProducts.find(p => p.id === productId);
        if (!product) return;
        const existing = cart.find(item => item.product_id === productId);
        if (existing) {
            if (existing.quantity >= product.current_stock) {
                showToast('Not enough stock available', 'error');
                return;
            }
            existing.quantity++;
        } else {
            cart.push({
                product_id: product.id,
                name: product.name,
                sku: product.sku || '',
                price: parseFloat(product.price),
                quantity: 1,
            });
        }
        updateCartUI();
    }

    function updateCartQuantity(productId, delta) {
        const item = cart.find(item => item.product_id === productId);
        if (!item) return;
        const newQty = item.quantity + delta;
        if (newQty <= 0) { removeFromCart(productId); return; }
        const product = posProducts.find(p => p.id === productId);
        if (product && newQty > product.current_stock) {
            showToast('Not enough stock available', 'error');
            return;
        }
        item.quantity = newQty;
        updateCartUI();
    }

    function removeFromCart(productId) {
        cart = cart.filter(item => item.product_id !== productId);
        updateCartUI();
    }

    function clearCart() {
        cart = [];
        updateCartUI();
        document.getElementById('payment_amount').value = '';
    }

    function updateCartUI() {
        const cartItemsEl = document.getElementById('cartItems');
        const cartEmptyEl = document.getElementById('cartEmpty');
        if (cart.length === 0) {
            cartEmptyEl.style.display = 'block';
            cartItemsEl.querySelectorAll('.cart-item').forEach(el => el.remove());
        } else {
            cartEmptyEl.style.display = 'none';
            cartItemsEl.querySelectorAll('.cart-item').forEach(el => el.remove());
            cart.forEach(item => {
                const subtotal = item.price * item.quantity;
                const div = document.createElement('div');
                div.className = 'cart-item';
                div.innerHTML = `
                    <div class="cart-item-info">
                        <div class="cart-item-name">${escapeHtml(item.name)}</div>
                        <div class="cart-item-price">₱${item.price.toFixed(2)} each</div>
                    </div>
                    <div class="cart-item-controls">
                        <button type="button" class="cart-qty-btn" onclick="updateCartQuantity(${item.product_id}, -1)">−</button>
                        <span class="cart-qty-value">${item.quantity}</span>
                        <button type="button" class="cart-qty-btn" onclick="updateCartQuantity(${item.product_id}, 1)">+</button>
                    </div>
                    <div class="cart-item-subtotal">₱${subtotal.toFixed(2)}</div>
                    <button type="button" class="cart-item-remove" onclick="removeFromCart(${item.product_id})" title="Remove">×</button>
                `;
                cartItemsEl.appendChild(div);
            });
        }
        updateSummary();
        filterPosProducts();
    }

    function updateSummary() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = subtotal * 0.12;
        const total = subtotal + tax;
        document.getElementById('summarySubtotal').textContent = '₱' + subtotal.toFixed(2);
        document.getElementById('summaryTax').textContent = '₱' + tax.toFixed(2);
        document.getElementById('summaryTotal').textContent = '₱' + total.toFixed(2);
        const count = cart.reduce((sum, item) => sum + item.quantity, 0);
        document.getElementById('cartCount').textContent = count + ' item' + (count !== 1 ? 's' : '');
        updateChange();
    }

    function updateChange() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const total = subtotal * 1.12;
        const payment = parseFloat(document.getElementById('payment_amount').value) || 0;
        const change = payment - total;
        const changeEl = document.getElementById('summaryChange');
        changeEl.textContent = '₱' + change.toFixed(2);
        changeEl.className = 'change-amount' + (change < 0 ? ' negative' : '');
    }

    // Process checkout
    async function processCheckout(e) {
        e.preventDefault();
        if (cart.length === 0) { showToast('Cart is empty', 'error'); return; }
        const btn = document.getElementById('checkoutBtn');
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const total = subtotal * 1.12;
        const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
        if (paymentAmount < total) { showToast('Insufficient funds.', 'error'); return; }
        if (!await showCheckoutConfirmModal(total, paymentAmount)) return;
        btn.disabled = true;
        showToast('Processing payment...', 'info');
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const items = cart.map(item => ({ product_id: item.product_id, quantity: item.quantity }));
            const res = await fetch('/api/pos/checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ cart_items: items, payment_amount: paymentAmount }),
            });
            if (res.ok) {
                const data = await res.json();
                showToast('Checkout completed! Change: ₱' + (data.change || 0).toFixed(2), 'success');
                clearCart();
            } else {
                const data = await res.json().catch(() => null);
                showToast(data?.message || 'Checkout failed.', 'error');
            }
        } catch (e) {
            showToast('Connection error. Please try again.', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    // Checkout confirmation modal
    function showCheckoutConfirmModal(total, payment) {
        const overlay = document.createElement('div');
        overlay.className = 'pos-modal-overlay';
        overlay.innerHTML = `
            <div class="pos-modal">
                <div class="pos-modal-header">
                    <h3>Confirm Checkout</h3>
                    <button type="button" class="pos-modal-close" data-action="cancel">×</button>
                </div>
                <div class="pos-modal-body">
                    <p>Are you sure you want to complete this transaction?</p>
                    <div class="pos-modal-summary">
                        <div class="pos-modal-row"><span>Total</span><span>₱${total.toFixed(2)}</span></div>
                        <div class="pos-modal-row"><span>Payment</span><span>₱${payment.toFixed(2)}</span></div>
                        <div class="pos-modal-row"><span>Change</span><span>₱${(payment - total).toFixed(2)}</span></div>
                    </div>
                </div>
                <div class="pos-modal-footer">
                    <button type="button" class="pos-btn pos-btn-gray" data-action="cancel">Cancel</button>
                    <button type="button" class="pos-btn pos-btn-blue" data-action="confirm">Confirm</button>
                </div>
            </div>
        `;
        return new Promise((resolve) => {
            document.body.appendChild(overlay);
            document.body.style.overflow = 'hidden';
            const cleanup = () => { overlay.remove(); document.body.style.overflow = ''; };
            overlay.addEventListener('click', (e) => { if (e.target === overlay) { cleanup(); resolve(false); } });
            overlay.querySelectorAll('[data-action]').forEach((el) => {
                el.addEventListener('click', () => { const action = el.getAttribute('data-action'); cleanup(); resolve(action === 'confirm'); });
            });
        });
    }

    // Show toast notification
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = `toast ${type}`;
        toast.classList.add('show');
        setTimeout(() => { toast.classList.remove('show'); }, 3000);
    }

    // Escape HTML utility
    function escapeHtml(text) {
        const map = { '&': '&', '<': '<', '>': '>', '"': '"', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Initialize
    loadPosProducts();
</script>
<?php $__env->stopPush(); ?>