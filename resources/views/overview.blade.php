@extends('layouts.app')

@section('title', 'Overview - Smart-Stock')

@section('content')
<div class="ov">

    <header class="page-header hero">
        <div class="hero-decor" aria-hidden="true">
            <span class="hero-orb orb-1"></span>
            <span class="hero-orb orb-2"></span>
            <span class="hero-orb orb-3"></span>
            <span class="hero-confetti cf-1"></span>
            <span class="hero-confetti cf-2"></span>
            <span class="hero-confetti cf-3"></span>
            <span class="hero-confetti cf-4"></span>
            <span class="hero-confetti cf-5"></span>
            <span class="hero-confetti cf-6"></span>
            <span class="hero-ring ring-1"></span>
            <span class="hero-ring ring-2"></span>
        </div>
        <div class="hero-content">
            <span class="hero-eyebrow"><span class="hero-wave">👋</span> Welcome back</span>
            <h1 class="page-title">Overview</h1>
            <p class="page-subtitle">Hello {{ ucfirst(Auth::user()->role) }} {{ ucwords(Auth::user()->name) }}, here is and overview of your inventory dashboard.</p>
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
        <div class="chart-panel is-loading" id="revenuePanel">
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
                <div class="chart-loading" id="chartLoading"><span class="spinner"></span> Loading chart data...</div>
            </div>
        </div>
        <!-- Top Selling Products (25%) -->
        <div class="top-products-panel is-loading" id="topPanel">
            <div class="section-header">
                <h2 class="section-title">Top Selling</h2>
                <div class="panel-controls">
                    <a href="{{ route('products') }}" class="panel-action" title="View all products" aria-label="View all products">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </a>
                </div>
            </div>
            <div class="top-products-list" id="topProductsList">
                <div class="list-loading" id="topLoading"><span class="spinner"></span> Loading top products...</div>
            </div>
        </div>
    </div>

    <!-- ====== SS-35: RESTOCK SUGGESTIONS (Admin only, demand-based) ====== -->
    <div class="content-panel is-loading" id="suggestPanel">
        <div class="section-header">
            <h2 class="section-title">Order Suggestions</h2>
            <div class="panel-controls">
                <label for="suggestWindow" class="control-label">Demand window:</label>
                <select id="suggestWindow" class="control-select" onchange="loadRestockSuggestions()">
                    <option value="7">Last 7 days</option>
                    <option value="14">Last 14 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="60">Last 60 days</option>
                    <option value="90">Last 90 days</option>
                </select>
                <a href="{{ route('stock-in') }}" class="panel-action" title="Receive new stock" aria-label="Receive new stock">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                </a>
            </div>
        </div>
        <div class="table-wrapper table-scroll is-loading" id="suggestTable">
            <div class="table-loading" id="suggestLoading"><span class="spinner"></span> Loading order suggestions...</div>
            <table>
                <thead>
                    <tr>
                        <th>Urgency</th>
                        <th>Product</th>
                        <th>Stock / Threshold</th>
                        <th>Sold (window)</th>
                        <th>Avg / day</th>
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
        <div class="content-panel is-loading" id="salesPanel">
            <div class="section-header">
                <h2 class="section-title">Transaction History</h2>
                <div class="panel-controls">
                    <button class="btn-export-sm" onclick="exportSummaryCsv()" title="Download transaction summary as CSV (Excel-compatible)">⬇ Export Summary CSV</button>
                    <a href="{{ route('transactions') }}" class="panel-action" title="View all transactions" aria-label="View all transactions">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </a>
                </div>
            </div>
            <div class="table-wrapper table-scroll is-loading" id="recentSalesTable">
                <div class="table-loading" id="salesLoading"><span class="spinner"></span> Loading recent sales...</div>
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
        <div class="content-panel is-loading" id="stockInPanel">
            <div class="section-header">
                <h2 class="section-title">Recent Stock-In Activity</h2>
                <div class="panel-controls">
                    <a href="{{ route('stock-in') }}" class="panel-action" title="View all stock-in records" aria-label="View all stock-in records">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </a>
                </div>
            </div>
            <div class="table-wrapper table-scroll is-loading" id="stockInTable">
                <div class="table-loading" id="stockInLoading"><span class="spinner"></span> Loading recent stock-ins...</div>
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
            /* Height system — loaded height derives from the SAME row math.
               Loading height is intentionally compact: spinner-only strip,
               then the panel expands to full height once data arrives. */
            --row-h:      44px;   /* one height for every data row          */
            --th-h:       38px;   /* one height for every header row        */
            --list-rows:  5;      /* rows every panel shows (matches API)   */
            --header-row: 42px;   /* section header (30px control + 12 gap)  */
            --loading-body-h: 64px; /* compact spinner-only body height    */

            /* Order Suggestions is the only list that is NOT capped at 5 by
               the API, so it reserves room for more and grows past that
               instead of hiding rows behind a scrollbar. */
            --suggest-rows: 8;
            --suggest-h:   calc(var(--th-h) + var(--row-h) * var(--suggest-rows));

            --list-h:     calc(var(--row-h) * var(--list-rows));               /* 220 */
            --table-h:    calc(var(--th-h) + var(--row-h) * var(--list-rows));  /* 258 */
            /* Panel = padding + header + body. Chart row uses --list-h as its
               body, the table panels use --table-h — both are 5 rows tall, so
               every section on the page ends up the same overall height. */
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
            min-height: 190px;
            display: flex;
            align-items: flex-end;
            justify-content: flex-start;
            padding: var(--sp-10) var(--sp-10) var(--sp-9);
            background:
                radial-gradient(600px 220px at 85% -20%, rgba(96,165,250,0.28), transparent 60%),
                radial-gradient(480px 200px at 10% 120%, rgba(74,222,128,0.14), transparent 60%),
                linear-gradient(115deg, #0b1526 0%, #13294f 45%, #1a3a7a 78%, #2563eb 130%);
            box-shadow: 0 12px 32px rgba(2,6,23,0.35);
        }
        .ov .hero-decor { position: absolute; inset: 0; pointer-events: none; }
        .ov .hero-orb { position: absolute; border-radius: 50%; filter: blur(2px); opacity: 0.55; }
        .ov .orb-1 { width: 220px; height: 220px; right: -50px; top: -80px;
            background: radial-gradient(circle at 30% 30%, rgba(147,197,253,0.7), rgba(37,99,235,0.15) 70%); }
        .ov .orb-2 { width: 130px; height: 130px; right: 190px; bottom: -55px;
            background: radial-gradient(circle at 30% 30%, rgba(74,222,128,0.5), transparent 70%); opacity: 0.35; }
        .ov .orb-3 { width: 90px; height: 90px; right: 46%; top: -30px;
            background: radial-gradient(circle at 30% 30%, rgba(251,191,36,0.55), transparent 70%); opacity: 0.3; }
        .ov .hero-ring { position: absolute; border-radius: 50%; border: 1.5px solid rgba(147,197,253,0.25); }
        .ov .ring-1 { width: 300px; height: 300px; right: -90px; top: -120px; }
        .ov .ring-2 { width: 200px; height: 200px; right: 120px; bottom: -110px; border-color: rgba(74,222,128,0.18); }
        .ov .hero-confetti { position: absolute; border-radius: 3px; opacity: 0.8; animation: heroFloat 5s ease-in-out infinite; }
        .ov .cf-1 { width: 9px; height: 9px; left: 42%; top: 22px; background: #fbbf24; transform: rotate(18deg); }
        .ov .cf-2 { width: 7px; height: 7px; left: 55%; top: 52px; background: #4ade80; border-radius: 50%; animation-delay: 0.8s; }
        .ov .cf-3 { width: 8px; height: 8px; left: 68%; top: 26px; background: #f472b6; transform: rotate(-14deg); animation-delay: 1.6s; }
        .ov .cf-4 { width: 6px; height: 6px; left: 78%; top: 62px; background: #93c5fd; border-radius: 50%; animation-delay: 2.2s; }
        .ov .cf-5 { width: 8px; height: 8px; left: 34%; bottom: 30px; background: #60a5fa; transform: rotate(30deg); animation-delay: 1.1s; }
        .ov .cf-6 { width: 6px; height: 6px; left: 88%; bottom: 36px; background: #fde68a; border-radius: 50%; animation-delay: 2.8s; }
        @keyframes heroFloat { 0%,100% { translate: 0 0; opacity: 0.55; } 50% { translate: 0 -9px; opacity: 1; } }
        .ov .hero-content { position: relative; z-index: 1; max-width: 640px; }
        .ov .hero-eyebrow {
            display: inline-flex; align-items: center; gap: var(--sp-2);
            font-size: var(--fs-label); font-weight: 700; letter-spacing: var(--track-caps); text-transform: uppercase;
            color: #bfdbfe; background: rgba(147,197,253,0.14); border: 1px solid rgba(147,197,253,0.3);
            padding: var(--sp-1) var(--sp-4); border-radius: 20px; margin-bottom: var(--sp-3);
        }
        .ov .hero-wave { display: inline-block; animation: heroWave 2.2s ease-in-out infinite; transform-origin: 70% 70%; }
        @keyframes heroWave { 0%,100% { transform: rotate(0); } 25% { transform: rotate(18deg); } 50% { transform: rotate(-8deg); } 75% { transform: rotate(14deg); } }
        .ov .page-header.hero .page-title {
            font-size: 30px;
            font-weight: 800;
            line-height: var(--lh-tight);
            letter-spacing: -0.02em;
            color: #ffffff;
            text-shadow: 0 2px 14px rgba(2,6,23,0.45);
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
        /* Compact-then-expand: loading = short spinner strip, loaded = full
           --panel-h. Height animates so the form visibly grows when data lands. */
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
            transition: height 0.4s ease;
        }
        .ov .chart-panel.is-loading,
        .ov .top-products-panel.is-loading {
            height: calc(var(--panel-pad) * 2 + var(--header-row) + var(--loading-body-h));
        }
        .ov .chart-container {
            position: relative;
            width: 100%;
            flex: 1;
            min-height: 0; /* lets the canvas shrink instead of overflowing */
            transition: min-height 0.4s ease, height 0.4s ease;
        }
        .ov .chart-panel.is-loading .chart-container {
            flex: 0 0 auto;
            height: var(--loading-body-h);
            min-height: var(--loading-body-h);
        }
        .ov .chart-panel.is-loading canvas { display: none; }
        .ov .chart-container canvas { width: 100% !important; height: 100% !important; }
        .ov .chart-loading,
        .ov .list-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--sp-3);
            width: 100%;
            color: #64748b;
            font-size: var(--fs-md);
        }
        .ov .chart-loading { position: absolute; inset: 0; }
        .ov .chart-panel.is-loading .chart-loading { position: static; height: var(--loading-body-h); }

        /* === TOP PRODUCTS LIST === */
        /* Loaded = --list-h (exactly 5 rows). Loading = compact spinner strip,
           then the panel expands to full height when rows arrive. */
        .ov .top-products-list {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: var(--list-h);
            transition: min-height 0.4s ease;
        }
        .ov .top-products-panel.is-loading .top-products-list { min-height: var(--loading-body-h); flex: 0 0 auto; }
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
            transition: min-height 0.4s ease;
        }
        /* Suggest panel: compact while spinner shows, expands when rows land. */
        .ov .content-panel.is-loading { min-height: 0; }
        .ov .content-panel .table-wrapper { background: transparent; border: none; border-radius: 0; }

        /* === STACKED ACTIVITY ROWS (Transaction History, then Stock-Ins) ===
           Stacked full-width instead of a 50/50 grid: six columns in a half
           panel were being clipped, and stacking gives each table the whole
           page width so every column is readable with no scrollbar.
           Compact-then-expand: loading = short spinner strip, loaded = full
           --panel-h with a smooth grow animation. */
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
            height: var(--panel-h);
            margin-bottom: 0;
            overflow: hidden;
            transition: height 0.4s ease, min-height 0.4s ease;
        }
        .ov .split-row .content-panel.is-loading {
            height: calc(var(--panel-pad) * 2 + var(--header-row) + var(--th-h) + var(--loading-body-h));
            min-height: 0;
        }
        /* These two tables are hard-capped at 5 rows by the API, so they are
           never taller than the reservation — the inner vertical scrollbar
           this used to force could only ever appear as empty dead space. */
        .ov .split-row .table-wrapper {
            flex: 1;
            min-height: var(--table-h);
            overflow-y: visible;
            transition: min-height 0.4s ease;
        }
        .ov .split-row .content-panel.is-loading .table-wrapper {
            flex: 0 0 auto;
            min-height: var(--loading-body-h);
        }
        .ov .top-products-list { min-height: var(--list-h); }
        /* Full-width tables have room to spare, so long names/suppliers
           ellipsise only as a safety net instead of stretching the column. */
        .ov .split-row td { white-space: nowrap; }
        .ov .split-row td:nth-child(2),
        .ov .split-row td:nth-child(3) {
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* === TABLE — identical cell rhythm for every table on the page === */
        .ov .table-wrapper {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            overflow-x: auto;
        }
        /* Loaded = full reserved height. Loading (.is-loading) = compact
           spinner-only strip, then the panel expands when data lands. */
        .ov .table-scroll {
            position: relative;
            min-height: var(--table-h);
            max-height: var(--table-h);
            overflow-y: auto;
            scrollbar-width: thin;
            transition: min-height 0.4s ease, max-height 0.4s ease;
        }
        .ov .table-scroll.is-loading {
            min-height: calc(var(--th-h) + var(--loading-body-h));
            max-height: calc(var(--th-h) + var(--loading-body-h));
            overflow: hidden;
        }
        /* Spinner row: static centered strip while loading, removed on settle. */
        .ov .table-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--sp-3);
            color: #64748b;
            font-size: var(--fs-md);
            height: var(--loading-body-h);
            min-height: var(--loading-body-h);
        }
        .ov .table-scroll.is-loading table tbody:empty { display: none; }
        /* Full width now, so no clipping is needed — but long text still
           ellipsises rather than stretching a column out of shape. */
        .ov .split-row td:nth-child(2),
        .ov .split-row td:nth-child(3) { max-width: 260px; }
        /* Order Suggestions: loaded = --suggest-h (8 rows) but never
           scroll-capped — if there are 7 or 12 suggestions they are all shown
           and the panel grows. Loading = compact spinner strip, then expands.
           No inner scrollbar, ever. */
        .ov #suggestTable.table-scroll {
            min-height: var(--suggest-h);
            max-height: none;
            overflow-y: visible;
        }
        .ov #suggestTable.table-scroll.is-loading {
            min-height: calc(var(--th-h) + var(--loading-body-h));
            max-height: calc(var(--th-h) + var(--loading-body-h));
            overflow: hidden;
        }
        .ov table { width: 100%; border-collapse: collapse; }
        /* Full-width tables need a floor to avoid crushing columns.
           The stacked activity tables have the whole page, so no floor. */
        .ov #suggestTable table { min-width: 680px; }
        .ov .split-row table { min-width: 0; }
        .ov thead th {
            background: rgba(255,255,255,0.03);
            padding: var(--sp-3) var(--cell-x);
            text-align: left;
            font-size: var(--fs-label);
            font-weight: 600;
            line-height: var(--lh-tight);
            letter-spacing: var(--track-caps);
            text-transform: uppercase;
            color: #64748b;
            white-space: nowrap;
        }
        /* Fixed row height + middle alignment is what makes the three tables
           read as one system. Variable row heights were the source of the
           ragged, inconsistent rhythm across sections. */
        .ov tbody tr {
            height: var(--row-h);
            border-top: 1px solid rgba(255,255,255,0.04);
            transition: background 0.15s;
        }
        .ov tbody tr:hover { background: rgba(255,255,255,0.02); }
        .ov tbody td {
            padding: var(--sp-1) var(--cell-x);
            font-size: var(--fs-md);
            line-height: var(--lh-body);
            color: #cbd5e1;
            vertical-align: middle;
        }
        /* The one-line-taller suggestion cells (name over sku) stay inside the
           same fixed row height instead of stretching it. */
        .ov tbody td:has(> br) { line-height: 1.35; }
        .ov tbody td strong { font-weight: 600; }

        /* === LOADING vs LOADED — compact-then-expand ===
           Loading (.is-loading) = short spinner-only strip (~64px body).
           Loaded = full reserved height (--table-h / --list-h / --suggest-h).
           The height transition above animates the grow. Loaded rows fade in. */
        .ov .table-scroll:not(.is-loading) tbody tr,
        .ov .top-products-panel:not(.is-loading) .top-product-item {
            animation: ovFadeUp 0.35s ease both;
        }
        .ov .table-scroll:not(.is-loading) tbody tr:nth-child(2) { animation-delay: 0.03s; }
        .ov .table-scroll:not(.is-loading) tbody tr:nth-child(3) { animation-delay: 0.06s; }
        .ov .table-scroll:not(.is-loading) tbody tr:nth-child(4) { animation-delay: 0.09s; }
        .ov .table-scroll:not(.is-loading) tbody tr:nth-child(5) { animation-delay: 0.12s; }
        @keyframes ovFadeUp { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        /* Empty state fills the loaded body so an empty panel still looks
           intentional instead of collapsing to just the header. */
        .ov .empty-state {
            text-align: center;
            color: #475569;
            padding: var(--sp-8) var(--cell-x);
            font-size: var(--fs-base);
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
        .ov .suggest-why { font-size: var(--fs-sm); line-height: 1.5; color: #94a3b8; max-width: 260px; }
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
        body.light-theme .ov .page-header.hero { border-color: rgba(37,99,235,0.18); box-shadow: 0 12px 28px rgba(37,99,235,0.18); }
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
        body.light-theme .ov .table-wrapper { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme .ov .table-loading { color: #94a3b8; }
        body.light-theme .ov thead th { background: #f8fafc; color: #64748b; }
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
                --row-h:      42px;
                --th-h:       36px;
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
            .ov #suggestTable table { min-width: 640px; }
            .ov .suggest-why { max-width: 200px; }
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
        // Compact-then-expand: panel starts short, expands to full height
        // when data lands. Chart renders AFTER expand so it measures full box.
        async function loadRevenueChart() {
            const panel = document.getElementById('revenuePanel');
            const loading = document.getElementById('chartLoading');
            try {
                const res = await fetch('/api/dashboard/revenue-chart');
                if (!res.ok) throw new Error('Failed to load chart');
                const d = await res.json();
                if (panel) panel.classList.remove('is-loading');
                if (loading) loading.remove();
                renderChart(d.labels, d.values);
                requestAnimationFrame(() => { if (revenueChartInstance) revenueChartInstance.resize(); });
            } catch (e) {
                if (panel) panel.classList.remove('is-loading');
                if (loading) loading.innerHTML = '<span class="empty-state-sm" style="color:#f87171;">Unable to load chart</span>';
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
        // Compact-then-expand: panel starts short, grows to full height on rows.
        async function loadTopProducts() {
            const panel = document.getElementById('topPanel');
            const el = document.getElementById('topProductsList');
            const loading = document.getElementById('topLoading');
            try {
                const res = await fetch('/api/dashboard/top-products');
                if (!res.ok) throw new Error('Failed');
                const items = await res.json();
                if (loading) loading.remove();
                if (panel) panel.classList.remove('is-loading');
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
                if (loading) loading.remove();
                if (panel) panel.classList.remove('is-loading');
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
                        <td>${escapeHtml(s.date)}</td>
                        <td><strong>${escapeHtml(s.product_name)}</strong></td>
                        <td>${escapeHtml(s.quantity)}</td>
                        <td>₱${Number(s.unit_price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td>₱${Number(s.line_total).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
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
                        <td>${escapeHtml(s.date)}</td>
                        <td><strong>${escapeHtml(s.product_name)}</strong></td>
                        <td>${escapeHtml(s.supplier_name)}</td>
                        <td>${s.quantity}</td>
                        <td>${escapeHtml(s.unit)}</td>
                        <td>${escapeHtml(s.staff_name)}</td>
                    </tr>`).join('');
            } catch (e) {
                settle(body);
                body.innerHTML = '<tr><td colspan="6" class="empty-state">Unable to load stock-in data</td></tr>';
                console.error('Stock-in load error:', e);
            }
        }

        // ── SS-35 Restock Suggestions ───────────────────────────
        // Reload (window change) re-enters compact spinner state so it
        // shrinks then expands again, same as first load.
        async function loadRestockSuggestions() {
            const body = document.getElementById('suggestBody');
            const winEl = document.getElementById('suggestWindow');
            const days = winEl ? winEl.value : 30;
            const wrap = document.getElementById('suggestTable');
            const panel = document.getElementById('suggestPanel');
            if (wrap && !wrap.classList.contains('is-loading')) {
                wrap.classList.add('is-loading');
                if (panel) panel.classList.add('is-loading');
                if (!wrap.querySelector('.table-loading')) {
                    const d = document.createElement('div');
                    d.className = 'table-loading';
                    d.innerHTML = '<span class="spinner"></span> Loading order suggestions...';
                    wrap.prepend(d);
                }
            }
            try {
                const res = await fetch('/api/dashboard/restock-suggestions?days=' + encodeURIComponent(days));
                if (!res.ok) throw new Error('Failed (' + res.status + ')');
                const d = await res.json();
                const items = d.suggestions || [];
                settle(body);
                if (items.length === 0) {
                    body.innerHTML = '<tr><td colspan="8" class="empty-state">All stocks healthy — no restock needed right now.</td></tr>';
                    return;
                }
                body.innerHTML = items.map(s => `
                    <tr>
                        <td><span class="urgency-pill urgency-${escapeHtml(s.urgency)}">${escapeHtml(s.urgency)}</span></td>
                        <td><strong>${escapeHtml(s.name)}</strong><br><span class="suggest-sku">${escapeHtml(s.sku)}</span></td>
                        <td>${s.current_stock} / ${s.reorder_threshold}</td>
                        <td>${s.sold_in_window} pcs</td>
                        <td>${s.avg_daily}/day</td>
                        <td>${s.days_until_out === null ? '—' : s.days_until_out + ' days'}</td>
                        <td class="suggest-qty">+${s.suggested_qty} pcs</td>
                        <td class="suggest-why">${escapeHtml(s.reason)}</td>
                    </tr>`).join('');
            } catch (e) {
                settle(body);
                body.innerHTML = '<tr><td colspan="8" class="empty-state">Unable to load suggestions</td></tr>';
                console.error('Suggestions load error:', e);
            }
        }

        // ── SS-25/SS-34 Export Summary CSV ──────────────────────
        function exportSummaryCsv() {
            window.location.href = '/api/dashboard/transactions/export';
        }

        // ── Helpers ─────────────────────────────────────────────
        /* Compact-then-expand: drop is-loading from table AND panel so CSS
           grows from 64px spinner strip to full height. Called on BOTH
           success and failure so a broken endpoint never leaves a panel
           stuck short. */
        function settle(body) {
            if (!body) return;
            body.classList.remove('is-loading');
            const wrap = body.closest('.table-scroll');
            if (wrap) {
                wrap.classList.remove('is-loading');
                const loader = wrap.querySelector('.table-loading');
                if (loader) loader.remove();
                const panel = wrap.closest('.content-panel');
                if (panel) panel.classList.remove('is-loading');
            }
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
        loadStats();
        loadRevenueChart();
        loadTopProducts();
        loadRecentSales();
        loadRecentStockIns();
        loadRestockSuggestions();
    })();
    </script>
@endpush
