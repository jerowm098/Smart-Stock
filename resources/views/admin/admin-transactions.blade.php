@extends('layouts.dashboard-main-frame')

@section('title', 'Transaction History - Smart-Stock')

@section('content')
    <header class="page-header hero">
        <div class="hero-content">
            <h1 class="page-title">Transaction History</h1>
            <p class="page-subtitle">View all completed sales</p>
        </div>
    </header>

    <!-- ====== FILTERS ====== -->
    <div class="white-form">
        <div class="section-card-header">
            <h2 class="section-card-title">Search & Filter</h2>
            <p class="section-card-desc">Find transactions by receipt, product, or date range</p>
        </div>
        <div class="filter-bar">
            <div class="filter-group">
                <label for="fSearch">Search</label>
                <input type="text" id="fSearch" placeholder="Receipt no, product, SKU..." autocomplete="off">
            </div>
            <div class="filter-group">
                <label for="fCashier">Cashier</label>
                <select id="fCashier">
                    <option value="">All cashiers</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="fFrom">From</label>
                <input type="date" id="fFrom" value="">
            </div>
            <div class="filter-group">
                <label for="fTo">To</label>
                <input type="date" id="fTo" value="">
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
                <button class="btn-primary" onclick="applyFilters()">Apply</button>
                <button class="btn-ghost" onclick="resetFilters()">Reset</button>
            </div>
        </div>
    </div>

    <!-- ====== TABLE ====== -->
    <div class="white-form">
        <div class="section-card-header section-card-header--with-meta">
            <div>
                <h2 class="section-card-title">Transaction History</h2>
                <p class="section-card-desc">View all completed sales and receipts</p>
            </div>
            <div class="section-card-actions">
                <span class="result-meta" id="resultMeta">Loading...</span>
                <button class="btn-export" onclick="exportCsv()" title="Download filtered transactions as CSV (Excel-compatible)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>Export CSV</button>
            </div>
        </div>
        <div class="table-wrapper" id="txnTableView">
                <table>
                    <thead>
                        <tr>
                            <th>Receipt No</th>
                            <th>Date</th>
                            <th>Cashier</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Change</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="txnBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ====== PAGINATION ====== -->
    <div class="pager" id="pager">
        <button class="btn-ghost" id="prevBtn" onclick="goPage(-1)">← Prev</button>
        <span id="pageLabel">Page 1 of 1</span>
        <button class="btn-ghost" id="nextBtn" onclick="goPage(1)">Next →</button>
    </div>

    <!-- ====== DETAIL MODAL ====== -->
    <div class="note-overlay" id="txnModal">
        <div class="note-card txn-modal-card">
            <h3 class="note-title" id="txnModalTitle">Receipt</h3>
            <p class="note-message" id="txnModalSub"></p>
            <div class="txn-items" id="txnModalItems"></div>
            <button class="note-btn" onclick="closeTxnModal()">Close</button>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .white-form {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px;
        }
        .white-form:last-child {
            margin-bottom: 0;
        }
        body.light-theme .white-form {
            background: #ffffff;
            border-color: rgba(15, 23, 42, 0.08);
        }
        .section-card-header {
            margin-bottom: 20px;
        }
        .section-card-header--with-meta {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }
        .section-card-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .section-card-title {
            font-size: 18px;
            font-weight: 700;
            color: #f8fafc;
            margin: 0 0 6px 0;
        }
        .section-card-desc {
            font-size: 13px;
            color: #94a3b8;
            margin: 0;
        }
        .table-wrapper {
            background: transparent;
            border: none;
            border-radius: 0;
            overflow-x: auto;
            overflow-y: visible;
        }
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
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            border: 1px solid var(--hero-card-border);
            margin-bottom: 20px;
            min-height: 140px;
            display: flex;
            align-items: flex-end;
            padding: 28px 28px 24px;
            background: var(--hero-card-bg);
        }
        .hero-content { position: relative; z-index: 1; max-width: 640px; }
        body.light-theme .page-header.hero {
            --hero-card-bg: #ffffff;
            --hero-card-border: rgba(37,99,235,0.15);
            --hero-card-title: #0f172a;
            --hero-card-subtitle: #475569;
        }
        .filter-bar {
            display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px; padding: 16px; margin-bottom: 12px;
        }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .filter-group input, .filter-group select {
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
            color: #e2e8f0; font-size: 13px; border-radius: 8px; padding: 8px 10px; outline: none; min-width: 160px;
        }
        .filter-actions { display: flex; gap: 8px; margin-left: auto; }
        .btn-primary {
            background: #2563eb; color: #fff; border: none;
            border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        /* Primary action, not a row action: solid fill matching .btn-primary /
           the cashier "Complete Sale" button, so it reads as the page's main CTA. */
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
        .btn-ghost {
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
            color: #cbd5e1; border-radius: 8px; padding: 9px 16px; font-size: 13px; font-weight: 500; cursor: pointer;
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.08); }
        .btn-ghost:disabled { opacity: 0.4; cursor: not-allowed; }
        /* Action button, not a neutral surface: no fill at rest, border tracks
           the text color, and hover shades that same color. Scoped under
           .filter-bar so it outranks the layout's `body.light-theme .btn-ghost`
           (specificity 0,2,1) in light theme. */
        .filter-bar .filter-actions .btn-ghost {
            background: transparent; border: 1px solid currentColor; color: #cbd5e1;
            font-weight: 600; font-family: 'Inter', sans-serif; transition: all 0.15s;
        }
        .filter-bar .filter-actions .btn-ghost:hover {
            border-color: currentColor; background: rgba(203,213,225,0.12); color: #e2e8f0;
        }
        body.light-theme .filter-bar .filter-actions .btn-ghost { color: #334155; }
        body.light-theme .filter-bar .filter-actions .btn-ghost:hover {
            border-color: currentColor; background: rgba(30,41,59,0.08); color: #0f172a;
        }
        .result-meta { font-size: 12px; color: #64748b; white-space: nowrap; }
        .table-wrapper {
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px; overflow-x: auto; overflow-y: visible;
        }
        table { width: 100%; min-width: 800px; border-collapse: separate; border-spacing: 0; border: 1px solid rgba(255,255,255,0.08); table-layout: fixed; }
        #txnTableView.loading table { min-width: 0; }
        thead th {
            background: rgba(255,255,255,0.04); padding: 14px 16px; text-align: left;
            font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: normal; border-bottom: 1px solid rgba(255,255,255,0.08); border-right: 1px solid rgba(255,255,255,0.06); line-height: 1.4;
        }
        thead th:last-child { border-right: none; }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.06); }
        tbody tr:hover { background: rgba(255,255,255,0.03); }
        tbody td { padding: 14px 16px; font-size: 13px; color: #cbd5e1; border-right: 1px solid rgba(255,255,255,0.06); border-bottom: 1px solid rgba(255,255,255,0.06); vertical-align: middle; }
        tbody td:last-child { border-right: none; }
        tbody tr:last-child td { border-bottom: none; }
        .receipt-no { font-family: monospace; font-weight: 700; color: #60a5fa; }
        .cashier-name { font-weight: 600; color: #f8fafc; }
        .cashier-email { font-size: 11px; color: #64748b; }
        .money { font-weight: 600; white-space: nowrap; }
        .money.total { color: #4ade80; }
        .view-btn {
            background: none; color: #60a5fa; border: 1px solid currentColor; border-radius: 7px;
            padding: 5px 11px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap;
            transition: all 0.15s;
        }
        .view-btn:hover { border-color: currentColor; background: rgba(96,165,250,0.12); color: #93c5fd; }
        body.light-theme .view-btn { color: #1e293b; }
        body.light-theme .view-btn:hover { border-color: currentColor; background: rgba(30,41,59,0.08); color: #0f172a; }
        .empty-state { text-align: center; color: #475569; padding: 48px; font-size: 14px; }
        .pager { display: flex; align-items: center; justify-content: center; gap: 14px; margin-top: 16px; }
        #pageLabel { font-size: 13px; color: #94a3b8; }
        .note-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px); z-index: 300; align-items: center; justify-content: center;
        }
        .note-overlay.active { display: flex; }
        .note-card {
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px;
            padding: 28px; max-width: 520px; width: 92%; max-height: 85vh; overflow-y: auto;
            text-align: center;
        }
        .note-title { font-size: 17px; font-weight: 700; color: #f8fafc; margin: 0 0 6px 0; }
        .note-message { font-size: 13px; color: #94a3b8; margin: 0 0 16px 0; }
        .txn-items { text-align: left; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden; }
        .txn-row { display: flex; justify-content: space-between; gap: 10px; padding: 10px 14px; font-size: 13px; color: #cbd5e1; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .txn-row:last-child { border-bottom: none; }
        .txn-row.header { background: rgba(255,255,255,0.03); font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600; }
        .txn-row.totals { background: rgba(74,222,128,0.06); font-weight: 700; }
        .note-btn {
            background: #2563eb; color: #fff; border: none;
            border-radius: 8px; padding: 10px 28px; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        body.light-theme .page-title { color: #0f172a; }
        body.light-theme .section-card-title { color: #0f172a; }
        body.light-theme .section-card-desc { color: #64748b; }
        body.light-theme .filter-bar { background: #fff; border-color: rgba(15,23,42,0.08); }
        body.light-theme .filter-group input, body.light-theme .filter-group select { background: #fff; border-color: rgba(15,23,42,0.12); color: #0f172a; }
        body.light-theme .table-wrapper { background: #fff; border-color: rgba(15,23,42,0.08); }
        body.light-theme table thead th { background: rgba(15,23,42,0.02); }
        body.light-theme table tbody td { color: #475569; }
        body.light-theme .cashier-name { color: #0f172a; }
        body.light-theme .note-card { background: #fff; border-color: rgba(15,23,42,0.1); }
        body.light-theme .note-title { color: #0f172a; }
        body.light-theme .txn-row { color: #475569; border-bottom-color: rgba(15,23,42,0.06); }
    </style>
@endpush

@push('scripts')
    <script>
    (function () {
        // BRD (Transaction Tracking): Default date range shows last 30 days so
        // seeded demo sales and historical transactions are visible by default.
        const today = new Date();
        const isoToday = today.getFullYear() + '-'
            + String(today.getMonth() + 1).padStart(2, '0') + '-'
            + String(today.getDate()).padStart(2, '0');
        const thirtyDaysAgo = new Date(today);
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 29);
        const isoThirtyDaysAgo = thirtyDaysAgo.getFullYear() + '-'
            + String(thirtyDaysAgo.getMonth() + 1).padStart(2, '0') + '-'
            + String(thirtyDaysAgo.getDate()).padStart(2, '0');

        let state = { page: 1, per_page: 15, search: '', cashier_id: '', date_from: isoThirtyDaysAgo, date_to: isoToday, last_page: 1, total: 0, cache: [] };

        function qs() {
            const p = new URLSearchParams();
            p.set('page', state.page);
            p.set('per_page', state.per_page);
            if (state.search) p.set('search', state.search);
            if (state.cashier_id) p.set('cashier_id', state.cashier_id);
            if (state.date_from) p.set('date_from', state.date_from);
            if (state.date_to) p.set('date_to', state.date_to);
            return p.toString();
        }

        async function loadTxns() {
            const body = document.getElementById('txnBody');
            const meta = document.getElementById('resultMeta');
            const tableView = document.getElementById('txnTableView');
            // Show loading message
            tableView.classList.add('loading');
            body.innerHTML = '<tr><td colspan="8" class="empty-state">Loading transactions...</td></tr>';
            try {
                const res = await fetch('/api/dashboard/transactions?' + qs());
                if (!res.ok) throw new Error('Failed (' + res.status + ')');
                const d = await res.json();
                state.last_page = d.last_page || 1;
                state.total = d.total || 0;
                state.cache = d.data || [];

                // Fill cashier dropdown once
                const sel = document.getElementById('fCashier');
                if (sel && sel.options.length <= 1 && d.cashiers) {
                    d.cashiers.forEach(c => {
                        const o = document.createElement('option');
                        o.value = c.id;
                        o.textContent = c.name + ' (' + c.email + ')';
                        sel.appendChild(o);
                    });
                    if (state.cashier_id) sel.value = state.cashier_id;
                }

                meta.textContent = state.total + ' transactions · Page ' + d.current_page + ' of ' + d.last_page;
                document.getElementById('pageLabel').textContent = 'Page ' + d.current_page + ' of ' + d.last_page;
                document.getElementById('prevBtn').disabled = d.current_page <= 1;
                document.getElementById('nextBtn').disabled = d.current_page >= d.last_page;

                if (state.cache.length === 0) {
                    body.innerHTML = '<tr><td colspan="8" class="empty-state">No transactions found. Try changing the filters.</td></tr>';
                    tableView.classList.remove('loading');
                    return;
                }

                body.innerHTML = state.cache.map((t, i) => `
                    <tr>
                        <td class="receipt-no">${escapeHtml(t.receipt_no)}</td>
                        <td>${escapeHtml(t.date)}</td>
                        <td><div class="cashier-name">${escapeHtml(t.cashier_name)}</div><div class="cashier-email">${escapeHtml(t.cashier_email)}</div></td>
                        <td>${t.items_count} pcs</td>
                        <td class="money total">₱${Number(t.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                        <td class="money">₱${Number(t.payment_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                        <td class="money">₱${Number(t.change_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                        <td><button class="view-btn" onclick="openTxnModal(${i})">View</button></td>
                    </tr>`).join('');
            } catch (e) {
                body.innerHTML = '<tr><td colspan="8" class="empty-state">Unable to load transactions</td></tr>';
                console.error('Transactions load error:', e);
            } finally {
                tableView.classList.remove('loading');
            }
        }

        function exportQs() {
            const p = new URLSearchParams();
            const search = document.getElementById('fSearch').value.trim();
            const cashierId = document.getElementById('fCashier').value;
            const dateFrom = document.getElementById('fFrom').value;
            const dateTo = document.getElementById('fTo').value;
            if (search) p.set('search', search);
            if (cashierId) p.set('cashier_id', cashierId);
            if (dateFrom) p.set('date_from', dateFrom);
            if (dateTo) p.set('date_to', dateTo);
            return p.toString();
        }

        window.exportCsv = function () {
            const q = exportQs();
            window.location.href = '/api/dashboard/transactions/export' + (q ? '?' + q : '');
        };

        window.applyFilters = function () {
            state.page = 1;
            state.search = document.getElementById('fSearch').value.trim();
            state.cashier_id = document.getElementById('fCashier').value;
            state.date_from = document.getElementById('fFrom').value;
            state.date_to = document.getElementById('fTo').value;
            state.per_page = document.getElementById('fPerPage').value;
            loadTxns();
        };

        window.resetFilters = function () {
            document.getElementById('fSearch').value = '';
            document.getElementById('fCashier').value = '';
            // Reset to last 30 days so seeded demo data is visible
            const t = new Date();
            const thirtyDaysAgo = new Date(t);
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 29);
            const iso = thirtyDaysAgo.getFullYear() + '-'
                + String(thirtyDaysAgo.getMonth() + 1).padStart(2, '0') + '-'
                + String(thirtyDaysAgo.getDate()).padStart(2, '0');
            const isoToday = t.getFullYear() + '-'
                + String(t.getMonth() + 1).padStart(2, '0') + '-'
                + String(t.getDate()).padStart(2, '0');
            document.getElementById('fFrom').value = iso;
            document.getElementById('fTo').value = isoToday;
            document.getElementById('fPerPage').value = '15';
            state = { page: 1, per_page: 15, search: '', cashier_id: '', date_from: iso, date_to: isoToday, last_page: 1, total: 0, cache: state.cache };
            loadTxns();
        };

        window.goPage = function (dir) {
            const next = state.page + dir;
            if (next < 1 || next > state.last_page) return;
            state.page = next;
            loadTxns();
        };

        window.openTxnModal = function (idx) {
            const t = state.cache[idx];
            if (!t) return;
            document.getElementById('txnModalTitle').textContent = t.receipt_no;
            document.getElementById('txnModalSub').textContent = t.date + ' · Cashier: ' + t.cashier_name;
            const rows = t.items.map(it => `
                <div class="txn-row">
                    <span><strong>${escapeHtml(it.product_name)}</strong> <span style="color:#64748b;font-size:11px;">${escapeHtml(it.sku)}</span><br><span style="font-size:12px;color:#94a3b8;">${it.quantity} × ₱${Number(it.unit_price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span></span>
                    <span class="money">₱${Number(it.line_total).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                </div>`).join('');
            document.getElementById('txnModalItems').innerHTML =
                '<div class="txn-row header"><span>Item</span><span>Line total</span></div>' + rows +
                '<div class="txn-row totals"><span>Total</span><span>₱' + Number(t.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) + '</span></div>' +
                '<div class="txn-row"><span>Payment</span><span>₱' + Number(t.payment_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) + '</span></div>' +
                '<div class="txn-row"><span>Change</span><span>₱' + Number(t.change_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) + '</span></div>';
            document.getElementById('txnModal').classList.add('active');
        };

        window.closeTxnModal = function () {
            document.getElementById('txnModal').classList.remove('active');
        };

        document.getElementById('txnModal').addEventListener('click', function (e) {
            if (e.target === this) closeTxnModal();
        });

        document.getElementById('fSearch').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') applyFilters();
        });

        function escapeHtml(text) {
            const d = document.createElement('div');
            d.textContent = text || '';
            return d.innerHTML;
        }
        window.escapeHtml = escapeHtml;

        // Show the Today default in the visible date inputs so the UI matches the
        // request actually being sent (BRD: date filters default to "Today").
        document.getElementById('fFrom').value = state.date_from;
        document.getElementById('fTo').value = state.date_to;

        loadTxns();
    })();
    </script>
@endpush
