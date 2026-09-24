@extends('layouts.app')

@section('title', 'Stock-In / Receiving - Smart-Stock')

@section('content')
    <div class="section-header">
        <div>
            <h1 class="page-title">Stock-In / Receiving</h1>
            <p class="page-subtitle">Record incoming supplies and update inventory levels</p>
        </div>
        <div class="section-actions">
            <button class="btn-add" onclick="openReceiveModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Receive Stock</span>
            </button>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Supplier / Sender</th>
                    <th>Current Stock</th>
                    <th>Receiving Unit</th>
                    <th>Latest Received</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="stockInTableBody">
                <tr id="stockInLoadingRow"><td colspan="8" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading products...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- RECEIVE STOCK MODAL -->
    <div class="modal-overlay" id="receiveModal">
        <div class="modal">
            <h2>Receive Stock</h2>
            <form onsubmit="handleReceiveStock(event)" autocomplete="off">
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
                    <div class="form-static" id="rPiecesPerUnit">—</div>
                </div>
                <div class="form-group">
                    <label>Supplier</label>
                    <select name="supplier_id" id="rSupplierId">
                        <option value="">No supplier (optional)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity Received *</label>
                    <input type="number" name="quantity_received" id="rQuantity" placeholder="e.g. 5" min="1" required>
                </div>
                <div class="form-group">
                    <label>Unit of Measure *</label>
                    <input type="text" name="unit_of_measure" id="rUnitMeasure" placeholder="e.g. box, bag, roll, piece" required>
                </div>
                <div class="form-group">
                    <label>Effective Piece Delta (auto-calculated)</label>
                    <div class="form-static" id="rPieceDelta">0</div>
                </div>
                <div class="form-group">
                    <label>Note / Reference</label>
                    <textarea name="note" id="rNote" rows="3" maxlength="255" placeholder="e.g. PO #1234, received from warehouse"></textarea>
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
        .page-subtitle { color: #64748b; font-size: 14px; margin-bottom: 24px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
        .section-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .btn-add { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; border: none; border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 6px; transition: opacity 0.15s; }
        .btn-add:hover { opacity: 0.9; }
        .table-wrapper { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; overflow-x: auto; }
        table { width: 100%; min-width: 720px; border-collapse: collapse; }
        thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }
        tbody td { padding: 12px 16px; font-size: 13px; color: #cbd5e1; }
        .stock-cell { font-weight: 600; } .stock-ok { color: #4ade80; } .stock-low { color: #fbbf24; } .stock-critical { color: #f87171; }
        .stock-badge { display: inline-block; padding: 3px 8px; border-radius: 5px; font-size: 11px; font-weight: 600; }
        .stock-badge.ok { background: rgba(74,222,128,0.12); color: #4ade80; } .stock-badge.low { background: rgba(251,191,36,0.12); color: #fbbf24; } .stock-badge.critical { background: rgba(248,113,113,0.12); color: #f87171; }
        .btn-edit { background: #3b82f6; color: #fff; border: none; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 12px; font-family: 'Inter', sans-serif; transition: background 0.15s, opacity 0.15s; margin-right: 4px; }
        .btn-edit:hover { background: #2563eb; }
        .btn-delete { background: #ef4444; color: #fff; border: none; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 12px; font-family: 'Inter', sans-serif; transition: background 0.15s, opacity 0.15s; }
        .btn-adjust { background: #f59e0b; color: #fff; border: none; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 12px; font-family: 'Inter', sans-serif; transition: background 0.15s, opacity 0.15s; margin-right: 4px; }
        .btn-adjust:hover { background: #d97706; }
        body.light-theme .btn-edit { background: #3b82f6; color: #fff; }
        body.light-theme .btn-delete { background: #ef4444; color: #fff; }
        body.light-theme .btn-adjust { background: #f59e0b; color: #fff; }
        body.light-theme .btn-edit:hover { background: #2563eb; }
        body.light-theme .btn-delete:hover { background: #dc2626; }
        body.light-theme .btn-adjust:hover { background: #d97706; }
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
        const canReceiveStock = {{ Auth::user()->isAdmin() ? 'true' : 'false' }};
        let allProducts = [];
        let allSuppliers = [];

        function showLoadingSpinner() {
            document.getElementById('stockInTableBody').innerHTML = '<tr id="stockInLoadingRow"><td colspan="8" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading products...</td></tr>';
        }

        function showTableError(message) {
            document.getElementById('stockInTableBody').innerHTML = `<tr><td colspan="8" class="empty-state">${escapeHtml(message)}</td></tr>`;
        }

        async function loadProducts() {
            showLoadingSpinner();
            try {
                const res = await fetch('/api/inventory/products');
                if (!res.ok) throw new Error('Unable to load products');
                allProducts = await res.json();
                renderStockInTable(allProducts);
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
                // Suppliers are optional, so we don't show an error toast
            }
        }

        function renderStockInTable(products) {
            const body = document.getElementById('stockInTableBody');
            if (products.length === 0) {
                body.innerHTML = '<tr><td colspan="8" class="empty-state">No products found</td></tr>';
            } else {
                body.innerHTML = products.map(p => {
                    // Determine stock status
                    let stockClass = 'ok';
                    let stockLabel = 'OK';
                    if (p.current_stock <= p.reorder_threshold) {
                        stockClass = 'critical';
                        stockLabel = 'Critical';
                    } else if (p.current_stock <= p.reorder_threshold * 1.5) {
                        stockClass = 'low';
                        stockLabel = 'Low';
                    }

                    // Latest supplier / sender from the most recent stock-in record
                    const lastSupplier = p.last_supplier_name
                        ? `<span title="Last supplier">${escapeHtml(p.last_supplier_name)}</span>`
                        : '<span class="text-muted">—</span>';

                    // Latest received timestamp (formatted for display)
                    const lastReceived = p.last_received_at
                        ? `<span title="${escapeHtml(p.last_received_at)}">${formatReceivedDate(p.last_received_at)}</span>`
                        : '<span class="text-muted">Never</span>';

                    return `<tr id="row-${p.id}">
                        <td><strong>${escapeHtml(p.name)}</strong></td>
                        <td>${escapeHtml(p.sku)}</td>
                        <td>${escapeHtml(p.category || '—')}</td>
                        <td>${lastSupplier}</td>
                        <td class="stock-cell stock-${stockClass}">${p.current_stock}</td>
                        <td>${escapeHtml(p.receiving_unit || 'piece')}</td>
                        <td>${lastReceived}</td>
                        <td>
                            ${canReceiveStock ? '<button class="btn-edit" onclick="openReceiveModalForProduct(' + p.id + ')">Receive</button>' : ''}
                        </td>
                    </tr>`;
                }).join('');
            }
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
            // Reset form
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

        // Update product details when product selection changes
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

        // Update piece delta when quantity changes
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

            // Basic client-side validation
            const productId = document.getElementById('rProductId').value;
            const quantity = document.getElementById('rQuantity').value;
            const unitMeasure = document.getElementById('rUnitMeasure').value.trim();

            if (!productId) {
                showToast('Please select a product', 'error');
                return;
            }

            if (!quantity || quantity < 1) {
                showToast('Please enter a valid quantity', 'error');
                return;
            }

            if (!unitMeasure) {
                showToast('Please enter unit of measure', 'error');
                return;
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);

                // Convert to appropriate types
                data.product_id = parseInt(data.product_id);
                data.quantity_received = parseInt(data.quantity_received);
                data.supplier_id = data.supplier_id ? parseInt(data.supplier_id) : null;

                const res = await fetch('/api/inventory/stock-in', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });

                if (res.ok) {
                    closeReceiveModal();
                    showToast('Stock received successfully!', 'success');
                    loadProducts(); // Refresh the table
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

        // Initialize
        loadProducts();
        loadSuppliers();
    </script>
@endpush