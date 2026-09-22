@extends('layouts.app')

@section('title', 'Overview - Smart-Stock')

@section('content')
    <h1 class="page-title">Overview</h1>
    <p class="page-subtitle">Welcome back, {{ Auth::user()->name }}. Here is an overview of your inventory system.</p>

    <!-- STATS GRID -->
    <div class="stats-grid">
        <div class="stat-card"><h3>Total Products</h3><div class="stat-value blue" id="totalProducts">0</div></div>
        <div class="stat-card"><h3>In Stock</h3><div class="stat-value green" id="inStock">0</div></div>
        <div class="stat-card"><h3>Low Stock</h3><div class="stat-value yellow" id="lowStock">0</div></div>
        <div class="stat-card"><h3>Critical</h3><div class="stat-value red" id="criticalStock">0</div></div>
    </div>

    <!-- QUICK SHORTCUTS -->
    <div class="quick-links-grid">
        <a href="{{ route('products') }}" class="quick-link-card">
            <div class="quick-link-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            </div>
            <div>
                <h4>Manage Products</h4>
                <p>Search, add, and manage your full catalog of items</p>
            </div>
        </a>
    </div>

    <!-- RECENT INVENTORY TABLE -->
    <div class="section-header">
        <h2 class="section-title">Recent Inventory List</h2>
        <a href="{{ route('products') }}" class="section-link">View All Products →</a>
    </div>
    <div class="table-wrapper" id="overviewTable">
        <table>
            <thead>
                <tr>
                    <th>SKU / Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Unit Price</th>
                    <th>Current Stock Count</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="overviewBody">
                <tr id="overviewLoadingRow"><td colspan="6" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading dashboard data...</td></tr>
            </tbody>
        </table>
    </div>
@endsection

