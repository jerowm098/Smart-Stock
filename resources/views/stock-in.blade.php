@extends('layouts.app')

@section('title', 'Stock-In / Receiving - Smart-Stock')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Stock-In / Receiving</h1>
        <p class="page-subtitle">Record incoming supplies and update inventory levels</p>
    </div>

    <div class="section-card">
        <div class="section-card-header">
            <h2 class="section-card-title">Search & Filter</h2>
            <p class="section-card-desc">Find and narrow down products using the controls below</p>
        </div>
        <div class="filter-bar">
            <div class="filter-bar-inner">
                <div class="search-bar">
                    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" class="search-input-full" id="searchInput" placeholder="Search products by name, SKU, or category..." oninput="filterProducts()" autocomplete="off">
                </div>
                <div class="filter-bar-divider"></div>
                <div class="filter-group">
                    <label class="filter-label">Search In</label>
                    <select class="search-column-select" id="searchColumn" onchange="filterProducts()">
                        <option value="">All Columns</option>
                        <option value="name">Product Name</option>
                        <option value="sku">SKU</option>
                        <option value="category">Category</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Filter By</label>
                    <select class="stock-filter-select" id="stockFilter" onchange="filterProducts()">
                        <option value="">All Stock Status</option>
                        <option value="ok">In Stock (OK)</option>
                        <option value="low">Low Stock</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select class="sort-select" id="sortSelect" onchange="filterProducts()">
                        <option value="">Default</option>
                        <option value="name-asc">Name (A → Z)</option>
                        <option value="name-desc">Name (Z → A)</option>
                        <option value="stock-asc">Stock (Low → High)</option>
                        <option value="stock-desc">Stock (High → Low)</option>
                        <option value="date-desc">Last Received (Newest)</option>
                        <option value="date-asc">Last Received (Oldest)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-card-header">
            <div class="section-card-header-row">
                <div>
                    <h2 class="section-card-title">Stock Receiving</h2>
                    <p class="section-card-desc">Overview of all products and their receiving status</p>
                </div>
                <button class="btn-add" onclick="openReceiveModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Receive Stock</span>
                </button>
            </div>
        </div>
        <div class="section-card-toolbar">
            <div class="view-toggle">
                <button class="view-toggle-btn active" id="tableViewBtn" onclick="switchView('table')" title="Table View">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                </button>
                <button class="view-toggle-btn" id="gridViewBtn" onclick="switchView('grid')" title="Grid View">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </button>
            </div>
        </div>
        <div class="table-wrapper" id="tableView">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Receiving Unit</th>
                        <th>Latest Received</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="stockInTableBody">
                    <tr id="stockInLoadingRow"><td colspan="7" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading products...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="grid-view hidden" id="gridView">
            <div id="stockInGridBody" class="product-grid">
                <div class="empty-state" style="grid-column: 1/-1;"><span class="spinner" aria-hidden="true"></span> Loading products...</div>
            </div>
        </div>
    </div>

    <!-- RECEIVE STOCK MODAL -->
    <div class="modal-overlay" id="receiveModal">
        <div class="modal">
            <h2>Receive Stock</h2>
            <form onsubmit="handleReceiveStock(event)" autocomplete="off" class="receive-form-grid">
                <div class="form-group">
                    <label>Product *</label>
                    <select name="product_id" id="rProductId" required>
                        <option value="">Select a product...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Current Stock</label>
                    <div class="form-static" id="rCurrentStock">—</div>
                </div>
                <div class="form-group">
                    <label>Receiving Unit</label>
                    <div class="form-static" id="rReceivingUnit">—</div>
                </div>
                <div class="form-group">
                    <label>Pieces per Unit</label>
                    <div class="form-static" id="rPiecesPerUnit">—</div>
                </div>
                <div class="form-group">
                    <label>Supplier</label>
                    <select name="supplier_id" id="rSupplierId">
                        <option value="">No supplier (optional)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Effective Piece Delta (auto-calculated)</label>
                    <div class="form-static" id="rPieceDelta">0</div>
                </div>
                <div class="form-group">
                    <label>Quantity Received *</label>
                    <input type="number" name="quantity_received" id="rQuantity" placeholder="e.g. 5" min="1" required>
                </div>
                <div class="form-group">
                    <label>Unit of Measure *</label>
                    <input type="text" name="unit_of_measure" id="rUnitMeasure" placeholder="e.g. box, bag, roll, piece" required>
                </div>
                <div class="form-group full-width">
                    <label>Note / Reference</label>
                    <textarea name="note" id="rNote" rows="2" maxlength="255" placeholder="e.g. PO #1234, received from warehouse"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeReceiveModal()">Cancel</button>
                    <button type="submit" class="btn btn-submit">Receive Stock</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .page-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 6px; }
        .page-subtitle { color: #64748b; font-size: 14px; margin-bottom: 0; }
        .page-header { margin-bottom: 20px; }

        /* SECTION CARD */
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
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }
        .section-card-title { font-size: 15px; font-weight: 700; color: #e2e8f0; margin: 0 0 3px 0; }
        body.light-theme .section-card-title { color: #1e293b; }
        .section-card-desc { font-size: 13px; color: #64748b; margin: 0; }
        body.light-theme .section-card-desc { color: #94a3b8; }

        /* FILTER BAR */
        .filter-bar {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 14px 16px;
            margin: 0 16px 16px 16px;
        }
        body.light-theme .filter-bar { background: #f8fafc; border-color: rgba(15,23,42,0.08); }
        .filter-bar-inner { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
        .filter-bar-divider { width: 1px; height: 28px; background: rgba(255,255,255,0.1); align-self: flex-end; flex-shrink: 0; }
        body.light-theme .filter-bar-divider { background: rgba(15,23,42,0.12); }
        .search-bar { flex: 1 1 200px; min-width: 180px; position: relative; display: flex; align-items: center; }
        .search-icon { position: absolute; left: 12px; color: #64748b; pointer-events: none; z-index: 1; flex-shrink: 0; }
        body.light-theme .search-icon { color: #94a3b8; }
        .search-input-full {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 0 14px 0 36px;
            height: 36px;
            color: #f8fafc;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .search-input-full:focus { border-color: #3b82f6; }
        .search-input-full::placeholder { color: #64748b; }
        body.light-theme .search-input-full { background: #ffffff; border-color: rgba(15,23,42,0.12); color: #0f172a; }
        body.light-theme .search-input-full::placeholder { color: #94a3b8; }
        .filter-group { display: flex; flex-direction: column; gap: 4px; flex-shrink: 0; }
        .filter-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        body.light-theme .filter-label { color: #94a3b8; }

        /* SELECTS */
        .search-column-select, .stock-filter-select, .sort-select {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            padding: 0 30px 0 12px;
            height: 36px;
            color: #f8fafc;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
            box-sizing: border-box;
            min-width: 120px;
        }
        .search-column-select:focus, .stock-filter-select:focus, .sort-select:focus { border-color: #3b82f6; }
        .search-column-select option, .stock-filter-select option, .sort-select option { background: #1e293b; color: #e2e8f0; }
        .sort-select { min-width: 150px; }

        /* BUTTONS & TOGGLE */
        .btn-add {
            background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; border: none; border-radius: 8px;
            padding: 0 18px; height: 36px; font-size: 13px; font-weight: 600; cursor: pointer;
            font-family: 'Inter', sans-serif; display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.15s; white-space: nowrap; flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(59,130,246,0.25);
        }
        .btn-add:hover { opacity: 0.9; box-shadow: 0 4px 12px rgba(59,130,246,0.35); transform: translateY(-1px); }
        .section-card-toolbar { display: flex; justify-content: flex-end; padding: 6px 20px 10px 20px; }
        .view-toggle {
            display: flex; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; overflow: hidden;
        }
        body.light-theme .view-toggle { background: #f1f5f9; border-color: rgba(15,23,42,0.1); }
        .view-toggle-btn {
            display: flex; align-items: center; justify-content: center; width: 36px; height: 34px;
            background: transparent; border: none; color: #64748b; cursor: pointer; transition: all 0.15s;
        }
        .view-toggle-btn:hover { color: #94a3b8; }
        .view-toggle-btn.active { background: rgba(59,130,246,0.15); color: #60a5fa; }
        body.light-theme .view-toggle-btn { color: #94a3b8; }
        body.light-theme .view-toggle-btn:hover { color: #64748b; }
        body.light-theme .view-toggle-btn.active { background: rgba(59,130,246,0.1); color: #3b82f6; }

        /* TABLE */
        .section-card .table-wrapper {
            border: 1px solid rgba(255,255,255,0.08); border-radius: 10px;
            margin: 0 16px 16px 16px; width: calc(100% - 32px); background: rgba(255,255,255,0.02);
        }
        body.light-theme .section-card .table-wrapper { border-color: rgba(15,23,42,0.1); background: #ffffff; }
        table { width: 100%; min-width: 720px; border-collapse: collapse; }
        thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }
        tbody td { padding: 12px 16px; font-size: 13px; color: #cbd5e1; }
        .stock-cell { font-weight: 600; }
        .stock-ok { color: #4ade80; }
        .stock-low { color: #fbbf24; }
        .stock-critical { color: #f87171; }
        .stock-badge { display: inline-block; padding: 3px 8px; border-radius: 5px; font-size: 11px; font-weight: 600; }
        .stock-badge.ok { background: rgba(74,222,128,0.12); color: #4ade80; }
        .stock-badge.low { background: rgba(251,191,36,0.12); color: #fbbf24; }
        .stock-badge.critical { background: rgba(248,113,113,0.12); color: #f87171; }
        .text-muted { color: #64748b; }
        .empty-state { text-align: center; color: #475569; padding: 48px; font-size: 14px; }
        .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.2); border-top-color: #3b82f6; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .hidden { display: none !important; }
        .action-link { background: none; border: 1px solid transparent; padding: 4px 10px; cursor: pointer; font-size: 13px; font-family: 'Inter', sans-serif; color: #60a5fa; text-decoration: none; transition: all 0.15s; margin-right: 10px; display: inline-flex; align-items: center; gap: 5px; border-radius: 4px; }
        .action-link:last-child { margin-right: 0; }
        .action-link:hover { border-color: rgba(96,165,250,0.4); background: rgba(96,165,250,0.08); color: #93c5fd; }
        .action-link svg { width: 13px; height: 13px; flex-shrink: 0; }

        /* GRID VIEW */
        .grid-view { padding: 0 16px 16px 16px; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
        .product-card {
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px; overflow: hidden; transition: all 0.2s; cursor: default;
        }
        .product-card:hover { border-color: rgba(59,130,246,0.3); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
        body.light-theme .product-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme .product-card:hover { border-color: rgba(59,130,246,0.3); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .product-card-img { width: 100%; height: 160px; object-fit: cover; background: rgba(255,255,255,0.03); display: block; }
        body.light-theme .product-card-img { background: #f1f5f9; }
        .product-card-body { padding: 14px; }
        .product-card-name { font-size: 14px; font-weight: 600; color: #e2e8f0; margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        body.light-theme .product-card-name { color: #1e293b; }
        .product-card-sku { font-size: 12px; color: #64748b; margin: 0 0 10px 0; }
        .product-card-meta { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .product-card-category {
            display: inline-block; font-size: 11px; color: #94a3b8;
            background: rgba(255,255,255,0.06); padding: 2px 8px; border-radius: 4px; margin-bottom: 10px;
        }
        body.light-theme .product-card-category { background: #f1f5f9; color: #64748b; }
        .product-card-stock { font-size: 12px; font-weight: 600; padding: 3px 8px; border-radius: 6px; }
        .product-card-stock.stock-ok { background: rgba(74,222,128,0.12); color: #4ade80; }
        .product-card-stock.stock-low { background: rgba(251,191,36,0.12); color: #fbbf24; }
        .product-card-stock.stock-critical { background: rgba(248,113,113,0.12); color: #f87171; }
        body.light-theme .product-card-stock.stock-ok { background: rgba(22,163,74,0.1); color: #16a34a; }
        body.light-theme .product-card-stock.stock-low { background: rgba(202,138,4,0.1); color: #ca8a04; }
        body.light-theme .product-card-stock.stock-critical { background: rgba(220,38,38,0.1); color: #dc2626; }
        .product-card-details { font-size: 12px; color: #64748b; margin-top: 8px; display: flex; flex-direction: column; gap: 3px; }
        body.light-theme .product-card-details { color: #94a3b8; }
        .product-card-actions { display: flex; gap: 6px; margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.06); }
        body.light-theme .product-card-actions { border-top-color: rgba(15,23,42,0.06); }
        .product-card-actions .action-link { font-size: 12px; padding: 4px 8px; }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 200; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 28px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
        .modal h2 { color: #f8fafc; font-size: 18px; font-weight: 700; margin-bottom: 20px; }
        .receive-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
        .receive-form-grid .form-group.full-width { grid-column: 1 / -1; }
        .modal .form-group { margin-bottom: 14px; }
        .modal .form-group label { display: block; color: #cbd5e1; font-size: 13px; font-weight: 500; margin-bottom: 5px; }
        .modal .form-group input { width: 100%; padding: 9px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; box-sizing: border-box; }
        .modal .form-group input:focus { border-color: #3b82f6; }
        .modal .form-group input::placeholder { color: #64748b; }
        .modal .form-group select { width: 100%; padding: 9px 32px 9px 12px; background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; cursor: pointer; appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; background-size: 12px; box-sizing: border-box; }
        .modal .form-group select:focus { border-color: #3b82f6; }
        .modal .form-group select option { background: #1e293b; color: #e2e8f0; }
        .modal .form-group textarea { width: 100%; padding: 9px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; resize: vertical; box-sizing: border-box; }
        .modal .form-group textarea:focus { border-color: #3b82f6; }
        .modal .form-group textarea::placeholder { color: #64748b; }
        .modal .form-group .form-static { color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; padding: 9px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; box-sizing: border-box; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
        .modal-actions .btn { padding: 9px 18px; border: none; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; transition: opacity 0.15s; }
        .modal-actions .btn:hover { opacity: 0.9; }
        .btn-cancel { background: rgba(255,255,255,0.1); color: #e2e8f0; }
        .btn-submit { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; }
        .form-hint { color: #475569; font-size: 11px; margin-top: 4px; display: block; }

        /* LIGHT THEME */
        body.light-theme .page-title { color: #0f172a; }
        body.light-theme .page-subtitle { color: #64748b; }
        body.light-theme .search-column-select {
            background: #ffffff;
            border-color: rgba(15,23,42,0.14);
            color: #0f172a;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
        }
        body.light-theme .search-column-select option { background: #ffffff; color: #0f172a; }
        body.light-theme .stock-filter-select {
            background: #ffffff;
            border-color: rgba(15,23,42,0.14);
            color: #0f172a;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
        }
        body.light-theme .stock-filter-select option { background: #ffffff; color: #0f172a; }
        body.light-theme .sort-select {
            background: #ffffff;
            border-color: rgba(15,23,42,0.14);
            color: #0f172a;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
        }
        body.light-theme .sort-select option { background: #ffffff; color: #0f172a; }
        body.light-theme thead th { background: #f8fafc; color: #64748b; }
        body.light-theme tbody td { color: #334155; }
        body.light-theme tbody tr { border-top-color: rgba(15,23,42,0.06); }
        body.light-theme tbody tr:hover { background: rgba(15,23,42,0.025); }
        body.light-theme .empty-state { color: #94a3b8; }
        body.light-theme .modal { background: #ffffff; border-color: rgba(15,23,42,0.1); }
        body.light-theme .modal h2 { color: #0f172a; }
        body.light-theme .modal .form-group label { color: #475569; }
        body.light-theme .modal .form-group input { background: #f8fafc; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .modal .form-group input::placeholder { color: #94a3b8; }
        body.light-theme .modal .form-group select { background-color: #ffffff; border-color: rgba(15,23,42,0.14); color: #0f172a; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; background-size: 12px; }
        body.light-theme .modal .form-group select option { background: #ffffff; color: #0f172a; }
        body.light-theme .modal .form-group textarea { background-color: #f8fafc; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .modal .form-group textarea::placeholder { color: #94a3b8; }
        body.light-theme .modal .form-group .form-static { background-color: #f8fafc; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .modal-actions .btn-cancel { background: #e2e8f0; color: #334155; }
        body.light-theme .action-link { color: #1e293b; }
        body.light-theme .action-link:hover { border-color: rgba(37,99,235,0.3); background: rgba(37,99,235,0.06); color: #1d4ed8; }

        /* MOBILE */
        @media (max-width: 640px) {
            .page-title { font-size: 20px; }
            .page-subtitle { font-size: 13px; margin-bottom: 0; }
            .page-header { margin-bottom: 14px; }
            .section-card { border-radius: 12px; }
            .section-card-header { padding: 14px 14px 0 14px; }
            .filter-bar { margin: 10px 12px 12px 12px; padding: 12px; }
            .section-card .table-wrapper { margin: 0 12px 12px 12px; width: calc(100% - 24px); }
            .filter-bar-inner { gap: 10px; }
            .filter-bar-divider { display: none; }
            .search-bar { flex-basis: 100%; min-width: 0; }
            .filter-group { flex: 1 1 120px; min-width: 120px; }
            .section-card-header-row { flex-direction: column; }
            .section-card-header-row .btn-add { width: 100%; justify-content: center; }
            .section-card-toolbar { padding: 4px 14px 8px 14px; }
            .grid-view { margin: 0 12px 12px 12px; padding: 0; }
            .product-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; }
            .table-wrapper { border-radius: 10px; }
            table { min-width: 600px; }
            thead th, tbody td { padding: 10px 10px; font-size: 12px; }
            .empty-state { padding: 32px 16px; font-size: 13px; }
            .modal { width: calc(100vw - 24px); padding: 20px; max-height: 95vh; }
            .modal h2 { font-size: 16px; }
            .modal .form-group input { padding: 10px 12px; font-size: 14px; }
            .receive-form-grid { grid-template-columns: 1fr; gap: 14px; }
            .modal-actions { flex-direction: column; gap: 8px; }
            .modal-actions .btn { width: 100%; text-align: center; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        const canReceiveStock = {{ Auth::user()->isAdmin() ? 'true' : 'false' }};
        let allProducts = [];
        let allSuppliers = [];
        let currentView = 'table';

        function switchView(view) {
            currentView = view;
            document.getElementById('tableViewBtn').classList.toggle('active', view === 'table');
            document.getElementById('gridViewBtn').classList.toggle('active', view === 'grid');
            document.getElementById('tableView').classList.toggle('hidden', view !== 'table');
            document.getElementById('gridView').classList.toggle('hidden', view !== 'grid');
        }

        function showLoadingSpinner() {
            document.getElementById('stockInTableBody').innerHTML = '<tr id="stockInLoadingRow"><td colspan="7" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading products...</td></tr>';
            document.getElementById('stockInGridBody').innerHTML = '<div class="empty-state" style="grid-column: 1/-1;"><span class="spinner" aria-hidden="true"></span> Loading products...</div>';
        }

        function showTableError(message) {
            document.getElementById('stockInTableBody').innerHTML = `<tr><td colspan="7" class="empty-state">${escapeHtml(message)}</td></tr>`;
            document.getElementById('stockInGridBody').innerHTML = `<div class="empty-state" style="grid-column: 1/-1;">${escapeHtml(message)}</div>`;
        }

        async function loadProducts() {
            showLoadingSpinner();
            try {
                const res = await fetch('/api/inventory/products');
                if (!res.ok) throw new Error('Unable to load products');
                allProducts = await res.json();
                filterProducts();
                populateProductSelect();
            } catch (e) {
                showTableError('Unable to load products. Please try again.');
                showToast('Failed to load products', 'error');
            }
        }

        async function loadSuppliers() {
            try {
                const res = await fetch('/api/suppliers/active');
                if (!res.ok) throw new Error('Unable to load suppliers');
                allSuppliers = await res.json();
                populateSupplierSelect();
            } catch (e) {
                console.error('Failed to load suppliers:', e);
            }
        }

        function getStockStatus(product) {
            if (product.current_stock <= product.reorder_threshold) {
                return { cls: 'critical', label: 'Critical' };
            } else if (product.current_stock <= product.reorder_threshold * 1.5) {
                return { cls: 'low', label: 'Low' };
            }
            return { cls: 'ok', label: 'OK' };
        }

        function filterProducts() {
            const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();
            const searchCol = document.getElementById('searchColumn').value;
            const stockFilter = document.getElementById('stockFilter').value;
            const sortVal = document.getElementById('sortSelect').value;

            let filtered = allProducts.filter(p => {
                // Stock status filter
                if (stockFilter) {
                    const status = getStockStatus(p);
                    if (status.cls !== stockFilter) return false;
                }
                // Search filter
                if (searchVal) {
                    if (searchCol === 'name' && !p.name.toLowerCase().includes(searchVal)) return false;
                    if (searchCol === 'sku' && !p.sku.toLowerCase().includes(searchVal)) return false;
                    if (searchCol === 'category' && !(p.category || '').toLowerCase().includes(searchVal)) return false;
                    if (!searchCol) {
                        const haystack = [p.name, p.sku, p.category || ''].join(' ').toLowerCase();
                        if (!haystack.includes(searchVal)) return false;
                    }
                }
                return true;
            });

            // Sort
            if (sortVal) {
                const [field, dir] = sortVal.split('-');
                const mult = dir === 'desc' ? -1 : 1;
                filtered.sort((a, b) => {
                    if (field === 'name') return mult * a.name.localeCompare(b.name);
                    if (field === 'stock') return mult * (a.current_stock - b.current_stock);
                    if (field === 'date') {
                        const aTime = a.last_received_at ? new Date(a.last_received_at).getTime() : 0;
                        const bTime = b.last_received_at ? new Date(b.last_received_at).getTime() : 0;
                        return mult * (aTime - bTime);
                    }
                    return 0;
                });
            }

            renderStockInTable(filtered);
            renderStockInGrid(filtered);
        }

        function renderStockInTable(products) {
            const body = document.getElementById('stockInTableBody');
            if (products.length === 0) {
                body.innerHTML = '<tr><td colspan="7" class="empty-state">No products found</td></tr>';
            } else {
                body.innerHTML = products.map(p => {
                    const status = getStockStatus(p);
                    const lastReceived = p.last_received_at
                        ? `<span title="${escapeHtml(p.last_received_at)}">${formatReceivedDate(p.last_received_at)}</span>`
                        : '<span class="text-muted">Never</span>';

                    return `<tr id="row-${p.id}">
                        <td><strong>${escapeHtml(p.name)}</strong></td>
                        <td>${escapeHtml(p.sku)}</td>
                        <td>${escapeHtml(p.category || '—')}</td>
                        <td class="stock-cell stock-${status.cls}">${p.current_stock}</td>
                        <td>${escapeHtml(p.receiving_unit || 'piece')}</td>
                        <td>${lastReceived}</td>
                        <td>
                            ${canReceiveStock ? '<button class="action-link" onclick="openReceiveModalForProduct(' + p.id + ')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>Receive</button>' : ''}
                        </td>
                    </tr>`;
                }).join('');
            }
        }

        function renderStockInGrid(products) {
            const body = document.getElementById('stockInGridBody');
            if (products.length === 0) {
                body.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;">No products found</div>';
            } else {
                body.innerHTML = products.map(p => {
                    const status = getStockStatus(p);
                    const lastReceived = p.last_received_at ? formatReceivedDate(p.last_received_at) : 'Never';
                    return `
                    <div class="product-card">
                        <img class="product-card-img" src="${getProductImage(p.category, p.name)}" alt="${escapeHtml(p.name)}" onerror="this.style.display='none'">
                        <div class="product-card-body">
                            <p class="product-card-name" title="${escapeHtml(p.name)}">${escapeHtml(p.name)}</p>
                            <p class="product-card-sku">${escapeHtml(p.sku)}</p>
                            ${p.category ? `<span class="product-card-category">${escapeHtml(p.category)}</span>` : ''}
                            <div class="product-card-meta">
                                <span class="product-card-stock stock-${status.cls}">${status.label} · ${p.current_stock} pcs</span>
                            </div>
                            <div class="product-card-details">
                                <span>Unit: ${escapeHtml(p.receiving_unit || 'piece')}</span>
                                <span>Last Received: ${lastReceived}</span>
                            </div>
                            <div class="product-card-actions">
                                ${canReceiveStock ? `<button class="action-link" onclick="openReceiveModalForProduct(${p.id})">Receive</button>` : ''}
                            </div>
                        </div>
                    </div>`;
                }).join('');
            }
        }

        function getProductImage(category, name) {
            const text = (category + ' ' + name).toLowerCase();
            const images = {
                'power tool': 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=400&h=300&fit=crop',
                'drill': 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=400&h=300&fit=crop',
                'saw': 'https://images.unsplash.com/photo-1530124566582-a45a7c0be13e?w=400&h=300&fit=crop',
                'hand tool': 'https://images.unsplash.com/photo-1581783898377-1c85bf937427?w=400&h=300&fit=crop',
                'hammer': 'https://images.unsplash.com/photo-1581783898377-1c85bf937427?w=400&h=300&fit=crop',
                'wrench': 'https://images.unsplash.com/photo-1581783898377-1c85bf937427?w=400&h=300&fit=crop',
                'screwdriver': 'https://images.unsplash.com/photo-1581783898377-1c85bf937427?w=400&h=300&fit=crop',
                'electrical': 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=400&h=300&fit=crop',
                'wire': 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=400&h=300&fit=crop',
                'cable': 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=400&h=300&fit=crop',
                'extension cord': 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=400&h=300&fit=crop',
                'electronic': 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&h=300&fit=crop',
                'flashlight': 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&h=300&fit=crop',
                'led': 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&h=300&fit=crop',
                'plumbing': 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=400&h=300&fit=crop',
                'pipe': 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=400&h=300&fit=crop',
                'pvc': 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=400&h=300&fit=crop',
                'painting': 'https://images.unsplash.com/photo-1562259948-e9299e178d8f?w=400&h=300&fit=crop',
                'paint': 'https://images.unsplash.com/photo-1562259948-e9299e178d8f?w=400&h=300&fit=crop',
                'roller': 'https://images.unsplash.com/photo-1562259948-e9299e178d8f?w=400&h=300&fit=crop',
                'measuring': 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?w=400&h=300&fit=crop',
                'level': 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?w=400&h=300&fit=crop',
                'tape': 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?w=400&h=300&fit=crop',
                'lumber': 'https://images.unsplash.com/photo-1520333789090-1afc82db536a?w=400&h=300&fit=crop',
                'wood': 'https://images.unsplash.com/photo-1520333789090-1afc82db536a?w=400&h=300&fit=crop',
                'fastener': 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=400&h=300&fit=crop',
                'screw': 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=400&h=300&fit=crop',
                'nail': 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=400&h=300&fit=crop',
                'safety': 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=400&h=300&fit=crop',
                'glove': 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=400&h=300&fit=crop',
                'cleaning': 'https://images.unsplash.com/photo-1585421514284-efb74c2b69ba?w=400&h=300&fit=crop',
                'adhesive': 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?w=400&h=300&fit=crop',
                'glue': 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?w=400&h=300&fit=crop'
            };
            for (const [key, url] of Object.entries(images)) {
                if (text.includes(key)) return url;
            }
            return 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=400&h=300&fit=crop';
        }

        function formatReceivedDate(isoString) {
            const date = new Date(isoString);
            if (isNaN(date.getTime())) return isoString;
            const now = new Date();
            const diffMs = now - date;
            const diffMin = Math.floor(diffMs / 60000);
            const diffHr = Math.floor(diffMs / 3600000);
            const diffDay = Math.floor(diffMs / 86400000);

            if (diffMin < 1) return 'Just now';
            if (diffMin < 60) return diffMin + ' min ago';
            if (diffHr < 24) return diffHr + ' hr ago';
            if (diffDay < 7) return diffDay + ' day ago';
            return date.toLocaleDateString();
        }

        function populateProductSelect() {
            const select = document.getElementById('rProductId');
            select.innerHTML = '<option value="">Select a product...</option>' + 
                allProducts.map(p => 
                    `<option value="${p.id}">${escapeHtml(p.name)} (${escapeHtml(p.sku)})</option>`
                ).join('');
        }

        function populateSupplierSelect() {
            const select = document.getElementById('rSupplierId');
            select.innerHTML = '<option value="">No supplier (optional)</option>' + 
                allSuppliers.map(s => 
                    `<option value="${s.id}">${escapeHtml(s.name)}</option>`
                ).join('');
        }

        function openReceiveModal() {
            document.getElementById('receiveModal').classList.add('active');
            document.getElementById('rProductId').focus();
            document.getElementById('receiveModal').querySelector('form').reset();
            updatePieceDelta();
        }

        function openReceiveModalForProduct(productId) {
            document.getElementById('receiveModal').classList.add('active');
            document.getElementById('rProductId').value = productId;
            document.getElementById('rProductId').dispatchEvent(new Event('change'));
            document.getElementById('rQuantity').focus();
        }

        function closeReceiveModal() {
            document.getElementById('receiveModal').classList.remove('active');
            document.getElementById('receiveModal').querySelector('form').reset();
        }

        document.getElementById('receiveModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('receiveModal')) closeReceiveModal();
        });

        document.getElementById('rProductId').addEventListener('change', function() {
            const productId = this.value;
            if (!productId) {
                document.getElementById('rCurrentStock').textContent = '—';
                document.getElementById('rReceivingUnit').textContent = '—';
                document.getElementById('rPiecesPerUnit').textContent = '—';
                updatePieceDelta();
                return;
            }
            const product = allProducts.find(p => p.id == productId);
            if (product) {
                document.getElementById('rCurrentStock').textContent = product.current_stock;
                document.getElementById('rReceivingUnit').textContent = product.receiving_unit || 'piece';
                document.getElementById('rPiecesPerUnit').textContent = product.pieces_per_receiving_unit || 1;
                updatePieceDelta();
            }
        });

        function updatePieceDelta() {
            const quantity = parseInt(document.getElementById('rQuantity').value) || 0;
            const productId = document.getElementById('rProductId').value;
            const product = allProducts.find(p => p.id == productId);
            const piecesPerUnit = product ? (product.pieces_per_receiving_unit || 1) : 1;
            const delta = quantity * piecesPerUnit;
            document.getElementById('rPieceDelta').textContent = delta;
        }

        document.getElementById('rQuantity').addEventListener('input', updatePieceDelta);
        document.getElementById('rProductId').addEventListener('change', updatePieceDelta);

        async function handleReceiveStock(e) {
            e.preventDefault();
            const productId = document.getElementById('rProductId').value;
            const quantity = document.getElementById('rQuantity').value;
            const unitMeasure = document.getElementById('rUnitMeasure').value.trim();

            if (!productId) { showToast('Please select a product', 'error'); return; }
            if (!quantity || quantity < 1) { showToast('Please enter a valid quantity', 'error'); return; }
            if (!unitMeasure) { showToast('Please enter unit of measure', 'error'); return; }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);
                data.product_id = parseInt(data.product_id);
                data.quantity_received = parseInt(data.quantity_received);
                data.supplier_id = data.supplier_id ? parseInt(data.supplier_id) : null;

                const res = await fetch('/api/inventory/stock-in', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(data)
                });

                if (res.ok) {
                    closeReceiveModal();
                    showToast('Stock received successfully!', 'success');
                    loadProducts();
                } else {
                    const errData = await res.json();
                    showToast(errData?.message || 'Error receiving stock', 'error');
                }
            } catch (e) {
                showToast('Connection error', 'error');
            }
        }

        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            t.textContent = msg; t.className = 'toast show ' + type;
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        function escapeHtml(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        loadProducts();
        loadSuppliers();
    </script>
@endpush