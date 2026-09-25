@extends('layouts.app')

@section('title', 'Suppliers - Smart-Stock')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Suppliers</h1>
        <p class="page-subtitle">Manage your supplier directory and add new contacts</p>
    </div>

    <div class="section-card">
        <div class="section-card-header">
            <h2 class="section-card-title">Search & Filter</h2>
            <p class="section-card-desc">Find and narrow down suppliers using the controls below</p>
        </div>
        <div class="filter-bar">
            <div class="filter-bar-inner">
                <div class="search-bar">
                    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" class="search-input-full" id="searchInput" placeholder="Search suppliers by name, contact, phone, or email..." oninput="filterSuppliers()" autocomplete="off">
                </div>
                <div class="filter-bar-divider"></div>
                <div class="filter-group">
                    <label class="filter-label">Search In</label>
                    <select class="search-column-select" id="searchColumn" onchange="filterSuppliers()">
                        <option value="">All Columns</option>
                        <option value="name">Supplier Name</option>
                        <option value="contact_person">Contact Person</option>
                        <option value="phone">Phone</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Filter By</label>
                    <select class="filter-select" id="statusFilter" onchange="filterSuppliers()">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select class="sort-select" id="sortSelect" onchange="filterSuppliers()">
                        <option value="">Default</option>
                        <option value="name-asc">Name (A → Z)</option>
                        <option value="name-desc">Name (Z → A)</option>
                        <option value="date-desc">Date Added (Newest)</option>
                        <option value="date-asc">Date Added (Oldest)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-card-header">
            <div class="section-card-header-row">
                <div>
                    <h2 class="section-card-title">Supplier Directory</h2>
                    <p class="section-card-desc">Overview of all registered suppliers and their contact details</p>
                </div>
                <button class="btn-add" onclick="openAddModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Add Supplier</span>
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
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="supplierTableBody">
                    <tr id="supplierLoadingRow"><td colspan="6" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading suppliers...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="grid-view hidden" id="gridView">
            <div id="supplierGridBody" class="product-grid">
                <div class="empty-state" style="grid-column: 1/-1;"><span class="spinner" aria-hidden="true"></span> Loading suppliers...</div>
            </div>
        </div>
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
        .search-column-select, .filter-select, .sort-select {
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
        .search-column-select:focus, .filter-select:focus, .sort-select:focus { border-color: #3b82f6; }
        .search-column-select option, .filter-select option, .sort-select option { background: #1e293b; color: #e2e8f0; }
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
        table { width: 100%; min-width: 600px; border-collapse: collapse; }
        thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }
        tbody td { padding: 12px 16px; font-size: 13px; color: #cbd5e1; }
        .status-text { font-size: 13px; font-weight: 500; }
        .status-text.stock-ok { color: #4ade80; }
        .empty-state { text-align: center; color: #475569; padding: 48px; font-size: 14px; }
        .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.2); border-top-color: #3b82f6; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .hidden { display: none !important; }

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
        .supplier-card-icon {
            width: 100%; height: 140px; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, rgba(59,130,246,0.08), rgba(59,130,246,0.02));
        }
        body.light-theme .supplier-card-icon { background: linear-gradient(135deg, rgba(59,130,246,0.06), rgba(59,130,246,0.01)); }
        .supplier-card-icon svg { width: 48px; height: 48px; color: #3b82f6; opacity: 0.6; }
        body.light-theme .supplier-card-icon svg { opacity: 0.5; }
        .product-card-body { padding: 14px; }
        .product-card-name { font-size: 14px; font-weight: 600; color: #e2e8f0; margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        body.light-theme .product-card-name { color: #1e293b; }
        .product-card-sku { font-size: 12px; color: #64748b; margin: 0 0 10px 0; }
        .supplier-card-contact { display: flex; flex-direction: column; gap: 4px; margin-bottom: 10px; }
        .supplier-card-contact span { font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px; }
        body.light-theme .supplier-card-contact span { color: #64748b; }
        .supplier-card-contact svg { width: 12px; height: 12px; flex-shrink: 0; }
        .product-card-meta { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .product-card-stock { font-size: 12px; font-weight: 600; padding: 3px 8px; border-radius: 6px; }
        .product-card-stock.stock-ok { background: rgba(74,222,128,0.12); color: #4ade80; }
        body.light-theme .product-card-stock.stock-ok { background: rgba(22,163,74,0.1); color: #16a34a; }
        .product-card-actions { display: flex; gap: 6px; margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.06); }
        body.light-theme .product-card-actions { border-top-color: rgba(15,23,42,0.06); }
        .product-card-actions .action-link { font-size: 12px; padding: 4px 8px; }
        .action-link { background: none; border: 1px solid transparent; padding: 4px 10px; cursor: pointer; font-size: 13px; font-family: 'Inter', sans-serif; color: #60a5fa; text-decoration: none; transition: all 0.15s; margin-right: 10px; display: inline-flex; align-items: center; gap: 5px; border-radius: 4px; }
        .action-link:last-child { margin-right: 0; }
        .action-link:hover { border-color: rgba(96,165,250,0.4); background: rgba(96,165,250,0.08); color: #93c5fd; }
        .action-link svg { width: 13px; height: 13px; flex-shrink: 0; }
        .action-link.danger { color: #f87171; }
        .action-link.danger:hover { border-color: rgba(248,113,113,0.4); background: rgba(248,113,113,0.08); color: #fca5a5; }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 200; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 28px; width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
        .modal h2 { color: #f8fafc; font-size: 18px; font-weight: 700; margin-bottom: 20px; }
        .modal .form-group { margin-bottom: 14px; }
        .modal .form-group label { display: block; color: #cbd5e1; font-size: 13px; font-weight: 500; margin-bottom: 5px; }
        .modal .form-group input { width: 100%; padding: 9px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; }
        .modal .form-group input:focus { border-color: #3b82f6; }
        .modal .form-group input::placeholder { color: #64748b; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
        .modal-actions .btn { padding: 9px 18px; border: none; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; transition: opacity 0.15s; }
        .modal-actions .btn:hover { opacity: 0.9; }
        .btn-cancel { background: rgba(255,255,255,0.1); color: #e2e8f0; }
        .btn-submit { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; }

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
        body.light-theme .filter-select {
            background: #ffffff;
            border-color: rgba(15,23,42,0.14);
            color: #0f172a;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
        }
        body.light-theme .filter-select option { background: #ffffff; color: #0f172a; }
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
        body.light-theme .action-link { color: #1e293b; }
        body.light-theme .action-link:hover { border-color: rgba(37,99,235,0.3); background: rgba(37,99,235,0.06); color: #1d4ed8; }
        body.light-theme .action-link.danger { color: #dc2626; }
        body.light-theme .action-link.danger:hover { border-color: rgba(220,38,38,0.3); background: rgba(220,38,38,0.06); color: #b91c1c; }

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
        let currentView = 'table';

        function switchView(view) {
            currentView = view;
            document.getElementById('tableViewBtn').classList.toggle('active', view === 'table');
            document.getElementById('gridViewBtn').classList.toggle('active', view === 'grid');
            document.getElementById('tableView').classList.toggle('hidden', view !== 'table');
            document.getElementById('gridView').classList.toggle('hidden', view !== 'grid');
        }

        function showLoadingSpinner() {
            document.getElementById('supplierTableBody').innerHTML = '<tr id="supplierLoadingRow"><td colspan="6" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading suppliers...</td></tr>';
            document.getElementById('supplierGridBody').innerHTML = '<div class="empty-state" style="grid-column: 1/-1;"><span class="spinner" aria-hidden="true"></span> Loading suppliers...</div>';
        }

        function showTableError(message) {
            document.getElementById('supplierTableBody').innerHTML = `<tr><td colspan="6" class="empty-state">${escapeHtml(message)}</td></tr>`;
            document.getElementById('supplierGridBody').innerHTML = `<div class="empty-state" style="grid-column: 1/-1;">${escapeHtml(message)}</div>`;
        }

        async function loadSuppliers() {
            showLoadingSpinner();
            try {
                const res = await fetch('/api/suppliers/active');
                if (!res.ok) throw new Error('Unable to load suppliers');
                allSuppliers = await res.json();
                filterSuppliers();
            } catch (e) {
                showTableError('Unable to load suppliers. Please try again.');
                showToast('Failed to load suppliers', 'error');
            }
        }

        function filterSuppliers() {
            const t = document.getElementById('searchInput').value.toLowerCase();
            const col = document.getElementById('searchColumn').value;
            const status = document.getElementById('statusFilter').value;

            const f = allSuppliers.filter(s => {
                if (status && (s.status || 'Active') !== status) return false;
                if (!t) return true;
                switch(col) {
                    case 'name': return s.name && s.name.toLowerCase().includes(t);
                    case 'contact_person': return s.contact_person && s.contact_person.toLowerCase().includes(t);
                    case 'phone': return s.phone && s.phone.toLowerCase().includes(t);
                    case 'email': return s.email && s.email.toLowerCase().includes(t);
                    default:
                        return (s.name && s.name.toLowerCase().includes(t)) ||
                               (s.contact_person && s.contact_person.toLowerCase().includes(t)) ||
                               (s.phone && s.phone.toLowerCase().includes(t)) ||
                               (s.email && s.email.toLowerCase().includes(t));
                }
            });
            const sorted = sortSuppliers(f);
            renderSupplierTable(sorted);
            renderSupplierGrid(sorted);
        }

        function sortSuppliers(suppliers) {
            const sortVal = document.getElementById('sortSelect').value;
            if (!sortVal) return suppliers;
            const sorted = [...suppliers];
            const [key, dir] = sortVal.split('-');
            const asc = dir === 'asc';
            sorted.sort((a, b) => {
                let va, vb;
                switch (key) {
                    case 'name':
                        va = (a.name || '').toLowerCase();
                        vb = (b.name || '').toLowerCase();
                        return asc ? va.localeCompare(vb) : vb.localeCompare(va);
                    case 'date':
                        va = new Date(a.created_at || 0).getTime();
                        vb = new Date(b.created_at || 0).getTime();
                        return asc ? va - vb : vb - va;
                    default: return 0;
                }
            });
            return sorted;
        }

        function renderSupplierTable(suppliers) {
            const body = document.getElementById('supplierTableBody');
            if (suppliers.length === 0) {
                body.innerHTML = '<tr><td colspan="6" class="empty-state">No suppliers found</td></tr>';
            } else {
                body.innerHTML = suppliers.map(s => `
                    <tr id="supplier-row-${s.id}">
                        <td><strong>${escapeHtml(s.name)}</strong></td>
                        <td>${escapeHtml(s.contact_person || '—')}</td>
                        <td>${escapeHtml(s.phone || '—')}</td>
                        <td>${escapeHtml(s.email || '—')}</td>
                        <td><span class="status-text stock-ok">Active</span></td>
                        <td>
                            <button class="action-link danger" onclick="deleteSupplier(${s.id}, '${escapeHtml(s.name).replace(/'/g, "\\'")}')" title="Delete supplier">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                Delete
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
        }

        function renderSupplierGrid(suppliers) {
            const grid = document.getElementById('supplierGridBody');
            if (suppliers.length === 0) {
                grid.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;">No suppliers found</div>';
            } else {
                grid.innerHTML = suppliers.map(s => `
                    <div class="product-card" id="card-${s.id}">
                        <div class="supplier-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <div class="product-card-body">
                            <h3 class="product-card-name" title="${escapeHtml(s.name)}">${escapeHtml(s.name)}</h3>
                            <p class="product-card-sku">${escapeHtml(s.contact_person || 'No contact person')}</p>
                            <div class="supplier-card-contact">
                                ${s.phone ? `<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>${escapeHtml(s.phone)}</span>` : ''}
                                ${s.email ? `<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>${escapeHtml(s.email)}</span>` : ''}
                            </div>
                            <div class="product-card-meta">
                                <span class="product-card-stock stock-ok">Active</span>
                            </div>
                            <div class="product-card-actions">
                                <button class="action-link danger" onclick="deleteSupplier(${s.id}, '${escapeHtml(s.name).replace(/'/g, "\\'")}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        }

        async function deleteSupplier(id, name) {
            if (!confirm(`Are you sure you want to delete "${name}"?`)) return;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch(`/api/suppliers/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                });
                if (res.ok) {
                    showToast('Supplier deleted successfully!', 'success');
                    loadSuppliers();
                } else {
                    const errData = await res.json().catch(() => null);
                    showToast(errData?.message || 'Error deleting supplier', 'error');
                }
            } catch (e) {
                showToast('Connection error', 'error');
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
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
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

        function escapeHtml(text) {
            const d = document.createElement('div');
            d.textContent = text || '';
            return d.innerHTML;
        }

        loadSuppliers();
    </script>
@endpush
