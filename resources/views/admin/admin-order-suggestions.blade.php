@extends('layouts.app')

@section('title', 'Order Suggestions - Smart-Stock')

@section('content')
    <div class="page-header hero">
        <div class="hero-content">
            <h1 class="page-title">Order Suggestions</h1>
            <p class="page-subtitle">Automated demand forecasting</p>
        </div>
    </div>

    <!-- ====== TOOLBAR ====== -->
    <div class="section-card">
        <div class="section-card-header">
            <h2 class="section-card-title">Filter Options</h2>
            <p class="section-card-desc">Adjust view settings and recompute forecasts</p>
        </div>
        <div class="filter-bar">
            <div class="filter-group">
                <label for="fStatus">Show</label>
                <select id="fStatus">
                    <option value="active">Needs action</option>
                    <option value="ordered">Ordered</option>
                    <option value="dismissed">Dismissed</option>
                    <option value="all">All</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn-primary" onclick="loadSuggestions()">Refresh</button>
                <button class="btn-ghost" onclick="recompute()">Recompute now</button>
                <button class="btn-ghost" onclick="exportCsv()">Export CSV</button>
            </div>
        </div>
    </div>

    <!-- ====== SUGGESTION LIST ====== -->
    <div class="section-card">
        <div class="section-card-header">
            <div>
                <h2 class="section-card-title">Order Suggestions</h2>
                <p class="section-card-desc">Automated demand forecasting and reorder recommendations</p>
            </div>
        </div>
        <div class="table-wrapper">
                <table class="data-table sug-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>SKU</th>
                            <th class="num">In Stock</th>
                            <th class="num">Avg Daily</th>
                            <th class="num">Reorder Pt</th>
                            <th class="num">Suggested</th>
                            <th class="num">Est. Cost</th>
                            <th>Urgency</th>
                            <th class="num">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sugTableBody">
                        </tbody>
                </table>
        </div>
    </div>

    <style>
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
        .section-card-header {
            padding: 18px 20px 16px 20px;
        }
        .section-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #e2e8f0;
            margin: 0 0 3px 0;
        }
        body.light-theme .section-card-title { color: #1e293b; }
        .section-card-desc {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }
        body.light-theme .section-card-desc { color: #94a3b8; }
        .section-card .filter-bar {
            margin: 14px 16px 16px 16px;
        }
        .section-card .table-wrapper {
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            margin: 0 16px 16px 16px;
            width: calc(100% - 32px);
            background: rgba(255,255,255,0.02);
        }
        body.light-theme .section-card .table-wrapper {
            border-color: rgba(15,23,42,0.1);
            background: #ffffff;
        }
        .sug-name { font-weight: 600; color: #f8fafc; margin-bottom: 3px; }
        /* The shared .data-table sets min-width:860px, which is wider than the
           content area beside the 240px sidebar on a ~1100px viewport — that
           forced a horizontal scroll and pushed the nowrap CRITICAL badge over
           the Actions column. Reset to 0 so this table fits its container and
           the explicit percentages below govern. */
        .sug-table { table-layout: fixed; min-width: 0; }
        .sug-table th:nth-child(1), .sug-table td:nth-child(1) { width: 20%; }
        .sug-table th:nth-child(2), .sug-table td:nth-child(2) { width: 11%; }
        .sug-table th:nth-child(3), .sug-table td:nth-child(3) { width: 7%; }
        .sug-table th:nth-child(4), .sug-table td:nth-child(4) { width: 8%; }
        .sug-table th:nth-child(5), .sug-table td:nth-child(5) { width: 9%; }
        .sug-table th:nth-child(6), .sug-table td:nth-child(6) { width: 9%; }
        .sug-table th:nth-child(7), .sug-table td:nth-child(7) { width: 10%; }
        /* Urgency holds a nowrap pill, so it must not be squeezed. */
        .sug-table th:nth-child(8), .sug-table td:nth-child(8) { width: 10%; }
        /* Actions must fit two buttons (~145px) inside the cell's content
           box. Too narrow and the flex row overflows toward the LEFT,
           drawing the buttons over the Urgency column. */
        .sug-table th:nth-child(9), .sug-table td:nth-child(9) { width: 16%; }
        .sug-table thead th { letter-spacing: 0.2px; }
        .sug-table tbody td { padding: 12px 8px; }
        .section-card .table-wrapper .sug-table thead th { padding: 14px 8px; }
        .section-card .table-wrapper .sug-table { border-collapse: separate; border-spacing: 0 4px; border: none; }
        .section-card .table-wrapper .sug-table th,
        .section-card .table-wrapper .sug-table td { border: none; }
        .section-card .table-wrapper .sug-table tbody tr,
        .section-card .table-wrapper .sug-table tbody tr:hover {
            background: rgba(255,255,255,0.045);
            border: none;
            border-radius: 8px;
            transition: none;
        }
        body.light-theme .section-card .table-wrapper .sug-table tbody tr,
        body.light-theme .section-card .table-wrapper .sug-table tbody tr:hover { background: rgba(15,23,42,0.04); }
        /* The generic `.sug-table tbody td` above is (0,1,2) and outranks the
           shared `.empty-state { padding: 48px }` (0,1,0), which squashed the
           loading/empty row to 40px while Products/Transactions render 112.8px.
           Scope the placeholder back so every tab's loading row matches. */
        .section-card .table-wrapper .sug-table tbody td.empty-state { padding: 48px; font-size: 14px; }

        .sug-name { font-weight: 600; color: #f8fafc; margin-bottom: 3px; }
        body.light-theme .sug-name { color: #0f172a; }
        .urgency-badge { display:inline-block; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.03em; white-space:nowrap; }
        .urgency-badge.critical { background:rgba(239,68,68,.16); color:#f87171; border:1px solid rgba(239,68,68,.4); }
        .urgency-badge.low      { background:rgba(245,158,11,.15); color:#fbbf24; border:1px solid rgba(245,158,11,.38); }
        .urgency-badge.watch    { background:rgba(148,163,184,.14); color:#94a3b8; border:1px solid rgba(148,163,184,.3); }
        .reason-cell {
            font-size: 11.5px; color: #94a3b8; line-height: 1.45;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; cursor: help;
        }
        .days-left { display: none; }
        .btn-mini { padding:5px 11px; font-size:12px; border-radius:7px; border:1px solid rgba(148,163,184,.3);
            background:transparent; color:inherit; cursor:pointer; white-space:nowrap; }
        /* Two buttons per row keeps each column narrow enough to fit.
           Wrapped in a div — making the <td> itself a flex container
           takes it out of the table's column-width algorithm. */
        /* Wrapped in a div — making the <td> itself a flex container takes it
           out of the table's column-width algorithm. flex-wrap is a safety
           net: on a narrow viewport the buttons stack inside their own cell
           rather than overflowing left across Urgency. */
        .action-row { display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap; }
        .btn-mini:hover { background:rgba(148,163,184,.12); }
        .btn-mini.ok   { border-color:rgba(16,185,129,.4); color:#34d399; }
        .btn-mini.ok:hover { background:rgba(16,185,129,.12); }
        .btn-mini + .btn-mini { margin-left:6px; }
        .page-header.hero {
            --hero-card-bg: #13294f;
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
    </style>
@endsection

@push('scripts')
<script>
    let STATUS_FILTER = 'active';

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function peso(n) {
        return n === null || n === undefined ? '—' : '₱' + Number(n).toFixed(2);
    }

    function toast(message, type = 'success') {
        if (typeof showToast === 'function') { showToast(message, type); return; }
        alert(message);
    }

    async function loadSuggestions() {
        STATUS_FILTER = document.getElementById('fStatus').value;
        const tbody = document.getElementById('sugTableBody');

        try {
            const res  = await fetch(`/api/dashboard/order-suggestions?status=${encodeURIComponent(STATUS_FILTER)}`,
                                     { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Unable to load suggestions');
            const data = await res.json();

            renderSuggestions(data.suggestions || []);
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="9" class="empty-state">Unable to load suggestions.</td></tr>';
        }
    }

    // Initial render - show loading message
    document.getElementById('sugTableBody').innerHTML = '<tr><td colspan="9" class="empty-state">Loading suggestions...</td></tr>';
    loadSuggestions();

    function renderSuggestions(rows) {
        const tbody = document.getElementById('sugTableBody');

        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="empty-state">No suggestions in this view.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(s => {
            const actionable = s.status === 'active';

            return `
            <tr>
                <td>
                    <div class="sug-name">${escapeHtml(s.name)}</div>
                    <div class="reason-cell">${escapeHtml(s.reason || '')}</div>
                </td>
                <td>${escapeHtml(s.sku)}</td>
                <td class="num">${s.current_stock}</td>
                <td class="num">${Number(s.avg_daily).toFixed(2)}</td>
                <td class="num">${Number(s.reorder_point).toFixed(1)}</td>
                <td class="num"><strong>${s.suggested_qty}</strong></td>
                <td class="num">${peso(s.est_cost)}</td>
                <td>
                    <span class="urgency-badge ${escapeHtml(s.urgency)}">${escapeHtml(s.urgency)}</span>
                </td>
                <td class="num">
                    ${actionable ? `
                        <div class="action-row">
                            <button class="btn-mini ok" onclick="act(${s.id}, 'ordered')" title="Mark this item as already ordered">Ordered</button>
                            <button class="btn-mini" onclick="act(${s.id}, 'dismiss')" title="Dismiss this suggestion">Dismiss</button>
                        </div>
                    ` : `<span class="reason-cell">${escapeHtml(s.status)}</span>`}
                </td>
            </tr>`;
        }).join('');
    }

    async function act(id, action) {
        const res = await fetch(`/api/dashboard/order-suggestions/${id}/${action}`, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json().catch(() => ({}));
        toast(data.message || 'Done', res.ok ? 'success' : 'error');
        loadSuggestions();
    }

    async function recompute() {
        toast('Recomputing forecasts…', 'success');
        const res = await fetch('/api/dashboard/order-suggestions/run', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json().catch(() => ({}));
        toast(data.message || 'Done', res.ok ? 'success' : 'error');
        loadSuggestions();
    }

    function exportCsv() {
        window.location.href = `/api/dashboard/order-suggestions/export?status=${encodeURIComponent(STATUS_FILTER)}`;
    }

    document.getElementById('fStatus').addEventListener('change', loadSuggestions);
    loadSuggestions();
</script>
@endpush
