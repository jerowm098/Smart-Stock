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
            display: flex;
            flex-direction: column;
        }
        body.light-theme { background: #f3f4f6; color: #334155; }

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
            background: #253347;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .header-left { display: flex; align-items: center; gap: 0; }
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
            background: rgba(255,255,255,0.1);
            padding: 6px;
        }
        body:not(.light-theme) .header-brand img { filter: brightness(0) invert(1); }
        body.light-theme .header-brand img { background: rgba(0,0,0,0.06); }
        .header-brand-name  { font-size: 18px; font-weight: 700; color: #f8fafc; line-height: 1.2; }
        .header-brand-sub   { font-size: 11px; color: #94a3b8; line-height: 1.2; }
        body.light-theme .header-brand-name { color: #0f172a; }
        body.light-theme .header-brand-sub  { color: #64748b; }

        .header-right { display: flex; align-items: center; gap: 12px; }

        /* ── NAV BAR ─────────────────────────────────────────────── */
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
            align-items: center;
            justify-content: space-between;
            gap: 170px;
            padding: 80px 40px 64px;
            max-width: 1100px;
            margin: 0 auto;
            position: relative;
        }
        .hero-bg {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100vw;
            height: 100%;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .hero-bg-circle {
            position: absolute;
            border-radius: 50%;
            border: 1.5px solid rgba(96,165,250,0.35);
            background: rgba(96,165,250,0.08);
            animation: heroFloat 6s ease-in-out infinite;
        }
        .hero-bg-circle:nth-child(1) { width: 340px; height: 340px; top: -80px; right: -60px; animation-delay: 0s; border-color: rgba(96,165,250,0.4); background: rgba(96,165,250,0.1); }
        .hero-bg-circle:nth-child(2) { width: 240px; height: 240px; bottom: -50px; left: -40px; animation-delay: 2s; border-color: rgba(59,130,246,0.3); background: rgba(59,130,246,0.08); }
        .hero-bg-circle:nth-child(3) { width: 180px; height: 180px; top: 15%; right: 28%; animation-delay: 4s; border-color: rgba(147,197,253,0.25); background: rgba(147,197,253,0.06); }
        body.light-theme .hero-bg-circle { border-color: rgba(37,99,235,0.2); background: rgba(37,99,235,0.06); }
        @keyframes heroFloat {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-14px) scale(1.04); }
        }
        .hero-bg-dots {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(96,165,250,0.3) 1.2px, transparent 1.2px);
            background-size: 24px 24px;
        }
        body.light-theme .hero-bg-dots { background-image: radial-gradient(circle, rgba(37,99,235,0.15) 1.2px, transparent 1.2px); }
        /* Horizontal line accent */
        .hero-bg::after {
            content: '';
            position: absolute;
            top: 40%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 5%, rgba(96,165,250,0.35) 30%, rgba(96,165,250,0.35) 70%, transparent 95%);
        }
        body.light-theme .hero-bg::after { background: linear-gradient(90deg, transparent 5%, rgba(37,99,235,0.2) 30%, rgba(37,99,235,0.2) 70%, transparent 95%); }
        .hero-left { flex: 1; min-width: 0; position: relative; z-index: 1; }
        .hero-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
            background: rgba(255,255,255,0.06);
            color: #94a3b8;
        }
        body.light-theme .hero-tag { background: rgba(0,0,0,0.04); color: #64748b; }
        .hero-title {
            font-size: 42px;
            font-weight: 700;
            line-height: 1.15;
            color: #f8fafc;
            margin-bottom: 16px;
        }
        body.light-theme .hero-title { color: #0f172a; }
        .hero-desc {
            font-size: 16px;
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 32px;
            max-width: 480px;
        }
        body.light-theme .hero-desc { color: #64748b; }
        .hero-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.15s, transform 0.1s;
        }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-secondary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.12);
            color: #cbd5e1;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            background: transparent;
            transition: background 0.15s, border-color 0.15s;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.2); }
        body.light-theme .btn-secondary { border-color: rgba(15,23,42,0.15); color: #475569; }
        body.light-theme .btn-secondary:hover { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.25); }
        .hero-right { flex: 0 0 auto; display: flex; align-items: center; justify-content: center; position: relative; z-index: 1; }
        .hero-illustration {
            width: 420px;
            height: 360px;
            position: relative;
            animation: heroFloatSvg 5s ease-in-out infinite;
        }
        @keyframes heroFloatSvg {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-14px); }
        }
        .hero-illustration svg { width: 100%; height: 100%; }
        .hero-counter-row {
            display: flex;
            gap: 32px;
            margin-top: 36px;
        }
        .hero-counter-item { display: flex; flex-direction: column; gap: 2px; }
        .hero-counter-value { font-size: 28px; font-weight: 700; color: #f8fafc; }
        body.light-theme .hero-counter-value { color: #0f172a; }
        .hero-counter-label { font-size: 12px; color: #64748b; font-weight: 500; }

        /* ── ABOUT ────────────────────────────────────────────────── */
        .about-section {
            padding: 80px 40px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .about-header {
            text-align: center;
            margin-bottom: 48px;
        }
        .section-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 12px;
            background: rgba(255,255,255,0.06);
            color: #94a3b8;
        }
        body.light-theme .section-tag { background: rgba(0,0,0,0.04); color: #64748b; }
        .section-title {
            font-size: 32px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 12px;
        }
        body.light-theme .section-title { color: #0f172a; }
        .section-subtitle {
            font-size: 15px;
            color: #94a3b8;
            max-width: 520px;
            margin: 0 auto;
            line-height: 1.6;
        }
        body.light-theme .section-subtitle { color: #64748b; }
        .about-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .about-card {
            padding: 28px 24px;
            border-radius: 12px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            text-align: center;
        }
        body.light-theme .about-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        .about-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(96,165,250,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        body.light-theme .about-card-icon { background: rgba(37,99,235,0.08); }
        .about-card h4 {
            font-size: 15px;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 8px;
        }
        body.light-theme .about-card h4 { color: #0f172a; }
        .about-card p {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.6;
        }
        body.light-theme .about-card p { color: #64748b; }

        /* ── REVIEWS ─────────────────────────────────────────────── */
        .reviews-section {
            padding: 80px 40px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 48px;
        }
        .review-card {
            padding: 24px;
            border-radius: 12px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
        }
        body.light-theme .review-card { background: #ffffff; border-color: rgba(15,23,42,0.08); }
        .review-stars {
            display: flex;
            gap: 2px;
            margin-bottom: 12px;
        }
        .review-star { color: #fbbf24; font-size: 14px; }
        .review-text {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        body.light-theme .review-text { color: #64748b; }
        .review-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .review-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(96,165,250,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #60a5fa;
        }
        body.light-theme .review-avatar { background: rgba(37,99,235,0.1); color: #2563eb; }
        .review-name { font-size: 13px; font-weight: 600; color: #f8fafc; }
        body.light-theme .review-name { color: #0f172a; }
        .review-role { font-size: 11px; color: #64748b; }
        .reviews-stats {
            display: flex;
            align-items: center;
            gap: 24px;
            justify-content: center;
            margin-top: 40px;
        }
        .reviews-rating {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }
        .reviews-rating-value {
            font-size: 48px;
            font-weight: 800;
            color: #f8fafc;
        }
        body.light-theme .reviews-rating-value { color: #0f172a; }
        .reviews-rating-max { font-size: 18px; color: #64748b; font-weight: 500; }
        .reviews-rating-stars { color: #fbbf24; font-size: 18px; letter-spacing: 1px; }
        .reviews-count { font-size: 13px; color: #64748b; }

        /* ── FOOTER ──────────────────────────────────────────────── */
        .site-footer {
            text-align: center;
            padding: 20px 40px;
            font-size: 12px;
            color: #475569;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        body.light-theme .site-footer { border-top-color: rgba(15,23,42,0.08); color: #94a3b8; }

        /* ── RESPONSIVE ──────────────────────────────────────────── */
        body { overflow-x: hidden; }

        /* Mobile navigation drawer */
        .mobile-menu-button {
            display: none;
            width: 38px;
            height: 38px;
            padding: 0;
            border: 0;
            border-radius: 8px;
            background: rgba(255,255,255,0.06);
            color: #f8fafc;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: background 0.15s ease;
        }
        body.light-theme .mobile-menu-button {
            background: rgba(15,23,42,0.06);
            color: #0f172a;
        }
        .mobile-menu-button:hover,
        .mobile-menu-button:focus-visible {
            background: rgba(255,255,255,0.12);
            outline: none;
        }
        .mobile-menu-button.mobile-open:hover,
        .mobile-menu-button.mobile-open:focus-visible {
            background: rgba(255,255,255,0.12);
        }
        body.light-theme .mobile-menu-button.mobile-open:hover,
        body.light-theme .mobile-menu-button.mobile-open:focus-visible {
            background: rgba(15,23,42,0.1);
        }
        .mobile-menu-icon {
            position: relative;
            display: block;
            width: 16px;
            height: 12px;
        }
        .mobile-menu-icon span {
            position: absolute;
            left: 0;
            width: 16px;
            height: 2px;
            border-radius: 2px;
            background: currentColor;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        .mobile-menu-icon span:nth-child(1) { top: 0; }
        .mobile-menu-icon span:nth-child(2) { top: 5px; }
        .mobile-menu-icon span:nth-child(3) { top: 10px; }
        .mobile-menu-button.mobile-open .mobile-menu-icon span:nth-child(1) { transform: translateY(5px) rotate(45deg); }
        .mobile-menu-button.mobile-open .mobile-menu-icon span:nth-child(2) { opacity: 0; }
        .mobile-menu-button.mobile-open .mobile-menu-icon span:nth-child(3) { transform: translateY(-5px) rotate(-45deg); }

        .mobile-menu-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 80;
            background: rgba(15,23,42,0.62);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }
        .mobile-menu-overlay.active { display: block; opacity: 1; pointer-events: auto; }
        body.light-theme .mobile-menu-overlay { background: rgba(15,23,42,0.42); }

        .mobile-sidebar {
            width: 0;
            min-width: 0;
            overflow: hidden;
            position: fixed;
            top: 64px;
            left: 0;
            bottom: 0;
            z-index: 90;
            background: #253347;
            border-right: 1px solid rgba(255,255,255,0.05);
            transition: width 0.25s ease, visibility 0.25s ease;
            visibility: hidden;
        }
        body.light-theme .mobile-sidebar {
            background: #ffffff;
            border-right-color: rgba(15,23,42,0.08);
        }
        .mobile-sidebar.mobile-open {
            width: 260px;
            min-width: 260px;
            overflow-y: auto;
            visibility: visible;
        }
        .mobile-menu-header {
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        body.light-theme .mobile-menu-header { border-bottom-color: rgba(15,23,42,0.1); }
        .mobile-menu-title { color: #f8fafc; font-size: 16px; font-weight: 700; }
        body.light-theme .mobile-menu-title { color: #0f172a; }
        .mobile-menu-close {
            width: 32px;
            height: 32px;
            padding: 0;
            border: 0;
            border-radius: 8px;
            background: rgba(255,255,255,0.06);
            color: #f8fafc;
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
        }
        body.light-theme .mobile-menu-close { background: rgba(15,23,42,0.06); color: #0f172a; }
        .mobile-menu-close:hover { background: rgba(255,255,255,0.12); }
        body.light-theme .mobile-menu-close:hover { background: rgba(15,23,42,0.1); }
        .mobile-sidebar-nav { padding: 12px; }
        .mobile-nav-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            padding: 12px 12px 6px;
            font-weight: 600;
        }
        body.light-theme .mobile-nav-label { color: #94a3b8; }
        .mobile-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
            margin-bottom: 6px;
        }
        .mobile-nav-item:hover,
        .mobile-nav-item.active { background: rgba(96,165,250,0.15); color: #60a5fa; }
        body.light-theme .mobile-nav-item { color: #64748b; }
        body.light-theme .mobile-nav-item:hover,
        body.light-theme .mobile-nav-item.active { background: rgba(37,99,235,0.1); color: #2563eb; }
        .mobile-nav-icon { width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }

        .site-header {
            overflow: visible;
        }

        @media (max-width: 768px) {
            .site-header { padding: 0 16px; }
            .header-left { display: flex; align-items: center; gap: 6px; }
            .header-center { display: none; }
            .mobile-menu-button { display: flex; }
            .mobile-menu-header { display: flex; }
            .mobile-sidebar { top: 64px; }
            .mobile-sidebar.mobile-open { width: 260px; min-width: 260px; }
            .header-brand { display: flex; padding: 0; }
            .header-brand .header-brand-name,
            .header-brand .header-brand-sub { display: none; }
            .header-brand img { width: 36px; height: 36px; }
            .header-right { gap: 6px; margin-left: auto; }
            .header-btn { padding: 6px 8px; }
            .header-user { padding: 8px; }
            .user-info { display: none; }
            .user-avatar { width: 32px; height: 32px; }
            .alert-dropdown { width: min(340px, calc(100vw - 24px)); }
            .user-dropdown { width: min(180px, calc(100vw - 24px)); }
        }

        @media (max-width: 768px) {
            .hero { flex-direction: column; padding: 48px 20px; text-align: center; }
            .hero-left { display: flex; flex-direction: column; align-items: center; }
            .hero-desc { margin-left: auto; margin-right: auto; }
            .hero-actions { justify-content: center; }
            .hero-counter-row { justify-content: center; }
            .hero-illustration { width: 280px; height: 220px; }
            .about-grid, .reviews-grid { grid-template-columns: 1fr; }
            .about-section, .reviews-section { padding: 48px 20px; }
        }

        @media (max-width: 480px) {
            .site-header { padding: 6px 8px; gap: 6px; }
            .mobile-menu-button { width: 34px; height: 34px; }
            .header-btn { padding: 6px; }
            .user-avatar { width: 30px; height: 30px; }
            .hero { padding: 32px 16px; }
            .hero-title { font-size: 28px; }
            .hero-desc { font-size: 14px; }
            .hero-counter-row { gap: 20px; }
            .hero-counter-value { font-size: 22px; }
            .section-title { font-size: 24px; }
            .reviews-rating-value { font-size: 36px; }
            .mobile-sidebar.mobile-open { width: calc(100vw - 40px); min-width: calc(100vw - 40px); }
        }

        @media (max-width: 300px) {
            .site-header { padding: 4px 6px; gap: 4px; }
            .mobile-menu-button { width: 30px; height: 30px; }
            .header-btn { padding: 4px; }
            .user-avatar { width: 28px; height: 28px; }
            .mobile-sidebar.mobile-open { width: calc(100vw - 20px); min-width: calc(100vw - 20px); }
            .mobile-menu-header { padding: 10px 8px; }
            .mobile-sidebar-nav { padding: 8px; }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="site-header">
        <div class="header-left">
            <a href="{{ route('home') }}" class="header-brand">
                <img src="{{ asset('assets/stock-logo.png') }}" alt="Smart-Stock logo">
                <div>
                    <div class="header-brand-name">Smart-Stock</div>
                    <div class="header-brand-sub">Inventory System</div>
                </div>
            </a>

            <button type="button" class="mobile-menu-button" id="mobileMenuButton" onclick="toggleMobileMenu(event)" aria-label="Open navigation menu" aria-controls="mobileNavigation" aria-expanded="false">
                <span class="mobile-menu-icon" aria-hidden="true"><span></span><span></span><span></span></span>
            </button>
        </div>

        <div class="header-center" id="headerNav">
            <a href="{{ route('home') }}" class="header-center-btn active">Home</a>
            @auth
                <a href="{{ route('dashboard') }}" class="header-center-btn">Dashboard</a>
            @endauth
        </div>

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
                            <span>Low Stock Alerts</span>
                            <span class="alert-dropdown-clear" onclick="clearAlerts(event)">Clear all</span>
                        </div>
                        <div id="headerAlertList"></div>
                    </div>
                </div>

                <!-- User button -->
                <div class="header-user" id="headerUserBtn" onclick="toggleUserDropdown(event)">
                    <div class="user-avatar">{{ substr(Auth::user()->name, 0, 1) }}</div>
                    <div class="user-info">
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

    <!-- MOBILE NAVIGATION -->
    <aside class="mobile-sidebar" id="mobileNavigation" aria-label="Mobile navigation">
        <div class="mobile-menu-header">
            <span class="mobile-menu-title">Smart-Stock</span>
            <button type="button" class="mobile-menu-close" onclick="closeMobileMenu()" aria-label="Close navigation menu">×</button>
        </div>
        <nav class="mobile-sidebar-nav">
            <div class="mobile-nav-label">Navigation</div>
            <a href="{{ route('home') }}" class="mobile-nav-item" data-page="home">
                <span class="mobile-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                </span>
                <span>Home</span>
            </a>
            @guest
                <a href="{{ route('login') }}" class="mobile-nav-item">
                    <span class="mobile-nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    </span>
                    <span>Login</span>
                </a>
                <a href="{{ route('register') }}" class="mobile-nav-item">
                    <span class="mobile-nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
                    </span>
                    <span>Register</span>
                </a>
            @else
                <a href="{{ route('products') }}" class="mobile-nav-item" data-page="products">
                    <span class="mobile-nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    </span>
                    <span>Products</span>
                </a>
            @endguest
        </nav>
    </aside>
    <div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="closeMobileMenu()"></div>

    <!-- HERO SECTION -->
    <section class="hero">
        <!-- 2D animated background -->
        <div class="hero-bg">
            <div class="hero-bg-circle"></div>
            <div class="hero-bg-circle"></div>
            <div class="hero-bg-circle"></div>
            <div class="hero-bg-dots"></div>
        </div>

        <div class="hero-left">
            <span class="hero-tag">Smart Inventory System</span>
            <h1 class="hero-title">Manage Your Inventory<br>with Smart Dashboard.</h1>
            <p class="hero-desc">
                Smart-Stock helps hardware stores track products, monitor stock levels in real time,
                and get instant alerts — all from one easy-to-use dashboard.
            </p>
            <div class="hero-actions">
                @guest
                    <a href="{{ route('login') }}" class="btn-primary">Get Started</a>
                    <a href="{{ route('register') }}" class="btn-secondary">Create Account</a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn-primary">Open Dashboard</a>
                @endguest
            </div>
            <div class="hero-counter-row">
                <div class="hero-counter-item">
                    <span class="hero-counter-value" data-count="500">0</span>
                    <span class="hero-counter-label">Products Tracked</span>
                </div>
                <div class="hero-counter-item">
                    <span class="hero-counter-value" data-count="50">0</span>
                    <span class="hero-counter-label">Active Stores</span>
                </div>
                <div class="hero-counter-item">
                    <span class="hero-counter-value" data-count="99">0</span>
                    <span class="hero-counter-label">% Uptime</span>
                </div>
            </div>
        </div>
        <div class="hero-right">
            <div class="hero-illustration">
                <svg viewBox="0 0 440 360" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Monitor / Screen - isometric -->
                    <g transform="translate(80, 30)">
                        <!-- Screen body (3D depth) -->
                        <path d="M20 20 L280 20 L300 40 L40 40 Z" fill="#1e293b" opacity="0.5"/>
                        <path d="M280 20 L280 210 L300 230 L300 40 Z" fill="#1a2332" opacity="0.5"/>
                        <!-- Screen frame -->
                        <rect x="16" y="16" width="268" height="198" rx="8" fill="#0f172a" stroke="#334155" stroke-width="1.5"/>
                        <!-- Screen display area -->
                        <rect x="24" y="24" width="252" height="170" rx="4" fill="#0c1425"/>
                        <!-- Chart grid lines -->
                        <line x1="24" y1="64" x2="276" y2="64" stroke="#1e3a5f" stroke-width="0.5" opacity="0.4"/>
                        <line x1="24" y1="104" x2="276" y2="104" stroke="#1e3a5f" stroke-width="0.5" opacity="0.4"/>
                        <line x1="24" y1="144" x2="276" y2="144" stroke="#1e3a5f" stroke-width="0.5" opacity="0.4"/>
                        <line x1="24" y1="184" x2="276" y2="184" stroke="#1e3a5f" stroke-width="0.5" opacity="0.4"/>
                        <!-- Candlestick bars -->
                        <rect x="40" y="120" width="6" height="40" rx="1" fill="#22c55e" opacity="0.8"/>
                        <rect x="56" y="100" width="6" height="55" rx="1" fill="#22c55e" opacity="0.8"/>
                        <rect x="72" y="130" width="6" height="30" rx="1" fill="#ef4444" opacity="0.8"/>
                        <rect x="88" y="110" width="6" height="50" rx="1" fill="#22c55e" opacity="0.8"/>
                        <rect x="104" y="90" width="6" height="60" rx="1" fill="#22c55e" opacity="0.8"/>
                        <rect x="120" y="105" width="6" height="40" rx="1" fill="#ef4444" opacity="0.8"/>
                        <rect x="136" y="80" width="6" height="55" rx="1" fill="#22c55e" opacity="0.8"/>
                        <rect x="152" y="95" width="6" height="45" rx="1" fill="#ef4444" opacity="0.8"/>
                        <rect x="168" y="70" width="6" height="65" rx="1" fill="#22c55e" opacity="0.8"/>
                        <rect x="184" y="60" width="6" height="50" rx="1" fill="#22c55e" opacity="0.8"/>
                        <rect x="200" y="75" width="6" height="35" rx="1" fill="#ef4444" opacity="0.8"/>
                        <rect x="216" y="55" width="6" height="60" rx="1" fill="#22c55e" opacity="0.8"/>
                        <rect x="232" y="45" width="6" height="55" rx="1" fill="#22c55e" opacity="0.8"/>
                        <rect x="248" y="50" width="6" height="40" rx="1" fill="#ef4444" opacity="0.8"/>
                        <!-- Trend line -->
                        <polyline class="chart-line" points="43,135 59,118 75,140 91,120 107,98 123,115 139,85 155,100 171,72 187,62 203,80 219,58 235,48 251,55"
                            stroke="#60a5fa" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"
                            stroke-dasharray="200" opacity="0.9"/>
                        <!-- Glow on trend line -->
                        <polyline points="43,135 59,118 75,140 91,120 107,98 123,115 139,85 155,100 171,72 187,62 203,80 219,58 235,48 251,55"
                            stroke="#60a5fa" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round" opacity="0.15"/>
                        <!-- Small notification dot -->
                        <circle cx="260" cy="32" r="4" fill="#22c55e"/>
                    </g>
                    <!-- Monitor stand -->
                    <g transform="translate(80, 30)">
                        <path d="M130 214 L130 240 L190 240 L190 214" fill="#1a2332" opacity="0.6"/>
                        <rect x="110" y="238" width="100" height="6" rx="3" fill="#1e293b" opacity="0.7"/>
                    </g>
                    <!-- BUY badge - green -->
                    <g transform="translate(160, 300)">
                        <rect x="0" y="0" width="56" height="26" rx="13" fill="#22c55e" opacity="0.9"/>
                        <text x="28" y="17" text-anchor="middle" fill="#fff" font-size="10" font-weight="700" font-family="Inter, sans-serif">BUY</text>
                    </g>
                    <!-- SELL badge - red -->
                    <g transform="translate(226, 300)">
                        <rect x="0" y="0" width="56" height="26" rx="13" fill="#ef4444" opacity="0.9"/>
                        <text x="28" y="17" text-anchor="middle" fill="#fff" font-size="10" font-weight="700" font-family="Inter, sans-serif">SELL</text>
                    </g>
                    <!-- Small floating document / spreadsheet card -->
                    <g transform="translate(20, 190)">
                        <rect x="0" y="0" width="120" height="80" rx="6" fill="#1e293b" stroke="#334155" stroke-width="1" opacity="0.8"/>
                        <!-- Mini grid lines -->
                        <line x1="10" y1="20" x2="110" y2="20" stroke="#334155" stroke-width="0.5"/>
                        <line x1="10" y1="36" x2="110" y2="36" stroke="#334155" stroke-width="0.5"/>
                        <line x1="10" y1="52" x2="110" y2="52" stroke="#334155" stroke-width="0.5"/>
                        <line x1="10" y1="68" x2="110" y2="68" stroke="#334155" stroke-width="0.5"/>
                        <line x1="45" y1="10" x2="45" y2="75" stroke="#334155" stroke-width="0.5"/>
                        <line x1="80" y1="10" x2="80" y2="75" stroke="#334155" stroke-width="0.5"/>
                        <!-- Mini data cells -->
                        <rect x="12" y="24" width="30" height="8" rx="2" fill="#22c55e" opacity="0.5"/>
                        <rect x="48" y="24" width="28" height="8" rx="2" fill="#334155" opacity="0.5"/>
                        <rect x="82" y="24" width="24" height="8" rx="2" fill="#60a5fa" opacity="0.4"/>
                        <rect x="12" y="40" width="30" height="8" rx="2" fill="#ef4444" opacity="0.4"/>
                        <rect x="48" y="40" width="28" height="8" rx="2" fill="#334155" opacity="0.5"/>
                        <rect x="82" y="40" width="24" height="8" rx="2" fill="#60a5fa" opacity="0.4"/>
                        <rect x="12" y="56" width="30" height="8" rx="2" fill="#22c55e" opacity="0.5"/>
                        <rect x="48" y="56" width="28" height="8" rx="2" fill="#334155" opacity="0.5"/>
                        <!-- Mini chart on card -->
                        <polyline points="16,66 30,60 44,64" stroke="#60a5fa" stroke-width="1" fill="none" stroke-linecap="round"/>
                    </g>
                    <!-- Floating sparkle dots -->
                    <circle cx="380" cy="60" r="3" fill="#60a5fa" opacity="0.4">
                        <animate attributeName="opacity" values="0.4;0.8;0.4" dur="2s" repeatCount="indefinite"/>
                    </circle>
                    <circle cx="400" cy="180" r="2" fill="#a855f7" opacity="0.3">
                        <animate attributeName="opacity" values="0.3;0.7;0.3" dur="2.5s" repeatCount="indefinite"/>
                    </circle>
                    <circle cx="50" cy="50" r="2.5" fill="#22c55e" opacity="0.3">
                        <animate attributeName="opacity" values="0.3;0.6;0.3" dur="3s" repeatCount="indefinite"/>
                    </circle>
                </svg>
            </div>
        </div>
    </section>

    <!-- ABOUT SECTION -->
    <section class="about-section">
        <div class="about-header">
            <span class="section-tag">Why Smart-Stock</span>
            <h2 class="section-title">Built for Trust</h2>
            <p class="section-subtitle">Trusted by hardware store owners for reliable, real-time inventory management that keeps your business running smoothly.</p>
        </div>
        <div class="about-grid">
            <div class="about-card">
                <div class="about-card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #60a5fa;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <h4>Secure & Reliable</h4>
                <p>Your data is protected with enterprise-grade security. Count on 99.9% uptime for your daily operations.</p>
            </div>
            <div class="about-card">
                <div class="about-card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #60a5fa;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
                <h4>Real-Time Tracking</h4>
                <p>Monitor stock levels as they change. Get instant alerts before items run out so you never miss a sale.</p>
            </div>
            <div class="about-card">
                <div class="about-card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #60a5fa;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <h4>Multi-User Access</h4>
                <p>Assign roles to staff members. Admins manage inventory while cashiers handle point-of-sale efficiently.</p>
            </div>
        </div>
    </section>

    <!-- REVIEWS SECTION -->
    <section class="reviews-section">
        <div class="about-header">
            <span class="section-tag">Testimonials</span>
            <h2 class="section-title">What Our Users Say</h2>
            <p class="section-subtitle">Hear from store owners who trust Smart-Stock for their daily inventory needs.</p>
        </div>
        <div class="reviews-stats">
            <div class="reviews-rating">
                <span class="reviews-rating-value" data-count="4.5">0</span>
                <span class="reviews-rating-max">/5</span>
            </div>
            <div>
                <div class="reviews-rating-stars">★★★★★</div>
                <div class="reviews-count">Based on 120+ reviews</div>
            </div>
        </div>
        <div class="reviews-grid">
            <div class="review-card">
                <div class="review-stars">★★★★★</div>
                <p class="review-text">"Smart-Stock completely changed how we manage our hardware store. Low stock alerts alone saved us from dozens of lost sales."</p>
                <div class="review-author">
                    <div class="review-avatar">JM</div>
                    <div>
                        <div class="review-name">Jerome M.</div>
                        <div class="review-role">Store Owner</div>
                    </div>
                </div>
            </div>
            <div class="review-card">
                <div class="review-stars">★★★★★</div>
                <p class="review-text">"Easy to use and very reliable. Our staff learned it in minutes. The POS integration makes checkout seamless."</p>
                <div class="review-author">
                    <div class="review-avatar">AR</div>
                    <div>
                        <div class="review-name">Ana R.</div>
                        <div class="review-role">Manager</div>
                    </div>
                </div>
            </div>
            <div class="review-card">
                <div class="review-stars">★★★★☆</div>
                <p class="review-text">"Finally a system built for small businesses. The dashboard gives me a clear picture of everything at a glance."</p>
                <div class="review-author">
                    <div class="review-avatar">MC</div>
                    <div>
                        <div class="review-name">Mark C.</div>
                        <div class="review-role">Owner</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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

        function setActiveMobileNav() {
            const path = window.location.pathname;
            let page = 'home';
            if (path.includes('/dashboard')) page = 'dashboard';
            if (path.includes('/products')) page = 'products';

            document.querySelectorAll('.mobile-nav-item[data-page]').forEach(item => {
                item.classList.toggle('active', item.dataset.page === page);
            });
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
        }

        document.addEventListener('DOMContentLoaded', function() {
            setActiveMobileNav();
        });
        document.querySelectorAll('.mobile-sidebar .mobile-nav-item').forEach(item => {
            item.addEventListener('click', closeMobileMenu);
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMobileMenu();
        });

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

        // Dismissed alert product IDs (client-side only)
        let dismissedAlertIds = new Set(JSON.parse(localStorage.getItem('dismissedAlertIds') || '[]'));

        // Load alerts
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
                    updateBadgeCount();
                }, 200);
            }
        }

        function updateBadgeCount() {
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

        // SS-60: Keep smartStockLastEmail on logout so login can pre-fill it
        function clearRememberEmail() {
            // intentionally kept — email stays for pre-fill on next login visit
        }

        // Counter animation
        function animateCounters() {
            document.querySelectorAll('[data-count]').forEach(function(el) {
                if (el.dataset.animated) return;
                el.dataset.animated = '1';
                var target = parseFloat(el.dataset.count);
                var isDecimal = target % 1 !== 0;
                var duration = 1500;
                var startTime = performance.now();
                function update(currentTime) {
                    var elapsed = currentTime - startTime;
                    var progress = Math.min(elapsed / duration, 1);
                    var eased = 1 - Math.pow(1 - progress, 3);
                    var current = eased * target;
                    el.textContent = isDecimal ? current.toFixed(1) : Math.floor(current);
                    if (progress < 1) requestAnimationFrame(update);
                }
                requestAnimationFrame(update);
            });
        }

        // Intersection Observer for counter animation
        var counterObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    animateCounters();
                    counterObserver.disconnect();
                }
            });
        }, { threshold: 0.3 });

        document.addEventListener('DOMContentLoaded', function() {
            var heroCounters = document.querySelector('.hero-counter-row');
            if (heroCounters) counterObserver.observe(heroCounters);
            var reviewsStats = document.querySelector('.reviews-stats');
            if (reviewsStats) counterObserver.observe(reviewsStats);
        });

        loadAlerts();
        setInterval(loadAlerts, 30000);
    </script>
</body>
</html>