@push('styles')
    <style>
        .page-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 6px; }
        .page-subtitle { color: #64748b; font-size: 14px; margin-bottom: 24px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 20px; transition: transform 0.15s, border-color 0.15s; }
        .stat-card:hover { border-color: rgba(255,255,255,0.12); }
        .stat-card h3 { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: 600; }
        .stat-value { font-size: 28px; font-weight: 700; color: #f8fafc; }
        .stat-value.blue { color: #60a5fa; } .stat-value.green { color: #4ade80; } .stat-value.yellow { color: #fbbf24; } .stat-value.red { color: #f87171; }
        .quick-links-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 360px)); gap: 16px; margin-bottom: 28px; }
        .quick-link-card { max-width: 360px; }
        .quick-link-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 20px; text-decoration: none; display: flex; align-items: center; gap: 16px; transition: background 0.15s, border-color 0.15s, color 0.15s; will-change: unset; }
        .quick-link-card:hover { border-color: rgba(96,165,250,0.3); background: rgba(96,165,250,0.05); }
        .quick-link-icon { width: 44px; height: 44px; border-radius: 10px; background: rgba(96,165,250,0.15); display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .quick-link-card h4 { color: #f8fafc; font-size: 15px; font-weight: 600; margin-bottom: 4px; }
        .quick-link-card p { color: #94a3b8; font-size: 12px; line-height: 1.4; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .section-title { font-size: 18px; font-weight: 600; color: #f8fafc; }
        .section-link { font-size: 13px; color: #60a5fa; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; }
        .section-link:hover { text-decoration: underline; }
        .table-wrapper { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; overflow-x: auto; }
        table { width: 100%; min-width: 680px; border-collapse: collapse; }
        thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }
        tbody td { padding: 12px 16px; font-size: 13px; color: #cbd5e1; }
        .stock-cell { font-weight: 600; } .stock-ok { color: #4ade80; } .stock-low { color: #fbbf24; } .stock-critical { color: #f87171; }
        .stock-badge { display: inline-block; padding: 3px 8px; border-radius: 5px; font-size: 11px; font-weight: 600; }
        .stock-badge.ok { background: rgba(74,222,128,0.12); color: #4ade80; } .stock-badge.low { background: rgba(251,191,36,0.12); color: #fbbf24; } .stock-badge.critical { background: rgba(248,113,113,0.12); color: #f87171; }
        .empty-state { text-align: center; color: #475569; padding: 48px; font-size: 14px; }
        body.light-theme .page-title { color: #0f172a; }
        body.light-theme .page-subtitle { color: #64748b; }
        body.light-theme .stat-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme .stat-card h3 { color: #64748b; }
        body.light-theme .stat-value { color: #0f172a; }
        body.light-theme .stat-value.blue { color: #2563eb; }
        body.light-theme .stat-value.green { color: #16a34a; }
        body.light-theme .stat-value.yellow { color: #d97706; }
        body.light-theme .stat-value.red { color: #dc2626; }
        body.light-theme .section-title { color: #0f172a; }
        body.light-theme .table-wrapper { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme table thead th { background: #f8fafc; color: #64748b; }
        body.light-theme table tbody td { color: #475569; }
        body.light-theme table tbody tr:hover { background: rgba(15,23,42,0.025); }
        body.light-theme .empty-state { color: #94a3b8; }
        body.light-theme .quick-link-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme .quick-link-card:hover { border-color: rgba(37,99,235,0.3); background: rgba(37,99,235,0.02); }
        body.light-theme .quick-link-card h4 { color: #0f172a; }
        body.light-theme .quick-link-card p { color: #64748b; }
        /* MOBILE */
        @media (max-width: 640px) {
            .page-title { font-size: 20px; }
            .page-subtitle { font-size: 13px; margin-bottom: 20px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 20px; }
            .stat-card { padding: 16px 12px; }
            .stat-card h3 { font-size: 11px; }
            .stat-value { font-size: 22px; }
            .quick-links-grid { grid-template-columns: 1fr; gap: 12px; margin-bottom: 20px; }
            .quick-link-card { padding: 16px; }
            .section-header { flex-wrap: wrap; gap: 8px; }
            .section-title { font-size: 16px; }
            .table-wrapper { border-radius: 10px; }
            thead th, tbody td { padding: 10px 10px; font-size: 12px; }
            .empty-state { padding: 32px 16px; font-size: 13px; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        function showLoadingSpinner() {
            document.getElementById('overviewBody').innerHTML = '<tr id="overviewLoadingRow"><td colspan="6" class="empty-state"><span class="spinner" aria-hidden="true"></span> Loading dashboard data...</td></tr>';
        }

        function showTableError(message) {
            document.getElementById('overviewBody').innerHTML = `<tr><td colspan="6" class="empty-state">${escapeHtml(message)}</td></tr>`;
        }

        async function loadOverview() {
            showLoadingSpinner();
            try {
                const res = await fetch('/api/inventory/products');
                if (!res.ok) throw new Error('Unable to load inventory');
                const products = await res.json();
                renderOverview(products);
            } catch (e) {
                showTableError('Unable to load inventory. Please try again.');
                showToast('Failed to load', 'error');
            }
        }

        function renderOverview(products) {
            document.getElementById('totalProducts').textContent = products.length;
            document.getElementById('inStock').textContent = products.filter(p => p.current_stock > p.reorder_threshold * 1.5).length;
            document.getElementById('lowStock').textContent = products.filter(p => p.current_stock <= p.reorder_threshold * 1.5 && p.current_stock > p.reorder_threshold).length;
            document.getElementById('criticalStock').textContent = products.filter(p => p.current_stock <= p.reorder_threshold).length;

            const body = document.getElementById('overviewBody');
            const top = products.slice(0, 8);
            if (top.length === 0) {
                body.innerHTML = '<tr><td colspan="6" class="empty-state">No products yet</td></tr>';
            } else {
                body.innerHTML = top.map(p => {
                    const s = getStatus(p.current_stock, p.reorder_threshold);
                    return `<tr>
                        <td>${escapeHtml(p.sku)}</td>
                        <td><strong>${escapeHtml(p.name)}</strong></td>
                        <td>${escapeHtml(p.category || '—')}</td>
                        <td>₱${parseFloat(p.price).toFixed(2)}</td>
                        <td class="stock-cell stock-${s.class}">${p.current_stock}</td>
                        <td><span class="stock-badge ${s.class}">${s.label}</span></td>
                    </tr>`;
                }).join('');
            }
        }

        function getStatus(stock, threshold) {
            if (stock <= threshold) return { label: 'Critical', class: 'critical' };
            if (stock <= threshold * 1.5) return { label: 'Low', class: 'low' };
            return { label: 'OK', class: 'ok' };
        }

        loadOverview();
    </script>
@endpush
