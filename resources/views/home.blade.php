<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Smart-Stock — Intelligent Inventory System</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }
        body.light-theme { background: #f1f5f9; color: #334155; }

        /* ── HEADER ─────────────────────────────────────────────── */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            height: 64px;
            background: rgba(15,23,42,0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        body.light-theme .site-header {
            background: rgba(255,255,255,0.9);
            border-bottom-color: rgba(15,23,42,0.08);
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .header-brand:hover .header-brand-name { color: #cbd5e1; }
        body.light-theme .header-brand:hover .header-brand-name { color: #334155; }
        .header-brand img {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            object-fit: contain;
        }
        body:not(.light-theme) .header-brand img { filter: brightness(0) invert(1); }
        .header-brand-name  { font-size: 18px; font-weight: 700; color: #f8fafc; line-height: 1.2; }
        .header-brand-sub   { font-size: 11px; color: #94a3b8; line-height: 1.2; }
        body.light-theme .header-brand-name { color: #0f172a; }
        body.light-theme .header-brand-sub  { color: #64748b; }

        .header-right { display: flex; align-items: center; gap: 12px; }

        /* Theme toggle - matches dashboard header-btn style */
        .header-btn { cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); color: #94a3b8; font-size: 14px; font-weight: 500; transition: background 0.15s; user-select: none; background: none; min-height: 40px; }
        #themeToggleBtn { padding: 8px; }
        .header-btn:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
        body.light-theme .header-btn { color: #64748b; border-color: rgba(15,23,42,0.1); }
        body.light-theme .header-btn:hover { background: rgba(15,23,42,0.05); color: #0f172a; }

        /* Header badge for alerts */
        .header-badge { background: #ef4444; color: #fff; border-radius: 8px; min-width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; font-size: 10px; font-weight: 700; }
        .header-badge.hidden { display: none; }

        /* Alerts wrapper */
        .header-alert-wrapper { position: relative; }
        .header-btn-icon { padding: 8px !important; min-width: 40px; justify-content: center; }
        .alert-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; width: 340px; max-height: 360px; overflow-y: auto; z-index: 150; display: none; box-shadow: none; }
        .alert-dropdown.active { display: block; }
        .alert-dropdown-header { padding: 12px 14px; font-size: 13px; font-weight: 600; color: #f8fafc; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; }
        .alert-dropdown-clear { font-size: 11px; color: #64748b; cursor: pointer; font-weight: 500; }
        .alert-dropdown-clear:hover { color: #f8fafc; }
        .alert-item { padding: 10px 14px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 8px; }
        .alert-item:last-child { border-bottom: none; }
        .alert-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .alert-dot.critical { background: #f87171; } .alert-dot.low { background: #fbbf24; }
        .alert-text { font-size: 12px; color: #cbd5e1; } .alert-text strong { color: #f8fafc; }
        .alert-empty { padding: 20px; text-align: center; color: #64748b; font-size: 12px; }
        body.light-theme .alert-dropdown { background: #ffffff; border-color: rgba(15,23,42,0.1); }
        body.light-theme .alert-dropdown-header { background: #f8fafc; color: #0f172a; border-bottom-color: rgba(15,23,42,0.1); }
        body.light-theme .alert-dropdown-clear { color: #64748b; }
        body.light-theme .alert-dropdown-clear:hover { color: #0f172a; }
        body.light-theme .alert-item { border-bottom-color: rgba(15,23,42,0.06); }
        body.light-theme .alert-text { color: #475569; }
        body.light-theme .alert-text strong { color: #0f172a; }
        body.light-theme .alert-empty { color: #64748b; }

        /* Login button (guest) */
        .btn-login {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .btn-login:hover { opacity: 0.9; }

        /* User button (authenticated) - matches dashboard header-user style */
        .header-user { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); cursor: pointer; position: relative; min-height: 40px; }
        .header-user:hover { background: rgba(255,255,255,0.05); }
        body.light-theme .header-user { border-color: rgba(15,23,42,0.1); }
        body.light-theme .header-user:hover { background: rgba(15,23,42,0.03); }
        .user-avatar {
            width: 32px; height: 32px; flex: 0 0 32px; border-radius: 50%;
            background: linear-gradient(135deg, #60a5fa, #2563eb);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 12px; font-weight: 700; text-transform: uppercase;
        }
        .user-name { font-size: 13px; font-weight: 600; color: #f8fafc; }
        .user-email { font-size: 11px; color: #64748b; }
        body.light-theme .user-name { color: #0f172a; }
        body.light-theme .user-email { color: #64748b; }

        /* User dropdown */
        .user-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; width: 180px; z-index: 150; display: none; overflow: hidden; box-shadow: none; }
        .user-dropdown.open { display: block; }
        body.light-theme .user-dropdown { background: #ffffff; border-color: rgba(15,23,42,0.1); box-shadow: none; }
        .dropdown-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
            cursor: pointer;
        }
        .dropdown-item:hover { background: rgba(255,255,255,0.05); color: #f8fafc; }
        body.light-theme .dropdown-item { color: #475569; }
        body.light-theme .dropdown-item:hover { background: rgba(15,23,42,0.05); color: #0f172a; }
        .dropdown-item.logout { color: #fca5a5; }
        .dropdown-item.logout:hover { background: rgba(239,68,68,0.1); color: #f87171; }
        body.light-theme .dropdown-item.logout { color: #dc2626; }
        body.light-theme .dropdown-item.logout:hover { background: rgba(239,68,68,0.07); }
        .dropdown-divider { height: 1px; background: rgba(255,255,255,0.06); margin: 4px 0; }
        body.light-theme .dropdown-divider { background: rgba(15,23,42,0.08); }

        /* User dropdown */
        .user-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            width: 180px;
            z-index: 200;
            display: none;
            overflow: hidden;
            box-shadow: none;
        }
        .user-dropdown.open { display: block; }
        body.light-theme .user-dropdown { background: #ffffff; border-color: rgba(15,23,42,0.1); box-shadow: none; }
        .dropdown-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
            cursor: pointer;
        }
        .dropdown-item:hover { background: rgba(255,255,255,0.06); color: #f8fafc; }
        .dropdown-item.logout { color: #fca5a5; }
        .dropdown-item.logout:hover { background: rgba(239,68,68,0.1); color: #f87171; }
        .dropdown-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 4px 0; }
        body.light-theme .dropdown-item { color: #475569; }
        body.light-theme .dropdown-item:hover { background: rgba(15,23,42,0.05); color: #0f172a; }
        body.light-theme .dropdown-item.logout { color: #dc2626; }
        body.light-theme .dropdown-item.logout:hover { background: rgba(239,68,68,0.07); }
        body.light-theme .dropdown-divider { background: rgba(15,23,42,0.08); }

        /* ── HERO ────────────────────────────────────────────────── */
        .hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 80px 24px 64px;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 20px;
            background: rgba(59,130,246,0.12);
            border: 1px solid rgba(59,130,246,0.25);
            color: #93c5fd;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
            margin-bottom: 24px;
        }
        body.light-theme .hero-badge { background: rgba(37,99,235,0.07); border-color: rgba(37,99,235,0.2); color: #2563eb; }
        .hero-title {
            font-size: 52px;
            font-weight: 800;
            line-height: 1.1;
            color: #f8fafc;
            margin-bottom: 20px;
            max-width: 700px;
        }
        .hero-title span { color: #60a5fa; }
        body.light-theme .hero-title { color: #0f172a; }
        body.light-theme .hero-title span { color: #2563eb; }
        .hero-desc {
            font-size: 17px;
            color: #94a3b8;
            line-height: 1.7;
            max-width: 580px;
            margin-bottom: 36px;
        }
        body.light-theme .hero-desc { color: #64748b; }
        .hero-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; justify-content: center; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 28px;
            border-radius: 10px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.15s, transform 0.1s;
        }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-secondary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 28px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.12);
            color: #cbd5e1;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            background: transparent;
            transition: background 0.15s, border-color 0.15s;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.2); }
        body.light-theme .btn-secondary { border-color: rgba(15,23,42,0.15); color: #475569; }
        body.light-theme .btn-secondary:hover { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.25); }

        /* ── FEATURES ────────────────────────────────────────────── */
        .features { padding: 0 24px 80px; max-width: 1100px; margin: 0 auto; }
        .features-label {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            margin-bottom: 12px;
        }
        .features-title {
            text-align: center;
            font-size: 30px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 8px;
        }
        body.light-theme .features-title { color: #0f172a; }
        .features-sub {
            text-align: center;
            font-size: 15px;
            color: #64748b;
            margin-bottom: 40px;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .feature-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 24px;
            transition: border-color 0.15s, transform 0.15s;
        }
        .feature-card:hover { border-color: rgba(96,165,250,0.25); transform: translateY(-2px); }
        body.light-theme .feature-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        body.light-theme .feature-card:hover { border-color: rgba(37,99,235,0.25); }
        .feature-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 14px;
        }
        .feature-icon.blue   { background: rgba(59,130,246,0.15);  color: #60a5fa; }
        .feature-icon.orange { background: rgba(251,146,60,0.15);   color: #fb923c; }
        .feature-icon.green  { background: rgba(74,222,128,0.12);   color: #4ade80; }
        .feature-icon.purple { background: rgba(168,85,247,0.15);   color: #c084fc; }
        body.light-theme .feature-icon.blue   { background: rgba(37,99,235,0.1);  color: #2563eb; }
        body.light-theme .feature-icon.orange { background: rgba(234,88,12,0.1);  color: #ea580c; }
        body.light-theme .feature-icon.green  { background: rgba(22,163,74,0.1);  color: #16a34a; }
        body.light-theme .feature-icon.purple { background: rgba(124,58,237,0.1); color: #7c3aed; }
        .feature-title { font-size: 15px; font-weight: 700; color: #f8fafc; margin-bottom: 8px; }
        body.light-theme .feature-title { color: #0f172a; }
        .feature-desc { font-size: 13px; color: #64748b; line-height: 1.6; }

        /* ── ABOUT STRIP ─────────────────────────────────────────── */
        .about-strip {
            background: rgba(255,255,255,0.02);
            border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 48px 24px;
            text-align: center;
            margin-bottom: 0;
        }
        body.light-theme .about-strip { background: rgba(15,23,42,0.02); border-color: rgba(15,23,42,0.07); }
        .about-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 12px; }
        body.light-theme .about-title { color: #0f172a; }
        .about-text  { font-size: 14px; color: #94a3b8; line-height: 1.8; max-width: 680px; margin: 0 auto; }
        body.light-theme .about-text { color: #64748b; }

        /* ── FOOTER ──────────────────────────────────────────────── */
        .site-footer {
            text-align: center;
            padding: 24px;
            font-size: 12px;
            color: #475569;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        body.light-theme .site-footer { border-top-color: rgba(15,23,42,0.07); }

        /* ── RESPONSIVE ──────────────────────────────────────────── */
        @media (max-width: 640px) {
            .site-header { padding: 0 16px; }
            .hero { padding: 56px 16px 48px; }
            .hero-title { font-size: 34px; }
            .hero-desc  { font-size: 15px; }
            .features   { padding: 0 16px 56px; }
            .features-title { font-size: 24px; }
            .about-strip { padding: 36px 16px; }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="site-header">
        <a href="{{ route('home') }}" class="header-brand">
            <img src="{{ asset('assets/stock-logo.png') }}" alt="Smart-Stock logo">
            <div>
                <div class="header-brand-name">Smart-Stock</div>
                <div class="header-brand-sub">Inventory System</div>
            </div>
        </a>

        <div class="header-right">
            <!-- Theme toggle -->
            <button type="button" id="themeToggleBtn" title="Toggle theme" class="header-btn" onclick="toggleTheme()">
                <svg id="themeIcon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </button>

            @guest
                <!-- Guest: show Login button -->
                <a href="{{ route('login') }}" class="btn-login">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    Login
                </a>
            @else
                <!-- Alerts button -->
                <div class="header-alert-wrapper">
                    <button type="button" class="header-btn header-btn-icon" onclick="toggleHeaderAlerts()" title="Notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        <span class="header-badge" id="headerAlertBadge">0</span>
                    </button>
                    <div class="alert-dropdown" id="headerAlertDropdown">
                        <div class="alert-dropdown-header">
                            <span>Notifications</span>
                            <span class="alert-dropdown-clear" onclick="clearAlerts(event)">Clear all</span>
                        </div>
                        <div id="headerAlertList"></div>
                    </div>
                </div>

                <!-- User button -->
                <div class="header-user" id="headerUserBtn" onclick="toggleUserDropdown(event)">
                    <div class="user-avatar">{{ substr(Auth::user()->name, 0, 1) }}</div>
                    <div>
                        <div class="user-name">{{ Auth::user()->name }}</div>
                        <div class="user-email">{{ Auth::user()->email }}</div>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="{{ route('dashboard') }}" class="dropdown-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                            Dashboard
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="dropdown-item logout" onclick="clearRememberEmail()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            @endguest
        </div>
    </header>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Web-Based Inventory System for Hardware Stores
        </div>
        <h1 class="hero-title">
            Smart Inventory.<br><span>Effortless Control.</span>
        </h1>
        <p class="hero-desc">
            SMART-STOCK helps hardware stores manage products, track stock levels in real time,
            and get instant alerts before running out — all from a single, easy-to-use dashboard.
        </p>
        <div class="hero-actions">
            @guest
                <a href="{{ route('login') }}" class="btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    Get Started — Sign In
                </a>
                <a href="{{ route('register') }}" class="btn-secondary">Create an Account</a>
            @endguest
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features">
        <div class="features-label">What's Inside</div>
        <h2 class="features-title">Everything your hardware store needs</h2>
        <p class="features-sub">Purpose-built tools to keep your shelves stocked and your team informed.</p>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </div>
                <div class="feature-title">Product Management</div>
                <div class="feature-desc">Add, update, and remove hardware products with SKUs, categories, prices, and stock counts in one place.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon orange">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </div>
                <div class="feature-title">Low-Stock Alerts</div>
                <div class="feature-desc">Automatic notifications when any product hits or falls below its reorder threshold — never miss a restock.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                </div>
                <div class="feature-title">Real-Time Overview</div>
                <div class="feature-desc">Live dashboard showing total products, in-stock counts, low-stock items, and critical alerts at a glance.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon purple">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <div class="feature-title">Role-Based Access</div>
                <div class="feature-desc">Admins control the full system. Cashiers handle daily stock operations. Each role sees only what they need.</div>
            </div>
        </div>
    </section>

    <!-- ABOUT STRIP -->
    <div class="about-strip">
        <div class="about-title">SMART-STOCK: A Web-Based Intelligent Inventory and Supply Management System for Hardware Stores</div>
        <p class="about-text">
            Designed to eliminate the guesswork in stock management, Smart-Stock gives hardware store owners and staff
            the tools to track every item, respond to shortages instantly, and make data-driven purchasing decisions —
            all through a secure, role-protected web interface.
        </p>
    </div>

    <!-- FOOTER -->
    <footer class="site-footer">
        &copy; {{ date('Y') }} Smart-Stock. All rights reserved.
    </footer>

    <script>
        // Theme init
        (function () {
            let saved = 'dark';
            try { saved = localStorage.getItem('smartStockTheme') || 'dark'; } catch (e) {}
            document.body.classList.toggle('light-theme', saved === 'light');
            updateThemeIcon(saved === 'light');
        })();

        function toggleTheme() {
            const isLight = document.body.classList.contains('light-theme');
            document.body.classList.toggle('light-theme', !isLight);
            updateThemeIcon(!isLight);
            try { localStorage.setItem('smartStockTheme', !isLight ? 'light' : 'dark'); } catch (e) {}
        }

        function updateThemeIcon(isLight) {
            const icon = document.getElementById('themeIcon');
            if (!icon) return;
            icon.innerHTML = isLight
                ? '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>'
                : '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
        }

        // User dropdown toggle
        function toggleUserDropdown(e) {
            if (e) e.stopPropagation();
            document.getElementById('userDropdown')?.classList.toggle('open');
            document.getElementById('headerAlertDropdown')?.classList.remove('active');
        }

        function toggleHeaderAlerts() {
            document.getElementById('headerAlertDropdown')?.classList.toggle('active');
            document.getElementById('userDropdown')?.classList.remove('open');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function (e) {
            const alertWrapper = document.querySelector('.header-alert-wrapper');
            const alertDropdown = document.getElementById('headerAlertDropdown');
            const userWrapper = document.getElementById('headerUserBtn');
            const dropdown = document.getElementById('userDropdown');
            if (alertWrapper && alertDropdown && !alertWrapper.contains(e.target)) {
                alertDropdown.classList.remove('active');
            }
            if (userWrapper && dropdown && !userWrapper.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });

        // Load alerts
        async function loadAlerts() {
            try {
                const res = await fetch('/api/inventory/alerts');
                const alerts = await res.json();
                const badge = document.getElementById('headerAlertBadge');
                badge.textContent = alerts.length;
                const list = document.getElementById('headerAlertList');
                if (alerts.length === 0) {
                    list.innerHTML = '<div class="alert-empty" style="padding:20px;">No low-stock items</div>';
                } else {
                    list.innerHTML = alerts.map(a => `<div class="alert-item"><span class="alert-dot ${a.current_stock <= 5 ? 'critical' : 'low'}"></span><span class="alert-text"><strong>${escapeHtml(a.name)}</strong> — ${a.current_stock} left (threshold: ${a.reorder_threshold})</span></div>`).join('');
                }
            } catch (e) { console.error(e); }
        }

        function clearAlerts(e) {
            e.stopPropagation();
            document.getElementById('headerAlertBadge').textContent = '0';
            document.getElementById('headerAlertList').innerHTML = '<div class="alert-empty" style="padding:20px;">No notifications</div>';
        }

        function escapeHtml(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

        // SS-60: Keep smartStockLastEmail on logout so login can pre-fill it
        function clearRememberEmail() {
            // intentionally kept — email stays for pre-fill on next login visit
        }

        loadAlerts();
        setInterval(loadAlerts, 30000);
    </script>
</body>
</html>
