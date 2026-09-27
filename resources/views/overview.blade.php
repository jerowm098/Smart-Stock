@extends('layouts.app')

@section('title', 'Overview - Smart-Stock')

@section('content')
    <h1 class="page-title">Overview</h1>
    <p class="page-subtitle">Hello {{ ucfirst(Auth::user()->role) }} {{ ucwords(Auth::user()->name) }}, here is your inventory dashboard.</p>

    <!-- ====== STAT CARDS (clickable) ====== -->
    <div class="stats-grid">
        @if(Auth::user()->isCashier())
        <a href="{{ route('pos') }}" class="stat-card stat-clickable" title="View POS">
        @else
        <div class="stat-card stat-clickable" title="View POS" onclick="showRevenueNote()" style="cursor:pointer;">
        @endif
            <div class="stat-card-header">
                <h3>Total Revenue</h3>
                <span class="stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </span>
            </div>
            <div class="stat-value blue" id="statRevenue">₱0.00</div>
            <div class="stat-subtext">All time sales</div>
        @if(Auth::user()->isCashier())
        </a>
        @else
        </div>
        @endif
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
        <div class="chart-panel">
            <div class="section-header">
                <h2 class="section-title">Revenue Overview</h2>
                <span class="chart-period-badge">Last 30 Days</span>
            </div>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
                <div class="chart-loading" id="chartLoading"><span class="spinner"></span> Loading chart data...</div>
            </div>
        </div>
        <!-- Top Selling Products (25%) -->
        <div class="top-products-panel">
            <div class="section-header">
                <h2 class="section-title">Top Selling</h2>
                <a href="{{ route('products') }}" class="section-link">View All →</a>
            </div>
            <div class="top-products-list" id="topProductsList">
                <div class="list-loading" id="topLoading"><span class="spinner"></span> Loading top products...</div>
            </div>
        </div>
    </div>

    <!-- ====== SS-35: RESTOCK SUGGESTIONS (Admin only, demand-based) ====== -->
    <div class="section-header" style="margin-top: 28px;">
        <h2 class="section-title">Order Suggestions <span class="suggest-badge" id="suggestCount">—</span></h2>
        <div class="suggest-controls">
            <label for="suggestWindow" class="suggest-label">Demand window:</label>
            <select id="suggestWindow" class="suggest-select" onchange="loadRestockSuggestions()">
                <option value="7">Last 7 days</option>
                <option value="14">Last 14 days</option>
                <option value="30" selected>Last 30 days</option>
                <option value="60">Last 60 days</option>
                <option value="90">Last 90 days</option>
            </select>
            <a href="{{ route('stock-in') }}" class="section-link">Receive Stock →</a>
        </div>
    </div>
    <p class="suggest-hint">Auto-computed mula sa aktwal na benta: avg daily × 14 days cover, minimum 2× reorder threshold. Critical muna, tapos mauubos agad, tapos pinakamalaking order.</p>
    <div class="table-wrapper" id="suggestTable">
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
            <tbody id="suggestBody">
                <tr>
                    <td colspan="8" class="empty-state">
                        <span class="spinner" aria-hidden="true"></span> Loading order suggestions...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ====== SS-24: TRANSACTION HISTORY LINK + SS-25/SS-34 EXPORT ====== -->
    <div class="section-header" style="margin-top: 28px;">
        <h2 class="section-title">Transaction History</h2>
        <div class="suggest-controls">
            <button class="btn-export-sm" onclick="exportSummaryCsv()" title="Download transaction summary as CSV (Excel-compatible)">⬇ Export Summary CSV</button>
            <a href="{{ route('transactions') }}" class="section-link">View All Transactions →</a>
        </div>
    </div>
    <p class="suggest-hint">Buong listahan ng benta na may resibo number, petsa, cashier name, items, total, payment, at change. May search, filter by cashier at date, at pagination. Ang Export button ay nagda-download ng summary na Excel-compatible.</p>

    <!-- ====== RECENT STOCK-IN ACTIVITY ====== -->
    <div class="section-header" style="margin-top: 28px;">
        <h2 class="section-title">Recent Stock-In Activity</h2>
        <a href="{{ route('stock-in') }}" class="section-link">View All →</a>
    </div>
    <div class="table-wrapper" id="stockInTable">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Supplier</th>
                    <th>Qty Received</th>
                    <th>Unit</th>
                    <th>Staff</th>
                </tr>
            </thead>
            <tbody id="stockInBody">
                <tr>
                    <td colspan="6" class="empty-state">
                        <span class="spinner" aria-hidden="true"></span> Loading recent stock-ins...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Revenue Note Modal -->
    <div class="note-overlay" id="revenueNoteModal">
        <div class="note-card">
            <div class="note-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            </div>
            <h3 class="note-title">POS Access Restricted</h3>
            <p class="note-message">The Point of Sale (POS) system is exclusively available for cashier accounts. As an admin, you can monitor revenue from this dashboard or assign a cashier to process transactions.</p>
            <button class="note-btn" onclick="closeRevenueNote()">Okay, Got It</button>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .note-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 300;
            align-items: center;
            justify-content: center;
        }
        .note-overlay.active { display: flex; }
        .note-card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 32px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            animation: noteIn 0.2s ease-out;
        }
        @keyframes noteIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        body.light-theme .note-card { background: #ffffff; border-color: rgba(15,23,42,0.1); }
        .note-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(59,130,246,0.12);
            color: #60a5fa;
            margin-bottom: 16px;
        }
        body.light-theme .note-icon { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .note-title {
            font-size: 17px;
            font-weight: 700;
            color: #f8fafc;
            margin: 0 0 10px 0;
        }
        body.light-theme .note-title { color: #0f172a; }
        .note-message {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.6;
            margin: 0 0 24px 0;
        }
        body.light-theme .note-message { color: #64748b; }
        .note-btn {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 28px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: opacity 0.15s;
        }
        .note-btn:hover { opacity: 0.9; }
    </style>
@endpush

@push('scripts')
    <script>
        function showRevenueNote() {
            document.getElementById('revenueNoteModal').classList.add('active');
        }
        function closeRevenueNote() {
            document.getElementById('revenueNoteModal').classList.remove('active');
        }
        document.getElementById('revenueNoteModal').addEventListener('click', function(e) {
            if (e.target === this) closeRevenueNote();
        });
    </script>
@endpush

@push('styles')
    <style>
        /* === PAGE HEADER === */
        .page-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 6px; }
        .page-subtitle { color: #64748b; font-size: 14px; margin-bottom: 24px; }

        /* === STAT CARDS === */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.15s, border-color 0.15s, background 0.15s;
            display: block;
            text-decoration: none;
        }
        .stat-clickable { cursor: pointer; }
        .stat-clickable:hover {
            transform: translateY(-2px);
            border-color: rgba(96,165,250,0.25);
            background: rgba(96,165,250,0.05);
        }
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .stat-card h3 {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 600;
            margin: 0;
        }
        .stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon.blue  { background: rgba(96,165,250,0.15);  color: #60a5fa; }
        .stat-icon.green { background: rgba(74,222,128,0.15);  color: #4ade80; }
        .stat-icon.yellow{ background: rgba(251,191,36,0.15);  color: #fbbf24; }
        .stat-icon.red   { background: rgba(248,113,113,0.15); color: #f87171; }
        .stat-value { font-size: 26px; font-weight: 700; color: #f8fafc; margin-bottom: 4px; }
        .stat-value.blue   { color: #60a5fa; }
        .stat-value.green  { color: #4ade80; }
        .stat-value.yellow { color: #fbbf24; }
        .stat-value.red    { color: #f87171; }
        .stat-subtext { font-size: 11px; color: #475569; }

        /* === CHART + TOP PRODUCTS ROW === */
        .chart-top-row {
            display: grid;
            grid-template-columns: 2.3fr 1.7fr;
            gap: 20px;
            margin-bottom: 0;
        }
        .chart-panel,
        .top-products-panel {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 20px;
        }
        .chart-panel { min-height: 320px; }
        .chart-container {
            position: relative;
            width: 100%;
            height: 260px;
        }
        .chart-container canvas { width: 100% !important; height: 100% !important; }
        .chart-loading {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #64748b;
            font-size: 13px;
        }
        .list-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #64748b;
            font-size: 13px;
            min-height: 200px;
            width: 100%;
        }
        .chart-period-badge {
            font-size: 11px;
            color: #60a5fa;
            background: rgba(96,165,250,0.1);
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
        }

        /* === TOP PRODUCTS LIST === */
        .top-products-panel { overflow: hidden; }
        .top-products-list { display: flex; flex-direction: column; gap: 0; }
        .top-product-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .top-product-item:last-child { border-bottom: none; }
        .top-product-rank {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(96,165,250,0.1);
            color: #60a5fa;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .top-product-rank.rank-1 { background: rgba(251,191,36,0.15); color: #fbbf24; }
        .top-product-rank.rank-2 { background: rgba(148,163,184,0.12); color: #94a3b8; }
        .top-product-rank.rank-3 { background: rgba(180,130,80,0.12); color: #c8956a; }
        .top-product-info { flex: 1; min-width: 0; }
        .top-product-name {
            font-size: 13px;
            font-weight: 600;
            color: #f8fafc;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .top-product-sku { font-size: 10px; color: #475569; font-family: monospace; }
        .top-product-stats { text-align: right; flex-shrink: 0; }
        .top-product-qty { font-size: 14px; font-weight: 700; color: #f8fafc; }
        .top-product-rev { font-size: 11px; color: #4ade80; font-weight: 500; }

        /* === SECTION HEADER / LINK === */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .section-title { font-size: 16px; font-weight: 600; color: #f8fafc; margin: 0; }
        .section-link { font-size: 12px; color: #60a5fa; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; }
        .section-link:hover { text-decoration: underline; }

        /* === TABLE === */
        .table-wrapper {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            overflow-x: auto;
        }
        table { width: 100%; min-width: 680px; border-collapse: collapse; }
        thead th {
            background: rgba(255,255,255,0.03);
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }
        tbody td { padding: 12px 16px; font-size: 13px; color: #cbd5e1; }
        .empty-state { text-align: center; color: #475569; padding: 48px; font-size: 14px; }

        /* === SS-35 RESTOCK SUGGESTIONS === */
        .suggest-badge {
            font-size: 11px;
            color: #fbbf24;
            background: rgba(251,191,36,0.12);
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 700;
            vertical-align: middle;
            margin-left: 6px;
        }
        .suggest-controls { display: flex; align-items: center; gap: 10px; }
        .suggest-label { font-size: 12px; color: #64748b; }
        .suggest-select {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            color: #e2e8f0;
            font-size: 12px;
            border-radius: 8px;
            padding: 6px 10px;
            outline: none;
        }
        .suggest-hint { font-size: 12px; color: #64748b; margin: 0 0 12px 0; }
        .urgency-pill {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }
        .urgency-critical { background: rgba(248,113,113,0.15); color: #f87171; }
        .urgency-low { background: rgba(251,191,36,0.15); color: #fbbf24; }
        .urgency-watch { background: rgba(96,165,250,0.12); color: #60a5fa; }
        .suggest-qty { font-weight: 700; color: #4ade80; font-size: 14px; }
        .suggest-why { font-size: 12px; color: #94a3b8; max-width: 260px; }
        .btn-export-sm {
            background: rgba(74,222,128,0.12); color: #4ade80; border: 1px solid rgba(74,222,128,0.3);
            border-radius: 8px; padding: 7px 14px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap;
            font-family: 'Inter', sans-serif;
        }
        .btn-export-sm:hover { background: rgba(74,222,128,0.2); }
        body.light-theme .suggest-select { background: #fff; border-color: rgba(15,23,42,0.12); color: #0f172a; }
        body.light-theme .suggest-hint { color: #94a3b8; }
        body.light-theme .suggest-why { color: #64748b; }
        body.light-theme .suggest-qty { color: #16a34a; }

        /* === LIGHT THEME === */
        body.light-theme .page-title { color: #0f172a; }
        body.light-theme .page-subtitle { color: #64748b; }
        body.light-theme .stat-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme .stat-card:hover { background: rgba(37,99,235,0.02); border-color: rgba(37,99,235,0.2); }
        body.light-theme .stat-card h3 { color: #64748b; }
        body.light-theme .stat-value { color: #0f172a; }
        body.light-theme .stat-value.blue   { color: #2563eb; }
        body.light-theme .stat-value.green  { color: #16a34a; }
        body.light-theme .stat-value.yellow { color: #d97706; }
        body.light-theme .stat-value.red    { color: #dc2626; }
        body.light-theme .stat-subtext { color: #94a3b8; }
        body.light-theme .stat-icon.blue   { background: rgba(37,99,235,0.1); }
        body.light-theme .stat-icon.green  { background: rgba(22,163,74,0.1); }
        body.light-theme .stat-icon.yellow { background: rgba(217,119,6,0.1); }
        body.light-theme .stat-icon.red    { background: rgba(220,38,38,0.1); }
        body.light-theme .chart-panel,
        body.light-theme .top-products-panel { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme .chart-period-badge { background: rgba(37,99,235,0.08); color: #2563eb; }
        body.light-theme .section-title { color: #0f172a; }
        body.light-theme .top-product-name { color: #0f172a; }
        body.light-theme .top-product-sku { color: #94a3b8; }
        body.light-theme .top-product-qty { color: #0f172a; }
        body.light-theme .top-product-rev { color: #16a34a; }
        body.light-theme .top-product-rank { background: rgba(37,99,235,0.08); color: #2563eb; }
        body.light-theme .top-product-rank.rank-1 { background: rgba(217,119,6,0.1); color: #d97706; }
        body.light-theme .table-wrapper { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme table thead th { background: #f8fafc; color: #64748b; }
        body.light-theme table tbody td { color: #475569; }
        body.light-theme table tbody tr:hover { background: rgba(15,23,42,0.025); }
        body.light-theme .empty-state { color: #94a3b8; }

        /* === MOBILE === */
        @media (max-width: 960px) {
            .chart-top-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .page-title { font-size: 20px; }
            .page-subtitle { font-size: 13px; margin-bottom: 20px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 20px; }
            .stat-card { padding: 16px 12px; }
            .stat-card h3 { font-size: 11px; }
            .stat-value { font-size: 22px; }
            .stat-icon { width: 30px; height: 30px; border-radius: 7px; }
            .stat-icon svg { width: 16px; height: 16px; }
            .chart-panel { min-height: 240px; }
            .chart-container { height: 200px; }
            .section-header { flex-wrap: wrap; gap: 8px; }
            .section-title { font-size: 15px; }
            .table-wrapper { border-radius: 10px; }
            thead th, tbody td { padding: 10px; font-size: 12px; }
            .empty-state { padding: 32px 16px; font-size: 13px; }
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
        async function loadRevenueChart() {
            const loading = document.getElementById('chartLoading');
            try {
                const res = await fetch('/api/dashboard/revenue-chart');
                if (!res.ok) throw new Error('Failed to load chart');
                const d = await res.json();
                if (loading) loading.style.display = 'none';
                renderChart(d.labels, d.values);
            } catch (e) {
                if (loading) loading.innerHTML = '<span style="color:#f87171;font-size:13px;">Unable to load chart</span>';
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
            const loading = document.getElementById('topLoading');
            try {
                const res = await fetch('/api/dashboard/top-products');
                if (!res.ok) throw new Error('Failed');
                const items = await res.json();
                if (loading) loading.remove();
                if (items.length === 0) {
                    el.innerHTML = '<div class="empty-state" style="padding:32px 12px;font-size:13px;">No sales data yet</div>';
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
                el.innerHTML = '<div class="empty-state" style="padding:32px 12px;font-size:13px;">Unable to load data</div>';
                console.error('Top products error:', e);
            }
        }

        // ── Recent Stock-Ins ────────────────────────────────────
        async function loadRecentStockIns() {
            const body = document.getElementById('stockInBody');
            try {
                const res = await fetch('/api/dashboard/recent-stockins');
                if (!res.ok) throw new Error('Failed');
                const items = await res.json();
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
                body.innerHTML = '<tr><td colspan="6" class="empty-state">Unable to load stock-in data</td></tr>';
                console.error('Stock-in load error:', e);
            }
        }

        // ── SS-35 Restock Suggestions ───────────────────────────
        async function loadRestockSuggestions() {
            const body = document.getElementById('suggestBody');
            const countEl = document.getElementById('suggestCount');
            const winEl = document.getElementById('suggestWindow');
            const days = winEl ? winEl.value : 30;
            try {
                const res = await fetch('/api/dashboard/restock-suggestions?days=' + encodeURIComponent(days));
                if (!res.ok) throw new Error('Failed (' + res.status + ')');
                const d = await res.json();
                const items = d.suggestions || [];
                if (countEl) countEl.textContent = d.count + ' items · ' + d.window_days + 'd window · ' + d.cover_days + 'd cover';
                if (items.length === 0) {
                    body.innerHTML = '<tr><td colspan="8" class="empty-state">All stocks healthy — no restock needed right now.</td></tr>';
                    return;
                }
                body.innerHTML = items.map(s => `
                    <tr>
                        <td><span class="urgency-pill urgency-${escapeHtml(s.urgency)}">${escapeHtml(s.urgency)}</span></td>
                        <td><strong>${escapeHtml(s.name)}</strong><br><span style="font-size:11px;color:#64748b;font-family:monospace;">${escapeHtml(s.sku)}</span></td>
                        <td>${s.current_stock} / ${s.reorder_threshold}</td>
                        <td>${s.sold_in_window} pcs</td>
                        <td>${s.avg_daily}/day</td>
                        <td>${s.days_until_out === null ? '—' : s.days_until_out + ' days'}</td>
                        <td class="suggest-qty">+${s.suggested_qty} pcs</td>
                        <td class="suggest-why">${escapeHtml(s.reason)}</td>
                    </tr>`).join('');
            } catch (e) {
                body.innerHTML = '<tr><td colspan="8" class="empty-state">Unable to load suggestions</td></tr>';
                console.error('Suggestions load error:', e);
            }
        }

        // ── SS-25/SS-34 Export Summary CSV ──────────────────────
        function exportSummaryCsv() {
            window.location.href = '/api/dashboard/transactions/export';
        }

        // ── Helpers ─────────────────────────────────────────────
        function escapeHtml(text) {
            const d = document.createElement('div');
            d.textContent = text || '';
            return d.innerHTML;
        }

        // ── Init ────────────────────────────────────────────────
        loadStats();
        loadRevenueChart();
        loadTopProducts();
        loadRecentStockIns();
        loadRestockSuggestions();
    })();
    </script>
@endpush
