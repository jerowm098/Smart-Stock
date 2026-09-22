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
        .search-input { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; padding: 8px 14px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; width: 240px; outline: none; transition: border-color 0.2s; }
        .search-input:focus { border-color: #3b82f6; } .search-input::placeholder { color: #64748b; }
        .table-wrapper { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; overflow-x: auto; }
        table { width: 100%; min-width: 720px; border-collapse: collapse; }
        thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }
        tbody td { padding: 12px 16px; font-size: 13px; color: #cbd5e1; }
        .stock-cell { font-weight: 600; } .stock-ok { color: #4ade80; } .stock-low { color: #fbbf24; } .stock-critical { color: #f87171; }
        .stock-badge { display: inline-block; padding: 3px 8px; border-radius: 5px; font-size: 11px; font-weight: 600; }
        .stock-badge.ok { background: rgba(74,222,128,0.12); color: #4ade80; } .stock-badge.low { background: rgba(251,191,36,0.12); color: #fbbf24; } .stock-badge.critical { background: rgba(248,113,113,0.12); color: #f87171; }
        .btn-delete { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 12px; font-family: 'Inter', sans-serif; transition: background 0.15s; }
        .btn-delete:hover { background: rgba(239,68,68,0.2); }
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
                renderFullTable(allProducts);
            } catch (e) {
                showTableError('Unable to load products. Please try again.');
                showToast('Failed to load products', 'error');
            }
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
                        <td><span class="stock-badge ${s.class}">${s.label}</span></td>
                        <td><button class="btn-delete" onclick="deleteProduct(${p.id})">Delete</button></td>
                    </tr>`;
                }).join('');
            }
        }

        function filterProducts() {
            const t = document.getElementById('searchInput').value.toLowerCase();
            const col = document.getElementById('searchColumn').value;
            
            const f = allProducts.filter(p => {
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

        loadProducts();
    </script>
@endpush
