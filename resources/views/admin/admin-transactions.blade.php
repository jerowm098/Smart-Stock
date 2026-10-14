@extends('layouts.dashboard-main-frame')

@section('title', 'Transaction History - Smart-Stock')

@section('content')
    <header class="page-header hero">
        <div class="hero-content">
            <h1 class="page-title">Transaction History</h1>
            <p class="page-subtitle">View all completed sales</p>
        </div>
    </header>

    <!-- ====== STAT CARDS ====== -->
    <div class="txn-stats-grid">
        <div class="txn-stat-card">
            <div class="txn-stat-header">
                <h3>Total Revenue</h3>
                <span class="txn-stat-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </span>
            </div>
            <div class="txn-stat-value green" id="txnStatRevenue">₱0.00</div>
            <div class="txn-stat-subtext">From completed sales</div>
        </div>
        <div class="txn-stat-card">
            <div class="txn-stat-header">
                <h3>Items Sold</h3>
                <span class="txn-stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </span>
            </div>
            <div class="txn-stat-value blue" id="txnStatItems">0</div>
            <div class="txn-stat-subtext">Total units sold</div>
        </div>
        <div class="txn-stat-card">
            <div class="txn-stat-header">
                <h3>Cashiers</h3>
                <span class="txn-stat-icon purple">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </span>
            </div>
            <div class="txn-stat-value purple" id="txnStatCashiers">0</div>
            <div class="txn-stat-subtext">Active cashiers</div>
        </div>
    </div>

    <!-- ====== FILTERS ====== -->
    <div class="white-form">
        <div class="section-card-header">
            <h2 class="section-card-title">Search and Filter Options</h2>
            <p class="section-card-desc">Find transactions by receipt, product, or date range</p>
        </div>
        <div class="filter-bar">
            <div class="txn-filter-inner">
                <div class="filter-group txn-filter-search">
                    <label class="filter-label" for="fSearch">Search</label>
                    <div class="valid-wrap has-icon-left">
                        <span class="input-icon-left" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </span>
                        <input type="text" class="field-input" id="fSearch" placeholder="Receipt no, product, SKU..." autocomplete="off">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="fCashierLabel">Cashier</label>
                    <div class="role-select" id="fCashierSelect">
                        <button type="button" class="field-input role-select-btn" id="fCashierBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="fCashierLabel fCashierBtnLabel" onclick="toggleFilterSelect(event, 'fCashierSelect')">
                            <span id="fCashierBtnLabel">All cashiers</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="fCashierList" role="listbox" aria-labelledby="fCashierLabel">
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="" data-label="All cashiers">All cashiers</button>
                        </div>
                        <input type="hidden" id="fCashier" value="">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="fFrom">From</label>
                    <input type="date" class="field-input" id="fFrom" value="">
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="fTo">To</label>
                    <input type="date" class="field-input" id="fTo" value="">
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="fPerPageLabel">Rows</label>
                    <div class="role-select" id="fPerPageSelect">
                        <button type="button" class="field-input role-select-btn" id="fPerPageBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="fPerPageLabel fPerPageBtnLabel" onclick="toggleFilterSelect(event, 'fPerPageSelect')">
                            <span id="fPerPageBtnLabel">15</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="fPerPageList" role="listbox" aria-labelledby="fPerPageLabel">
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="10" onclick="selectFilterOption('fPerPage', '10', '10')">10</button>
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="15" onclick="selectFilterOption('fPerPage', '15', '15')">15</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="25" onclick="selectFilterOption('fPerPage', '25', '25')">25</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="50" onclick="selectFilterOption('fPerPage', '50', '50')">50</button>
                        </div>
                        <input type="hidden" id="fPerPage" value="15">
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn-primary" onclick="applyFilters()">Apply</button>
                    <button class="btn-ghost" onclick="resetFilters()">Reset</button>
                </div>
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
                            <th>Actions</th>
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
        /* STAT CARDS — mirrors User Accounts / Order Suggestions */
        .txn-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }
        .txn-stat-card {
            background: rgba(255,255,255,0.025);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        body.light-theme .txn-stat-card {
            background: #ffffff;
            border-color: rgba(15,23,42,0.08);
        }
        .txn-stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }
        .txn-stat-header h3 {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #ffffff;
            margin: 0;
        }
        body.light-theme .txn-stat-header h3 { color: #64748b; }
        .txn-stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .txn-stat-icon svg { width: 20px; height: 20px; display: block; }
        .txn-stat-icon.green  { background: rgba(74,222,128,0.15);  color: #4ade80; }
        .txn-stat-icon.blue   { background: rgba(96,165,250,0.15);  color: #60a5fa; }
        .txn-stat-icon.purple { background: rgba(167,139,250,0.15); color: #a78bfa; }
        .txn-stat-value {
            font-size: 26px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            color: #f8fafc;
        }
        .txn-stat-value.green  { color: #4ade80; }
        .txn-stat-value.blue   { color: #60a5fa; }
        .txn-stat-value.purple { color: #a78bfa; }
        .txn-stat-subtext { font-size: 12px; color: #ffffff; }
        body.light-theme .txn-stat-value { color: #0f172a; }
        body.light-theme .txn-stat-value.green  { color: #16a34a; }
        body.light-theme .txn-stat-value.blue   { color: #2563eb; }
        body.light-theme .txn-stat-value.purple { color: #9333ea; }
        body.light-theme .txn-stat-subtext { color: #94a3b8; }
        body.light-theme .txn-stat-icon.green  { background: rgba(22,163,74,0.1); }
        body.light-theme .txn-stat-icon.blue   { background: rgba(37,99,235,0.1); }
        body.light-theme .txn-stat-icon.purple { background: rgba(147,51,234,0.1); }
        .table-wrapper {
            background: transparent;
            border: none;
            border-radius: 0;
            overflow-x: auto;
            overflow-y: visible;
        }
        .page-header.hero {
            --hero-card-bg: rgba(255,255,255,0.025);
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
            background: rgba(255,255,255,0.045); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px; padding: 16px; margin-bottom: 12px;
        }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .filter-group input, .filter-group select {
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
            color: #e2e8f0; font-size: 13px; border-radius: 8px; padding: 8px 10px; outline: none; min-width: 160px;
        }
        body.light-theme .filter-group input, body.light-theme .filter-group select { background: #f1f5f9; border-color: rgba(15,23,42,0.12); color: #0f172a; }
        .filter-actions { display: flex; gap: 8px; margin-left: auto; }
        .btn-primary {
            background: #2563eb; color: #fff; border: none;
            border-radius: 8px; padding: 0 18px; height: 36px; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        /* Primary action, not a row action: solid fill matching .btn-primary /
           the cashier "Complete Sale" button, so it reads as the page's main CTA. */
        body.light-theme .btn-primary { background: #2563eb; color: #fff; }
        body.light-theme .btn-primary:hover { background: #1d4ed8; }
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
            padding: 0 16px; height: 36px;
            font-weight: 600; font-family: 'Inter', sans-serif; transition: all 0.15s;
        }
        .filter-bar .filter-actions .btn-ghost:hover {
            border-color: currentColor; background: rgba(203,213,225,0.12); color: #e2e8f0;
        }
        body.light-theme .filter-bar { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.08); }
        body.light-theme .filter-bar .filter-actions .btn-ghost { background: #f1f5f9; border-color: rgba(15,23,42,0.12); color: #334155; }
        body.light-theme .filter-bar .filter-actions .btn-ghost:hover {
            border-color: currentColor; background: rgba(30,41,59,0.08); color: #0f172a;
        }
        .result-meta { font-size: 12px; color: #64748b; white-space: nowrap; }

        /* SEARCH & FILTER — 2 rows: the search field spans the full width on
           row 1, row 2 holds the Cashier / From / To / Rows controls plus the
           Apply / Reset actions. Same surface and field styling as the Order
           Suggestions and User Accounts filter sections. */
        .txn-filter-inner {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
            gap: 14px 16px;
            align-items: end;
            /* The shared layout makes `.filter-bar` a flex container, so this
               grid would shrink-to-fit its content and leave the right half of
               the bar empty. Claim the full bar width. */
            width: 100%;
            min-width: 0;
        }
        .txn-filter-inner > .txn-filter-search { grid-column: 1 / -1; }
        /* The shared `.filter-actions { margin-left: auto }` would push the
           buttons to the far right in a grid context — reset so it stays in
           its own column next to the filters. */
        .txn-filter-inner > .filter-actions {
            grid-column: auto;
            margin-left: 0;
            justify-content: flex-end;
        }
        .txn-filter-inner .field-input { cursor: pointer; }
        .txn-filter-inner input.field-input { cursor: text; }
        .filter-group .filter-label {
            font-size: 11px; font-weight: 700; color: #cbd5e1;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        body.light-theme .filter-group .filter-label { color: #334155; }

        /* FIELDS */
        .field-input {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            padding: 0 12px;
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
            box-sizing: border-box;
            width: 100%;
            min-width: 0;
            color-scheme: dark;
        }
        input.field-input { cursor: text; padding: 0 12px 0 14px; min-width: 0; }
        .field-input:focus { border-color: #3b82f6; }
        .field-input::placeholder { color: #64748b; }
        body.light-theme .field-input { background: #ffffff; border-color: rgba(15,23,42,0.14); color: #0f172a; color-scheme: light; }
        body.light-theme .field-input::placeholder { color: #94a3b8; }
        /* Date inputs keep their native calendar picker; only the surface changes. */
        input[type="date"].field-input {
            appearance: auto; -webkit-appearance: auto; -moz-appearance: auto;
            cursor: pointer; padding-right: 8px;
        }
        input[type="date"].field-input::-webkit-calendar-picker-indicator { cursor: pointer; opacity: 0.6; }

        /* CUSTOM DROPDOWN — width-locked to its field so the list can never
           overflow the card like the native <select> popup did. */
        .role-select { position: relative; width: 100%; min-width: 0; }
        .role-select-btn {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            width: 100%; text-align: left; font-weight: 400;
        }
        .role-select-btn > span {
            min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .role-select-btn svg { flex-shrink: 0; color: #94a3b8; transition: transform 0.15s; }
        .role-select.open .role-select-btn svg { transform: rotate(180deg); }
        .role-select-list {
            position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 60;
            display: none; flex-direction: column; padding: 4px;
            background: #1e293b; border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px; box-shadow: 0 12px 28px rgba(2,6,23,0.45);
            max-height: 180px; overflow-y: auto;
        }
        .role-select.open .role-select-list { display: flex; }
        /* The bottom row sits near the card edge, so flip the list upward when
           there is not enough room below the trigger. */
        .role-select.drop-up .role-select-list { top: auto; bottom: calc(100% + 6px); }
        body.light-theme .role-select-list { background: #ffffff; border-color: rgba(15,23,42,0.12); box-shadow: 0 12px 28px rgba(15,23,42,0.15); }
        .role-select-option {
            border: none; background: transparent; text-align: left; cursor: pointer;
            font-family: 'Inter', sans-serif; font-size: 13px; color: #e2e8f0;
            padding: 9px 10px; border-radius: 6px; width: 100%;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .role-select-option:hover { background: rgba(59,130,246,0.15); }
        .role-select-option.selected { color: #93c5fd; font-weight: 600; }
        .role-select-option.selected:hover { background: rgba(59,130,246,0.15); }
        body.light-theme .role-select-option { color: #0f172a; }
        body.light-theme .role-select-option:hover { background: rgba(37,99,235,0.08); }
        body.light-theme .role-select-option.selected { color: #1d4ed8; }
        body.light-theme .role-select-option.selected:hover { background: rgba(37,99,235,0.08); }

        /* LEADING INPUT ICON (left side inside the field) */
        .valid-wrap { position: relative; width: 100%; min-width: 0; }
        .input-icon-left {
            position: absolute; top: 50%; left: 12px; transform: translateY(-50%);
            width: 16px; height: 16px; display: inline-flex;
            align-items: center; justify-content: center;
            color: #64748b; pointer-events: none;
        }
        .input-icon-left svg { width: 16px; height: 16px; display: block; }
        .valid-wrap.has-icon-left .field-input { padding-left: 36px; }
        body.light-theme .input-icon-left { color: #94a3b8; }

        /* Keep the 2-row feel on medium screens: fields pair up, actions span. */
        @media (max-width: 900px) {
            .txn-filter-inner { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .txn-filter-inner > .filter-actions { grid-column: 1 / -1; }
        }
        @media (max-width: 620px) {
            .txn-filter-inner { grid-template-columns: minmax(0, 1fr); }
            .txn-filter-inner > .filter-actions { grid-column: 1 / -1; justify-content: flex-start; }
        }
        .table-wrapper {
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px; overflow-x: auto; overflow-y: visible;
        }
        table { width: 100%; min-width: 800px; border-collapse: separate; border-spacing: 0; border: 1px solid rgba(255,255,255,0.08); table-layout: fixed; }
        #txnTableView.loading table { min-width: 0; }
        thead th {
            background: rgba(255,255,255,0.04); padding: 14px 16px; text-align: center;
            font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: normal; border-bottom: 1px solid rgba(255,255,255,0.08); border-right: 1px solid rgba(255,255,255,0.06); line-height: 1.4;
        }
        thead th:last-child { border-right: none; }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.06); }
        tbody tr:hover { background: rgba(255,255,255,0.03); }
        tbody td { padding: 14px 16px; font-size: 13px; color: #cbd5e1; border-right: 1px solid rgba(255,255,255,0.06); border-bottom: 1px solid rgba(255,255,255,0.06); vertical-align: middle; text-align: center; }
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
        body.light-theme .filter-bar { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.08); }
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

        /* ---- Custom filter dropdowns --------------------------------------
           The native <select> popup is OS-rendered and ignores the page theme,
           so the Cashier / Rows filters use a button + listbox pair backed by
           a hidden input. Values stay on the hidden input, so the existing
           filter code keeps reading `document.getElementById(id).value`. */
        window.toggleFilterSelect = function (e, wrapId) {
            if (e) e.stopPropagation();
            const wrap = document.getElementById(wrapId);
            if (!wrap) return;
            const btn = wrap.querySelector('.role-select-btn');
            const willOpen = !wrap.classList.contains('open');
            closeAllFilterSelects();
            if (willOpen) {
                // Flip the list upward when the viewport bottom would clip it.
                const list = wrap.querySelector('.role-select-list');
                let listH = 0;
                if (list) {
                    list.style.visibility = 'hidden';
                    list.style.display = 'flex';
                    listH = list.offsetHeight;
                    list.style.display = '';
                    list.style.visibility = '';
                }
                const spaceBelow = window.innerHeight - btn.getBoundingClientRect().bottom;
                wrap.classList.toggle('drop-up', spaceBelow < listH + 16);
            }
            wrap.classList.toggle('open', willOpen);
            btn?.setAttribute('aria-expanded', String(willOpen));
        };

        function closeAllFilterSelects() {
            document.querySelectorAll('.role-select.open').forEach((wrap) => {
                wrap.classList.remove('open');
                wrap.querySelector('.role-select-btn')?.setAttribute('aria-expanded', 'false');
            });
        }

        /* `silent` skips the change event so resetFilters() can normalise every
           control at once and reload the list a single time. */
        window.selectFilterOption = function (inputId, value, label, silent = false) {
            const input = document.getElementById(inputId);
            if (input) {
                input.value = value;
                if (!silent) input.dispatchEvent(new Event('change', { bubbles: true }));
            }
            const wrap = input?.closest('.role-select');
            const labelEl = wrap?.querySelector('.role-select-btn span');
            if (labelEl) labelEl.textContent = label;
            wrap?.querySelectorAll('.role-select-option').forEach((opt) => {
                const selected = opt.dataset.value === value;
                opt.classList.toggle('selected', selected);
                opt.setAttribute('aria-selected', String(selected));
            });
            closeAllFilterSelects();
        };

        // Cashier options come from data, so select via a delegated handler.
        document.getElementById('fCashierList')?.addEventListener('click', (e) => {
            const opt = e.target.closest('.role-select-option');
            if (!opt) return;
            window.selectFilterOption('fCashier', opt.dataset.value, opt.dataset.label || 'All cashiers');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.role-select')) closeAllFilterSelects();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAllFilterSelects();
        });

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

                updateStatCards(state.cache, d.cashiers);

                // Fill the cashier dropdown once (custom listbox backed by #fCashier)
                const list = document.getElementById('fCashierList');
                if (list && list.dataset.filled !== '1' && d.cashiers) {
                    list.innerHTML = '<button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="" data-label="All cashiers">All cashiers</button>'
                        + d.cashiers.map(c => {
                            const label = c.name + ' (' + c.email + ')';
                            return `<button type="button" class="role-select-option" role="option" aria-selected="false" data-value="${escapeHtml(String(c.id))}" data-label="${escapeHtml(label)}">${escapeHtml(label)}</button>`;
                        }).join('');
                    list.dataset.filled = '1';
                    if (state.cashier_id) {
                        const match = list.querySelector(`.role-select-option[data-value="${state.cashier_id}"]`);
                        if (match) window.selectFilterOption('fCashier', match.dataset.value, match.dataset.label, true);
                    }
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

        function updateStatCards(transactions, cashiers) {
            const revenue = transactions.reduce((sum, t) => sum + Number(t.total_amount || 0), 0);
            const items = transactions.reduce((sum, t) => sum + Number(t.items_count || 0), 0);
            const cashierCount = (cashiers && Array.isArray(cashiers)) ? cashiers.length : 0;
            document.getElementById('txnStatRevenue').textContent = '₱' + revenue.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('txnStatItems').textContent = items.toLocaleString();
            document.getElementById('txnStatCashiers').textContent = cashierCount;
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
            window.selectFilterOption('fCashier', '', 'All cashiers', true);
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
            window.selectFilterOption('fPerPage', '15', '15', true);
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
