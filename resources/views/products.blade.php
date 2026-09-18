<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Products - Smart-Stock</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; flex-direction: column; }
 
        /* SIDEBAR */
        .sidebar { width: 240px; min-height: calc(100vh - 60px); background: #1e293b; border-right: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; position: fixed; top: 60px; left: 0; bottom: 0; z-index: 90; }
        .sidebar-nav { flex: 1; padding: 12px; overflow-y: auto; }
        .nav-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; padding: 12px 12px 6px; font-weight: 600; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.15s; margin-bottom: 6px; position: relative; }
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
        .mobile-menu-open { overflow: hidden; }

        /* TOP HEADER */
        .top-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 12px 32px; background: #253347; border-bottom: 1px solid rgba(255,255,255,0.08); box-shadow: 0 2px 8px rgba(0,0,0,0.25); position: sticky; top: 0; z-index: 50; }
        .header-brand { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .header-brand .brand-mark { width: 36px; height: 36px; flex: 0 0 36px; border-radius: 10px; object-fit: contain; display: block; }
        .header-brand .brand-name { color: #f8fafc; font-size: 18px; font-weight: 700; line-height: 1.2; }
        .header-brand .brand-subtitle { color: #94a3b8; font-size: 11px; line-height: 1.2; }
        .header-right { display: flex; align-items: center; gap: 20px; }
        .header-alerts { position: relative; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 8px; color: #94a3b8; font-size: 14px; font-weight: 500; transition: background 0.15s; user-select: none; }
        .header-alerts:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
        .header-badge { background: #ef4444; color: #fff; border-radius: 8px; min-width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; font-size: 10px; font-weight: 700; }
        .header-badge.hidden { display: none; }
        .header-user { display: flex; align-items: center; gap: 10px; position: relative; cursor: pointer; }
        .header-user:hover .user-avatar { transform: scale(1.05); }
        .header-user .user-avatar {
            width: 32px; height: 32px; flex: 0 0 32px; border-radius: 50%;
            background: linear-gradient(135deg, #60a5fa, #2563eb);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 12px; font-weight: 700; text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }
        .header-user .user-name { font-size: 13px; font-weight: 600; color: #f8fafc; }
        .header-user .user-email { font-size: 11px; color: #64748b; }
        .header-logout { display: flex; align-items: center; gap: 6px; padding: 7px 14px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #fca5a5; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; font-family: 'Inter', sans-serif; transition: background 0.15s; text-decoration: none; }
        .header-logout:hover { background: rgba(239,68,68,0.2); }

        /* SETTINGS DROPDOWN */
        .settings-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; width: 220px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); z-index: 200; display: none; overflow: hidden; }
        .settings-dropdown.active { display: block; }
        .settings-dropdown-header { padding: 12px 14px; font-size: 13px; font-weight: 600; color: #f8fafc; border-bottom: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02); }
        .settings-section { padding: 14px; }
        .settings-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 10px; font-weight: 600; }
        .theme-options { display: flex; gap: 8px; }
        .theme-option { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 10px 6px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; color: #94a3b8; font-size: 11px; font-weight: 500; cursor: pointer; transition: all 0.15s; font-family: 'Inter', sans-serif; }
        .theme-option:hover { background: rgba(96,165,250,0.12); color: #e2e8f0; border-color: rgba(96,165,250,0.3); }
        .theme-option.active { background: rgba(96,165,250,0.15); color: #60a5fa; border-color: rgba(96,165,250,0.4); }
        .theme-swatch { width: 24px; height: 24px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.15); }
        .theme-swatch.dark { background: linear-gradient(135deg, #1e293b, #0f172a); }
        .theme-swatch.light { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); }
        .theme-swatch.active { border-color: #60a5fa; box-shadow: 0 0 0 2px rgba(96,165,250,0.3); }
        .page-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 6px; }
        .page-subtitle { color: #64748b; font-size: 14px; margin-bottom: 24px; }
        .user-caret { color: #64748b; font-size: 10px; }

        /* LIGHT THEME */
        body.light-theme { background: #f1f5f9; color: #334155; }
        body.light-theme .top-header { background: #ffffff; border-bottom-color: rgba(15,23,42,0.08); box-shadow: 0 2px 8px rgba(15,23,42,0.08); }
        body.light-theme .header-brand .brand-name { color: #0f172a; }
        body.light-theme .header-brand .brand-subtitle { color: #64748b; }
        body.light-theme .header-alerts { color: #64748b; }
        body.light-theme .header-alerts:hover { background: rgba(15,23,42,0.05); color: #0f172a; }
        body.light-theme .header-user .user-name { color: #0f172a; }
        body.light-theme .header-user .user-email { color: #64748b; }
        body.light-theme .header-logout { background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.18); color: #dc2626; }
        body.light-theme .header-logout:hover { background: rgba(239,68,68,0.14); }
        body.light-theme .mobile-menu-button { background: rgba(15,23,42,0.06); color: #0f172a; }
        body.light-theme .mobile-menu-button:hover,
        body.light-theme .mobile-menu-button:focus-visible { background: rgba(15,23,42,0.1); }
        body.light-theme .mobile-menu-overlay { background: rgba(15,23,42,0.42); }
        body.light-theme .mobile-menu-header { border-bottom-color: rgba(15,23,42,0.1); }
        body.light-theme .mobile-menu-title { color: #0f172a; }
        body.light-theme .mobile-menu-close { background: rgba(15,23,42,0.06); color: #0f172a; }
        body.light-theme .mobile-menu-close:hover { background: rgba(15,23,42,0.1); }
        body.light-theme .sidebar { background: #f8fafc; border-right-color: rgba(15,23,42,0.1); }
        body.light-theme .nav-label { color: #94a3b8; }
        body.light-theme .nav-item { color: #64748b; }
        body.light-theme .nav-item:hover { background: rgba(15,23,42,0.05); color: #0f172a; }
        body.light-theme .nav-item.active { background: rgba(37,99,235,0.1); color: #2563eb; }
        body.light-theme .main { background: #f1f5f9; }
        body.light-theme .section-title { color: #0f172a; }
        body.light-theme .search-input { background: #ffffff; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .search-input::placeholder { color: #94a3b8; }
        body.light-theme .table-wrapper { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme table thead th { background: #f8fafc; color: #64748b; }
        body.light-theme table tbody td { color: #475569; }
        body.light-theme table tbody tr:hover { background: rgba(15,23,42,0.025); }
        body.light-theme .empty-state { color: #94a3b8; }
        body.light-theme .modal { background: #ffffff; border-color: rgba(15,23,42,0.1); }
        body.light-theme .modal h2 { color: #0f172a; }
        body.light-theme .modal .form-group label { color: #475569; }
        body.light-theme .modal .form-group input { background: #f8fafc; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .modal-actions .btn-cancel { background: #e2e8f0; color: #334155; }
        body.light-theme .alert-dropdown,
        body.light-theme .settings-dropdown { background: #ffffff; border-color: rgba(15,23,42,0.1); }
        body.light-theme .settings-dropdown-header { background: #f8fafc; color: #0f172a; border-bottom-color: rgba(15,23,42,0.1); }
        body.light-theme .settings-label { color: #64748b; }
        body.light-theme .theme-option { background: #f8fafc; border-color: rgba(15,23,42,0.1); color: #64748b; }
        body.light-theme .theme-option:hover,
        body.light-theme .theme-option.active { color: #2563eb; background: rgba(37,99,235,0.08); border-color: rgba(37,99,235,0.3); }
        body.light-theme .page-title { color: #0f172a; }
        body.light-theme .page-subtitle { color: #64748b; }
        body.light-theme .user-caret { color: #94a3b8; }
        body.light-theme .alert-dropdown-header { color: #0f172a; border-bottom-color: rgba(15,23,42,0.1); }
        body.light-theme .alert-item { border-bottom-color: rgba(15,23,42,0.06); }
        body.light-theme .alert-text { color: #475569; }
        body.light-theme .alert-text strong { color: #0f172a; }
        body.light-theme .alert-empty { color: #64748b; }
        body.light-theme .add-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme .add-card-label { color: #475569; }
        body.light-theme .add-card-input { background: #f8fafc; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .add-card-input::placeholder { color: #94a3b8; }

        /* MAIN */
        .main { flex: 1; margin-left: 240px; min-height: 100vh; }
        .content { padding: 32px; }

        /* TABLE & CONTROLS */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
        .section-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .section-title { font-size: 18px; font-weight: 600; color: #f8fafc; }
        .btn-add { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; border: none; border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 6px; transition: opacity 0.15s; }
        .btn-add:hover { opacity: 0.9; }
        .search-input { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; padding: 8px 14px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; width: 240px; outline: none; transition: border-color 0.2s; }
        .search-input:focus { border-color: #3b82f6; } .search-input::placeholder { color: #64748b; }
        .table-wrapper { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; overflow-x: auto; }
        table { width: 100%; min-width: 720px; border-collapse: collapse; }
        thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }
        tbody td { padding: 12px 16px; font-size: 13px; color: #cbd5e1; }
        .stock-cell { font-weight: 600; } .stock-ok { color: #4ade80; } .stock-low { color: #fbbf24; } .stock-critical { color: #f87171; }
        .stock-badge { display: inline-block; padding: 3px 8px; border-radius: 5px; font-size: 11px; font-weight: 600; }
        .stock-badge.ok { background: rgba(74,222,128,0.12); color: #4ade80; } .stock-badge.low { background: rgba(251,191,36,0.12); color: #fbbf24; } .stock-badge.critical { background: rgba(248,113,113,0.12); color: #f87171; }
        .btn-delete { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 12px; font-family: 'Inter', sans-serif; transition: background 0.15s; }
        .btn-delete:hover { background: rgba(239,68,68,0.2); }
        .empty-state { text-align: center; color: #475569; padding: 48px; font-size: 14px; }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 200; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 28px; width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
        .modal h2 { color: #f8fafc; font-size: 18px; font-weight: 700; margin-bottom: 20px; }
        .modal .form-group { margin-bottom: 14px; }
        .modal .form-group label { display: block; color: #cbd5e1; font-size: 13px; font-weight: 500; margin-bottom: 5px; }
        .modal .form-group input { width: 100%; padding: 9px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; }
        .modal .form-group input:focus { border-color: #3b82f6; } .modal .form-group input::placeholder { color: #64748b; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
        .modal-actions .btn { padding: 9px 18px; border: none; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; transition: opacity 0.15s; }
        .modal-actions .btn:hover { opacity: 0.9; } .btn-cancel { background: rgba(255,255,255,0.1); color: #e2e8f0; } .btn-submit { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; }

        /* ALERT DROPDOWN */
        .alert-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; width: 340px; max-height: 360px; overflow-y: auto; box-shadow: 0 20px 40px rgba(0,0,0,0.4); z-index: 150; display: none; }
        .alert-dropdown.active { display: block; }
        .alert-dropdown-header { padding: 12px 14px; font-size: 13px; font-weight: 600; color: #f8fafc; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .alert-item { padding: 10px 14px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 8px; }
        .alert-item:last-child { border-bottom: none; }
        .alert-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .alert-dot.critical { background: #f87171; } .alert-dot.low { background: #fbbf24; }
        .alert-text { font-size: 12px; color: #cbd5e1; } .alert-text strong { color: #f8fafc; }
        .alert-empty { padding: 20px; text-align: center; color: #64748b; font-size: 12px; }

        /* TOAST */
        .toast { position: fixed; bottom: 24px; right: 24px; padding: 12px 18px; border-radius: 10px; font-size: 13px; font-weight: 500; z-index: 300; display: none; animation: slideUp 0.3s ease; }
        .toast.show { display: block; } .toast.success { background: #065f46; color: #a7f3d0; border: 1px solid rgba(74,222,128,0.3); } .toast.error { background: #7f1d1d; color: #fca5a5; border: 1px solid rgba(248,113,113,0.3); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .mobile-menu-button { display: inline-flex; }
            .header-brand { display: none; }
            .sidebar { top: 0; width: min(260px, calc(100vw - 32px)); transform: translateX(-105%); visibility: hidden; transition: transform 0.22s ease, visibility 0.22s ease; }
            .sidebar.mobile-open { transform: translateX(0); visibility: visible; }
            .mobile-menu-header { display: flex; padding-top: calc(44px + 12px); }
            .main { margin-left: 0; }
            .top-header { padding: 8px 10px; gap: 8px; z-index: 110; border-bottom: 0; box-shadow: none; }
            .header-right { flex: 1; min-width: 0; justify-content: flex-end; gap: 6px; }
            .header-user { gap: 0; } .header-user .user-name, .header-user .user-email, .header-user .user-caret { display: none; }
            .header-alert-label { display: none; }
            .header-alerts { padding: 6px 8px; }
            .header-logout { padding: 6px; } .header-logout span:last-child { display: none; }
            .alert-dropdown, .settings-dropdown { width: min(280px, calc(100vw - 24px)); }
        }

        @media (max-width: 480px) {
            .top-header { padding: 6px 8px; gap: 6px; border-bottom: 0; box-shadow: none; }
            .mobile-menu-button { width: 34px; height: 34px; }
            .mobile-menu-header { padding-top: calc(40px + 12px); }
            .header-alerts { padding: 6px; }
            .header-user .user-avatar { width: 30px; height: 30px; flex-basis: 30px; }
            .header-logout { padding: 6px; }
            .content { padding: 14px; }
            .section-header { flex-direction: column; align-items: stretch; }
            .section-actions { flex-direction: column; width: 100%; }
            .search-input { width: 100%; }
            .btn-add { width: 100%; justify-content: center; }
            .modal { width: calc(100vw - 24px); padding: 20px; }
        }
    </style>
</head>
<body>
    <script>
        (function () {
            let saved = 'dark';
            try { saved = localStorage.getItem('smartStockTheme') || 'dark'; } catch (e) {}
            document.body.classList.toggle('light-theme', saved === 'light');
            document.addEventListener('DOMContentLoaded', function () {
                var dark = document.getElementById('themeDark');
                var light = document.getElementById('themeLight');
                if (dark && light) {
                    dark.classList.toggle('active', saved !== 'light');
                    light.classList.toggle('active', saved === 'light');
                }
            });
        })();
    </script>

    <!-- TOP HEADER -->
    <header class="top-header">
        <div class="header-brand">
            <img src="{{ asset('assets/stock-logo.png') }}" alt="Smart-Stock Logo" class="brand-mark">
            <div>
                <div class="brand-name">Smart-Stock</div>
                <div class="brand-subtitle">Inventory System</div>
            </div>
        </div>
        <button type="button" class="mobile-menu-button" id="mobileMenuButton" onclick="toggleMobileMenu(event)" aria-label="Open navigation menu" aria-controls="mobileNavigation" aria-expanded="false">
            <span class="mobile-menu-icon" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
        <div class="header-right">
            <div class="header-alerts" onclick="toggleHeaderAlerts()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <span class="header-alert-label">Alerts</span>
                <span class="header-badge" id="headerAlertBadge">0</span>
            </div>
            <div class="alert-dropdown" id="headerAlertDropdown">
                <div class="alert-dropdown-header">Notifications</div>
                <div id="headerAlertList"></div>
            </div>
            <div class="header-user" id="headerUser" onclick="toggleSettings(event)">
                <div class="user-avatar">{{ Auth::user()->name ? substr(Auth::user()->name, 0, 1) : 'U' }}</div>
                <div>
                    <div class="user-name">{{ Auth::user()->name }}</div>
                    <div class="user-email">{{ Auth::user()->email }}</div>
                </div>
                <span class="user-caret">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
                <div class="settings-dropdown" id="settingsDropdown">
                    <div class="settings-dropdown-header">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>Settings
                    </div>
                    <div class="settings-section">
                        <div class="settings-label">Theme</div>
                        <div class="theme-options">
                            <button type="button" class="theme-option" id="themeDark" onclick="setTheme('dark')">
                                <span class="theme-swatch dark"></span>
                                <span>Dark</span>
                            </button>
                            <button type="button" class="theme-option" id="themeLight" onclick="setTheme('light')">
                                <span class="theme-swatch light"></span>
                                <span>Light</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="header-logout">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </header>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="mobileNavigation">
        <div class="mobile-menu-header">
            <span class="mobile-menu-title">Smart-Stock</span>
            <button type="button" class="mobile-menu-close" onclick="closeMobileMenu()" aria-label="Close navigation menu">×</button>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main</div>
            <a href="{{ route('dashboard') }}" class="nav-item" id="navDashboard">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </span>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('products') }}" class="nav-item active" id="navProducts">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </span>
                <span>Products</span>
            </a>
        </nav>
    </aside>
    <div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="closeMobileMenu()"></div>

    <!-- MAIN -->
    <div class="main">
        <main class="content">
            <div class="section-header">
                <div>
                    <h1 class="page-title">Products</h1>
                    <p class="page-subtitle">Manage inventory items and add new products</p>
                </div>
                <div class="section-actions">
                    <input type="text" class="search-input" id="searchInput" placeholder="Search products by name, SKU, category..." oninput="filterProducts()">
                    <button class="btn-add" onclick="openModal()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Add Product</span>
                    </button>
                </div>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Threshold</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody">
                        <tr><td colspan="8" class="empty-state">Loading products...</td></tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- ADD PRODUCT MODAL -->
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <h2>Add New Product</h2>
            <form onsubmit="handleAddProduct(event)">
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" id="pNameM" placeholder="e.g. Wireless Mouse" required>
                </div>
                <div class="form-group">
                    <label>SKU *</label>
                    <input type="text" name="sku" id="pSkuM" placeholder="e.g. WM-001" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" id="pCategoryM" placeholder="e.g. Electronics">
                </div>
                <div class="form-group">
                    <label>Price (₱) *</label>
                    <input type="number" name="price" id="pPriceM" placeholder="0.00" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Current Stock *</label>
                    <input type="number" name="current_stock" id="pStockM" placeholder="0" min="0" required>
                </div>
                <div class="form-group">
                    <label>Reorder Threshold *</label>
                    <input type="number" name="reorder_threshold" id="pThresholdM" placeholder="0" min="0" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-submit">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TOAST -->
    <div class="toast" id="toast"></div>

    <script>
        let allProducts = [];

        function getStatus(stock, threshold) {
            if (stock <= threshold) return { label: 'Critical', class: 'critical' };
            if (stock <= threshold * 1.5) return { label: 'Low', class: 'low' };
            return { label: 'OK', class: 'ok' };
        }

        function escapeHtml(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            t.textContent = msg; t.className = 'toast show ' + type;
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        async function loadProducts() {
            try {
                const res = await fetch('/api/inventory/products');
                allProducts = await res.json();
                renderFullTable(allProducts);
                loadAlerts();
            } catch (e) { showToast('Failed to load products', 'error'); }
        }

        function renderFullTable(products) {
            const body = document.getElementById('productTableBody');
            if (products.length === 0) { 
                body.innerHTML = '<tr><td colspan="8" class="empty-state">No products found</td></tr>'; 
            } else { 
                body.innerHTML = products.map(p => { 
                    const s = getStatus(p.current_stock, p.reorder_threshold); 
                    return `<tr id="row-${p.id}">
                        <td><strong>${escapeHtml(p.name)}</strong></td>
                        <td>${escapeHtml(p.sku)}</td>
                        <td>${escapeHtml(p.category || '—')}</td>
                        <td>₱${parseFloat(p.price).toFixed(2)}</td>
                        <td class="stock-cell stock-${s.class}">${p.current_stock}</td>
                        <td>${p.reorder_threshold}</td>
                        <td><span class="stock-badge ${s.class}">${s.label}</span></td>
                        <td><button class="btn-delete" onclick="deleteProduct(${p.id})">Delete</button></td>
                    </tr>`; 
                }).join(''); 
            }
        }

        function filterProducts() {
            const t = document.getElementById('searchInput').value.toLowerCase();
            const f = allProducts.filter(p => 
                (p.name && p.name.toLowerCase().includes(t)) || 
                (p.sku && p.sku.toLowerCase().includes(t)) || 
                (p.category && p.category.toLowerCase().includes(t))
            );
            renderFullTable(f);
        }

        function openModal() {
            document.getElementById('addModal').classList.add('active');
            document.getElementById('pNameM').focus();
        }

        function closeModal() { 
            document.getElementById('addModal').classList.remove('active'); 
            document.getElementById('addModal').querySelector('form').reset(); 
        }

        document.getElementById('addModal').addEventListener('click', (e) => { 
            if (e.target === document.getElementById('addModal')) closeModal(); 
        });

        async function handleAddProduct(e) {
            e.preventDefault();
            const fd = new FormData(e.target); 
            const data = Object.fromEntries(fd);
            data.price = parseFloat(data.price); 
            data.current_stock = parseInt(data.current_stock); 
            data.reorder_threshold = parseInt(data.reorder_threshold);
            
            try { 
                const res = await fetch('/api/inventory/add', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' }, 
                    body: JSON.stringify(data) 
                }); 
                if (res.ok) { 
                    closeModal(); 
                    showToast('Product added successfully!', 'success'); 
                    loadProducts(); 
                } else { 
                    const errData = await res.json().catch(() => null);
                    showToast(errData?.message || 'Error adding product', 'error'); 
                } 
            } catch (e) { 
                showToast('Connection error', 'error'); 
            }
        }

        async function deleteProduct(id) { 
            if (!confirm('Are you sure you want to delete this product?')) return; 
            try { 
                const res = await fetch(`/api/inventory/${id}`, { method: 'DELETE' }); 
                if (res.ok) { 
                    showToast('Product deleted', 'success'); 
                    loadProducts(); 
                } else {
                    showToast('Failed to delete', 'error');
                }
            } catch (e) { 
                showToast('Failed to connect', 'error'); 
            } 
        }

        function toggleHeaderAlerts() { document.getElementById('headerAlertDropdown').classList.toggle('active'); }
        document.addEventListener('click', (e) => {
            const wrapper = document.querySelector('.header-alerts');
            const dropdown = document.getElementById('headerAlertDropdown');
            if (wrapper && dropdown && !wrapper.contains(e.target)) { dropdown.classList.remove('active'); }
        });

        async function loadAlerts() {
            try {
                const res = await fetch('/api/inventory/alerts');
                const alerts = await res.json();
                const badge = document.getElementById('headerAlertBadge');
                badge.textContent = alerts.length;
                badge.classList.toggle('hidden', alerts.length === 0);
                const list = document.getElementById('headerAlertList');
                if (alerts.length === 0) { list.innerHTML = '<div class="alert-empty" style="padding:20px;">No low-stock items</div>'; }
                else { list.innerHTML = alerts.map(a => `<div class="alert-item"><span class="alert-dot ${a.current_stock <= 5 ? 'critical' : 'low'}"></span><span class="alert-text"><strong>${escapeHtml(a.name)}</strong> — ${a.current_stock} left (threshold: ${a.reorder_threshold})</span></div>`).join(''); }
            } catch (e) { console.error(e); }
        }

        function toggleSettings(e) {
            if (e) e.stopPropagation();
            document.getElementById('settingsDropdown').classList.toggle('active');
        }

        function setTheme(theme) {
            const isLight = theme === 'light';
            document.body.classList.toggle('light-theme', isLight);
            document.getElementById('themeDark').classList.toggle('active', !isLight);
            document.getElementById('themeLight').classList.toggle('active', isLight);
            document.getElementById('settingsDropdown').classList.remove('active');
            try { localStorage.setItem('smartStockTheme', theme); } catch (e) {}
        }

        document.addEventListener('click', (e) => {
            const wrapper = document.getElementById('headerUser');
            const dropdown = document.getElementById('settingsDropdown');
            if (wrapper && dropdown && !wrapper.contains(e.target)) dropdown.classList.remove('active');
        });

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

        loadProducts();
        setInterval(loadAlerts, 30000);
    </script>
</body>
</html>
