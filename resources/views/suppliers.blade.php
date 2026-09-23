@extends('layouts.app')

@section('title', 'Suppliers - Smart-Stock')

@section('content')
    <div class="section-header">
        <div>
            <h1 class="page-title">Suppliers</h1>
            <p class="page-subtitle">Manage your supplier directory and add new contacts</p>
        </div>
        <div class="section-actions">
            <button class="btn-add" onclick="openAddModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add Supplier</span>
            </button>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Supplier Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="supplierTableBody">
                <tr id="supplierLoadingRow"><td colspan="5" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading suppliers...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- ADD SUPPLIER MODAL -->
    <div class="modal-overlay" id="addSupplierModal">
        <div class="modal">
            <h2>Add Supplier</h2>
            <form onsubmit="handleAddSupplier(event)" autocomplete="off">
                <div class="form-group">
                    <label>Supplier Name *</label>
                    <input type="text" name="name" id="sName" placeholder="e.g. Hardware World" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person" id="sContact" placeholder="e.g. Juan Dela Cruz" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="sPhone" placeholder="e.g. 09123456789" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="sEmail" placeholder="e.g. contact@example.com" autocomplete="off">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-submit">Add Supplier</button>
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
        table { width: 100%; min-width: 600px; border-collapse: collapse; }
        thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }
        tbody td { padding: 12px 16px; font-size: 13px; color: #cbd5e1; }
        .stock-badge { display: inline-block; padding: 3px 8px; border-radius: 5px; font-size: 11px; font-weight: 600; }
        .stock-badge.ok { background: rgba(74,222,128,0.12); color: #4ade80; }
        .empty-state { text-align: center; color: #475569; padding: 48px; font-size: 14px; }
        .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.2); border-top-color: #3b82f6; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        /* MODAL */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 200; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 28px; width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
        .modal h2 { color: #f8fafc; font-size: 18px; font-weight: 700; margin-bottom: 20px; }
        .modal .form-group { margin-bottom: 14px; }
        .modal .form-group label { display: block; color: #cbd5e1; font-size: 13px; font-weight: 500; margin-bottom: 5px; }
        .modal .form-group input { width: 100%; padding: 9px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; }
        .modal .form-group input:focus { border-color: #3b82f6; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
        .modal-actions .btn { padding: 9px 18px; border: none; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; transition: opacity 0.15s; }
        .modal-actions .btn:hover { opacity: 0.9; }
        .btn-cancel { background: rgba(255,255,255,0.1); color: #e2e8f0; }
        .btn-submit { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; }
        /* LIGHT THEME */
        body.light-theme .page-title { color: #0f172a; }
        body.light-theme .page-subtitle { color: #64748b; }
        body.light-theme .table-wrapper { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme thead th { background: #f8fafc; color: #64748b; }
        body.light-theme tbody td { color: #475569; }
        body.light-theme tbody tr:hover { background: rgba(15,23,42,0.025); }
        body.light-theme .empty-state { color: #94a3b8; }
        body.light-theme .modal { background: #ffffff; border-color: rgba(15,23,42,0.1); }
        body.light-theme .modal h2 { color: #0f172a; }
        body.light-theme .modal .form-group label { color: #475569; }
        body.light-theme .modal .form-group input { background: #f8fafc; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .modal .form-group input::placeholder { color: #94a3b8; }
        body.light-theme .modal-actions .btn-cancel { background: #e2e8f0; color: #334155; }
        /* MOBILE */
        @media (max-width: 640px) {
            .page-title { font-size: 20px; }
            .page-subtitle { font-size: 13px; margin-bottom: 16px; }
            .section-header { flex-direction: column; align-items: stretch; gap: 12px; }
            .section-actions { flex-direction: column; width: 100%; gap: 10px; }
            .btn-add { width: 100%; justify-content: center; padding: 10px 18px; }
            .table-wrapper { border-radius: 10px; }
            table { min-width: 580px; }
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
        let allSuppliers = [];

        function showLoadingSpinner() {
            document.getElementById('supplierTableBody').innerHTML = '<tr id="supplierLoadingRow"><td colspan="5" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading suppliers...</td></tr>';
        }

        function showTableError(message) {
            document.getElementById('supplierTableBody').innerHTML = `<tr><td colspan="5" class="empty-state">${escapeHtml(message)}</td></tr>`;
        }

        async function loadSuppliers() {
            showLoadingSpinner();
            try {
                const res = await fetch('/api/suppliers/active');
                if (!res.ok) throw new Error('Unable to load suppliers');
                allSuppliers = await res.json();
                renderSupplierTable(allSuppliers);
            } catch (e) {
                showTableError('Unable to load suppliers. Please try again.');
                showToast('Failed to load suppliers', 'error');
            }
        }

        function renderSupplierTable(suppliers) {
            const body = document.getElementById('supplierTableBody');
            if (suppliers.length === 0) {
                body.innerHTML = '<tr><td colspan="5" class="empty-state">No suppliers found</td></tr>';
            } else {
                body.innerHTML = suppliers.map(s => `
                    <tr id="supplier-row-${s.id}">
                        <td><strong>${escapeHtml(s.name)}</strong></td>
                        <td>${escapeHtml(s.contact_person || '—')}</td>
                        <td>${escapeHtml(s.phone || '—')}</td>
                        <td>${escapeHtml(s.email || '—')}</td>
                        <td><span class="stock-badge ok">Active</span></td>
                    </tr>
                `).join('');
            }
        }

        function openAddModal() {
            document.getElementById('addSupplierModal').classList.add('active');
            document.getElementById('sName').focus();
        }

        function closeAddModal() {
            document.getElementById('addSupplierModal').classList.remove('active');
            document.getElementById('addSupplierModal').querySelector('form').reset();
        }

        document.getElementById('addSupplierModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('addSupplierModal')) closeAddModal();
        });

        async function handleAddSupplier(e) {
            e.preventDefault();
            const fd = new FormData(e.target);
            const data = Object.fromEntries(fd);

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/api/suppliers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(data)
                });
                if (res.ok) {
                    closeAddModal();
                    showToast('Supplier added successfully!', 'success');
                    loadSuppliers();
                } else {
                    const errData = await res.json().catch(() => null);
                    showToast(errData?.message || 'Error adding supplier', 'error');
                }
            } catch (e) {
                showToast('Connection error', 'error');
            }
        }

        loadSuppliers();
    </script>
@endpush