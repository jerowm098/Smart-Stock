@extends('layouts.app')

@section('title', 'Order Suggestions - Smart-Stock')

@section('content')
    <h1 class="page-title">Order Suggestions</h1>
    <p class="page-subtitle">
        Automated demand forecasting. Figures come from the nightly
        <code>forecast:orders</code> job &mdash; 30-day sales velocity, 7-day reorder
        point, and a suggested top-up to restore 30 days of supply.
    </p>

    <!-- ====== TOOLBAR ====== -->
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

    <div id="formulaNote" class="formula-note"></div>

    <!-- ====== SUGGESTION LIST ====== -->
    <div class="table-wrap">
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
                <tr>
                    <td colspan="9" class="empty-state">
                        <span class="spinner" aria-hidden="true"></span> Loading suggestions...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <style>
        .formula-note { margin: 0 0 16px; padding: 10px 14px; border-radius: 10px;
            background: rgba(59,130,246,.08); border: 1px solid rgba(59,130,246,.22);
            color: #93c5fd; font-size: 12.5px; }
        body.light-theme .formula-note { background: rgba(59,130,246,.06); color: #1d4ed8; }
        .formula-note code { background: rgba(0,0,0,.25); padding: 1px 6px; border-radius: 5px; }

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
        .sug-table tbody td, .sug-table thead th { padding: 12px 8px; }

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
        .days-left { font-size: 11.5px; color: #94a3b8; margin-top: 4px; white-space: nowrap; }
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

    function renderFormulas(f) {
        const note = document.getElementById('formulaNote');
        if (!f || !note) return;
        note.innerHTML =
            `<strong>Forecast rules:</strong> ` +
            `daily velocity = units sold / ${f.window_days} days &nbsp;•&nbsp; ` +
            `reorder point = velocity &times; ${f.safety_days} days &nbsp;•&nbsp; ` +
            `suggested qty = (velocity &times; ${f.cover_days} days) &minus; current stock &nbsp;•&nbsp; ` +
            `items with &lt; 7 days of history fall back to a static ${f.fallback_threshold}-unit threshold.`;
    }

    async function loadSuggestions() {
        STATUS_FILTER = document.getElementById('fStatus').value;
        const tbody = document.getElementById('sugTableBody');

        try {
            const res  = await fetch(`/api/dashboard/order-suggestions?status=${encodeURIComponent(STATUS_FILTER)}`,
                                     { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Unable to load suggestions');
            const data = await res.json();

            renderFormulas(data.formulas);
            renderSuggestions(data.suggestions || []);
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="9" class="empty-state">Unable to load suggestions.</td></tr>';
        }
    }

    function renderSuggestions(rows) {
        const tbody = document.getElementById('sugTableBody');

        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="empty-state">No suggestions in this view.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(s => {
            const actionable = s.status === 'active';
            const daysLeft   = s.days_until_out !== null ? `${s.days_until_out} days left` : '—';

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
                    <div class="days-left">${escapeHtml(daysLeft)}</div>
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
