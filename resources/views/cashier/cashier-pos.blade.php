@extends('layouts.dashboard-main-frame')

@section('title', 'POS Checkout - Smart-Stock')

@section('content')
<div class="pos-page">
    <div class="page-header hero">
        <div class="hero-content">
            <h1 class="page-title">POS Checkout</h1>
            <p class="page-subtitle">Process sales and manage cart</p>
        </div>
    </div>

    <!-- ====== STAT CARDS ====== -->
    <div class="pos-stats-grid">
        <div class="pos-stat-card">
            <div class="pos-stat-header">
                <h3>Today's Revenue</h3>
                <span class="pos-stat-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </span>
            </div>
            <div class="pos-stat-value green" id="posStatRevenue">₱0.00</div>
            <div class="pos-stat-subtext">Completed sales this shift</div>
        </div>
        <div class="pos-stat-card">
            <div class="pos-stat-header">
                <h3>Cart Total</h3>
                <span class="pos-stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </span>
            </div>
            <div class="pos-stat-value blue" id="posStatCartTotal">₱0.00</div>
            <div class="pos-stat-subtext">Current sale amount</div>
        </div>
        <div class="pos-stat-card">
            <div class="pos-stat-header">
                <h3>Items in Cart</h3>
                <span class="pos-stat-icon purple">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </span>
            </div>
            <div class="pos-stat-value purple" id="posStatCartItems">0</div>
            <div class="pos-stat-subtext">Line items in current cart</div>
        </div>
    </div>

    <!-- SECTION CARD 1: Search and Filter Options -->
    <div class="section-card pos-filter-card">
        <div class="section-card-header">
            <h2 class="section-card-title">Search and Filter Options</h2>
            <p class="section-card-desc">Find and narrow down products using the controls below</p>
        </div>
        <div class="filter-bar">
            <div class="pos-filter-inner">
                <div class="filter-group pos-filter-search">
                    <label class="filter-label" for="posSearchInput">Search</label>
                    <div class="valid-wrap has-icon-left">
                        <span class="input-icon-left" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </span>
                        <input type="text" class="field-input" id="posSearchInput" placeholder="Search products by name, SKU, or category..." oninput="filterPosProducts()" autocomplete="off">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="posSearchColumnLabel">Search In</label>
                    <div class="role-select" id="posSearchColumnSelect">
                        <button type="button" class="field-input role-select-btn" id="posSearchColumnBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="posSearchColumnLabel posSearchColumnBtnLabel" onclick="toggleFilterSelect(event, 'posSearchColumnSelect')">
                            <span id="posSearchColumnBtnLabel">All Columns</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="posSearchColumnList" role="listbox" aria-labelledby="posSearchColumnLabel">
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="" onclick="selectPosFilter('posSearchColumn', '', 'All Columns')">All Columns</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="name" onclick="selectPosFilter('posSearchColumn', 'name', 'Product Name')">Product Name</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="sku" onclick="selectPosFilter('posSearchColumn', 'sku', 'SKU / Code (stock keeping unit)')">SKU / Code (stock keeping unit)</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="category" onclick="selectPosFilter('posSearchColumn', 'category', 'Category')">Category</button>
                        </div>
                        <input type="hidden" id="posSearchColumn" value="">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="posCategoryFilterLabel">Filter By</label>
                    <div class="role-select" id="posCategoryFilterSelect">
                        <button type="button" class="field-input role-select-btn" id="posCategoryFilterBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="posCategoryFilterLabel posCategoryFilterBtnLabel" onclick="toggleFilterSelect(event, 'posCategoryFilterSelect')">
                            <span id="posCategoryFilterBtnLabel">All Categories</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="posCategoryFilterList" role="listbox" aria-labelledby="posCategoryFilterLabel">
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="" data-label="All Categories">All Categories</button>
                        </div>
                        <input type="hidden" id="posCategoryFilter" value="">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="posSortSelectLabel">Sort By</label>
                    <div class="role-select" id="posSortSelectWrap">
                        <button type="button" class="field-input role-select-btn" id="posSortSelectBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="posSortSelectLabel posSortSelectBtnLabel" onclick="toggleFilterSelect(event, 'posSortSelectWrap')">
                            <span id="posSortSelectBtnLabel">Default</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="posSortSelectList" role="listbox" aria-labelledby="posSortSelectLabel">
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="" onclick="selectPosFilter('posSortSelect', '', 'Default')">Default</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="name-asc" onclick="selectPosFilter('posSortSelect', 'name-asc', 'Name (A → Z)')">Name (A → Z)</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="name-desc" onclick="selectPosFilter('posSortSelect', 'name-desc', 'Name (Z → A)')">Name (Z → A)</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="price-asc" onclick="selectPosFilter('posSortSelect', 'price-asc', 'Price (Low → High)')">Price (Low → High)</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="price-desc" onclick="selectPosFilter('posSortSelect', 'price-desc', 'Price (High → Low)')">Price (High → Low)</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="stock-asc" onclick="selectPosFilter('posSortSelect', 'stock-asc', 'Stock (Low → High)')">Stock (Low → High)</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="stock-desc" onclick="selectPosFilter('posSortSelect', 'stock-desc', 'Stock (High → Low)')">Stock (High → Low)</button>
                        </div>
                        <input type="hidden" id="posSortSelect" value="">
                    </div>
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
                                <th>SKU / CODE<br><span style="font-size:0.85em;text-transform:none;white-space:nowrap;">(stock keeping unit)</span></th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Updated</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="posProductTableBody">
                        </tbody>
                    </table>
                </div>
                <!-- Grid View -->
                <div class="grid-view hidden" id="gridView">
                    <div id="posProductGridBody" class="product-grid">
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
                            {{-- BRD (POS): "The system shall allow the user to click a single,
                                 prominent 'Complete Sale' button to finalize the transaction."
                                 Reliability: the button disables immediately after one click
                                 to prevent accidental double-billing / double deduction. --}}
                            <button type="submit" class="pos-btn pos-btn-blue" id="checkoutBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>Complete Sale
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
    /* Hero banner surface + text are theme tokens: the base block is the DARK
       treatment and .light-theme overrides it. Hardcoding #ffffff on the base
       rule left the banner white in dark mode with near-invisible text. */
    .page-header.hero {
        --hero-card-bg: rgba(255,255,255,0.025);
        --hero-card-border: rgba(96,165,250,0.18);
        --hero-card-title: #f8fafc;
        --hero-card-subtitle: rgba(226,232,240,0.88);
    }
    .page-title { font-size: 30px; font-weight: 800; color: var(--hero-card-title, #f8fafc); margin: 0 0 6px 0; line-height: 1.25; letter-spacing: -0.02em; }
    .page-subtitle { color: var(--hero-card-subtitle, rgba(226,232,240,0.88)); font-size: 14px; margin: 0; line-height: 1.5; }
    .page-header.hero .page-subtitle { margin-bottom: 0; }
    .page-header.hero {
        position: relative; overflow: hidden; border-radius: 16px;
        border: 1px solid var(--hero-card-border); margin-bottom: 20px;
        min-height: 140px; display: flex; align-items: flex-end;
        padding: 28px 28px 24px; background: var(--hero-card-bg);
    }
    .hero-content { position: relative; z-index: 1; max-width: 640px; }
    body.light-theme .page-header.hero {
        --hero-card-bg: #ffffff;
        --hero-card-border: rgba(37,99,235,0.15);
        --hero-card-title: #0f172a;
        --hero-card-subtitle: #475569;
    }
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
    }
    .section-card-header { padding: 18px 20px 16px 20px; }
    .section-card-header-row {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    }
    .section-card-title { font-size: 15px; font-weight: 700; color: #e2e8f0; margin: 0 0 3px 0; }
    body.light-theme .section-card-title { color: #1e293b; }
    .section-card-desc { font-size: 13px; color: #64748b; margin: 0; }
    body.light-theme .section-card-desc { color: #94a3b8; }
    /* STAT CARDS — mirrors Transactions / Order Suggestions */
    .pos-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .pos-stat-card {
        background: rgba(255,255,255,0.025);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    body.light-theme .pos-stat-card {
        background: #ffffff;
        border-color: rgba(15,23,42,0.08);
    }
    .pos-stat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 6px;
    }
    .pos-stat-header h3 {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #ffffff;
        margin: 0;
    }
    body.light-theme .pos-stat-header h3 { color: #64748b; }
    .pos-stat-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .pos-stat-icon svg { width: 20px; height: 20px; display: block; }
    .pos-stat-icon.green  { background: rgba(74,222,128,0.15);  color: #4ade80; }
    .pos-stat-icon.blue   { background: rgba(96,165,250,0.15);  color: #60a5fa; }
    .pos-stat-icon.purple { background: rgba(167,139,250,0.15); color: #a78bfa; }
    .pos-stat-value {
        font-size: 26px;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: -0.02em;
        color: #f8fafc;
    }
    .pos-stat-value.green  { color: #4ade80; }
    .pos-stat-value.blue   { color: #60a5fa; }
    .pos-stat-value.purple { color: #a78bfa; }
    .pos-stat-subtext { font-size: 12px; color: #ffffff; }
    body.light-theme .pos-stat-value { color: #0f172a; }
    body.light-theme .pos-stat-value.green  { color: #16a34a; }
    body.light-theme .pos-stat-value.blue   { color: #2563eb; }
    body.light-theme .pos-stat-value.purple { color: #9333ea; }
    body.light-theme .pos-stat-subtext { color: #94a3b8; }
    body.light-theme .pos-stat-icon.green  { background: rgba(22,163,74,0.1); }
    body.light-theme .pos-stat-icon.blue   { background: rgba(37,99,235,0.1); }
    body.light-theme .pos-stat-icon.purple { background: rgba(147,51,234,0.1); }
    .section-card .filter-bar { margin: 14px 16px 16px 16px; }

    /* ===== FILTER BAR =====
       2 rows: a full-width search field on row 1, the filter dropdowns on
       row 2. Mirrors the Search and Filter Options layout used on the
       Transactions / Order Suggestions / User Accounts pages. */
    .filter-bar {
        background: rgba(255,255,255,0.045); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px; padding: 14px 16px;
    }
    body.light-theme .filter-bar { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.08); }
    /* The shared .section-card clips its contents; dropdown lists are drawn
       outside the card so the filter card must allow overflow. */
    .pos-filter-card { overflow: visible; }
    .pos-filter-inner {
        display: grid;
        grid-template-columns: 1fr 1fr 1.5fr;
        gap: 16px 20px;
        align-items: end;
        width: 100%;
        min-width: 0;
    }
    .pos-filter-inner > .pos-filter-search { grid-column: 1 / -1; }
    .filter-group { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
    .filter-group .filter-label {
        font-size: 11px; font-weight: 700; color: #cbd5e1;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    body.light-theme .filter-group .filter-label { color: #334155; }

    /* ===== FIELDS ===== */
    .field-input {
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12);
        border-radius: 8px; padding: 0 12px; height: 36px; color: #f8fafc;
        font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s;
        cursor: pointer; appearance: none; -webkit-appearance: none; -moz-appearance: none;
        box-sizing: border-box; width: 100%; min-width: 0;
    }
    input.field-input { cursor: text; padding: 0 12px 0 14px; min-width: 0; }
    .field-input:focus { border-color: #3b82f6; }
    .field-input::placeholder { color: #64748b; }
    body.light-theme .field-input { background: #ffffff; border-color: rgba(15,23,42,0.14); color: #0f172a; }
    body.light-theme .field-input::placeholder { color: #94a3b8; }

    /* ===== CUSTOM DROPDOWN =====
       Native <select> popups ignore the page theme, so the filters use a
       button + listbox pair backed by hidden inputs (same pattern as the
       Transactions / Order Suggestions pages). */
    .role-select { position: relative; width: 100%; min-width: 0; }
    .role-select-btn {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        width: 100%; text-align: left; font-weight: 400;
    }
    .role-select-btn > span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .role-select-btn svg { flex-shrink: 0; color: #94a3b8; transition: transform 0.15s; }
    .role-select.open .role-select-btn svg { transform: rotate(180deg); }
    .role-select-list {
        position: absolute; top: calc(100% + 6px); left: 0; z-index: 60;
        min-width: 100%;
        width: max-content;
        max-width: calc(100vw - 96px);
        display: none; flex-direction: column; padding: 6px; gap: 2px;
        background: #1e293b; border: 1px solid rgba(255,255,255,0.12);
        border-radius: 8px; box-shadow: 0 12px 28px rgba(2,6,23,0.5);
        max-height: 50vh; overflow-y: auto;
    }
    .role-select.open .role-select-list { display: flex; }
    .role-select.drop-up .role-select-list { top: auto; bottom: calc(100% + 6px); }
    body.light-theme .role-select-list { background: #ffffff; border-color: rgba(15,23,42,0.12); box-shadow: 0 12px 28px rgba(15,23,42,0.15); }
    .role-select-option {
        border: none; background: transparent; text-align: left; cursor: pointer;
        font-family: 'Inter', sans-serif; font-size: 13px; color: #e2e8f0;
        padding: 9px 10px; border-radius: 6px; width: 100%;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .role-select-option:hover { background: rgba(59,130,246,0.15); }
    .role-select-option.selected { color: #93c5fd; font-weight: 600; }
    body.light-theme .role-select-option { color: #0f172a; }
    body.light-theme .role-select-option:hover { background: rgba(37,99,235,0.08); }
    body.light-theme .role-select-option.selected { color: #1d4ed8; }

    /* ===== LEADING INPUT ICON (inside the field, on the left) ===== */
    .valid-wrap { position: relative; width: 100%; min-width: 0; }
    .input-icon-left {
        position: absolute; top: 50%; left: 12px; transform: translateY(-50%);
        width: 16px; height: 16px; display: inline-flex;
        align-items: center; justify-content: center;
        color: #64748b; pointer-events: none;
    }
    .input-icon-left svg { width: 16px; height: 16px; display: block; }
    .valid-wrap.has-icon-left .field-input { padding-left: 36px; }
    body.light-theme .input-icon-left { color: #94a3b8; }

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
        background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;
        margin: 0 16px 16px 16px; width: calc(100% - 32px); overflow-x: auto; overflow-y: visible;
    }
    body.light-theme .section-card .table-wrapper { border-color: rgba(15,23,42,0.12); background: #ffffff; }
    table { width: 100%; min-width: 600px; border-collapse: separate; border-spacing: 0; border: 1px solid rgba(255,255,255,0.08); table-layout: fixed; }
    thead th { background: rgba(255,255,255,0.04); padding: 14px 16px; text-align: center; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: normal; border-bottom: 1px solid rgba(255,255,255,0.08); border-right: 1px solid rgba(255,255,255,0.06); line-height: 1.4; }
    thead th:last-child { border-right: none; }
    tbody tr { border-top: 1px solid rgba(255,255,255,0.06); transition: background 0.15s; }
    tbody tr:hover { background: rgba(255,255,255,0.03); }
    tbody td { padding: 14px 16px; font-size: 13px; color: #cbd5e1; border-right: 1px solid rgba(255,255,255,0.06); border-bottom: 1px solid rgba(255,255,255,0.06); vertical-align: middle; text-align: center; }
    tbody td:last-child { border-right: none; }
    tbody tr:last-child td { border-bottom: none; }
    /* Hide text until data loads */
    #tableView.loading tbody td:not(.empty-state) { color: transparent; }
    #tableView.loading tbody td.empty-state { color: #64748b; }
    #tableView.loading table { min-width: 0; }
    body.light-theme table thead th { background: rgba(15,23,42,0.02); color: #64748b; }
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
    .product-card:hover { border-color: rgba(59,130,246,0.3); transform: translateY(-2px); }
    body.light-theme .product-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
    body.light-theme .product-card:hover { border-color: rgba(59,130,246,0.3); }
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
    .product-card-time { font-size: 11px; line-height: 1.5; color: #64748b; margin: 0 0 8px 0; }
    body.light-theme .product-card-time { color: #64748b; }
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
    /* Border tracks the text colour (currentColor) so the outline always
       matches the label, and hover shades that same colour instead of
       falling back to a fixed blue. */
    .action-link {
        background: none; border: 1px solid currentColor; padding: 4px 10px; cursor: pointer;
        font-size: 13px; font-family: 'Inter', sans-serif; color: #60a5fa; text-decoration: none;
        transition: all 0.15s; margin-right: 8px; display: inline-flex; align-items: center; gap: 5px;
        border-radius: 6px;
    }
    .action-link:last-child { margin-right: 0; }
    .action-link:hover { border-color: currentColor; background: rgba(96,165,250,0.12); color: #93c5fd; }
    .action-link svg { width: 13px; height: 13px; flex-shrink: 0; }
    .action-link:disabled { opacity: 0.4; cursor: not-allowed; transform: none; filter: none; }
    body.light-theme .action-link { color: #1e293b; }
    body.light-theme .action-link:hover { border-color: currentColor; background: rgba(30,41,59,0.08); color: #0f172a; }

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
    }
    .pos-btn svg { width: 14px; height: 14px; flex-shrink: 0; }
    .pos-btn:active { filter: brightness(0.85); transform: scale(0.97); }
    .pos-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; filter: none; }
    .pos-btn-blue { background: #2563eb; color: #fff; }
    .pos-btn-blue:hover { background: #1d4ed8; }
    .pos-btn-red { background: #dc2626; color: #fff; }
    .pos-btn-red:hover { background: #b91c1c; }
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
        overflow: hidden;
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

    /* Page title/subtitle colours now come from the --hero-card-* tokens on
       .page-header.hero, so these duplicate overrides are no longer needed. */

    /* ===== RESPONSIVE ===== */
    @media (max-width: 900px) {
        .pos-content-grid { grid-template-columns: 1fr; }
        .pos-filter-inner { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .page-title { font-size: 20px; }
        .page-subtitle { font-size: 13px; margin-bottom: 0; }
        .page-header { margin-bottom: 14px; }
        .section-card { border-radius: 12px; }
        .section-card-header { padding: 14px 14px 0 14px; }
        .section-card .filter-bar { margin: 10px 12px 12px 12px; padding: 12px; }
        .section-card .table-wrapper { margin: 0 12px 12px 12px; width: calc(100% - 24px); }
        .pos-filter-inner { grid-template-columns: minmax(0, 1fr); gap: 10px; }
        .section-card-header-row { flex-direction: column; }
        .pos-content-grid { padding: 0 12px 12px 12px; gap: 12px; }
        .grid-view { padding: 0 12px 12px 12px; }
        .product-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
        .product-card-img { height: 100px; }
        .product-card-body { padding: 10px; }
        .table-wrapper { border-radius: 10px; }
        table { min-width: 400px; }
        #tableView.loading table { min-width: 0; }
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
    let posSessionRevenue = 0; // Tracks today's revenue in this session

    /* ---- Filter dropdowns -------------------------------------------------
       The native <select> popup is OS-rendered and ignores the page theme, so
       the Search In / Filter By / Sort By controls use a button + listbox pair
       backed by hidden inputs. The rest of the filter code keeps reading the
       hidden input `.value`, so no other logic needs to change. */
    function toggleFilterSelect(e, wrapId) {
        if (e) e.stopPropagation();
        const wrap = document.getElementById(wrapId);
        if (!wrap) return;
        const btn = wrap.querySelector('.role-select-btn');
        const willOpen = !wrap.classList.contains('open');
        closeAllFilterSelects();
        if (willOpen) {
            // Flip the list upward when the viewport bottom would clip it.
            const list = wrap.querySelector('.role-select-list');
            let listH = 0;
            if (list) {
                list.style.visibility = 'hidden';
                list.style.display = 'flex';
                listH = list.offsetHeight;
                list.style.display = '';
                list.style.visibility = '';
            }
            const spaceBelow = window.innerHeight - btn.getBoundingClientRect().bottom;
            wrap.classList.toggle('drop-up', spaceBelow < listH + 16);
        }
        wrap.classList.toggle('open', willOpen);
        btn?.setAttribute('aria-expanded', String(willOpen));
    }

    function closeAllFilterSelects() {
        document.querySelectorAll('.role-select.open').forEach((wrap) => {
            wrap.classList.remove('open');
            wrap.querySelector('.role-select-btn')?.setAttribute('aria-expanded', 'false');
        });
    }

    /* `silent` skips the change event so programmatic normalisation does not
       trigger extra work. */
    function selectFilterOption(inputId, value, label, silent = false) {
        const input = document.getElementById(inputId);
        if (input) {
            input.value = value;
            if (!silent) input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        const wrap = input?.closest('.role-select');
        const labelEl = wrap?.querySelector('.role-select-btn span');
        if (labelEl) labelEl.textContent = label;
        wrap?.querySelectorAll('.role-select-option').forEach((opt) => {
            const selected = opt.dataset.value === value;
            opt.classList.toggle('selected', selected);
            opt.setAttribute('aria-selected', String(selected));
        });
        closeAllFilterSelects();
    }

    // Update the chosen filter value, then re-run the product filter.
    function selectPosFilter(inputId, value, label) {
        selectFilterOption(inputId, value, label);
        filterPosProducts();
    }

    // Category options are rebuilt from data, so select via a delegated handler.
    document.getElementById('posCategoryFilterList')?.addEventListener('click', (e) => {
        const opt = e.target.closest('.role-select-option');
        if (!opt) return;
        selectPosFilter('posCategoryFilter', opt.dataset.value, opt.dataset.label || 'All Categories');
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.role-select')) closeAllFilterSelects();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAllFilterSelects();
    });

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
        const tableView = document.getElementById('tableView');
        tableView.classList.add('loading');
        try {
            // Cashier-safe catalog: excludes deactivated products and Admin-only fields.
            const res = await fetch('/api/pos/products');
            if (!res.ok) throw new Error('Unable to load products');
            posProducts = await res.json();
            populateCategoryFilter();
            filterPosProducts();
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Unable to load products. Please try again.</td></tr>';
            grid.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;">Unable to load products. Please try again.</div>';
            showToast('Failed to load products', 'error');
        } finally {
            tableView.classList.remove('loading');
        }
    }

    function populateCategoryFilter() {
        const list = document.getElementById('posCategoryFilterList');
        const input = document.getElementById('posCategoryFilter');
        if (!list || !input) return;
        const selected = input.value;
        const categories = [...new Set(posProducts.map(p => (p.category || '').trim()).filter(c => c))].sort((a, b) => a.localeCompare(b));
        const option = (value, label, isSel) => `<button type="button" class="role-select-option${isSel ? ' selected' : ''}" role="option" aria-selected="${isSel}" data-value="${escapeHtml(value)}" data-label="${escapeHtml(label)}">${escapeHtml(label)}</button>`;
        list.innerHTML = option('', 'All Categories', selected === '')
            + categories.map(c => option(c, c, c === selected)).join('');
        // If the previously selected category is gone, fall back to All.
        if (selected && !categories.includes(selected)) {
            selectFilterOption('posCategoryFilter', '', 'All Categories');
        }
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
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No products found</td></tr>';
            return;
        }
        tbody.innerHTML = products.map(p => {
            const s = getStockStatus(p.current_stock, p.reorder_threshold);
            const isInCart = cart.find(item => item.product_id === p.id);
            const outOfStock = p.current_stock <= 0;
            const actionBtn = outOfStock
                ? `<button class="action-link" disabled style="color:#f87171;border-color:#f87171;cursor:not-allowed;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>Out of Stock</button>`
                : `<button class="action-link" onclick="addToCart(${p.id})" ${isInCart ? '' : ''}><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>Add</button>`;
            return `<tr>
                <td><strong>${escapeHtml(p.name)}</strong></td>
                <td>${escapeHtml(p.sku || '—')}</td>
                <td>${escapeHtml(p.category || '—')}</td>
                <td>₱${parseFloat(p.price).toFixed(2)}</td>
                <td><span class="stock-cell stock-${s.class}">${p.current_stock}</span></td>
                <td>${fmtDateTime(p.updated_at)}</td>
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
                ? `<button class="action-link" disabled style="color:#f87171;border-color:#f87171;cursor:not-allowed;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>Out of Stock</button>`
                : `<button class="action-link" onclick="addToCart(${p.id})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>Add</button>`;
            return `<div class="product-card">
                <img class="product-card-img" src="${img}" alt="${escapeHtml(p.name)}" loading="lazy" onerror="this.src='${productImages['default']}'">
                <div class="product-card-body">
                    <div class="product-card-category">${escapeHtml(p.category || 'Uncategorized')}</div>
                    <h3 class="product-card-name" title="${escapeHtml(p.name)}">${escapeHtml(p.name)}</h3>
                    <p class="product-card-sku">${escapeHtml(p.sku || '')}</p>
                    <p class="product-card-time">Updated ${fmtDateTime(p.updated_at)}</p>
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
        document.getElementById('summarySubtotal').textContent = '₱' + subtotal.toFixed(2);
        document.getElementById('summaryTotal').textContent = '₱' + subtotal.toFixed(2);
        const count = cart.reduce((sum, item) => sum + item.quantity, 0);
        document.getElementById('cartCount').textContent = count + ' item' + (count !== 1 ? 's' : '');
        updateChange();
        updatePosStatCards(subtotal, count);
    }

    function updatePosStatCards(cartTotal, cartItems) {
        document.getElementById('posStatCartTotal').textContent = '₱' + cartTotal.toFixed(2);
        document.getElementById('posStatCartItems').textContent = cartItems.toLocaleString();
    }

    function updateChange() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        // BRD (POS) Business rules: "Cart Total = Sum of (Item Unit Price * Quantity)."
        // No tax multiplier — the server records total_amount as the plain subtotal
        // (PosCheckoutController: "No tax applied: the store sells at listed price").
        const total = subtotal;
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
        // BRD (POS) Business rules: "Cart Total = Sum of (Item Unit Price * Quantity)."
        // Keep this in sync with the server, which records the plain subtotal with
        // no tax multiplier applied.
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const total = subtotal;
        const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
        if (paymentAmount < total) { showToast('Insufficient funds.', 'error'); return; }
        if (!await showCheckoutConfirmModal(total, paymentAmount)) return;
        // BRD (POS) Reliability: "The 'Complete Sale' button shall disable
        // immediately after one click to prevent accidental double-billing or
        // double-inventory deduction."
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
                posSessionRevenue += total;
                updatePosStatCards(0, 0); // cart cleared, but update today's revenue
                document.getElementById('posStatRevenue').textContent = '₱' + posSessionRevenue.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                // BRD (POS) Constraint: "The system shall clear the cart and reset
                // the POS interface to a blank state immediately after a sale is
                // finalized." clearCart() re-enables the button for the next customer.
                clearCart();
            } else {
                const data = await res.json().catch(() => null);
                showToast(data?.message || 'Checkout failed.', 'error');
                // Failed sale — allow a retry, but only when the cart still has items.
                btn.disabled = cart.length === 0;
            }
        } catch (e) {
            showToast('Connection error. Please try again.', 'error');
            btn.disabled = cart.length === 0;
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
    // Show loading message initially
    document.getElementById('tableView').classList.add('loading');
    document.getElementById('posProductTableBody').innerHTML = '<tr><td colspan="7" class="empty-state">Loading products...</td></tr>';
    loadPosProducts();
    updatePosStatCards(0, 0);
</script>
<?php $__env->stopPush(); ?>