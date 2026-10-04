@extends('layouts.app')

@section('title', 'Overview - Smart-Stock')

@section('content')
<div class="ov">

    <header class="page-header hero">
        <div class="hero-content">
            <h1 class="page-title">Overview</h1>
            <p class="page-subtitle">Hello {{ ucfirst(Auth::user()->role) }} {{ ucwords(Auth::user()->name) }}, here is an overview of your inventory dashboard.</p>
        </div>
    </header>

    <!-- ====== STAT CARDS (clickable) ======
         This is the ADMIN overview. Cashiers get cashier-overview.blade.php,
         so the markup below is admin-only and needs no role branching. -->
    <div class="stats-grid">
        <a href="{{ route('transactions') }}" class="stat-card stat-clickable" title="View Transaction History">
            <div class="stat-card-header">
                <h3>Total Revenue</h3>
                <span class="stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21V3h4.5a4 4 0 0 1 0 8H8"></path><line x1="2.5" y1="7.5" x2="13.5" y2="7.5"></line><line x1="2.5" y1="12.5" x2="13.5" y2="12.5"></line></svg>
                </span>
            </div>
            <div class="stat-value blue" id="statRevenue">₱0.00</div>
            <div class="stat-subtext">All time sales</div>
        </a>
        <a href="{{ route('products') }}" class="stat-card stat-clickable" title="View Products">
            <div class="stat-card-header">
                <h3>Total Products</h3>
                <span class="stat-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                </span>
            </div>
            <div class="stat-value green" id="statProducts">0</div>
            <div class="stat-subtext">Items in catalog</div>
        </a>
        <a href="{{ route('products') }}" class="stat-card stat-clickable" title="View Low Stock Products">
            <div class="stat-card-header">
                <h3>Low Stock Alerts</h3>
                <span class="stat-icon red">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </span>
            </div>
            <div class="stat-value red" id="statLowStock">0</div>
            <div class="stat-subtext">Items below threshold</div>
        </a>
        <a href="{{ route('suppliers') }}" class="stat-card stat-clickable" title="View Suppliers">
            <div class="stat-card-header">
                <h3>Suppliers</h3>
                <span class="stat-icon yellow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </span>
            </div>
            <div class="stat-value yellow" id="statSuppliers">0</div>
            <div class="stat-subtext">Active suppliers</div>
        </a>
    </div>

    <!-- ====== CHART + TOP SELLING ROW ====== -->
    <div class="chart-top-row">
        <!-- Revenue Chart (75%) -->
        <div class="chart-panel" id="revenuePanel">
            <div class="section-header">
                <h2 class="section-title">Revenue Overview</h2>
                <div class="panel-controls">
                    <span class="chart-period-badge">Last 30 Days</span>
                    <a href="{{ route('transactions') }}" class="panel-action" title="View all transactions" aria-label="View all transactions">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </a>
                </div>
            </div>
            <div class="chart-container" id="revenueBox">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        <!-- Top Selling Products (25%) -->
        <div class="top-products-panel" id="topPanel">
            <div class="section-header">
                <h2 class="section-title">Top Selling</h2>
                <div class="panel-controls">
                    <a href="{{ route('products') }}" class="panel-action" title="View all products" aria-label="View all products">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </a>
                </div>
            </div>
            <div class="top-products-list" id="topProductsList">
            </div>
        </div>
    </div>

    <!-- ====== SS-35: RESTOCK SUGGESTIONS (Admin only, demand-based) ====== -->
    <div class="content-panel" id="suggestPanel">
        <div class="section-header">
            <h2 class="section-title">Order Suggestions</h2>
            <div class="panel-controls">
                {{-- BRD (Demand Forecasting) fixes the demand window at 30 days:
                     "Daily Velocity = Total Units Sold / 30 days". The selector was
                     removed so the on-screen figures always match the BRD formulas.
                     Recomputed nightly by the `forecast:orders` job. --}}
                <span class="control-label">Demand window: last 30 days (nightly job)</span>
                <a href="{{ route('order-suggestions') }}" class="panel-action" title="Open full Order Suggestions dashboard" aria-label="Open full Order Suggestions dashboard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="M7 15l4-5 3 3 5-7"></path></svg>
                </a>
                <a href="{{ route('stock-in') }}" class="panel-action" title="Receive new stock" aria-label="Receive new stock">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                </a>
            </div>
        </div>
        <div class="table-wrapper table-scroll" id="suggestTable">
            <table>
                <thead>
                    <tr>
                        <th>Urgency</th>
                        <th>Product</th>
                        <th>Stock / Threshold</th>
                        {{-- The three demand figures are merged into one column:
                             at this width they were squeezing "Why" down to
                             ~160px, which forced 117px-tall rows. --}}
                        <th>Sold / Avg per day</th>
                        <th>Days left</th>
                        <th>Suggested order</th>
                        <th>Why</th>
                    </tr>
                </thead>
                <tbody id="suggestBody"></tbody>
            </table>
        </div>
    </div>

    <!-- ====== SS-24 TRANSACTION HISTORY + SS-40 STOCK-INS (stacked, full width) ====== -->
    <div class="split-row">
        <div class="content-panel" id="salesPanel">
            <div class="section-header">
                <h2 class="section-title">Transaction History</h2>
                <div class="panel-controls">
                    <button class="btn-export-sm" onclick="exportSummaryCsv()" title="Download transaction summary as CSV (Excel-compatible)">⬇ Export Summary CSV</button>
                    <a href="{{ route('transactions') }}" class="panel-action" title="View all transactions" aria-label="View all transactions">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </a>
                </div>
            </div>
            <div class="table-wrapper table-scroll" id="recentSalesTable">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Line Total</th>
                        </tr>
                    </thead>
                    <tbody id="recentSalesBody"></tbody>
                </table>
            </div>
        </div>

        <!-- ====== SS-40: RECENT STOCK-IN ACTIVITY ====== -->
        <div class="content-panel" id="stockInPanel">
            <div class="section-header">
                <h2 class="section-title">Recent Stock-In Activity</h2>
                <div class="panel-controls">
                    <a href="{{ route('stock-in') }}" class="panel-action" title="View all stock-in records" aria-label="View all stock-in records">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </a>
                </div>
            </div>
            <div class="table-wrapper table-scroll" id="stockInTable">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Supplier</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Staff</th>
                        </tr>
                    </thead>
                    <tbody id="stockInBody"></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
    <style>
        /* =====================================================================
           OVERVIEW — SPACING SYSTEM
           Single source of truth. Every gap, padding and rhythm below is
           derived from these tokens; no rule may invent its own numbers.
           Scope is `.ov` so the shared layout's global table rules can never
           fight these (that was the source of the compressed cell spacing).
           ===================================================================== */
        .ov {
            /* Spacing scale (the only place raw px gaps live) */
            --sp-1:  4px;
            --sp-2:  6px;
            --sp-3:  8px;
            --sp-4: 10px;
            --sp-5: 12px;
            --sp-6: 14px;
            --sp-7: 16px;
            --sp-8: 20px;
            --sp-9: 24px;
            --sp-10: 28px;

            /* Layout rhythm */
            --section-gap: var(--sp-10); /* vertical gap between sections  */
            --panel-pad:    var(--sp-8);  /* padding inside every panel    */
            --header-gap:   var(--sp-5);  /* header -> its content         */
            --header-h: 30px;             /* one height for all header controls */
            --cell-x:       var(--sp-7);  /* table cell horizontal padding*/
            --control-gap:  var(--sp-4);  /* gap between header controls   */
            /* Height system — every panel keeps the SAME height whether it is
               loading or loaded, so a spinner simply appears inside a stable
               box instead of the panel growing when data lands. */
            --row-h:      53px;   /* one height for every data row          */
            --row-pad-y:  14px;   /* cell padding that produces --row-h      */
            --th-h:       43px;   /* one height for every header row        */
            --list-rows:  5;      /* rows every panel shows (matches API)   */
            --header-row: 42px;   /* section header (30px control + 12 gap)  */

            /* Every body is a MINIMUM, never a maximum: a table is exactly as
               tall as its rows, so it grows with the data instead of hiding
               rows behind an inner scrollbar. */
            --list-h:     calc(var(--row-h) * var(--list-rows));               /* 220 */
            --table-h:    calc(var(--th-h) + var(--row-h) * var(--list-rows));  /* 258 */
            --suggest-h:  calc(var(--th-h) + var(--row-h) * 5);                 /* 258 */
            /* Panel = padding + header + body. Chart row uses --list-h as its
               body, the table panels use --table-h — both are 5 rows tall, so
               every section on the page lines up. */
            --panel-h:    calc(var(--panel-pad) * 2 + var(--header-row) + var(--table-h));

            /* Type scale */
            --fs-micro: 10px;
            --fs-label: 11px;
            --fs-sm:    12px;
            --fs-md:    13px;
            --fs-base:  14px;
            --fs-h2:    16px;
            --fs-h1:    22px;
            --fs-stat:  26px;
            --lh-tight: 1.25;
            --lh-body:  1.5;
            --track-caps: 0.06em;

            font-size: var(--fs-md);
            line-height: var(--lh-body);
        }

        /* === PAGE HEADER HERO — welcome banner === */
        .ov .page-header.hero {
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            border: 1px solid rgba(96,165,250,0.18);
            margin-bottom: var(--section-gap);
            min-height: 140px;
            display: flex;
            align-items: flex-end;
            justify-content: flex-start;
            padding: 28px 28px 24px;
            background: #13294f;
        }
        .ov .hero-decor { position: absolute; inset: 0; pointer-events: none; }
        .ov .hero-content { position: relative; z-index: 1; max-width: 640px; }
        .ov .hero-eyebrow {
            display: inline-flex; align-items: center; gap: var(--sp-2);
            font-size: var(--fs-label); font-weight: 700; letter-spacing: var(--track-caps); text-transform: uppercase;
            color: #bfdbfe; background: rgba(147,197,253,0.14); border: 1px solid rgba(147,197,253,0.3);
            padding: var(--sp-1) var(--sp-4); border-radius: 20px; margin-bottom: var(--sp-3);
        }
        .ov .page-header.hero .page-title {
            font-size: 30px;
            font-weight: 800;
            line-height: var(--lh-tight);
            letter-spacing: -0.02em;
            color: #ffffff;
            margin: 0 0 var(--sp-2);
        }
        .ov .page-header.hero .page-subtitle {
            font-size: var(--fs-base);
            line-height: var(--lh-body);
            color: rgba(226,232,240,0.85);
            margin: 0;
        }

        /* === STAT CARDS === */
        .ov .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--sp-7);
            margin-bottom: var(--section-gap);
        }
        .ov .stat-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: var(--panel-pad);
            display: flex;
            flex-direction: column;
            gap: var(--sp-1);
            text-decoration: none;
            transition: transform 0.15s, border-color 0.15s, background 0.15s;
        }
        .ov .stat-clickable { cursor: pointer; }
        .ov .stat-clickable:hover {
            transform: translateY(-2px);
            border-color: rgba(96,165,250,0.25);
            background: rgba(96,165,250,0.05);
        }
        .ov .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--sp-4);
            margin-bottom: var(--sp-2);
        }
        .ov .stat-card h3 {
            font-size: var(--fs-label);
            line-height: var(--lh-tight);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: var(--track-caps);
            color: #64748b;
            margin: 0;
        }
        .ov .stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .ov .stat-icon svg { width: 20px; height: 20px; display: block; }
        .ov .stat-icon.blue   { background: rgba(96,165,250,0.15);  color: #60a5fa; }
        .ov .stat-icon.green  { background: rgba(74,222,128,0.15);  color: #4ade80; }
        .ov .stat-icon.yellow { background: rgba(251,191,36,0.15);  color: #fbbf24; }
        .ov .stat-icon.red    { background: rgba(248,113,113,0.15); color: #f87171; }
        .ov .stat-value {
            font-size: var(--fs-stat);
            font-weight: 700;
            line-height: var(--lh-tight);
            letter-spacing: -0.02em;
            color: #f8fafc;
        }
        .ov .stat-value.blue   { color: #60a5fa; }
        .ov .stat-value.green  { color: #4ade80; }
        .ov .stat-value.yellow { color: #fbbf24; }
        .ov .stat-value.red    { color: #f87171; }
        .ov .stat-subtext {
            font-size: var(--fs-label);
            line-height: var(--lh-tight);
            color: #475569;
        }

        /* === CHART + TOP PRODUCTS ROW === */
        .ov .chart-top-row {
            display: grid;
            grid-template-columns: 2.3fr 1.7fr;
            gap: var(--panel-pad);
            margin-bottom: var(--section-gap);
            align-items: start;
        }
        /* Fixed at --panel-h in both states — loading and loaded are the same
           height, so the spinner sits inside a box that never resizes. */
        .ov .chart-panel,
        .ov .top-products-panel {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: var(--panel-pad);
            display: flex;
            flex-direction: column;
            height: var(--panel-h);
            overflow: hidden;
        }
        .ov .chart-container {
            position: relative;
            width: 100%;
            flex: 1;
            min-height: 0; /* lets the canvas shrink instead of overflowing */
        }
        .ov .chart-container canvas { width: 100% !important; height: 100% !important; }

        /* === TOP PRODUCTS LIST === */
        /* Always reserves the full 5-row height; the spinner sits centred in
           it so the panel never resizes when the rows arrive. */
        .ov .top-products-list {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: var(--list-h);
        }
        .ov .top-product-item {
            display: flex;
            align-items: center;
            gap: var(--sp-5);
            flex: 1 1 0;
            min-height: 0;
            padding: var(--sp-3) 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        /* The loading row is the one child that must not stretch. */
        .ov .top-products-list > .list-loading { flex: 0 0 auto; align-self: center; margin: auto 0; }
        .ov .top-product-item:last-child { border-bottom: none; }
        .ov .top-product-rank {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(96,165,250,0.1);
            color: #60a5fa;
            font-size: var(--fs-sm);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .ov .top-product-rank.rank-1 { background: rgba(251,191,36,0.15); color: #fbbf24; }
        .ov .top-product-rank.rank-2 { background: rgba(148,163,184,0.12); color: #94a3b8; }
        .ov .top-product-rank.rank-3 { background: rgba(180,130,80,0.12); color: #c8956a; }
        .ov .top-product-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: var(--sp-1);
        }
        .ov .top-product-name {
            font-size: var(--fs-md);
            line-height: var(--lh-tight);
            font-weight: 600;
            color: #f8fafc;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ov .top-product-sku {
            font-size: var(--fs-micro);
            line-height: var(--lh-tight);
            color: #475569;
            font-family: monospace;
        }
        .ov .top-product-stats {
            display: flex;
            flex-direction: column;
            gap: var(--sp-1);
            text-align: right;
            flex-shrink: 0;
        }
        .ov .top-product-qty { font-size: var(--fs-base); line-height: var(--lh-tight); font-weight: 700; color: #f8fafc; }
        .ov .top-product-rev { font-size: var(--fs-label); line-height: var(--lh-tight); font-weight: 500; color: #4ade80; }

        /* === SECTION HEADER + CONTROLS === */
        /* min-height = control height + the gap beneath it, so a header with
           or without controls reserves identical space. */
        .ov .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--sp-5);
            flex-wrap: wrap;
            min-height: var(--header-row);
            margin-bottom: 0;
            padding-bottom: var(--header-gap);
        }
        .ov .section-title {
            font-size: var(--fs-h2);
            font-weight: 600;
            line-height: var(--lh-tight);
            color: #f8fafc;
            margin: 0;
        }
        /* One control row for every panel: badge, label, select, button, link. */
        .ov .panel-controls {
            display: flex;
            align-items: center;
            gap: var(--control-gap);
            flex-wrap: wrap;
        }
        .ov .control-label {
            font-size: var(--fs-sm);
            line-height: var(--lh-tight);
            color: #64748b;
        }

        /* === PANEL-LEVEL CONTROLS (all share --header-h) === */
        .ov .panel-action,
        .ov .control-select,
        .ov .btn-export-sm,
        .ov .chart-period-badge {
            height: var(--header-h);
            flex-shrink: 0;
        }
        .ov .panel-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: var(--header-h);
            border: 1px solid rgba(148,163,184,0.3);
            border-radius: 7px;
            background: none;
            color: #94a3b8;
            text-decoration: none;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .ov .panel-action:hover {
            background: rgba(148,163,184,0.12);
            border-color: rgba(148,163,184,0.5);
            color: #e2e8f0;
        }
        .ov .panel-action svg { width: 15px; height: 15px; display: block; }
        .ov .control-select {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            color: #e2e8f0;
            font-family: 'Inter', sans-serif;
            font-size: var(--fs-sm);
            line-height: var(--lh-tight);
            border-radius: 8px;
            padding: 0 var(--sp-4);
            outline: none;
            cursor: pointer;
        }
        .ov .btn-export-sm {
            display: inline-flex;
            align-items: center;
            background: rgba(74,222,128,0.12);
            color: #4ade80;
            border: 1px solid rgba(74,222,128,0.3);
            border-radius: 8px;
            padding: 0 var(--sp-6);
            font-family: 'Inter', sans-serif;
            font-size: var(--fs-sm);
            font-weight: 600;
            line-height: var(--lh-tight);
            white-space: nowrap;
            cursor: pointer;
        }
        .ov .btn-export-sm:hover { background: rgba(74,222,128,0.2); }
        .ov .chart-period-badge {
            display: inline-flex;
            align-items: center;
            font-size: var(--fs-label);
            line-height: var(--lh-tight);
            font-weight: 600;
            color: #60a5fa;
            background: rgba(96,165,250,0.1);
            padding: 0 var(--sp-4);
            border-radius: 6px;
            white-space: nowrap;
        }
        /* === CONTENT PANEL (SS-35 suggestions, SS-24 transactions, stock-ins) === */
        .ov .content-panel {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: var(--panel-pad);
            margin-bottom: var(--section-gap);
            min-width: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        /* The shared table frame in layouts/app.blade.php supplies the outer
           border and column rules; only the fill is dropped here so the
           wrapper blends into its content panel. */
        .ov .content-panel .table-wrapper { background: transparent; }

        /* === STACKED ACTIVITY ROWS (Transaction History, then Stock-Ins) ===
           Stacked full-width instead of a 50/50 grid: six columns in a half
           panel were being clipped, and stacking gives each table the whole
           page width so every column is readable with no scrollbar.
           Height is a floor, not a cap, so the panel grows with its rows. */
        .ov .split-row {
            display: flex;
            flex-direction: column;
            gap: var(--section-gap);
            margin-bottom: 0;
        }
        /* gap already spaces these, so the panel's own margin is cancelled —
           otherwise the two panels drift twice as far apart as every other
           section on the page. */
        .ov .split-row .content-panel {
            min-height: var(--panel-h);
            margin-bottom: 0;
            overflow: visible;
        }
        .ov .split-row .table-wrapper {
            flex: 1;
            min-height: var(--table-h);
            min-width: 0;
        }
        .ov .top-products-list { min-height: var(--list-h); }
        /* These tables are full-width, so the only thing that ever pushed
           them wide was `white-space: nowrap` on a long product name. Text
           wraps inside its column instead. */
        .ov .split-row td { overflow-wrap: break-word; }

        /* === TABLE — identical cell rhythm for every table on the page ===
           Each table is exactly as tall as its rows (no inner vertical
           scrollbar) and scrolls HORIZONTALLY inside its own frame once the
           pane is narrower than the table's min-width — the same
           scroll-to-view pattern the Products tab uses. Columns are sized
           with shares of that min-width, so the layout is identical at every
           viewport and only the scrollbar appears. */
        .ov .table-wrapper {
            /* Radius comes from the shared frame's --table-radius so the
               header band's corners line up with this wrapper's outline. */
            border-radius: var(--table-radius, 12px);
            /* Matches the Products tab frame: scroll the table sideways,
               never clip it. */
            overflow-x: auto;
            overflow-y: hidden;
            /* Room for the scrollbar so it does not sit on the last row. */
            scrollbar-width: thin;
        }
        .ov .table-wrapper::-webkit-scrollbar { height: 8px; }
        .ov .table-wrapper::-webkit-scrollbar-track { background: transparent; }
        .ov .table-wrapper::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.28); border-radius: 4px; }
        .ov .table-wrapper::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,0.45); }
        body.light-theme .ov .table-wrapper::-webkit-scrollbar-thumb { background: rgba(15,23,42,0.2); }
        body.light-theme .ov .table-wrapper::-webkit-scrollbar-thumb:hover { background: rgba(15,23,42,0.32); }
        /* Floor only — the table grows past it when there are more rows. */
        .ov .table-scroll {
            position: relative;
            min-height: var(--table-h);
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: thin;
            /* As a flex item the frame's default `min-width: auto` would let it
               grow to its content's min-width instead of shrinking, so the
               wide Order Suggestions table stretched its panel and got clipped
               by the panel's `overflow: hidden` rather than scrolling.
               `min-width: 0` lets the frame stay exactly the panel's width
               and scroll internally. */
            min-width: 0;
        }
        .ov .table-scroll::-webkit-scrollbar { height: 8px; }
        .ov .table-scroll::-webkit-scrollbar-track { background: transparent; }
        .ov .table-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.28); border-radius: 4px; }
        .ov .table-scroll::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,0.45); }
        body.light-theme .ov .table-scroll::-webkit-scrollbar-thumb { background: rgba(15,23,42,0.2); }
        body.light-theme .ov .table-scroll::-webkit-scrollbar-thumb:hover { background: rgba(15,23,42,0.32); }
        /* Spinner strip: centered in the reserved body, removed on settle. */
        .ov .table-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--sp-3);
            color: #64748b;
            font-size: var(--fs-md);
            min-height: calc(var(--table-h) - var(--th-h));
        }
        /* Order Suggestions keeps the same shared floor as every other table
           so all three read as one system; it grows if rows run long. */
        .ov #suggestTable.table-scroll { min-height: var(--suggest-h); }
        /* border-collapse is inherited from the shared table frame in
           layouts/app.blade.php (separate, so row borders survive).
           The min-width is what makes the frame scroll instead of squeezing
           the columns: below it, overflow-x on .table-wrapper kicks in and
           the user scrolls to see the remaining columns. Sized per table so
           the widest header ("Suggested order", "Unit Price") still reads
           without wrapping, at the same rhythm the desktop layout has. */
        .ov table { width: 100%; table-layout: fixed; min-width: 720px; }
        /* Order Suggestions has 7 columns with two long sentence headers, so
           it needs more room than the two 5/6-column tables. The id is on the
           scroll frame, not the <table>, so the min-width must target the
           table inside it — on the frame it would just widen the scroller. */
        .ov #suggestTable table { min-width: 1040px; }
        .ov thead th {
            /* background, colour and corner radius come from the shared
               table frame in layouts/app.blade.php so this page matches
               every other table. Only rhythm lives here — and the rhythm is
               the same 14px vertical padding every other table uses, so the
               header reads at the same height as Products / User Accounts. */
            padding: 14px var(--cell-x);
            text-align: left;
            font-size: var(--fs-label);
            font-weight: 600;
            /* 1.4 reproduces the `normal` line-height the other tabs inherit
               (11px -> 15.4px). Inheriting .ov's --lh-body of 1.5 would make
               every header 16.5px and the header row 1.1px too tall. */
            line-height: 1.4;
            letter-spacing: var(--track-caps);
            text-transform: uppercase;
            /* Headers wrap at word boundaries. `nowrap` here would make the
               header row, not the data, the thing that forces a horizontal
               scrollbar on a narrow pane. */
            white-space: normal;
            overflow-wrap: normal;
        }
        /* Proportional columns. Without these, fixed layout divides the pane
           evenly and squeezes the date/supplier columns; the split below
           gives the text-heavy columns the room they actually need. */
        /* Transaction History: Date, Product, Qty, Unit Price, Line Total */
        .ov #recentSalesTable th:nth-child(1) { width: 17%; }
        .ov #recentSalesTable th:nth-child(2) { width: 31%; }
        .ov #recentSalesTable th:nth-child(3) { width: 8%; }
        .ov #recentSalesTable th:nth-child(4) { width: 20%; }
        .ov #recentSalesTable th:nth-child(5) { width: 24%; }
        /* Stock-Ins: Date, Product, Supplier, Qty, Unit, Staff */
        .ov #stockInTable th:nth-child(1) { width: 17%; }
        .ov #stockInTable th:nth-child(2) { width: 23%; }
        .ov #stockInTable th:nth-child(3) { width: 23%; }
        .ov #stockInTable th:nth-child(4) { width: 8%; }
        .ov #stockInTable th:nth-child(5) { width: 11%; }
        .ov #stockInTable th:nth-child(6) { width: 18%; }
        /* Rows carry no height and no border of their own — the rhythm comes
           from the cell padding below, and each row is drawn as a filled
           container by the shared table frame in layouts/app.blade.php. */
        .ov tbody tr {
            height: auto;
            transition: background 0.15s;
        }
        .ov tbody tr:hover { background: rgba(255,255,255,0.02); }
        /* Row height comes from padding, not `min-height` on <tr>: table
           layout ignores a min-height there, which collapsed every row to
           its bare text and clipped the sub-lines. Padding also grows the
           row automatically when a cell wraps to more lines. */
        .ov tbody td {
            padding: var(--row-pad-y) var(--cell-x);
            font-size: var(--fs-md);
            line-height: var(--lh-body);
            color: #cbd5e1;
            vertical-align: middle;
        }
        /* Cells that stack a name over a sub-line read tighter when the two
           lines are pulled together. */
        .ov tbody td:has(> br) { line-height: 1.35; }
        .ov tbody td strong { font-weight: 600; }

        /* === LOADING vs LOADED ===
           Panels keep their loaded height throughout; only the contents
           change (spinner → rows). Loaded rows fade in on arrival. */
        .ov .table-scroll tbody tr,
        .ov .top-products-panel .top-product-item {
            animation: ovFadeUp 0.35s ease both;
        }
        .ov .table-scroll tbody tr:nth-child(2) { animation-delay: 0.03s; }
        .ov .table-scroll tbody tr:nth-child(3) { animation-delay: 0.06s; }
        .ov .table-scroll tbody tr:nth-child(4) { animation-delay: 0.09s; }
        .ov .table-scroll tbody tr:nth-child(5) { animation-delay: 0.12s; }
        @keyframes ovFadeUp { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        /* Empty state fills the loaded body so an empty panel still looks
           intentional instead of collapsing to just the header.
           Padding and font-size deliberately mirror `.ov tbody td` so the
           loading/placeholder row is pixel-identical in height to a real data
           row (10px 16px / 13px) and the panel does not jump when the fetch
           resolves. */
        .ov .empty-state {
            text-align: center;
            color: #475569;
            padding: var(--row-pad-y) var(--cell-x);
            font-size: var(--fs-md);
            vertical-align: middle;
        }
        /* The row fill must reach the bottom of the reserved panel. A single
           placeholder row at data-row height leaves 150-180px of dead white
           under it, which reads as "the colour shrank". Stretching the row to
           the reserved body height makes the fill continuous, exactly like the
           loading block on the Products tab. */
        .ov .table-scroll tbody tr:has(> td.empty-state:only-child) {
            height: calc(var(--table-h) - var(--th-h));
        }
        .ov #suggestTable.table-scroll tbody tr:has(> td.empty-state:only-child) {
            height: calc(var(--suggest-h) - var(--th-h));
        }
        .ov .table-scroll tbody tr:has(> td.empty-state:only-child) td {
            vertical-align: middle;
        }
        .ov .empty-state-sm {
            padding: var(--sp-8) var(--sp-5);
            font-size: var(--fs-md);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* === SS-35 RESTOCK SUGGESTION CELLS === */
        .ov .urgency-pill {
            display: inline-block;
            font-size: var(--fs-micro);
            font-weight: 700;
            line-height: 1.6;
            text-transform: uppercase;
            letter-spacing: var(--track-caps);
            padding: 0 var(--sp-4);
            border-radius: 20px;
            white-space: nowrap;
        }
        .ov .urgency-critical { background: rgba(248,113,113,0.15); color: #f87171; }
        .ov .urgency-low     { background: rgba(251,191,36,0.15); color: #fbbf24; }
        .ov .urgency-watch   { background: rgba(96,165,250,0.12); color: #60a5fa; }
        .ov .suggest-qty { font-size: var(--fs-base); font-weight: 700; color: #4ade80; }
        /* The merged "Sold / Avg per day" cell: primary figure with the
           velocity as a dimmer suffix so one column carries both numbers. */
        .ov .suggest-avg { color: #64748b; font-size: var(--fs-sm); }
        body.light-theme .ov .suggest-avg { color: #94a3b8; }
        /* Widened from 260px: the explanation below is a full sentence, and a
           narrow column wrapped it into a 5-line block that stretched every
           row to ~117px. A share (not a fixed px floor) is used so the column
           absorbs whatever space the six numeric columns don't need, and
           shrinks with the pane instead of forcing the table to scroll. */
        .ov .suggest-why { font-size: var(--fs-sm); line-height: 1.5; color: #94a3b8; }
        /* Fixed layout so the split below is authoritative. Under the default
           auto layout these are only hints and the long headers win, which is
           what squeezed "Why" to ~160px and stretched rows to ~117px tall. */
        .ov #suggestTable th { white-space: normal; overflow-wrap: normal; }
        .ov #suggestTable td { overflow-wrap: break-word; }
        .ov #suggestTable th:last-child,
        .ov #suggestTable td.suggest-why { width: 31%; }
        .ov #suggestTable th:nth-child(2) { width: 18%; }
        .ov #suggestTable th:not(:last-child):not(:nth-child(2)) { width: 8.6%; }
        .ov .suggest-sku {
            font-size: var(--fs-micro);
            line-height: var(--lh-tight);
            color: #64748b;
            font-family: monospace;
        }
        .ov .cell-sku { color: #94a3b8; }

        /* === LIGHT THEME === */
        body.light-theme .ov .panel-action { border-color: rgba(15,23,42,0.15); color: #64748b; }
        body.light-theme .ov .panel-action:hover { background: rgba(15,23,42,0.06); border-color: rgba(15,23,42,0.3); color: #0f172a; }
        body.light-theme .ov .control-select { background: #fff; border-color: rgba(15,23,42,0.12); color: #0f172a; }
        body.light-theme .ov .suggest-why { color: #64748b; }
        body.light-theme .ov .suggest-qty { color: #16a34a; }
        body.light-theme .ov .page-title { color: #0f172a; }
        body.light-theme .ov .page-subtitle { color: #64748b; }
        body.light-theme .ov .page-header.hero { border-color: rgba(37,99,235,0.18); }
        body.light-theme .ov .page-header.hero .page-title { color: #ffffff; }
        body.light-theme .ov .page-header.hero .page-subtitle { color: rgba(226,232,240,0.88); }
        body.light-theme .ov .hero-eyebrow { color: #dbeafe; background: rgba(255,255,255,0.14); border-color: rgba(255,255,255,0.35); }
        body.light-theme .ov .stat-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme .ov .stat-card:hover { background: rgba(37,99,235,0.02); border-color: rgba(37,99,235,0.2); }
        body.light-theme .ov .stat-card h3 { color: #64748b; }
        body.light-theme .ov .stat-value { color: #0f172a; }
        body.light-theme .ov .stat-value.blue   { color: #2563eb; }
        body.light-theme .ov .stat-value.green  { color: #16a34a; }
        body.light-theme .ov .stat-value.yellow { color: #d97706; }
        body.light-theme .ov .stat-value.red    { color: #dc2626; }
        body.light-theme .ov .stat-subtext { color: #94a3b8; }
        body.light-theme .ov .stat-icon.blue   { background: rgba(37,99,235,0.1); }
        body.light-theme .ov .stat-icon.green  { background: rgba(22,163,74,0.1); }
        body.light-theme .ov .stat-icon.yellow { background: rgba(217,119,6,0.1); }
        body.light-theme .ov .stat-icon.red    { background: rgba(220,38,38,0.1); }
        body.light-theme .ov .chart-panel,
        body.light-theme .ov .top-products-panel,
        body.light-theme .ov .content-panel { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme .ov .chart-period-badge { background: rgba(37,99,235,0.08); color: #2563eb; }
        body.light-theme .ov .section-title { color: #0f172a; }
        body.light-theme .ov .top-product-name { color: #0f172a; }
        body.light-theme .ov .top-product-sku { color: #94a3b8; }
        body.light-theme .ov .top-product-qty { color: #0f172a; }
        body.light-theme .ov .top-product-rev { color: #16a34a; }
        body.light-theme .ov .top-product-rank { background: rgba(37,99,235,0.08); color: #2563eb; }
        body.light-theme .ov .top-product-rank.rank-1 { background: rgba(217,119,6,0.1); color: #d97706; }
        body.light-theme .ov .table-wrapper { background: #ffffff; }
        body.light-theme .ov .table-loading { color: #94a3b8; }
        body.light-theme .ov thead th { color: #1e293b; }
        body.light-theme .ov tbody td { color: #475569; }
        body.light-theme .ov tbody tr:hover { background: rgba(15,23,42,0.025); }
        body.light-theme .ov .empty-state { color: #94a3b8; }
        body.light-theme .ov .suggest-sku,
        body.light-theme .ov .cell-sku { color: #64748b; }

        /* === RESPONSIVE — tokens step down, positions stay put === */
        @media (max-width: 960px) {
            .ov .chart-top-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .ov .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .ov {
                --section-gap: var(--sp-9);
                --panel-pad:    var(--sp-6);
                --header-gap:   var(--sp-4);
                --cell-x:       var(--sp-5);
                --control-gap:  var(--sp-3);
                --row-h:      51px;
                --th-h:       41px;
                --header-row: 38px;
                --fs-h1: 20px;
                --fs-h2: 15px;
                --fs-stat: 22px;
            }
            .ov .page-header.hero { min-height: 160px; padding: var(--sp-8) var(--sp-6) var(--sp-6); }
            .ov .page-header.hero .page-title { font-size: 24px; }
            .ov .stats-grid { gap: var(--sp-5); }
            .ov .stat-icon { width: 30px; height: 30px; border-radius: 7px; }
            .ov .stat-icon svg { width: 16px; height: 16px; }
            .ov .section-header { gap: var(--sp-3); }
            .ov .table-wrapper { border-radius: 10px; }
            /* Table min-widths step down with the cell padding so a phone
               still scrolls the minimum distance needed to read the columns,
               rather than the full desktop width. The frame below keeps
               overflow-x: auto, so the scrollbar appears instead of the
               columns squeezing. */
            .ov table { min-width: 560px; }
            .ov #suggestTable table { min-width: 880px; }
            /* Reveal the scroll affordance on touch devices, where an
               overlay scrollbar is otherwise invisible until you swipe. */
            .ov .table-wrapper,
            .ov .table-scroll { -webkit-overflow-scrolling: touch; }
        }
    </style>
@endpush

@push('scripts')
    <!-- Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const isLight = () => document.body.classList.contains('light-theme');
        let revenueChartInstance = null;

        // ── Stat Cards ──────────────────────────────────────────
        async function loadStats() {
            try {
                const res = await fetch('/api/dashboard/stats');
                if (!res.ok) throw new Error('Failed to load stats');
                const d = await res.json();
                document.getElementById('statRevenue').textContent = '₱' + Number(d.total_revenue).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('statProducts').textContent = d.total_products;
                document.getElementById('statLowStock').textContent = d.low_stock_count;
                document.getElementById('statSuppliers').textContent = d.supplier_count;
            } catch (e) {
                console.error('Stats load error:', e);
            }
        }

        // ── Revenue Chart ───────────────────────────────────────
        // The panel is already at full height, so the chart can render as
        // soon as the data arrives — nothing has to resize first.
        async function loadRevenueChart() {
            try {
                const res = await fetch('/api/dashboard/revenue-chart');
                if (!res.ok) throw new Error('Failed to load chart');
                const d = await res.json();
                renderChart(d.labels, d.values);
                requestAnimationFrame(() => { if (revenueChartInstance) revenueChartInstance.resize(); });
            } catch (e) {
                console.error('Chart load error:', e);
            }
        }

        function renderChart(labels, values) {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const textColor = isLight() ? '#64748b' : '#94a3b8';
            const gridColor  = isLight() ? 'rgba(15,23,42,0.06)' : 'rgba(255,255,255,0.06)';

            if (revenueChartInstance) revenueChartInstance.destroy();

            revenueChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: values,
                        borderColor: '#60a5fa',
                        backgroundColor: 'rgba(96,165,250,0.08)',
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointBackgroundColor: '#60a5fa',
                        pointBorderColor: '#60a5fa',
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.35,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#f8fafc',
                            bodyColor: '#cbd5e1',
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 10,
                            callbacks: {
                                label: ctx => '₱' + ctx.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2 })
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: textColor, font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 },
                            grid:  { display: false },
                            border: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: textColor,
                                font: { size: 11 },
                                callback: v => '₱' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v)
                            },
                            grid:  { color: gridColor },
                            border: { display: false }
                        }
                    }
                }
            });
        }

        // ── Top Selling Products ────────────────────────────────
        async function loadTopProducts() {
            const el = document.getElementById('topProductsList');
            try {
                const res = await fetch('/api/dashboard/top-products');
                if (!res.ok) throw new Error('Failed');
                const items = await res.json();
                if (items.length === 0) {
                    el.innerHTML = '<div class="empty-state empty-state-sm">No sales data yet</div>';
                    return;
                }
                el.innerHTML = items.map((p, i) => {
                    const rankClass = i === 0 ? ' rank-1' : i === 1 ? ' rank-2' : i === 2 ? ' rank-3' : '';
                    return `
                        <div class="top-product-item">
                            <div class="top-product-rank${rankClass}">${i + 1}</div>
                            <div class="top-product-info">
                                <div class="top-product-name">${escapeHtml(p.name)}</div>
                                <div class="top-product-sku">${escapeHtml(p.sku)}</div>
                            </div>
                            <div class="top-product-stats">
                                <div class="top-product-qty">${p.total_qty} sold</div>
                                <div class="top-product-rev">₱${Number(p.total_revenue).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
                            </div>
                        </div>`;
                }).join('');
            } catch (e) {
                el.innerHTML = '<div class="empty-state empty-state-sm">Unable to load data</div>';
                console.error('Top products error:', e);
            }
        }

        // ── Recent Sales (SS-24) ───────────────────────────────
        async function loadRecentSales() {
            const body = document.getElementById('recentSalesBody');
            if (!body) return;
            try {
                const res = await fetch('/api/dashboard/recent-sales');
                if (!res.ok) throw new Error('Failed');
                const items = await res.json();
                settle(body);
                if (items.length === 0) {
                    body.innerHTML = '<tr><td colspan="5" class="empty-state">No sales recorded yet</td></tr>';
                    return;
                }
                body.innerHTML = items.map(s => `
                    <tr>
                        <td data-label="Date">${escapeHtml(s.date)}</td>
                        <td data-label="Product"><strong>${escapeHtml(s.product_name)}</strong></td>
                        <td data-label="Qty">${escapeHtml(s.quantity)}</td>
                        <td data-label="Unit Price">₱${Number(s.unit_price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td data-label="Line Total">₱${Number(s.line_total).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    </tr>`).join('');
            } catch (e) {
                settle(body);
                body.innerHTML = '<tr><td colspan="5" class="empty-state">Unable to load recent sales</td></tr>';
                console.error('Recent sales error:', e);
            }
        }

        // ── Recent Stock-Ins ────────────────────────────────────
        async function loadRecentStockIns() {
            const body = document.getElementById('stockInBody');
            try {
                const res = await fetch('/api/dashboard/recent-stockins');
                if (!res.ok) throw new Error('Failed');
                const items = await res.json();
                settle(body);
                if (items.length === 0) {
                    body.innerHTML = '<tr><td colspan="6" class="empty-state">No stock-in records yet</td></tr>';
                    return;
                }
                body.innerHTML = items.map(s => `
                    <tr>
                        <td data-label="Date">${escapeHtml(s.date)}</td>
                        <td data-label="Product"><strong>${escapeHtml(s.product_name)}</strong></td>
                        <td data-label="Supplier">${escapeHtml(s.supplier_name)}</td>
                        <td data-label="Qty">${s.quantity}</td>
                        <td data-label="Unit">${escapeHtml(s.unit)}</td>
                        <td data-label="Staff">${escapeHtml(s.staff_name)}</td>
                    </tr>`).join('');
            } catch (e) {
                settle(body);
                body.innerHTML = '<tr><td colspan="6" class="empty-state">Unable to load stock-in data</td></tr>';
                console.error('Stock-in load error:', e);
            }
        }

        // ── SS-35 Restock Suggestions ───────────────────────────
        // Reads the pre-computed rows written by the nightly `forecast:orders`
        // job (BRD: forecasting runs as a scheduled task so the page does not
        // slow down). No demand-window selector: the BRD fixes it at 30 days.
        async function loadRestockSuggestions() {
            const body = document.getElementById('suggestBody');
            try {
                const res = await fetch('/api/dashboard/restock-suggestions');
                if (!res.ok) throw new Error('Failed (' + res.status + ')');
                const d = await res.json();
                const items = d.suggestions || [];
                if (items.length === 0) {
                    body.innerHTML = '<tr><td colspan="7" class="empty-state">All stocks healthy — no restock needed right now.</td></tr>';
                    return;
                }
                body.innerHTML = items.map(s => `
                    <tr>
                        <td data-label="Urgency"><span class="urgency-pill urgency-${escapeHtml(s.urgency)}">${escapeHtml(s.urgency)}</span></td>
                        <td data-label="Product"><strong>${escapeHtml(s.name)}</strong><br><span class="suggest-sku">${escapeHtml(s.sku)}</span></td>
                        <td data-label="Stock / Threshold">${s.current_stock} / ${s.reorder_threshold}</td>
                        <td data-label="Sold / Avg per day">${s.sold_in_window} sold <span class="suggest-avg">· ${s.avg_daily}/day</span></td>
                        <td data-label="Days left">${s.days_until_out === null ? '—' : s.days_until_out + ' days'}</td>
                        <td data-label="Suggested order" class="suggest-qty">+${s.suggested_qty} pcs</td>
                        <td data-label="Why" class="suggest-why">${escapeHtml(briefReason(s.reason))}</td>
                    </tr>`).join('');
            } catch (e) {
                body.innerHTML = '<tr><td colspan="7" class="empty-state">Unable to load suggestions</td></tr>';
                console.error('Suggestions load error:', e);
            }
        }

        // ── SS-25/SS-34 Export Summary CSV ──────────────────────
        function exportSummaryCsv() {
            window.location.href = '/api/dashboard/transactions/export';
        }

        /* Overview-only trim of the "Why" text. The stored reason is a full
           sentence pair joined by an em dash; this column is a summary, so
           everything from the dash onward is dropped. Done at render time on
           purpose: the reason in the database and on the full Order
           Suggestions page stays complete. */
        function briefReason(text) {
            if (!text) return '';
            const parts = String(text).split(/\s+[—–-]\s+/);
            // No dash: the reason is already a single short sentence, so it is
            // left exactly as stored (trailing period included).
            if (parts.length === 1) return String(text).trim();
            return parts[0].replace(/[.\s]+$/, '').trim();
        }

        // ── Helpers ─────────────────────────────────────────────
        function settle(body) {
            // No-op - loading states removed
        }

        function escapeHtml(text) {
            const d = document.createElement('div');
            d.textContent = text || '';
            return d.innerHTML;
        }

        // Exposed for inline onchange/onclick handlers in markup above.
        window.loadRestockSuggestions = loadRestockSuggestions;
        window.exportSummaryCsv = exportSummaryCsv;

        // ── Init ────────────────────────────────────────────────
        // Show loading messages initially
        document.getElementById('suggestBody').innerHTML = '<tr><td colspan="7" class="empty-state">Loading suggestions...</td></tr>';
        document.getElementById('recentSalesBody').innerHTML = '<tr><td colspan="5" class="empty-state">Loading recent sales...</td></tr>';
        document.getElementById('stockInBody').innerHTML = '<tr><td colspan="6" class="empty-state">Loading stock-in activity...</td></tr>';
        loadStats();
        loadRevenueChart();
        loadTopProducts();
        loadRecentSales();
        loadRecentStockIns();
        loadRestockSuggestions();
    })();
    </script>
@endpush
