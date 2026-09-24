@extends('layouts.app')

@section('title', 'Products - Smart-Stock')

@section('content')
    <div class="section-header">
        <div>
            <h1 class="page-title">Products</h1>
            <p class="page-subtitle">Manage inventory items and add new products</p>
        </div>
        <div class="section-actions">
            <select class="search-column-select" id="searchColumn" onchange="filterProducts()">
                <option value="">All Columns</option>
                <option value="sku">SKU / Code</option>
                <option value="name">Item Name</option>
                <option value="category">Category</option>
            </select>
            <select class="category-select" id="categoryFilter" onchange="filterProducts()">
                <option value="">All Categories</option>
            </select>
            <input type="text" class="search-input" id="searchInput" placeholder="Search products..." oninput="filterProducts()" autocomplete="off">
            <button class="btn-add" onclick="openModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add Product</span>
            </button>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>SKU / Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Unit Price</th>
                    <th>Current Stock Count</th>
                    <th>Reorder Threshold</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <tr id="productLoadingRow"><td colspan="8" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading products...</td></tr>
            </tbody>
        </table>
    </div>
    <!-- ADD PRODUCT MODAL -->
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <h2>Add New Product</h2>
            <form onsubmit="handleAddProduct(event)" autocomplete="off">
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" id="pNameM" placeholder="e.g. Wireless Mouse" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>SKU *</label>
                    <input type="text" name="sku" id="pSkuM" placeholder="e.g. WM-001" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" id="pCategoryM" placeholder="e.g. Electronics" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Price (₱) *</label>
                    <input type="number" name="price" id="pPriceM" placeholder="0.00" step="0.01" min="0" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Current Stock *</label>
                    <input type="number" name="current_stock" id="pStockM" placeholder="0" min="0" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Reorder Threshold *</label>
                    <input type="number" name="reorder_threshold" id="pThresholdM" placeholder="0" min="0" required autocomplete="off">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-submit">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT PRODUCT MODAL -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <h2>Edit Product</h2>
            <form onsubmit="handleEditProduct(event)" autocomplete="off">
                <input type="hidden" id="eId" name="id">
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" id="eName" placeholder="e.g. Wireless Mouse" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>SKU *</label>
                    <input type="text" name="sku" id="eSku" placeholder="e.g. WM-001" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" id="eCategory" placeholder="e.g. Electronics" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Price (₱) *</label>
                    <input type="number" name="price" id="ePrice" placeholder="0.00" step="0.01" min="0" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Current Stock *</label>
                    <input type="number" name="current_stock" id="eStock" placeholder="0" min="0" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Reorder Threshold *</label>
                    <input type="number" name="reorder_threshold" id="eThreshold" placeholder="0" min="0" required autocomplete="off">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-submit">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- STOCK ADJUSTMENT MODAL -->
    <div class="modal-overlay" id="adjustModal">
        <div class="modal">
            <h2>Adjust Stock</h2>
            <form onsubmit="handleAdjustStock(event)" autocomplete="off">
                <input type="hidden" id="aId" name="product_id">
                <div class="form-group">
                    <label>Product</label>
                    <div class="form-static" id="aProductName">—</div>
                </div>
                <div class="form-group">
                    <label>Current Stock</label>
                    <div class="form-static" id="aCurrentStock">0</div>
                </div>
                <div class="form-group">
                    <label>Reason *</label>
                    <select name="reason" id="aReason" required>
                        <option value="" disabled selected>Select a reason</option>
                        <option value="damaged">Damaged</option>
                        <option value="lost">Lost</option>
                        <option value="internal_transfer">Internal Transfer</option>
                        <option value="correction">Stock Correction</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Stock Delta *</label>
                    <input type="number" name="delta" id="aDelta" placeholder="-2" step="1" required>
                    <small class="form-hint">Use a negative value to reduce stock or a positive value to add stock.</small>
                </div>
                <div class="form-group">
                    <label>Reason / Note</label>
                    <textarea name="reason_note" id="aReasonNote" rows="3" maxlength="255" placeholder="e.g. 2 units damaged during inspection"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeAdjustModal()">Cancel</button>
                    <button type="submit" class="btn btn-submit">Adjust Stock</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .page-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 6px; }
        .page-subtitle { color: #64748b; font-size: 14px; margin-bottom: 24px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
        .section-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .btn-add { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; border: none; border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 6px; transition: opacity 0.15s; }
        .btn-add:hover { opacity: 0.9; }
        .search-column-select {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            padding: 8px 32px 8px 12px;
            color: #f8fafc;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s;
            cursor: pointer;
            min-width: 130px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
        }
        .search-column-select:focus { border-color: #3b82f6; }
        .search-column-select option { background: #1e293b; color: #e2e8f0; }
        .category-select {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            padding: 8px 32px 8px 12px;
            color: #f8fafc;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s;
            cursor: pointer;
            min-width: 150px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
        }
        .category-select:focus { border-color: #3b82f6; }
        .category-select option { background: #1e293b; color: #e2e8f0; }
        .search-input { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; padding: 8px 14px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; width: 240px; outline: none; transition: border-color 0.2s; }
        .search-input:focus { border-color: #3b82f6; } .search-input::placeholder { color: #64748b; }
        .table-wrapper { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; overflow-x: auto; }
        table { width: 100%; min-width: 720px; border-collapse: collapse; }
        thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }
        tbody td { padding: 12px 16px; font-size: 13px; color: #cbd5e1; }
        .stock-cell { font-weight: 600; } .stock-ok { color: #4ade80; } .stock-low { color: #fbbf24; } .stock-critical { color: #f87171; }
        .status-text { font-size: 13px; font-weight: 500; }
        .action-link { background: none; border: 1px solid transparent; padding: 4px 10px; cursor: pointer; font-size: 13px; font-family: 'Inter', sans-serif; color: #60a5fa; text-decoration: none; transition: all 0.15s; margin-right: 10px; display: inline-flex; align-items: center; gap: 5px; border-radius: 4px; }
        .action-link:last-child { margin-right: 0; }
        .action-link:hover { border-color: rgba(96,165,250,0.4); background: rgba(96,165,250,0.08); color: #93c5fd; }
        .action-link svg { width: 13px; height: 13px; flex-shrink: 0; }
        .action-link.danger { color: #f87171; }
        .action-link.danger:hover { border-color: rgba(248,113,113,0.4); background: rgba(248,113,113,0.08); color: #fca5a5; }
        body.light-theme .action-link { color: #1e293b; }
        body.light-theme .action-link:hover { border-color: rgba(37,99,235,0.3); background: rgba(37,99,235,0.06); color: #1d4ed8; }
        body.light-theme .action-link.danger { color: #dc2626; }
        body.light-theme .action-link.danger:hover { border-color: rgba(220,38,38,0.3); background: rgba(220,38,38,0.06); color: #b91c1c; }
        .empty-state { text-align: center; color: #475569; padding: 48px; font-size: 14px; }
        /* MODAL */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 200; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 28px; width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
        .modal h2 { color: #f8fafc; font-size: 18px; font-weight: 700; margin-bottom: 20px; }
        .modal .form-group { margin-bottom: 14px; }
        .modal .form-group label { display: block; color: #cbd5e1; font-size: 13px; font-weight: 500; margin-bottom: 5px; }
        .modal .form-group input { width: 100%; padding: 9px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; }
        .modal .form-group input:focus { border-color: #3b82f6; } .modal .form-group input::placeholder { color: #64748b; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
        .modal-actions .btn { padding: 9px 18px; border: none; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; transition: opacity 0.15s; }
        .modal-actions .btn:hover { opacity: 0.9; } .btn-cancel { background: rgba(255,255,255,0.1); color: #e2e8f0; } .btn-submit { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; }
        .modal select { width: 100%; padding: 9px 32px 9px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; cursor: pointer; appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; background-size: 12px; }
        .modal select:focus { border-color: #3b82f6; }
        .modal select option { background: #1e293b; color: #e2e8f0; }
        .modal textarea { width: 100%; padding: 9px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; resize: vertical; }
        .modal textarea:focus { border-color: #3b82f6; }
        .modal textarea::placeholder { color: #64748b; }
        .modal .form-static { color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; padding: 9px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; }
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
        body.light-theme .category-select {
            background: #ffffff;
            border-color: rgba(15,23,42,0.14);
            color: #0f172a;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
        }
        body.light-theme .category-select option { background: #ffffff; color: #0f172a; }
        body.light-theme .search-input { background: #ffffff; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .search-input::placeholder { color: #94a3b8; }
        body.light-theme .table-wrapper { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme table thead th { background: #f8fafc; color: #64748b; }
        body.light-theme table tbody td { color: #475569; }
        body.light-theme table tbody tr:hover { background: rgba(15,23,42,0.025); }
        body.light-theme .empty-state { color: #94a3b8; }
        body.light-theme .modal { background: #ffffff; border-color: rgba(15,23,42,0.1); }
        body.light-theme .modal h2 { color: #0f172a; }
        body.light-theme .modal .form-group label { color: #475569; }
        body.light-theme .modal .form-group input { background: #f8fafc; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .modal-actions .btn-cancel { background: #e2e8f0; color: #334155; }

        /* STOCK ADJUSTMENT MODAL - LIGHT THEME */
        body.light-theme #adjustModal .modal { background: #ffffff; border-color: rgba(15,23,42,0.1); }
        body.light-theme #adjustModal .modal h2 { color: #0f172a; }
        body.light-theme #adjustModal .modal .form-group label { color: #475569; }
        body.light-theme #adjustModal .modal .form-static {
            color: #0f172a;
            background: #f8fafc;
            border-color: rgba(15,23,42,0.14);
        }
        body.light-theme #adjustModal .modal .form-group input {
            background: #f8fafc;
            border-color: rgba(15,23,42,0.14);
            color: #0f172a;
        }
        body.light-theme #adjustModal .modal .form-group input::placeholder { color: #94a3b8; }
        body.light-theme #adjustModal .modal select {
            background-color: #ffffff;
            border-color: rgba(15,23,42,0.14);
            color: #0f172a;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        }
        body.light-theme #adjustModal .modal select:focus { border-color: #2563eb; }
        body.light-theme #adjustModal .modal select option { background: #ffffff; color: #0f172a; }
        body.light-theme #adjustModal .modal textarea {
            background: #f8fafc;
            border-color: rgba(15,23,42,0.14);
            color: #0f172a;
        }
        body.light-theme #adjustModal .modal textarea::placeholder { color: #94a3b8; }
        body.light-theme #adjustModal .modal .form-hint { color: #64748b; }
        body.light-theme #adjustModal .modal .btn-cancel { background: #e2e8f0; color: #334155; }
        /* MOBILE */
        @media (max-width: 640px) {
            .page-title { font-size: 20px; }
            .page-subtitle { font-size: 13px; margin-bottom: 16px; }
            .section-header { flex-direction: column; align-items: stretch; gap: 12px; }
            .section-actions { flex-direction: column; width: 100%; gap: 10px; }
            .search-input { width: 100%; }
            .btn-add { width: 100%; justify-content: center; padding: 10px 18px; }
            .table-wrapper { border-radius: 10px; }
            table { min-width: 600px; }
            thead th, tbody td { padding: 10px 10px; font-size: 12px; }
            .empty-state { padding: 32px 16px; font-size: 13px; }
            .modal { width: calc(100vw - 24px); padding: 20px; max-height: 95vh; }
            .modal h2 { font-size: 16px; }
            .modal .form-group input { padding: 10px 12px; font-size: 14px; }
            .modal-actions { flex-direction: column; gap: 8px; }
            .modal-actions .btn { width: 100%; text-align: center; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        const canAdjustStock = {{ Auth::user()->isAdmin() ? 'true' : 'false' }};
        const canManageProducts = {{ Auth::user()->isCashier() ? 'true' : 'false' }};
        let allProducts = [];

        function showLoadingSpinner() {
            document.getElementById('productTableBody').innerHTML = '<tr id="productLoadingRow"><td colspan="8" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading products...</td></tr>';
        }

        function showTableError(message) {
            document.getElementById('productTableBody').innerHTML = `<tr><td colspan="8" class="empty-state">${escapeHtml(message)}</td></tr>`;
        }

        async function loadProducts() {
            showLoadingSpinner();
            try {
                const res = await fetch('/api/inventory/products');
                if (!res.ok) throw new Error('Unable to load products');
                allProducts = await res.json();
                populateCategoryFilter();
                filterProducts();
            } catch (e) {
                showTableError('Unable to load products. Please try again.');
                showToast('Failed to load products', 'error');
            }
        }

        function populateCategoryFilter() {
            const select = document.getElementById('categoryFilter');
            const selectedCategory = select.value;
            const categories = [
                ...new Set(
                    allProducts
                        .map(product => (product.category || '').trim())
                        .filter(category => category)
                )
            ].sort((a, b) => a.localeCompare(b));

            select.innerHTML = '<option value="">All Categories</option>' + categories.map(category =>
                `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`
            ).join('');

            select.value = categories.includes(selectedCategory) ? selectedCategory : '';
        }

        function renderFullTable(products) {
            const body = document.getElementById('productTableBody');
            if (products.length === 0) {
                body.innerHTML = '<tr><td colspan="8" class="empty-state">No products found</td></tr>';
            } else {
                body.innerHTML = products.map(p => {
                    const s = getStatus(p.current_stock, p.reorder_threshold);
                    return `<tr id="row-${p.id}">
                        <td>${escapeHtml(p.sku)}</td>
                        <td><strong>${escapeHtml(p.name)}</strong></td>
                        <td>${escapeHtml(p.category || '—')}</td>
                        <td>₱${parseFloat(p.price).toFixed(2)}</td>
                        <td class="stock-cell stock-${s.class}">${p.current_stock}</td>
                        <td>${p.reorder_threshold}</td>
                        <td><span class="status-text stock-${s.class}">${s.label}</span></td>
                        <td>${canManageProducts ? '<button class="action-link" onclick="openEditModal(' + p.id + ')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>Edit</button>' : ''}${canAdjustStock ? '<button class="action-link" onclick="openAdjustModal(' + p.id + ', ' + p.current_stock + ')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>Adjust</button>' : ''}${canManageProducts ? '<button class="action-link danger" onclick="deleteProduct(' + p.id + ')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>Delete</button>' : ''}</td>
                    </tr>`;
                }).join('');
            }
        }

        function filterProducts() {
            const t = document.getElementById('searchInput').value.toLowerCase();
            const col = document.getElementById('searchColumn').value;
            const category = document.getElementById('categoryFilter').value;

            const f = allProducts.filter(p => {
                if (category && (p.category || '').trim().toLowerCase() !== category.toLowerCase()) {
                    return false;
                }

                if (!t) return true;

                switch(col) {
                    case 'name':
                        return p.name && p.name.toLowerCase().includes(t);
                    case 'sku':
                        return p.sku && p.sku.toLowerCase().includes(t);
                    case 'category':
                        return p.category && p.category.toLowerCase().includes(t);
                    default:
                        return (p.name && p.name.toLowerCase().includes(t)) ||
                               (p.sku && p.sku.toLowerCase().includes(t)) ||
                               (p.category && p.category.toLowerCase().includes(t));
                }
            });
            renderFullTable(f);
        }

        function openModal() {
            document.getElementById('addModal').classList.add('active');
            document.getElementById('pNameM').focus();
        }

        function closeModal() {
            document.getElementById('addModal').classList.remove('active');
            document.getElementById('addModal').querySelector('form').reset();
        }

        document.getElementById('addModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('addModal')) closeModal();
        });

        async function handleAddProduct(e) {
            e.preventDefault();
            const fd = new FormData(e.target);
            const data = Object.fromEntries(fd);
            data.price = parseFloat(data.price);
            data.current_stock = parseInt(data.current_stock);
            data.reorder_threshold = parseInt(data.reorder_threshold);

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/api/inventory/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });
                if (res.ok) {
                    closeModal();
                    showToast('Product added successfully!', 'success');
                    loadProducts();
                } else {
                    const errData = await res.json().catch(() => null);
                    showToast(errData?.message || 'Error adding product', 'error');
                }
            } catch (e) {
                showToast('Connection error', 'error');
            }
        }

        async function deleteProduct(id) {
            if (!confirm('Are you sure you want to delete this product?')) return;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch(`/api/inventory/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                if (res.ok) {
                    showToast('Product deleted', 'success');
                    loadProducts();
                } else {
                    const errData = await res.json().catch(() => null);
                    showToast(errData?.message || 'Failed to delete product', 'error');
                }
            } catch (e) {
                showToast('Failed to connect', 'error');
            }
        }

        function getStatus(stock, threshold) {
            if (stock <= threshold) return { label: 'Critical', class: 'critical' };
            if (stock <= threshold * 1.5) return { label: 'Low', class: 'low' };
            return { label: 'OK', class: 'ok' };
        }

        // --- SS-82: Edit Product modal helpers ---

        function openEditModal(id) {
            const product = allProducts.find(p => p.id === id);
            if (!product) {
                showToast('Product not found', 'error');
                return;
            }

            document.getElementById('eId').value = product.id;
            document.getElementById('eName').value = product.name || '';
            document.getElementById('eSku').value = product.sku || '';
            document.getElementById('eCategory').value = product.category || '';
            document.getElementById('ePrice').value = product.price || '';
            document.getElementById('eStock').value = product.current_stock || '';
            document.getElementById('eThreshold').value = product.reorder_threshold || '';

            document.getElementById('editModal').classList.add('active');
            document.getElementById('eName').focus();
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            document.getElementById('editModal').querySelector('form').reset();
        }

        document.getElementById('editModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('editModal')) closeEditModal();
        });

        // --- SS-83: Frontend field validations ---

        function validateEditForm() {
            const name = document.getElementById('eName').value.trim();
            const sku = document.getElementById('eSku').value.trim();
            const price = parseFloat(document.getElementById('ePrice').value);
            const stock = parseInt(document.getElementById('eStock').value, 10);
            const threshold = parseInt(document.getElementById('eThreshold').value, 10);

            if (!name) { showToast('Product name is required', 'error'); return false; }
            if (!sku) { showToast('SKU is required', 'error'); return false; }
            if (isNaN(price) || price < 0) { showToast('Price must be a non-negative number', 'error'); return false; }
            if (isNaN(stock) || stock < 0) { showToast('Current stock must be a non-negative integer', 'error'); return false; }
            if (isNaN(threshold) || threshold < 0) { showToast('Reorder threshold must be a non-negative integer', 'error'); return false; }

            return true;
        }

        async function handleEditProduct(e) {
            e.preventDefault();
            if (!validateEditForm()) return;

            const fd = new FormData(e.target);
            const data = Object.fromEntries(fd);
            data.price = parseFloat(data.price);
            data.current_stock = parseInt(data.current_stock);
            data.reorder_threshold = parseInt(data.reorder_threshold);

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/api/inventory/update', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });

                if (res.ok) {
                    closeEditModal();
                    showToast('Product updated successfully!', 'success');
                    loadProducts();
                } else {
                    const errData = await res.json().catch(() => null);
                    showToast(errData?.message || 'Error updating product', 'error');
                }
            } catch (e) {
                showToast('Connection error', 'error');
            }
        }

        // --- SS-96: Stock Adjustment modal helpers ---

        function openAdjustModal(id, currentStock) {
            const product = allProducts.find(p => p.id === id);
            if (!product) {
                showToast('Product not found', 'error');
                return;
            }

            document.getElementById('aId').value = product.id;
            document.getElementById('aProductName').textContent = `${product.name} (${product.sku})`;
            document.getElementById('aCurrentStock').textContent = product.current_stock;
            document.getElementById('aDelta').value = '';
            document.getElementById('aReason').value = '';
            document.getElementById('aReasonNote').value = '';

            document.getElementById('adjustModal').classList.add('active');
            document.getElementById('aReason').focus();
        }

        function closeAdjustModal() {
            document.getElementById('adjustModal').classList.remove('active');
            document.getElementById('adjustModal').querySelector('form').reset();
        }

        document.getElementById('adjustModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('adjustModal')) closeAdjustModal();
        });

        function validateAdjustForm() {
            const reason = document.getElementById('aReason').value;
            const delta = parseInt(document.getElementById('aDelta').value, 10);

            if (!reason) { showToast('Please select a reason', 'error'); return false; }
            if (isNaN(delta) || delta === 0) { showToast('Please enter a non-zero stock delta', 'error'); return false; }

            return true;
        }

        async function handleAdjustStock(e) {
            e.preventDefault();
            if (!validateAdjustForm()) return;

            const fd = new FormData(e.target);
            const data = Object.fromEntries(fd);
            data.product_id = parseInt(data.product_id, 10);
            data.delta = parseInt(data.delta, 10);

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/api/inventory/adjust', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });

                if (res.ok) {
                    closeAdjustModal();
                    showToast('Stock adjusted successfully!', 'success');
                    loadProducts();
                } else {
                    const errData = await res.json().catch(() => null);
                    showToast(errData?.message || 'Error adjusting stock', 'error');
                }
            } catch (e) {
                showToast('Connection error', 'error');
            }
        }

        loadProducts();
    </script>
@endpush
