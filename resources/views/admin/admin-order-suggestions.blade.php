@extends('layouts.dashboard-main-frame')

@section('title', 'Order Suggestions - Smart-Stock')

@section('content')
    <div class="page-header hero">
        <div class="hero-content">
            <h1 class="page-title">Order Suggestions</h1>
            <p class="page-subtitle">Automated demand forecasting</p>
        </div>
    </div>

    <!-- ====== STAT CARDS ====== -->
    <div class="sug-stats-grid">
        <div class="sug-stat-card">
            <div class="sug-stat-header">
                <h3>Total Suggestions</h3>
                <span class="sug-stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </span>
            </div>
            <div class="sug-stat-value blue" id="sugStatTotal">0</div>
            <div class="sug-stat-subtext">Active order suggestions</div>
        </div>
        <div class="sug-stat-card">
            <div class="sug-stat-header">
                <h3>Critical Urgency</h3>
                <span class="sug-stat-icon red">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </span>
            </div>
            <div class="sug-stat-value red" id="sugStatCritical">0</div>
            <div class="sug-stat-subtext">Immediate reorder needed</div>
        </div>
        <div class="sug-stat-card">
            <div class="sug-stat-header">
                <h3>Low Urgency</h3>
                <span class="sug-stat-icon amber">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </span>
            </div>
            <div class="sug-stat-value amber" id="sugStatLow">0</div>
            <div class="sug-stat-subtext">Monitor stock levels</div>
        </div>
    </div>

    <!-- ====== TOOLBAR ====== -->
    <div class="section-card">
        <div class="section-card-header">
            <h2 class="section-card-title">Search and Filter Options</h2>
            <p class="section-card-desc">Search, narrow, and update forecasts</p>
        </div>
        <div class="filter-bar">
            <div class="osg-filter-inner">
                <div class="filter-group osg-filter-search">
                    <label class="filter-label" for="fSearch">Search</label>
                    <div class="valid-wrap has-icon-left">
                        <span class="input-icon-left" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </span>
                        <input type="text" class="field-input" id="fSearch" placeholder="Item name, SKU, category..." autocomplete="off">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="fStatusLabel">Show</label>
                    <div class="role-select" id="fStatusSelect">
                        <button type="button" class="field-input role-select-btn" id="fStatusBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="fStatusLabel fStatusBtnLabel" onclick="toggleFilterSelect(event, 'fStatusSelect')">
                            <span id="fStatusBtnLabel">Default</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="fStatusList" role="listbox" aria-labelledby="fStatusLabel">
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="active" onclick="selectFilterOption('fStatus', 'active', 'Default')">Default</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="ordered" onclick="selectFilterOption('fStatus', 'ordered', 'Ordered')">Ordered</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="dismissed" onclick="selectFilterOption('fStatus', 'dismissed', 'Dismissed')">Dismissed</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="all" onclick="selectFilterOption('fStatus', 'all', 'All')">All</button>
                        </div>
                        <input type="hidden" id="fStatus" value="active">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="fUrgencyLabel">Urgency</label>
                    <div class="role-select" id="fUrgencySelect">
                        <button type="button" class="field-input role-select-btn" id="fUrgencyBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="fUrgencyLabel fUrgencyBtnLabel" onclick="toggleFilterSelect(event, 'fUrgencySelect')">
                            <span id="fUrgencyBtnLabel">All urgency</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="fUrgencyList" role="listbox" aria-labelledby="fUrgencyLabel">
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="" onclick="selectFilterOption('fUrgency', '', 'All urgency')">All urgency</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="critical" onclick="selectFilterOption('fUrgency', 'critical', 'Critical')">Critical</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="low" onclick="selectFilterOption('fUrgency', 'low', 'Low')">Low</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="watch" onclick="selectFilterOption('fUrgency', 'watch', 'Watch')">Watch</button>
                        </div>
                        <input type="hidden" id="fUrgency" value="">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="fCategoryLabel">Category</label>
                    <div class="role-select" id="fCategorySelect">
                        <button type="button" class="field-input role-select-btn" id="fCategoryBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="fCategoryLabel fCategoryBtnLabel" onclick="toggleFilterSelect(event, 'fCategorySelect')">
                            <span id="fCategoryBtnLabel">All categories</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="fCategoryList" role="listbox" aria-labelledby="fCategoryLabel">
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="" data-label="All categories">All categories</button>
                        </div>
                        <input type="hidden" id="fCategory" value="">
                    </div>
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
                    <button class="btn-primary" onclick="recompute()" title="Recalculate demand from the last 30 days of sales and update the suggestions below">Update Forecast</button>
                    <button class="btn-ghost" onclick="resetFilters()">Reset</button>
                </div>
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
                            <th>Item Name</th>
                            <th>SKU / CODE<br><span style="font-size:0.85em;text-transform:none;white-space:nowrap;">(stock keeping unit)</span></th>
                            <th class="num">Current Stock</th>
                            <th class="num">Average Daily Sales</th>
                            <th class="num">Reorder Point</th>
                            <th class="num">Suggested Order Quantity</th>
                            <th class="num">Estimated Cost</th>
                            <th>Urgency</th>
                            <th>Generated</th>
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
            /* visible so the custom filter dropdowns can escape the card
               instead of being clipped by the rounded border. */
            overflow: visible;
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
        /* STAT CARDS — mirrors User Accounts .user-stat-card */
        .sug-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }
        .sug-stat-card {
            background: rgba(255,255,255,0.025);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        body.light-theme .sug-stat-card {
            background: #ffffff;
            border-color: rgba(15,23,42,0.08);
        }
        .sug-stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }
        .sug-stat-header h3 {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #ffffff;
            margin: 0;
        }
        body.light-theme .sug-stat-header h3 { color: #64748b; }
        .sug-stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .sug-stat-icon svg { width: 20px; height: 20px; display: block; }
        .sug-stat-icon.blue   { background: rgba(96,165,250,0.15);  color: #60a5fa; }
        .sug-stat-icon.red    { background: rgba(248,113,113,0.15); color: #f87171; }
        .sug-stat-icon.amber  { background: rgba(251,191,36,0.15);  color: #fbbf24; }
        .sug-stat-value {
            font-size: 26px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            color: #f8fafc;
        }
        .sug-stat-value.blue   { color: #60a5fa; }
        .sug-stat-value.red    { color: #f87171; }
        .sug-stat-value.amber  { color: #fbbf24; }
        .sug-stat-subtext { font-size: 12px; color: #ffffff; }
        body.light-theme .sug-stat-value { color: #0f172a; }
        body.light-theme .sug-stat-value.blue   { color: #2563eb; }
        body.light-theme .sug-stat-value.red    { color: #dc2626; }
        body.light-theme .sug-stat-value.amber  { color: #d97706; }
        body.light-theme .sug-stat-subtext { color: #94a3b8; }
        body.light-theme .sug-stat-icon.blue   { background: rgba(37,99,235,0.1); }
        body.light-theme .sug-stat-icon.red    { background: rgba(220,38,38,0.1); }
        body.light-theme .sug-stat-icon.amber  { background: rgba(217,119,6,0.1); }
        .section-card .filter-bar {
            margin: 14px 16px 16px 16px;
            background: rgba(255,255,255,0.045); border: 1px solid rgba(255,255,255,0.08);
        }
        body.light-theme .filter-bar { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.08); }

        /* SEARCH & FILTER — 2 rows: the search field spans the full width on
           row 1, row 2 holds the Show / Urgency / Category / Rows selects plus
           the Update Forecast / Reset actions. Same surface and field styling
           as the User Accounts Search & Filter section. */
        .osg-filter-inner {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
            gap: 16px 20px;
            align-items: end;
            /* The shared layout makes `.filter-bar` a flex container, so this
               grid would shrink-to-fit its content and leave the right half of
               the bar empty. Claim the full bar width. */
            width: 100%;
            min-width: 0;
        }
        .osg-filter-inner > .osg-filter-search { grid-column: 1 / -1; }
        /* The shared `.filter-actions { margin-left: auto }` would push the
           buttons to the far right in a grid context — reset so it stays in
           its own column next to the filters. */
        .osg-filter-inner > .filter-actions {
            grid-column: auto;
            margin-left: 0;
            justify-content: flex-end;
        }
        .osg-filter-inner .field-input { cursor: pointer; }
        .osg-filter-inner input.field-input { cursor: text; }
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
        }
        input.field-input { cursor: text; padding: 0 12px 0 14px; }
        .field-input:focus { border-color: #3b82f6; }
        .field-input::placeholder { color: #64748b; }
        body.light-theme .field-input { background: #ffffff; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .field-input::placeholder { color: #94a3b8; }

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
            position: absolute; top: calc(100% + 6px); left: 0; z-index: 60;
            min-width: 100%;
            width: max-content;
            max-width: calc(100vw - 96px);
            display: none; flex-direction: column; padding: 6px; gap: 2px;
            background: #1e293b; border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px; box-shadow: 0 12px 28px rgba(2,6,23,0.5);
            max-height: 50vh; overflow-y: auto;
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
            .osg-filter-inner { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .osg-filter-inner > .filter-actions { grid-column: 1 / -1; }
        }
        @media (max-width: 620px) {
            .osg-filter-inner { grid-template-columns: minmax(0, 1fr); }
            .osg-filter-inner > .filter-actions { grid-column: 1 / -1; justify-content: flex-start; }
        }
        /* Narrow viewports (e.g. docked side panels): tighten cell padding and
           font so the full 10-column table still fits without heavy overflow. */
        @media (max-width: 1000px) {
            .sug-table th, .sug-table td { padding: 10px 6px; font-size: 12px; }
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
        .sug-table { table-layout: auto; min-width: 860px; }
        /* Let long item names / SKUs / reasons expand and wrap instead of
           being clipped or overflowing into a single cramped line. Urgency and
           Action columns keep their nowrap rules below. */
        .sug-table tbody td { white-space: normal; overflow-wrap: anywhere; }
        /* Urgency pill + Generated + Action buttons must stay on a single line. */
        .sug-table tbody td:nth-child(8),
        .sug-table tbody td:nth-child(9),
        .sug-table tbody td:nth-child(10) { white-space: nowrap; }
        .section-card .table-wrapper.is-loading { overflow-x: hidden; }
        .section-card .table-wrapper.is-loading .sug-table { min-width: 0; }
        /* Column widths match the 10-column layout: Item Name, SKU,
           Current Stock, Average Daily Sales, Reorder Point, Suggested
           Order Quantity, Estimated Cost, Urgency, Generated, Actions. */
        .sug-table th:nth-child(1), .sug-table td:nth-child(1) { width: 18%; }
        .sug-table th:nth-child(2), .sug-table td:nth-child(2) { width: 9%; }
        .sug-table th:nth-child(3), .sug-table td:nth-child(3) { width: 7%; }
        .sug-table th:nth-child(4), .sug-table td:nth-child(4) { width: 9%; }
        .sug-table th:nth-child(5), .sug-table td:nth-child(5) { width: 8%; }
        .sug-table th:nth-child(6), .sug-table td:nth-child(6) { width: 11%; }
        .sug-table th:nth-child(7), .sug-table td:nth-child(7) { width: 8%; }
        /* Urgency holds a nowrap pill, so it must not be squeezed. */
        .sug-table th:nth-child(8), .sug-table td:nth-child(8) { width: 8%; }
        .sug-table th:nth-child(9), .sug-table td:nth-child(9) { width: 9%; }
        /* Actions must fit two buttons (~145px) inside the cell's content
           box. Too narrow and the flex row overflows toward the LEFT,
           drawing the buttons over the Urgency column. */
        .sug-table th:nth-child(10), .sug-table td:nth-child(10) { width: 13%; }
        /* The shared `.data-table thead th` is `white-space: nowrap`, so the
           long headers ("Average Daily Sales", "Suggested Order Quantity",
           "Estimated Cost") could never wrap and spilled over neighbouring
           columns. Force wrap + center with higher specificity + !important. */
        .section-card .table-wrapper .sug-table thead th {
            letter-spacing: 0.2px;
            white-space: normal;
            overflow-wrap: break-word;
            line-height: 1.3;
            text-align: center !important;
        }
        /* The shared header cells carry vertical padding sized for a single
           nowrap line. With wrapping on, that padding dominates and the band
           grows to ~57px with a large gap under the text. Trim it so a
           two-line header reads as two lines, not a tall empty strip. */
        .section-card .table-wrapper .sug-table thead th { padding: 10px 8px; }
        /* Body cells: center + wrap — override layout .data-table td.num { text-align: right } */
        .sug-table tbody td { padding: 12px 8px; text-align: center !important; }
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
            border-radius: 8px; padding: 0 16px; height: 36px; font-size: 13px; font-weight: 600; cursor: pointer;
            white-space: nowrap; font-family: 'Inter', sans-serif;
            transition: filter 0.1s ease, transform 0.1s ease;
        }
        .filter-actions .btn-primary:hover { background: #1d4ed8; filter: none; }
        .filter-actions .btn-primary:active { filter: brightness(0.85); transform: scale(0.97); }
        /* Reset is a secondary action: no fill at rest, border tracks the text
           color, hover shades that same color. */
        .filter-bar .filter-actions .btn-ghost {
            background: transparent; border: 1px solid currentColor; color: #cbd5e1;
            padding: 0 16px; height: 36px; font-weight: 600; font-family: 'Inter', sans-serif;
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
        .sug-table .action-row { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; width: 100%; }
        .sug-table .btn-mini { white-space: nowrap; }
        .btn-mini { color:#f87171; }
        .btn-mini:hover { border-color:currentColor; background:color-mix(in srgb, currentColor 12%, transparent); }
        .btn-mini.ok   { color:#34d399; }
        .btn-mini.ok:hover { border-color:currentColor; }
        .btn-mini + .btn-mini { margin-left:6px; }
        body.light-theme .btn-mini { color:#dc2626; }
        body.light-theme .btn-mini.ok { color:#047857; }
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
    window.ALL_ROWS = [];
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

    /* ---- Custom filter dropdowns --------------------------------------
       The native <select> popup is OS-rendered and ignores the page theme,
       so each filter uses a button + listbox pair backed by a hidden input.
       Values stay on the hidden input, so the existing filter code keeps
       reading `document.getElementById(id).value` unchanged. */
    function toggleFilterSelect(e, wrapId) {
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
    }

    function closeAllFilterSelects() {
        document.querySelectorAll('.role-select.open').forEach((wrap) => {
            wrap.classList.remove('open');
            wrap.querySelector('.role-select-btn')?.setAttribute('aria-expanded', 'false');
        });
    }

    /* `silent` skips the change event so resetFilters() can normalise every
       control at once and reload the list a single time. */
    function selectFilterOption(inputId, value, label, silent = false) {
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
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.role-select')) closeAllFilterSelects();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAllFilterSelects();
    });

    async function loadSuggestions() {
        STATUS_FILTER = document.getElementById('fStatus').value;
        const tbody = document.getElementById('sugTableBody');
        const wrapper = tbody.closest('.table-wrapper');

        try {
            const res  = await fetch(`/api/dashboard/order-suggestions?status=${encodeURIComponent(STATUS_FILTER)}`,
                                     { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Unable to load suggestions');
            const data = await res.json();

            window.ALL_ROWS = data.suggestions || [];
            syncCategoryOptions(window.ALL_ROWS);
            updateStatCards();
            renderSuggestions(applyFilters(window.ALL_ROWS));
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="10" class="empty-state">Unable to load suggestions.</td></tr>';
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
        const list  = document.getElementById('fCategoryList');
        const input = document.getElementById('fCategory');
        if (!list || !input) return;

        const current = input.value;
        const cats = [...new Set(rows.map(r => r.category).filter(Boolean))].sort();
        const option = (value, label) => {
            const selected = value === current;
            return `<button type="button" class="role-select-option${selected ? ' selected' : ''}" role="option" aria-selected="${selected}" data-value="${escapeHtml(value)}" data-label="${escapeHtml(label)}">${escapeHtml(label)}</button>`;
        };

        list.innerHTML = option('', 'All categories')
            + cats.map(c => option(c, c)).join('');

        // Drop the chosen category if it no longer exists after a status change.
        if (current && !cats.includes(current)) {
            selectFilterOption('fCategory', '', 'All categories', true);
        }
    }

    /* Category labels come from data, so select via a delegated handler
       instead of an inline onclick (which would break on quotes). */
    document.getElementById('fCategoryList')?.addEventListener('click', (e) => {
        const opt = e.target.closest('.role-select-option');
        if (!opt) return;
        selectFilterOption('fCategory', opt.dataset.value, opt.dataset.label || 'All categories');
    });

    function resetFilters() {
        document.getElementById('fSearch').value = '';
        selectFilterOption('fUrgency', '', 'All urgency', true);
        selectFilterOption('fCategory', '', 'All categories', true);
        selectFilterOption('fPerPage', '15', '15', true);
        selectFilterOption('fStatus', 'active', 'Default', true);
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
    document.getElementById('sugTableBody').innerHTML = '<tr><td colspan="10" class="empty-state">Loading suggestions...</td></tr>';

    function updateStatCards() {
        if (!window.ALL_ROWS) return;
        const total = window.ALL_ROWS.filter(r => r.status === 'active').length;
        const critical = window.ALL_ROWS.filter(r => r.status === 'active' && r.urgency === 'critical').length;
        const low = window.ALL_ROWS.filter(r => r.status === 'active' && r.urgency === 'low').length;
        document.getElementById('sugStatTotal').textContent = total;
        document.getElementById('sugStatCritical').textContent = critical;
        document.getElementById('sugStatLow').textContent = low;
    }

    function renderSuggestions(rows) {
        const tbody = document.getElementById('sugTableBody');

        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="10" class="empty-state">No suggestions match these filters.</td></tr>`;
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
                <td>${escapeHtml(s.generated_at || '—')}</td>
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
        const maxPage = Math.max(1, Math.ceil(applyFilters(window.ALL_ROWS).length / currentFilters().perPage));
        const next = PAGE + delta;
        if (next < 1 || next > maxPage) return;
        PAGE = next;
        renderSuggestions(applyFilters(window.ALL_ROWS));
    }

    function refreshFromFilters() {
        PAGE = 1;
        renderSuggestions(applyFilters(window.ALL_ROWS));
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
