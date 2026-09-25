<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart-Stock')</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; height: 100%; display: flex; flex-direction: column; overflow: hidden; }

        /* SIDEBAR */
        .sidebar { width: 240px; min-height: calc(100vh - 64px); background: #253347; border-right: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; position: fixed; top: 64px; left: 0; bottom: 0; z-index: 90; }
        .sidebar-nav { flex: 1; padding: 12px; overflow-y: auto; }
        .nav-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; padding: 12px 12px 6px; font-weight: 600; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.15s; margin-bottom: 6px; }
        .nav-item:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
        .nav-item.active { background: rgba(96,165,250,0.15); color: #60a5fa; }
        .nav-icon { width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; color: currentColor; }
        .nav-icon svg { display: block; }
        .mobile-menu-button { display: none; width: 36px; height: 36px; padding: 0; border: 0; border-radius: 8px; background: rgba(255,255,255,0.06); color: #f8fafc; cursor: pointer; align-items: center; justify-content: center; transition: background 0.15s; }
        .mobile-menu-button:hover, .mobile-menu-button:focus-visible { background: rgba(255,255,255,0.12); outline: none; }
        .mobile-menu-icon { position: relative; display: block; width: 16px; height: 12px; }
        .mobile-menu-icon span { position: absolute; left: 0; width: 16px; height: 2px; border-radius: 2px; background: currentColor; transition: transform 0.2s ease, opacity 0.2s ease; }
        .mobile-menu-icon span:nth-child(1) { top: 0; }
        .mobile-menu-icon span:nth-child(2) { top: 5px; }
        .mobile-menu-icon span:nth-child(3) { top: 10px; }
        .mobile-menu-button.mobile-open .mobile-menu-icon span:nth-child(1) { transform: translateY(5px) rotate(45deg); }
        .mobile-menu-button.mobile-open .mobile-menu-icon span:nth-child(2) { opacity: 0; }
        .mobile-menu-button.mobile-open .mobile-menu-icon span:nth-child(3) { transform: translateY(-5px) rotate(-45deg); }
        .mobile-menu-overlay { display: none; position: fixed; inset: 0; z-index: 85; background: rgba(15,23,42,0.62); opacity: 0; pointer-events: none; transition: opacity 0.2s ease; }
        .mobile-menu-overlay.active { display: block; opacity: 1; pointer-events: auto; }
        .mobile-menu-header { display: none; align-items: center; justify-content: space-between; padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .mobile-menu-title { color: #f8fafc; font-size: 16px; font-weight: 700; }
        .mobile-menu-close { width: 32px; height: 32px; padding: 0; border: 0; border-radius: 8px; background: rgba(255,255,255,0.06); color: #f8fafc; font-size: 20px; line-height: 1; cursor: pointer; }
        .mobile-menu-close:hover { background: rgba(255,255,255,0.12); }
        .mobile-menu-open { overflow: auto; }

        /* TOP HEADER - FIXED */
        .top-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 12px 40px; background: #253347; border-bottom: 1px solid rgba(255,255,255,0.08); position: fixed; top: 0; left: 0; right: 0; z-index: 100; height: 64px; }
        .header-center { display: flex; align-items: center; gap: 4px; margin-left: 12px; }
        .header-center-btn {
            display: inline-flex; align-items: center; padding: 0 16px; height: 36px; border-radius: 8px;
            border: none; color: #1e293b; font-size: 14px; font-weight: 500;
            text-decoration: none; transition: color 0.15s; background: none; cursor: pointer;
            font-family: 'Inter', sans-serif;
        }
        .header-center-btn:hover { color: #0f172a; }
        .header-center-btn.active { color: #94a3b8; }
        body.light-theme .header-center-btn { color: #1e293b; }
        body.light-theme .header-center-btn:hover { color: #0f172a; }
        body.light-theme .header-center-btn.active { color: #94a3b8; }
        .header-brand { display: flex; align-items: center; gap: 10px; flex-shrink: 0; text-decoration: none; }
        .header-brand:hover .brand-name { color: #cbd5e1; }
        body.light-theme .header-brand:hover .brand-name { color: #334155; }
        .header-brand .brand-mark { width: 36px; height: 36px; flex: 0 0 36px; border-radius: 10px; object-fit: contain; display: block; background: rgba(255,255,255,0.1); padding: 6px; }
        /* Invert logo on dark theme so it's visible */
        body:not(.light-theme) .brand-mark { filter: brightness(0) invert(1); }
        body.light-theme .brand-mark { background: rgba(0,0,0,0.06); }
        .header-brand .brand-name { color: #f8fafc; font-size: 18px; font-weight: 700; line-height: 1.2; }
        .header-brand .brand-subtitle { color: #94a3b8; font-size: 11px; line-height: 1.2; }
        .header-right { display: flex; align-items: center; gap: 12px; }
        .header-btn { cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); color: #94a3b8; font-size: 14px; font-weight: 500; transition: background 0.15s; user-select: none; background: none; min-height: 40px; }
        #themeToggleBtn { padding: 8px; }
        .header-btn:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
        .header-badge { background: #ef4444; color: #fff; border-radius: 8px; min-width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; font-size: 10px; font-weight: 700; }
        .header-badge.hidden { display: none; }
        .header-user { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); cursor: pointer; position: relative; min-height: 40px; }
        .header-user:hover { background: rgba(255,255,255,0.05); }
        .user-avatar {
            width: 32px; height: 32px; flex: 0 0 32px; border-radius: 50%;
            background: linear-gradient(135deg, #60a5fa, #2563eb);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 12px; font-weight: 700; text-transform: uppercase;
            flex-shrink: 0;
        }
        .user-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .user-name { font-size: 13px; font-weight: 600; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px; }
        .user-email { font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        /* ALERT WRAPPER - anchors alert dropdown */
        .header-alert-wrapper { position: relative; }
        /* ALERT BUTTON - TAB STYLE */
        .header-btn-icon { padding: 8px !important; min-width: 40px; justify-content: center; }
        .alert-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; width: 380px; max-height: 420px; overflow-y: auto; z-index: 150; display: none; box-shadow: 0 16px 48px rgba(0,0,0,0.35); }
        .alert-dropdown.active { display: block; }
        .alert-dropdown-header { padding: 14px 16px; font-size: 13px; font-weight: 600; color: #f8fafc; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center; }
        .alert-dropdown-clear { font-size: 11px; color: #64748b; cursor: pointer; font-weight: 500; transition: color 0.15s; }
        .alert-dropdown-clear:hover { color: #f8fafc; }
        .alert-empty { padding: 32px 16px; text-align: center; color: #475569; font-size: 13px; }
        /* Alert card items */
        .alert-card { padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; gap: 10px; transition: background 0.15s; position: relative; }
        .alert-card:last-child { border-bottom: none; }
        .alert-card:hover { background: rgba(255,255,255,0.03); }
        .alert-card-severity { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
        .alert-card-severity.critical { background: #f87171; box-shadow: 0 0 6px rgba(248,113,113,0.5); }
        .alert-card-severity.low { background: #fbbf24; box-shadow: 0 0 6px rgba(251,191,36,0.4); }
        .alert-card-body { flex: 1; min-width: 0; }
        .alert-card-top-row { display: flex; align-items: baseline; gap: 6px; margin-bottom: 3px; }
        .alert-card-name { font-size: 13px; font-weight: 600; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .alert-card-sku { font-size: 10px; color: #64748b; font-family: monospace; white-space: nowrap; }
        .alert-card-stock { font-size: 11px; color: #94a3b8; margin-bottom: 5px; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
        .alert-stock-pill { display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px; border-radius: 4px; font-size: 10px; font-weight: 600; }
        .alert-stock-pill.critical { background: rgba(248,113,113,0.12); color: #f87171; }
        .alert-stock-pill.low { background: rgba(251,191,36,0.12); color: #fbbf24; }
        .alert-card-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .alert-date-tag { font-size: 10px; color: #475569; }
        .alert-dismiss-btn { position: absolute; top: 8px; right: 8px; background: none; border: none; color: #475569; cursor: pointer; font-size: 14px; line-height: 1; padding: 2px 5px; border-radius: 4px; transition: all 0.15s; }
        .alert-dismiss-btn:hover { color: #f87171; background: rgba(248,113,113,0.1); }
        .alert-card { animation: alertSlideIn 0.2s ease; }
        @keyframes alertSlideIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
        .alert-card.removing { animation: alertSlideOut 0.2s ease forwards; }
        @keyframes alertSlideOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(12px); } }
        body.light-theme .alert-dropdown { background: #ffffff; border-color: rgba(15,23,42,0.1); box-shadow: 0 16px 48px rgba(0,0,0,0.1); }
        body.light-theme .alert-dropdown-header { background: #f8fafc; color: #0f172a; border-bottom-color: rgba(15,23,42,0.08); }
        body.light-theme .alert-dropdown-clear { color: #64748b; }
        body.light-theme .alert-dropdown-clear:hover { color: #0f172a; }
        body.light-theme .alert-empty { color: #94a3b8; }
        body.light-theme .alert-card { border-bottom-color: rgba(15,23,42,0.06); }
        body.light-theme .alert-card:hover { background: rgba(15,23,42,0.025); }
        body.light-theme .alert-card-name { color: #0f172a; }
        body.light-theme .alert-card-sku { color: #94a3b8; }
        body.light-theme .alert-card-stock { color: #64748b; }
        body.light-theme .alert-date-tag { color: #94a3b8; }
        body.light-theme .alert-dismiss-btn { color: #94a3b8; }
        body.light-theme .alert-dismiss-btn:hover { color: #ef4444; background: rgba(239,68,68,0.08); }

        /* USER DROPDOWN */
        .user-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; width: 180px; z-index: 150; display: none; overflow: hidden; }
        .user-dropdown.active { display: block; }
        .user-dropdown-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; color: #cbd5e1; font-size: 13px; font-weight: 500; cursor: pointer; transition: background 0.15s; text-decoration: none; border: none; background: none; width: 100%; text-align: left; font-family: inherit; }
        .user-dropdown-item:hover { background: rgba(255,255,255,0.05); color: #f8fafc; }
        .user-dropdown-item.logout { color: #fca5a5; }
        .user-dropdown-item.logout:hover { background: rgba(239,68,68,0.1); color: #f87171; }
        .user-dropdown-divider { height: 1px; background: rgba(255,255,255,0.06); margin: 4px 0; }
        body.light-theme .user-dropdown { background: #ffffff; border-color: rgba(15,23,42,0.1); }
        body.light-theme .user-dropdown-item { color: #475569; border: none; background: none; width: 100%; text-align: left; font-family: inherit; }
        body.light-theme .user-dropdown-item:hover { background: rgba(15,23,42,0.05); color: #0f172a; }
        body.light-theme .user-dropdown-item.logout { color: #dc2626; }
        body.light-theme .user-dropdown-item.logout:hover { background: rgba(239,68,68,0.08); color: #ef4444; }
        body.light-theme .user-dropdown-divider { background: rgba(15,23,42,0.08); }

        /* LIGHT THEME */
        body.light-theme { background: #f3f4f6; color: #334155; }
        body.light-theme .top-header { background: #ffffff; border-bottom-color: rgba(15,23,42,0.08); }
        body.light-theme .header-brand .brand-name { color: #0f172a; }
        body.light-theme .header-brand .brand-subtitle { color: #64748b; }
        body.light-theme .header-btn { color: #64748b; border-color: rgba(15,23,42,0.1); }
        body.light-theme .header-btn:hover { background: rgba(15,23,42,0.05); color: #0f172a; }
        body.light-theme .header-user { border-color: rgba(15,23,42,0.1); background: #fff; }
        body.light-theme .header-user:hover { background: rgba(15,23,42,0.03); }
        body.light-theme .user-name { color: #0f172a; }
        body.light-theme .user-email { color: #64748b; }
        body.light-theme .mobile-menu-button { background: rgba(15,23,42,0.06); color: #0f172a; }
        body.light-theme .mobile-menu-button:hover,
        body.light-theme .mobile-menu-button:focus-visible { background: rgba(15,23,42,0.1); }
        body.light-theme .mobile-menu-overlay { background: rgba(15,23,42,0.42); }
        body.light-theme .mobile-menu-header { border-bottom-color: rgba(15,23,42,0.1); }
        body.light-theme .mobile-menu-title { color: #0f172a; }
        body.light-theme .mobile-menu-close { background: rgba(15,23,42,0.06); color: #0f172a; }
        body.light-theme .mobile-menu-close:hover { background: rgba(15,23,42,0.1); }
        body.light-theme .sidebar { background: #ffffff; border-right-color: rgba(15,23,42,0.08); }
        body.light-theme .nav-label { color: #94a3b8; }
        body.light-theme .nav-item { color: #64748b; }
        body.light-theme .nav-item:hover { background: rgba(15,23,42,0.05); color: #0f172a; }
        body.light-theme .nav-item.active { background: rgba(15,23,42,0.08); color: #1e293b; }
        body.light-theme .main { background: #f3f4f6; }

        /* MAIN CONTENT AREA - SCROLLABLE */
        .main {
            position: fixed;
            top: 64px;
            left: 240px;
            right: 0;
            bottom: 0;
            background: #0f172a;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
        }
        .main::-webkit-scrollbar { width: 12px; }
        .main::-webkit-scrollbar-track { background: #f0f0f0; }
        .main::-webkit-scrollbar-thumb { background: #c0c0c0; border: 2px solid #f0f0f0; }
        .main::-webkit-scrollbar-thumb:hover { background: #a0a0a0; }
        .main::-webkit-scrollbar-thumb:active { background: #808080; }
        body.light-theme .main { background: #f3f4f6; }
        body.light-theme .main::-webkit-scrollbar-track { background: #e0e0e0; }
        body.light-theme .main::-webkit-scrollbar-thumb { background: #b0b0b0; border-color: #e0e0e0; }
        body.light-theme .main::-webkit-scrollbar-thumb:hover { background: #909090; }
        body.light-theme .main::-webkit-scrollbar-thumb:active { background: #707070; }
        .content {
            padding: 32px;
            min-height: calc(100vh - 64px);
        }


        /* SPINNER (SS-48) - reusable loading indicator for inventory fetches */
        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.15);
            border-top-color: #60a5fa;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }
        body.light-theme .spinner { border-color: rgba(15,23,42,0.12); border-top-color: #2563eb; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* TOAST */
        .toast { position: fixed; bottom: 24px; right: 24px; padding: 12px 18px; border-radius: 10px; font-size: 13px; font-weight: 500; z-index: 300; display: none; animation: slideUp 0.3s ease; }
        .toast.show { display: block; } .toast.success { background: #065f46; color: #a7f3d0; border: 1px solid rgba(74,222,128,0.3); } .toast.error { background: #7f1d1d; color: #fca5a5; border: 1px solid rgba(248,113,113,0.3); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .top-header {
                padding: 8px 16px;
                z-index: 110;
            }
            .header-left { display: flex; align-items: center; gap: 6px; }
            .header-center { display: none; }
            .header-right { gap: 6px; margin-left: auto; }
            .header-btn { padding: 6px 8px; }
            .alert-dropdown { width: min(280px, calc(100vw - 24px)); }
            .user-dropdown { width: min(180px, calc(100vw - 24px)); }
            /* Show hamburger menu button on mobile */
            .mobile-menu-button { display: flex; }
            .header-brand { display: flex; padding: 0; }
            .header-brand .brand-name,
            .header-brand .brand-subtitle { display: none; }
            .header-brand .brand-mark { width: 36px; height: 36px; }
            /* Hide sidebar by default on mobile, slide-in when open */
            .sidebar {
                width: 0;
                min-width: 0;
                overflow: hidden;
                transition: width 0.25s ease, visibility 0.25s ease;
                visibility: hidden;
            }
            .sidebar.mobile-open {
                width: 260px;
                min-width: 260px;
                overflow-y: auto;
                visibility: visible;
            }
            /* Show mobile menu header when in mobile mode */
            .mobile-menu-header { display: flex; }
            /* Main content starts at left: 0 on mobile */
            .main { left: 0; }
            /* Compress user info to avatar only on mobile */
            .user-info { display: none; }
            .header-user { padding: 8px; }
            .mobile-menu-overlay {
                z-index: 85;
            }
        }
        @media (max-width: 480px) {
            .top-header { padding: 6px 8px; gap: 6px; }
            .mobile-menu-button { width: 34px; height: 34px; }
            .mobile-menu-header { padding-top: 12px; }
            .header-btn { padding: 6px; }
            .user-avatar { width: 30px; height: 30px; }
            .content { padding: 14px; }
            .sidebar.mobile-open { width: calc(100vw - 40px); }
        }
        @media (max-width: 300px) {
            .top-header { padding: 4px 6px; gap: 4px; }
            .mobile-menu-button { width: 30px; height: 30px; }
            .header-btn { padding: 4px; }
            .user-avatar { width: 28px; height: 28px; }
            .content { padding: 10px; }
            .sidebar.mobile-open { width: calc(100vw - 20px); }
        }
        /* MOBILE CONTENT */
        @media (max-width: 640px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .stat-card { padding: 16px 12px; }
            .stat-card h3 { font-size: 11px; }
            .stat-value { font-size: 22px; }
            .quick-links-grid { grid-template-columns: 1fr; gap: 12px; }
            .quick-link-card { padding: 16px; }
            .section-header { flex-wrap: wrap; gap: 8px; }
            .section-title { font-size: 16px; }
            .table-wrapper { border-radius: 10px; }
            table { min-width: 600px; }
            thead th, tbody td { padding: 10px 10px; font-size: 12px; }
            .empty-state { padding: 32px 16px; font-size: 13px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- TOP HEADER (FIXED) -->
    <header class="top-header">
        <div class="header-left">
            <a href="{{ route('home') }}" class="header-brand">
                <img src="{{ asset('assets/stock-logo.png') }}" alt="Smart-Stock Logo" class="brand-mark">
                <div>
                    <div class="brand-name">Smart-Stock</div>
                    <div class="brand-subtitle">Inventory System</div>
                </div>
            </a>
            <button type="button" class="mobile-menu-button" id="mobileMenuButton" onclick="toggleMobileMenu(event)" aria-label="Open navigation menu" aria-controls="mobileNavigation" aria-expanded="false">
                <span class="mobile-menu-icon" aria-hidden="true"><span></span><span></span><span></span></span>
            </button>
        </div>
        <div class="header-center" id="headerNav">
            <a href="{{ route('home') }}" class="header-center-btn {{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
            <a href="{{ route('dashboard') }}" class="header-center-btn {{ !request()->routeIs('home') ? 'active' : '' }}">Dashboard</a>
        </div>
        <div class="header-right">
            <button type="button" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle theme" class="header-btn">
                <svg id="themeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </button>
            <div class="header-alert-wrapper">
                <button type="button" class="header-btn header-btn-icon" onclick="toggleHeaderAlerts()" title="Notifications">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span class="header-badge" id="headerAlertBadge">0</span>
                </button>
                <div class="alert-dropdown" id="headerAlertDropdown">
                    <div class="alert-dropdown-header">
                        <span>Low Stock Alerts</span>
                        <span class="alert-dropdown-clear" onclick="clearAlerts(event)">Clear all</span>
                    </div>
                    <div id="headerAlertList"></div>
                </div>
            </div>
            <div class="header-user" id="headerUserBtn" onclick="toggleUserDropdown(event)">
                <div class="user-avatar">{{ Auth::user()->name ? substr(Auth::user()->name, 0, 1) : 'U' }}</div>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->name }}</div>
                    <div class="user-email">{{ Auth::user()->email }}</div>
                </div>
                <div class="user-dropdown" id="userDropdown">
                    <a href="#" class="user-dropdown-item" onclick="navigateToSettings(event)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82-.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        <span>Settings</span>
                    </a>
                    <div class="user-dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;" id="logoutForm">
                        @csrf
                        <button type="submit" class="user-dropdown-item logout" onclick="handleLogout(event)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- SIDEBAR (FIXED) -->
    <aside class="sidebar" id="mobileNavigation">
        <div class="mobile-menu-header">
            <span class="mobile-menu-title">Smart-Stock</span>
            <button type="button" class="mobile-menu-close" onclick="closeMobileMenu()" aria-label="Close navigation menu">×</button>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Home</div>
            <a href="{{ route('dashboard') }}" class="nav-item" id="navOverview" data-page="overview">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </span>
                <span>Overview</span>
            </a>
            <div class="nav-label">Management</div>
            <a href="{{ route('products') }}" class="nav-item" id="navProducts" data-page="products">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </span>
                <span>Products</span>
            </a>
            @if(auth()->user()?->isAdmin())
                <a href="{{ route('suppliers') }}" class="nav-item" id="navSuppliers" data-page="suppliers">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </span>
                    <span>Suppliers</span>
                </a>
            @endif
            @if(auth()->user()?->isAdmin())
                <a href="{{ route('stock-in') }}" class="nav-item" id="navStockIn" data-page="stock-in">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"></path><path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
                    </span>
                    <span>Stock-In</span>
                </a>
            @endif
            @if(auth()->user()?->isCashier())
                <div class="nav-label">Sale</div>
                <a href="{{ route('pos') }}" class="nav-item" id="navPos" data-page="pos">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    </span>
                    <span>POS Checkout</span>
                </a>
            @endif
        </nav>
    </aside>
    <div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="closeMobileMenu()"></div>

    <!-- MAIN CONTENT (SCROLLABLE) -->
    <div class="main">
        <main class="content">
            @yield('content')
        </main>
    </div>

    <!-- TOAST -->
    <div class="toast" id="toast"></div>

    <script>
        (function () {
            let saved = 'dark';
            try { saved = localStorage.getItem('smartStockTheme') || 'dark'; } catch (e) {}
            document.body.classList.toggle('light-theme', saved === 'light');
            document.addEventListener('DOMContentLoaded', function () {
                updateThemeIcon(saved === 'light');
                setActiveNav();
            });
        })();

        function toggleTheme() {
            const isLight = document.body.classList.contains('light-theme');
            const newTheme = isLight ? 'dark' : 'light';
            document.body.classList.toggle('light-theme', !isLight);
            updateThemeIcon(!isLight);
            try { localStorage.setItem('smartStockTheme', newTheme); } catch (e) {}
        }

        function updateThemeIcon(isLight) {
            const icon = document.getElementById('themeIcon');
            if (!icon) return;
            if (isLight) {
                icon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
            } else {
                icon.innerHTML = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
            }
        }

        function setActiveNav() {
            const path = window.location.pathname;
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            if (path.includes('/pos')) {
                document.getElementById('navPos')?.classList.add('active');
            } else if (path.includes('/products')) {
                document.getElementById('navProducts').classList.add('active');
            } else if (path.includes('/stock-in')) {
                document.getElementById('navStockIn')?.classList.add('active');
            } else if (path.includes('/suppliers')) {
                document.getElementById('navSuppliers')?.classList.add('active');
            } else {
                document.getElementById('navOverview').classList.add('active');
            }
        }

        function toggleHeaderAlerts() {
            document.getElementById('headerAlertDropdown').classList.toggle('active');
            // Close user dropdown when opening alerts
            document.getElementById('userDropdown')?.classList.remove('active');
        }

        function toggleUserDropdown(e) {
            if (e) e.stopPropagation();
            const dropdown = document.getElementById('userDropdown');
            dropdown?.classList.toggle('active');
            // Close alerts dropdown when opening user menu
            document.getElementById('headerAlertDropdown')?.classList.remove('active');
        }

        function navigateToSettings(e) {
            e.preventDefault();
            // Close dropdown
            document.getElementById('userDropdown')?.classList.remove('active');
            // Show settings toast or navigate to settings page
            showToast('Settings coming soon!', 'success');
        }

        // SS-60: On logout, keep smartStockLastEmail in localStorage so that
        // the login page can auto-fill the email of the last logged-in user.
        // Only remove it when a DIFFERENT user logs in successfully.
        function handleLogout(e) {
            // intentionally do NOT remove smartStockLastEmail here —
            // the login page reads it to pre-fill the email field.
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            const alertWrapper = document.querySelector('.header-alert-wrapper');
            const alertDropdown = document.getElementById('headerAlertDropdown');
            const userWrapper = document.getElementById('headerUserBtn');
            const userDropdown = document.getElementById('userDropdown');

            if (alertWrapper && alertDropdown && !alertWrapper.contains(e.target)) {
                alertDropdown.classList.remove('active');
            }
            if (userWrapper && userDropdown && !userWrapper.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });

        // Dismissed alert product IDs (client-side only)
        let dismissedAlertIds = new Set(JSON.parse(localStorage.getItem('dismissedAlertIds') || '[]'));

        async function loadAlerts() {
            try {
                const res = await fetch('/api/inventory/alerts');
                const alerts = await res.json();
                const badge = document.getElementById('headerAlertBadge');
                badge.textContent = alerts.length;
                const list = document.getElementById('headerAlertList');
                if (alerts.length === 0) {
                    list.innerHTML = '<div class="alert-empty">No low-stock items</div>';
                    return;
                }
                list.innerHTML = alerts.map(a => {
                    const severity = a.current_stock <= 5 ? 'critical' : 'low';
                    const sku = escapeHtml(a.sku || '—');
                    const name = escapeHtml(a.name);
                    const stockLeft = a.current_stock;
                    const threshold = a.reorder_threshold;
                    const lastDate = getLastActivityDate(a);
                    return `<div class="alert-card" id="alert-card-${a.id}">
                        <div class="alert-card-severity ${severity}"></div>
                        <div class="alert-card-body">
                            <div class="alert-card-top-row">
                                <span class="alert-card-name">${name}</span>
                                <span class="alert-card-sku">${sku}</span>
                            </div>
                            <div class="alert-card-stock">
                                <span class="alert-stock-pill ${severity}">${stockLeft} left</span>
                                <span>threshold: ${threshold}</span>
                            </div>
                            <div class="alert-card-meta">
                                <span class="alert-date-tag">${lastDate}</span>
                            </div>
                        </div>
                        <button class="alert-dismiss-btn" onclick="dismissAlert(${a.id}, event)" title="Dismiss">×</button>
                    </div>`;
                }).join('');
            } catch (e) { console.error(e); }
        }

        function getLastActivityDate(alert) {
            // Prefer last_received_at from stock_ins, fall back to last_adjusted_at, then updated_at
            const dateStr = alert.last_received_at || alert.last_adjusted_at || alert.updated_at;
            if (!dateStr) return 'Never';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return formatRelativeDate(dateStr);
            return formatDateShort(d);
        }

        function formatDateShort(d) {
            const now = new Date();
            const diffMs = now - d;
            const diffDay = Math.floor(diffMs / 86400000);
            if (diffDay === 0) return 'Today';
            if (diffDay === 1) return 'Yesterday';
            if (diffDay < 7) return diffDay + ' days ago';
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        function formatRelativeDate(isoStr) {
            const d = new Date(isoStr);
            if (isNaN(d.getTime())) return isoStr;
            const now = new Date();
            const diffMs = now - d;
            const diffMin = Math.floor(diffMs / 60000);
            const diffHr = Math.floor(diffMs / 3600000);
            const diffDay = Math.floor(diffMs / 86400000);
            if (diffMin < 1) return 'Just now';
            if (diffMin < 60) return diffMin + ' min ago';
            if (diffHr < 24) return diffHr + ' hr ago';
            if (diffDay < 7) return diffDay + ' day ago';
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        function dismissAlert(productId, event) {
            event.stopPropagation();
            const card = document.getElementById('alert-card-' + productId);
            if (card) {
                card.classList.add('removing');
                setTimeout(() => {
                    card.remove();
                    dismissedAlertIds.add(productId);
                    try { localStorage.setItem('dismissedAlertIds', JSON.stringify([...dismissedAlertIds])); } catch(e) {}
                    // Recount visible alerts after dismiss
                    updateBadgeCount();
                }, 200);
            }
        }

        function updateBadgeCount() {
            // Count currently visible alert cards
            const count = document.querySelectorAll('.alert-card:not(.removing)').length;
            document.getElementById('headerAlertBadge').textContent = count;
            if (count === 0) {
                document.getElementById('headerAlertList').innerHTML = '<div class="alert-empty">All caught up!</div>';
            }
        }

        function clearAlerts(e) {
            e.stopPropagation();
            document.getElementById('headerAlertBadge').textContent = '0';
            document.getElementById('headerAlertList').innerHTML = '<div class="alert-empty">All caught up!</div>';
            dismissedAlertIds.clear();
            try { localStorage.removeItem('dismissedAlertIds'); } catch(e) {}
        }

        function escapeHtml(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            t.textContent = msg; t.className = 'toast show ' + type;
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        function toggleMobileMenu(e) {
            if (e) e.stopPropagation();
            const sidebar = document.getElementById('mobileNavigation');
            const overlay = document.getElementById('mobileMenuOverlay');
            const button = document.getElementById('mobileMenuButton');
            const isOpen = sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active', isOpen);
            button.classList.toggle('mobile-open', isOpen);
            button.setAttribute('aria-expanded', String(isOpen));
            button.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
            document.body.classList.toggle('mobile-menu-open', isOpen);
        }

        function closeMobileMenu() {
            const sidebar = document.getElementById('mobileNavigation');
            const overlay = document.getElementById('mobileMenuOverlay');
            const button = document.getElementById('mobileMenuButton');
            if (sidebar) sidebar.classList.remove('mobile-open');
            if (overlay) overlay.classList.remove('active');
            if (button) {
                button.classList.remove('mobile-open');
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('aria-label', 'Open navigation menu');
            }
            if (document.body) document.body.classList.remove('mobile-menu-open');
        }

        document.querySelectorAll('.sidebar .nav-item').forEach(item => {
            item.addEventListener('click', closeMobileMenu);
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMobileMenu();
        });

        loadAlerts();
        setInterval(loadAlerts, 30000);
    </script>
    @stack('scripts')
</body>
</html>
