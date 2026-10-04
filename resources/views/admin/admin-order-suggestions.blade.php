@extends('layouts.dashboard-main-frame')

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
            <p class="section-card-desc">Search, narrow, and update forecasts</p>
        </div>
        <div class="filter-bar">
            <div class="filter-group">
                <label for="fSearch">Search</label>
                <input type="text" id="fSearch" placeholder="Item name, SKU, category..." autocomplete="off">
            </div>
            <div class="filter-group">
                <label for="fStatus">Show</label>
                <select id="fStatus">
                    <option value="active">Default</option>
                    <option value="ordered">Ordered</option>
                    <option value="dismissed">Dismissed</option>
                    <option value="all">All</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="fUrgency">Urgency</label>
                <select id="fUrgency">
                    <option value="">All urgency</option>
                    <option value="critical">Critical</option>
                    <option value="low">Low</option>
                    <option value="watch">Watch</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="fCategory">Category</label>
                <select id="fCategory">
                    <option value="">All categories</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="fPerPage">Rows</label>
                <select id="fPerPage">
                    <option value="10">10</option>
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn-primary" onclick="recompute()" title="Recalculate demand from the last 30 days of sales and update the suggestions below">Update Forecast</button>
                <button class="btn-ghost" onclick="resetFilters()">Reset</button>
            </div>
        </div>
    </div>

    <!-- ====== SUGGESTION LIST ====== -->
    <div class="section-card">
        <div class="section-card-header section-card-header--with-meta">
            <div>
                <h2 class="section-card-title">Order Suggestions</h2>
                <p class="section-card-desc">Automated demand forecasting and reorder recommendations</p>
            </div>
            <div class="section-card-actions">
                <span class="result-meta" id="resultMeta">Loading...</span>
                <button class="btn-export" onclick="exportCsv()" title="Download filtered suggestions as CSV (Excel-compatible)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>Export CSV</button>
            </div>
        </div>
        <div class="table-wrapper is-loading">
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
        <div class="pager" id="sugPager" style="display: none;"></div>
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
        .section-card-header--with-meta {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
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
            overflow-x: auto;
        }
        body.light-theme .section-card .table-wrapper {
            border-color: rgba(15,23,42,0.1);
            background: #ffffff;
        }
        .sug-name { font-weight: 600; color: #f8fafc; margin-bottom: 3px; }
        /* The shared .data-table sets min-width:860px, which is wider than the
           content area beside the 240px sidebar on a ~1100px viewport — that
           forces a horizontal scroll so the nowrap CRITICAL badge and the
           two-button Actions row keep their full width instead of being
           squeezed. The percentages below still govern the column split.
           While loading there is only one full-width placeholder cell, which
           never needs that width, so collapse the table and hide the
           scrollbar until real rows arrive. */
        .sug-table { table-layout: fixed; min-width: 860px; }
        .section-card .table-wrapper.is-loading { overflow-x: hidden; }
        .section-card .table-wrapper.is-loading .sug-table { min-width: 0; }
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
        /* The shared `.data-table thead th` is `white-space: nowrap`, so the
           three-word headers ("Avg Daily", "Reorder Pt", "Est. Cost") could
           never wrap and, under `table-layout: fixed`, spilled over the
           neighbouring columns. Let headers wrap onto a second line; the
           urgency pill in the body keeps its own nowrap. */
        .sug-table thead th {
            letter-spacing: 0.2px;
            white-space: normal;
            overflow-wrap: break-word;
            line-height: 1.3;
        }
        /* The shared header cells carry vertical padding sized for a single
           nowrap line. With wrapping on, that padding dominates and the band
           grows to ~57px with a large gap under the text. Trim it so a
           two-line header reads as two lines, not a tall empty strip. */
        .section-card .table-wrapper .sug-table thead th { padding: 10px 8px; }
        /* Numeric headers are right-aligned; wrap them on the word boundary
           so "In Stock" breaks as "In / Stock" and stays flush right. */
        .sug-table thead th.num { text-align: right; }
        .sug-table tbody td { padding: 12px 8px; }
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
        .urgency-badge.critical { background:transparent; color:#f87171; border:none; }
        .urgency-badge.low      { background:transparent; color:#fbbf24; border:none; }
        .urgency-badge.watch    { background:transparent; color:#94a3b8; border:none; }
        .reason-cell {
            font-size: 11.5px; color: #94a3b8; line-height: 1.45;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; cursor: help;
        }
        .days-left { display: none; }
        body.light-theme .urgency-badge.critical { color:#b91c1c; }
        body.light-theme .urgency-badge.low      { color:#b45309; }
        body.light-theme .urgency-badge.watch    { color:#475569; }
        /* Primary action, not a row action: solid fill matching the transactions
           Export CSV button and the cashier "Complete Sale" button. */
        .btn-export {
            background: #2563eb; color: #fff; border: none;
            border-radius: 8px; padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
            white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;
            font-family: 'Inter', sans-serif; transition: filter 0.1s ease, transform 0.1s ease;
        }
        .btn-export svg { width: 14px; height: 14px; flex-shrink: 0; }
        .btn-export:hover { background: #1d4ed8; }
        .btn-export:active { filter: brightness(0.85); transform: scale(0.97); }
        .btn-export:disabled { opacity: 0.45; cursor: not-allowed; transform: none; filter: none; }
        /* The shared .btn-primary hovers with a brightness filter; this page's
           primary buttons hover with a darker fill. Match .btn-export exactly. */
        .filter-actions .btn-primary {
            background: #2563eb; color: #fff; border: none;
            border-radius: 8px; padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
            white-space: nowrap; font-family: 'Inter', sans-serif;
            transition: filter 0.1s ease, transform 0.1s ease;
        }
        .filter-actions .btn-primary:hover { background: #1d4ed8; filter: none; }
        .filter-actions .btn-primary:active { filter: brightness(0.85); transform: scale(0.97); }
        /* Reset is a secondary action: no fill at rest, border tracks the text
           color, hover shades that same color. */
        .filter-bar .filter-actions .btn-ghost {
            background: transparent; border: 1px solid currentColor; color: #cbd5e1;
            padding: 9px 16px; font-weight: 600; font-family: 'Inter', sans-serif;
            white-space: nowrap; transition: all 0.15s;
        }
        .filter-bar .filter-actions .btn-ghost:hover {
            border-color: currentColor; background: rgba(203,213,225,0.12); color: #e2e8f0;
        }
        body.light-theme .filter-bar .filter-actions .btn-ghost { color: #334155; }
        body.light-theme .filter-bar .filter-actions .btn-ghost:hover {
            border-color: currentColor; background: rgba(30,41,59,0.08); color: #0f172a;
        }
        .section-card-actions {
            display: flex; align-items: center; gap: 12px; flex-shrink: 0;
        }
        .result-meta { font-size: 12px; color: #64748b; white-space: nowrap; }
        .pager {
            display: flex; align-items: center; justify-content: center; gap: 14px;
            margin: 0 16px 16px 16px;
        }
        .pager-label { font-size: 13px; color: #94a3b8; }
        .pager .btn-ghost {
            background: transparent; border: 1px solid currentColor; color: #cbd5e1;
            padding: 7px 14px; font-weight: 600; font-family: 'Inter', sans-serif;
            transition: all 0.15s;
        }
        .pager .btn-ghost:hover:not(:disabled) {
            border-color: currentColor; background: rgba(203,213,225,0.12); color: #e2e8f0;
        }
        .pager .btn-ghost:disabled { opacity: 0.4; cursor: not-allowed; }
        body.light-theme .pager .btn-ghost { color: #334155; }
        body.light-theme .pager .btn-ghost:hover:not(:disabled) {
            border-color: currentColor; background: rgba(30,41,59,0.08); color: #0f172a;
        }
        body.light-theme .pager-label { color: #475569; }
        .btn-mini { padding:5px 11px; font-size:12px; border-radius:7px; border:1px solid currentColor;
            background:transparent; color:inherit; cursor:pointer; white-space:nowrap; transition:all .15s; }
        /* Two buttons per row keeps each column narrow enough to fit.
           Wrapped in a div — making the <td> itself a flex container
           takes it out of the table's column-width algorithm. */
        /* Wrapped in a div — making the <td> itself a flex container takes it
           out of the table's column-width algorithm. flex-wrap is a safety
           net: on a narrow viewport the buttons stack inside their own cell
           rather than overflowing left across Urgency. */
        .action-row { display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap; }
        .btn-mini { color:#f87171; }
        .btn-mini:hover { border-color:currentColor; background:color-mix(in srgb, currentColor 12%, transparent); }
        .btn-mini.ok   { color:#34d399; }
        .btn-mini.ok:hover { border-color:currentColor; }
        .btn-mini + .btn-mini { margin-left:6px; }
        body.light-theme .btn-mini { color:#dc2626; }
        body.light-theme .btn-mini.ok { color:#047857; }
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
    let ALL_ROWS = [];
    let PAGE = 1;

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
        const wrapper = tbody.closest('.table-wrapper');

        try {
            const res  = await fetch(`/api/dashboard/order-suggestions?status=${encodeURIComponent(STATUS_FILTER)}`,
                                     { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Unable to load suggestions');
            const data = await res.json();

            ALL_ROWS = data.suggestions || [];
            syncCategoryOptions(ALL_ROWS);
            renderSuggestions(applyFilters(ALL_ROWS));
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="9" class="empty-state">Unable to load suggestions.</td></tr>';
            setResultMeta(null);
        } finally {
            wrapper.classList.remove('is-loading');
        }
    }

    /* ---- Client-side filtering ----------------------------------------
       The API only accepts ?status=, but it returns the whole matching set in
       one response, so search / urgency / category / rows are narrowed here
       instead of adding query params the controller does not read. */

    function currentFilters() {
        return {
            search:  document.getElementById('fSearch').value.trim().toLowerCase(),
            urgency: document.getElementById('fUrgency').value,
            category:document.getElementById('fCategory').value,
            perPage: parseInt(document.getElementById('fPerPage').value, 10) || 15,
        };
    }

    function applyFilters(rows) {
        const f = currentFilters();
        return rows.filter(s => {
            if (f.urgency && s.urgency !== f.urgency) return false;
            if (f.category && s.category !== f.category) return false;
            if (f.search) {
                const haystack = `${s.name ?? ''} ${s.sku ?? ''} ${s.category ?? ''}`.toLowerCase();
                if (!haystack.includes(f.search)) return false;
            }
            return true;
        });
    }

    function syncCategoryOptions(rows) {
        const sel = document.getElementById('fCategory');
        const current = sel.value;
        const cats = [...new Set(rows.map(r => r.category).filter(Boolean))].sort();
        sel.innerHTML = '<option value="">All categories</option>'
            + cats.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
        // Keep the chosen category if it still exists after a status change.
        if (cats.includes(current)) sel.value = current;
    }

    function resetFilters() {
        document.getElementById('fSearch').value   = '';
        document.getElementById('fUrgency').value = '';
        document.getElementById('fCategory').value= '';
        document.getElementById('fPerPage').value = '15';
        document.getElementById('fStatus').value  = 'active';
        PAGE = 1;
        loadSuggestions();
    }

    function setResultMeta(shown, total) {
        const el = document.getElementById('resultMeta');
        if (!el) return;
        el.textContent = shown === null
            ? 'Unable to load'
            : `${shown} of ${total} suggestion${total === 1 ? '' : 's'}`;
    }

    // Initial render - show loading message
    document.getElementById('sugTableBody').innerHTML = '<tr><td colspan="9" class="empty-state">Loading suggestions...</td></tr>';

    function renderSuggestions(rows) {
        const tbody = document.getElementById('sugTableBody');

        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="empty-state">No suggestions match these filters.</td></tr>`;
            setResultMeta(0, 0);
            renderPager();
            return;
        }

        const perPage = currentFilters().perPage;
        const maxPage = Math.max(1, Math.ceil(rows.length / perPage));
        if (PAGE > maxPage) PAGE = maxPage;
        const start = (PAGE - 1) * perPage;
        const pageRows = rows.slice(start, start + perPage);

        setResultMeta(pageRows.length, rows.length);
        renderPager(maxPage);

        tbody.innerHTML = pageRows.map(s => {
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

    function renderPager(maxPage = 1) {
        const pager = document.getElementById('sugPager');
        if (!pager) return;
        if (maxPage <= 1) { pager.style.display = 'none'; return; }
        pager.style.display = 'flex';
        pager.innerHTML = `
            <button class="btn-ghost" id="sugPrev" onclick="goPage(-1)" ${PAGE === 1 ? 'disabled' : ''}>&larr; Prev</button>
            <span class="pager-label">Page ${PAGE} of ${maxPage}</span>
            <button class="btn-ghost" id="sugNext" onclick="goPage(1)" ${PAGE === maxPage ? 'disabled' : ''}>Next &rarr;</button>`;
    }

    function goPage(delta) {
        const maxPage = Math.max(1, Math.ceil(applyFilters(ALL_ROWS).length / currentFilters().perPage));
        const next = PAGE + delta;
        if (next < 1 || next > maxPage) return;
        PAGE = next;
        renderSuggestions(applyFilters(ALL_ROWS));
    }

    function refreshFromFilters() {
        PAGE = 1;
        renderSuggestions(applyFilters(ALL_ROWS));
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    async function act(id, action) {
        const res = await fetch(`/api/dashboard/order-suggestions/${id}/${action}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        });
        const data = await res.json().catch(() => ({}));
        toast(data.message || 'Done', res.ok ? 'success' : 'error');
        loadSuggestions();
    }

    async function recompute() {
        toast('Updating forecast…', 'success');
        const res = await fetch('/api/dashboard/order-suggestions/run', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        });
        const data = await res.json().catch(() => ({}));
        toast(data.message || 'Done', res.ok ? 'success' : 'error');
        loadSuggestions();
    }

    function exportCsv() {
        window.location.href = `/api/dashboard/order-suggestions/export?status=${encodeURIComponent(STATUS_FILTER)}`;
    }

    document.getElementById('fStatus').addEventListener('change', () => { PAGE = 1; loadSuggestions(); });
    document.getElementById('fUrgency').addEventListener('change', refreshFromFilters);
    document.getElementById('fCategory').addEventListener('change', refreshFromFilters);
    document.getElementById('fPerPage').addEventListener('change', refreshFromFilters);

    let searchTimer;
    document.getElementById('fSearch').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(refreshFromFilters, 250);
    });

    loadSuggestions();
</script>
@endpush
