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
            border: none; color: #94a3b8; font-size: 14px; font-weight: 500;
            text-decoration: none; transition: color 0.15s, font-weight 0.15s; background: none; cursor: pointer;
            font-family: 'Inter', sans-serif;
        }
        .header-center-btn:hover { color: #1e293b; }
        .header-center-btn.active { color: #0f172a; font-weight: 700; }
        body.light-theme .header-center-btn { color: #94a3b8; }
        body.light-theme .header-center-btn:hover { color: #1e293b; }
        body.light-theme .header-center-btn.active { color: #0f172a; font-weight: 700; }

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

        /* ── HERO — centered, no image, graph backdrop ───────────── */
        .hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 0;
            padding: 120px 24px 100px;
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            isolation: isolate;
            overflow: visible;
        }
        /* full-bleed canvas */
        .hero-bg {
            position: absolute;
            top: -64px;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 100vw;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
            background:
                radial-gradient(900px 420px at 12% 8%, rgba(59,130,246,0.14), transparent 60%),
                radial-gradient(800px 420px at 88% 18%, rgba(168,85,247,0.12), transparent 60%),
                radial-gradient(700px 500px at 55% 100%, rgba(34,211,238,0.08), transparent 60%);
        }
        body.light-theme .hero-bg {
            background:
                radial-gradient(900px 420px at 12% 8%, rgba(37,99,235,0.10), transparent 60%),
                radial-gradient(800px 420px at 88% 18%, rgba(168,85,247,0.10), transparent 60%),
                radial-gradient(700px 500px at 55% 100%, rgba(6,182,212,0.08), transparent 60%);
        }
        /* aurora blobs — pure CSS3 blur + blend */
        .hero-aurora { position: absolute; border-radius: 50%; filter: blur(70px); mix-blend-mode: screen; opacity: .85; animation: auroraDrift 12s ease-in-out infinite alternate; }
        .hero-aurora.a1 { width: 520px; height: 520px; top: -160px; right: -80px;
            background: radial-gradient(circle at 30% 30%, #3b82f6 0%, #6366f1 35%, transparent 70%); opacity: .38; }
        .hero-aurora.a2 { width: 460px; height: 460px; bottom: -180px; left: -120px;
            background: radial-gradient(circle at 60% 40%, #06b6d4 0%, #3b82f6 40%, transparent 70%); opacity: .28; animation-delay: -4s; }
        .hero-aurora.a3 { width: 320px; height: 320px; top: 18%; left: 44%;
            background: radial-gradient(circle at 50% 50%, #a855f7 0%, transparent 68%); opacity: .22; animation-delay: -8s; }
        body.light-theme .hero-aurora { mix-blend-mode: multiply; opacity: .22; filter: blur(80px); }
        @keyframes auroraDrift {
            0% { transform: translate(0,0) scale(1) rotate(0deg); }
            50% { transform: translate(24px,-28px) scale(1.08) rotate(8deg); }
            100% { transform: translate(-18px,18px) scale(0.96) rotate(-6deg); }
        }
        /* perspective grid with mask fade */
        .hero-grid {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(148,163,184,0.14) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148,163,184,0.14) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(720px 420px at 50% 38%, black 30%, transparent 72%);
            -webkit-mask-image: radial-gradient(720px 420px at 50% 38%, black 30%, transparent 72%);
            opacity: .7;
        }
        body.light-theme .hero-grid {
            background-image:
                linear-gradient(rgba(15,23,42,0.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15,23,42,0.07) 1px, transparent 1px);
        }
        .hero-dots {
            position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(96,165,250,0.35) 1.3px, transparent 1.3px);
            background-size: 22px 22px;
            mask-image: radial-gradient(520px 320px at 78% 45%, black 0%, transparent 70%);
            -webkit-mask-image: radial-gradient(520px 320px at 78% 45%, black 0%, transparent 70%);
            opacity: .55;
        }
        body.light-theme .hero-dots { background-image: radial-gradient(circle, rgba(37,99,235,0.22) 1.3px, transparent 1.3px); }
        /* film grain — SVG noise data-uri */
        .hero-noise {
            position: absolute; inset: 0; opacity: .05; mix-blend-mode: overlay;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            background-size: 180px 180px;
        }
        /* rotating conic beam + hairline */
        .hero-beam {
            position: absolute; top: -120px; left: 50%; width: 720px; height: 720px;
            transform: translateX(-50%); border-radius: 50%;
            background: conic-gradient(from 0deg, transparent 0deg, rgba(59,130,246,0.14) 28deg, transparent 56deg, transparent 180deg, rgba(168,85,247,0.10) 208deg, transparent 236deg);
            filter: blur(2px); animation: beamSpin 22s linear infinite; opacity: .9;
        }
        @keyframes beamSpin { to { transform: translateX(-50%) rotate(360deg); } }
        .hero-hairline {
            position: absolute; top: 42%; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent 4%, rgba(96,165,250,0.45) 28%, rgba(34,211,238,0.5) 50%, rgba(168,85,247,0.45) 72%, transparent 96%);
            box-shadow: 0 0 24px rgba(59,130,246,0.35);
        }
        body.light-theme .hero-hairline { background: linear-gradient(90deg, transparent 4%, rgba(37,99,235,0.28) 30%, rgba(6,182,212,0.28) 50%, rgba(168,85,247,0.28) 70%, transparent 96%); box-shadow: none; }
        /* giant ring */
        .hero-ring {
            position: absolute; width: 560px; height: 560px; right: -140px; top: -140px;
            border-radius: 50%; border: 1px solid rgba(148,163,184,0.18);
            box-shadow: inset 0 0 80px rgba(59,130,246,0.08);
        }
        .hero-ring::before { content:''; position: absolute; inset: 28px; border-radius: 50%; border: 1px dashed rgba(148,163,184,0.14); animation: beamSpin 40s linear infinite; }
        .hero-ring::after { content:''; position: absolute; top: 18px; left: 50%; width: 10px; height: 10px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 16px #22c55e; }
        body.light-theme .hero-ring { border-color: rgba(15,23,42,0.10); box-shadow: inset 0 0 80px rgba(37,99,235,0.06); }
        /* floating particles */
        .hero-particle { position: absolute; border-radius: 50%; animation: particleFloat 7s ease-in-out infinite; }
        .hero-particle.p1 { width: 8px; height: 8px; top: 22%; right: 32%; background: #22c55e; box-shadow: 0 0 14px #22c55e; }
        .hero-particle.p2 { width: 6px; height: 6px; top: 64%; right: 8%; background: #60a5fa; box-shadow: 0 0 12px #60a5fa; animation-delay: -2s; }
        .hero-particle.p3 { width: 5px; height: 5px; top: 18%; left: 42%; background: #a855f7; box-shadow: 0 0 12px #a855f7; animation-delay: -4s; }
        .hero-particle.p4 { width: 4px; height: 4px; bottom: 18%; left: 36%; background: #22d3ee; box-shadow: 0 0 10px #22d3ee; animation-delay: -1s; }
        @keyframes particleFloat { 0%,100% { transform: translateY(0) scale(1); opacity: .9; } 50% { transform: translateY(-16px) scale(1.2); opacity: 1; } }

        .hero-left { flex: none; width: 100%; max-width: 780px; position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; }
        /* subtle increasing line-graph backdrop — pure CSS3 + inline SVG */
        .hero-graph {
            position: absolute;
            left: 50%;
            bottom: -10px;
            transform: translateX(-50%);
            width: min(960px, 110vw);
            height: 340px;
            z-index: 1;
            pointer-events: none;
            opacity: .9;
            mask-image: linear-gradient(to top, black 55%, transparent 98%);
            -webkit-mask-image: linear-gradient(to top, black 55%, transparent 98%);
        }
        .hero-graph svg { width: 100%; height: 100%; display: block; overflow: visible; }
        .hero-graph .g-grid { stroke: rgba(148,163,184,0.16); stroke-width: 1; stroke-dasharray: 3 6; }
        body.light-theme .hero-graph .g-grid { stroke: rgba(15,23,42,0.10); }
        .hero-graph .g-area { fill: url(#heroAreaFill); opacity: .5; }
        .hero-graph .g-line {
            fill: none; stroke: url(#heroLineGrad); stroke-width: 3; stroke-linecap: round; stroke-linejoin: round;
            stroke-dasharray: 1200; stroke-dashoffset: 1200;
            animation: heroDraw 2.6s .3s ease forwards;
            filter: drop-shadow(0 0 10px rgba(59,130,246,0.55));
        }
        @keyframes heroDraw { to { stroke-dashoffset: 0; } }
        .hero-graph .g-dot { fill: #22c55e; stroke: #fff; stroke-width: 2; filter: drop-shadow(0 0 8px rgba(34,197,94,0.8)); animation: particleFloat 3s ease-in-out infinite; }
        .hero-graph .g-label {
            font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 800; fill: #4ade80;
            background: transparent;
        }
        .hero-title {
            font-size: clamp(40px, 5vw, 64px);
            font-weight: 800; line-height: 1.04; letter-spacing: -0.035em;
            text-wrap: balance; max-width: 18ch;
            color: #f8fafc; margin: 0 auto 18px; text-align: center;
        }
        .hero-title .grad {
            background: linear-gradient(92deg, #60a5fa 0%, #22d3ee 38%, #a78bfa 72%, #f472b6 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
            filter: drop-shadow(0 0 22px rgba(96,165,250,0.35));
        }
        .hero-title .stroke { position: relative; white-space: nowrap; }
        .hero-title .stroke::after {
            content:''; position: absolute; left: 0; right: 0; bottom: 2px; height: 10px; z-index: -1;
            background: linear-gradient(90deg, rgba(59,130,246,0.35), rgba(34,211,238,0.28));
            border-radius: 6px; transform: skewX(-12deg) rotate(-1deg);
        }
        body.light-theme .hero-title { color: #0f172a; }
        body.light-theme .hero-title .grad { filter: none; }
        .hero-desc {
            font-size: 17px; color: #94a3b8; line-height: 1.75;
            margin: 0 auto 32px; max-width: 58ch; text-wrap: pretty; text-align: center;
        }
        body.light-theme .hero-desc { color: #64748b; }
        .hero-actions { display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap; }
        .btn-primary {
            position: relative; overflow: hidden;
            display: inline-flex; align-items: center; gap: 9px;
            padding: 14px 28px; border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 55%, #1d4ed8 100%);
            color: #fff; font-size: 14.5px; font-weight: 700; text-decoration: none;
            box-shadow: 0 12px 32px rgba(37,99,235,0.42), inset 0 1px 0 rgba(255,255,255,0.25);
            border: 1px solid rgba(255,255,255,0.14);
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .btn-primary::after {
            content:''; position: absolute; top: 0; left: -70%; width: 55%; height: 100%;
            background: linear-gradient(105deg, transparent, rgba(255,255,255,0.45), transparent);
            transform: skewX(-20deg); transition: left .6s ease;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 18px 44px rgba(37,99,235,0.55), inset 0 1px 0 rgba(255,255,255,0.25); }
        .btn-primary:hover::after { left: 130%; }
        .btn-secondary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 14px 26px; border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.14);
            color: #e2e8f0; font-size: 14.5px; font-weight: 600; text-decoration: none;
            background: rgba(255,255,255,0.04); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            transition: all .18s ease;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.24); transform: translateY(-2px); }
        body.light-theme .btn-secondary { border-color: rgba(15,23,42,0.14); color: #334155; background: rgba(255,255,255,0.9); }
        body.light-theme .btn-secondary:hover { background: #fff; border-color: rgba(15,23,42,0.22); }
        .hero-counter-row {
            display: flex; gap: 0; margin: 38px auto 0;
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px; padding: 16px 8px; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
            max-width: 520px; width: 100%; justify-content: center;
        }
        body.light-theme .hero-counter-row { background: rgba(255,255,255,0.9); border-color: rgba(15,23,42,0.08); box-shadow: 0 12px 32px rgba(15,23,42,0.06); }
        .hero-counter-item { flex: 1; display: flex; flex-direction: column; gap: 2px; padding: 0 22px; position: relative; }
        .hero-counter-item + .hero-counter-item::before { content:''; position: absolute; left: 0; top: 6px; bottom: 6px; width: 1px; background: linear-gradient(to bottom, transparent, rgba(148,163,184,0.28), transparent); }
        .hero-counter-value { font-size: 28px; font-weight: 800; letter-spacing: -0.02em; color: #f8fafc; font-variant-numeric: tabular-nums; }
        body.light-theme .hero-counter-value { color: #0f172a; }
        .hero-counter-label { font-size: 11.5px; color: #64748b; font-weight: 600; letter-spacing: .3px; text-transform: uppercase; }

        /* hero image removed — centered layout only */

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

        @media (max-width: 1024px) {
            .hero { max-width: 100%; padding: 90px 28px 80px; }
            .hero-left { max-width: 720px; }
            .hero-graph { width: min(860px, 112vw); height: 300px; }
        }
        @media (max-width: 768px) {
            .hero { padding: 72px 20px 64px; }
            .hero-left { max-width: 640px; }
            .hero-title { max-width: 16ch; }
            .hero-desc { max-width: 52ch; }
            .hero-counter-row { width: 100%; }
            .hero-graph { height: 260px; opacity: .75; }
            .hero-ring { right: -220px; }
            .about-grid, .reviews-grid { grid-template-columns: 1fr; }
            .about-section, .reviews-section { padding: 48px 20px; }
        }

        @media (max-width: 480px) {
            .site-header { padding: 6px 8px; gap: 6px; }
            .mobile-menu-button { width: 34px; height: 34px; }
            .header-btn { padding: 6px; }
            .user-avatar { width: 30px; height: 30px; }
            .hero { padding: 56px 16px 48px; }
            .hero-title { font-size: 32px; }
            .hero-desc { font-size: 14px; }
            .hero-counter-row { flex-direction: row; padding: 14px 4px; }
            .hero-counter-item { padding: 0 12px; }
            .hero-counter-value { font-size: 22px; }
            .hero-graph { height: 220px; opacity: .65; }
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

        /* ── LOGIN REQUIRED MODAL ─────────────────────────────── */
        .auth-modal-overlay {
            position: fixed; inset: 0; z-index: 300;
            background: rgba(2,6,23,0.7); backdrop-filter: blur(4px);
            display: none; align-items: center; justify-content: center; padding: 20px;
        }
        body.light-theme .auth-modal-overlay { background: rgba(15,23,42,0.45); }
        .auth-modal-overlay.active { display: flex; animation: authModalFade 0.2s ease; }
        @keyframes authModalFade { from { opacity: 0; } to { opacity: 1; } }
        .auth-modal {
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px;
            width: 100%; max-width: 420px; padding: 28px; text-align: center;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5); animation: authModalPop 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }
        body.light-theme .auth-modal { background: #ffffff; border-color: rgba(15,23,42,0.1); box-shadow: 0 24px 64px rgba(15,23,42,0.2); }
        @keyframes authModalPop { from { opacity: 0; transform: scale(0.92) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .auth-modal-icon {
            width: 56px; height: 56px; border-radius: 50%; margin: 0 auto 16px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(59,130,246,0.12); color: #60a5fa;
        }
        body.light-theme .auth-modal-icon { background: rgba(37,99,235,0.1); color: #2563eb; }
        .auth-modal-title { font-size: 18px; font-weight: 700; color: #f8fafc; margin-bottom: 8px; }
        body.light-theme .auth-modal-title { color: #0f172a; }
        .auth-modal-text { font-size: 14px; color: #94a3b8; line-height: 1.6; margin-bottom: 22px; }
        body.light-theme .auth-modal-text { color: #64748b; }
        .auth-modal-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .auth-modal-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            padding: 10px 20px; border-radius: 9px; font-size: 14px; font-weight: 600;
            text-decoration: none; cursor: pointer; font-family: 'Inter', sans-serif;
            border: 1px solid transparent; transition: filter 0.15s, background 0.15s;
        }
        .auth-modal-btn.primary { background: #3b82f6; color: #fff; }
        .auth-modal-btn.primary:hover { filter: brightness(1.1); }
        .auth-modal-btn.ghost { background: transparent; color: #94a3b8; border-color: rgba(255,255,255,0.15); }
        .auth-modal-btn.ghost:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
        body.light-theme .auth-modal-btn.ghost { color: #64748b; border-color: rgba(15,23,42,0.15); }
        body.light-theme .auth-modal-btn.ghost:hover { background: rgba(15,23,42,0.05); color: #0f172a; }
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
            @else
                <a href="{{ route('dashboard') }}" class="header-center-btn" onclick="requireLogin(event, 'Dashboard')">Dashboard</a>
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
                <a href="{{ route('dashboard') }}" class="mobile-nav-item" onclick="requireLogin(event, 'Dashboard')">
                    <span class="mobile-nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </span>
                    <span>Dashboard</span>
                </a>
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

    <!-- HERO SECTION — centered, no image, graph backdrop -->
    <section class="hero">
        <div class="hero-bg" aria-hidden="true">
            <div class="hero-aurora a1"></div>
            <div class="hero-aurora a2"></div>
            <div class="hero-aurora a3"></div>
            <div class="hero-grid"></div>
            <div class="hero-dots"></div>
            <div class="hero-beam"></div>
            <div class="hero-hairline"></div>
            <div class="hero-ring"></div>
            <span class="hero-particle p1"></span>
            <span class="hero-particle p2"></span>
            <span class="hero-particle p3"></span>
            <span class="hero-particle p4"></span>
            <div class="hero-noise"></div>
            <div class="hero-graph" aria-hidden="true">
                <svg viewBox="0 0 960 340" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="heroLineGrad" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="#60a5fa" stop-opacity="0.35"/>
                            <stop offset="45%" stop-color="#38bdf8" stop-opacity="0.9"/>
                            <stop offset="75%" stop-color="#22d3ee" stop-opacity="1"/>
                            <stop offset="100%" stop-color="#4ade80" stop-opacity="1"/>
                        </linearGradient>
                        <linearGradient id="heroAreaFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.28"/>
                            <stop offset="60%" stop-color="#38bdf8" stop-opacity="0.08"/>
                            <stop offset="100%" stop-color="#38bdf8" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <g>
                        <line class="g-grid" x1="0" y1="70" x2="960" y2="70"/>
                        <line class="g-grid" x1="0" y1="140" x2="960" y2="140"/>
                        <line class="g-grid" x1="0" y1="210" x2="960" y2="210"/>
                        <line class="g-grid" x1="0" y1="280" x2="960" y2="280"/>
                    </g>
                    <path class="g-area" d="M0,300 C80,290 140,260 220,245 C300,230 340,240 420,200 C500,160 540,170 620,130 C700,90 780,100 860,55 C890,38 920,30 960,22 L960,340 L0,340 Z"/>
                    <path class="g-line" d="M0,300 C80,290 140,260 220,245 C300,230 340,240 420,200 C500,160 540,170 620,130 C700,90 780,100 860,55 C890,38 920,30 960,22"/>
                    <circle class="g-dot" cx="860" cy="55" r="7"/>
                    <circle class="g-dot" cx="620" cy="130" r="4" style="animation-delay:-1s;fill:#38bdf8"/>
                    <circle class="g-dot" cx="420" cy="200" r="4" style="animation-delay:-2s;fill:#38bdf8"/>
                </svg>
            </div>
        </div>

        <div class="hero-left">
            <h1 class="hero-title">Manage Your Hardware Products with <span class="grad stroke">Smart Stock</span> Inventory</h1>
            <p class="hero-desc">
                Smart-Stock helps hardware stores track products, monitor stock levels in real time,
                and get instant alerts — all from one easy-to-use dashboard.
            </p>
            <div class="hero-actions">
                @guest
                    <a href="{{ route('login') }}" class="btn-primary">Get Started <span aria-hidden="true">→</span></a>
                    <a href="{{ route('register') }}" class="btn-secondary">Create Account</a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn-primary">Open Dashboard <span aria-hidden="true">→</span></a>
                @endguest
            </div>
            <div class="hero-counter-row">
                <div class="hero-counter-item">
                    <span class="hero-counter-value" data-count="{{ $heroStats['products'] ?? 0 }}">0</span>
                    <span class="hero-counter-label">Products Tracked</span>
                </div>
                <div class="hero-counter-item">
                    <span class="hero-counter-value" data-count="{{ $heroStats['suppliers'] ?? 0 }}">0</span>
                    <span class="hero-counter-label">Suppliers</span>
                </div>
                <div class="hero-counter-item">
                    <span class="hero-counter-value" data-count="{{ $heroStats['sales'] ?? 0 }}">0</span>
                    <span class="hero-counter-label">Sales Recorded</span>
                </div>
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

    <!-- LOGIN REQUIRED MODAL -->
    <div class="auth-modal-overlay" id="authModalOverlay" role="dialog" aria-modal="true" aria-labelledby="authModalTitle" onclick="if (event.target === this) closeAuthModal();">
        <div class="auth-modal">
            <div class="auth-modal-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <h3 class="auth-modal-title" id="authModalTitle">Login Required</h3>
            <p class="auth-modal-text">You must be logged in to access the <span id="authModalTarget">Dashboard</span>. Please log in to continue.</p>
            <div class="auth-modal-actions">
                <a href="{{ route('login') }}" class="auth-modal-btn primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    Go to Login
                </a>
                <button type="button" class="auth-modal-btn ghost" onclick="closeAuthModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        /* ── Login-required modal (guests only) ──
           NOTE: deliberately does NOT toggle body overflow.
           Hiding the scrollbar changes the viewport width and shifts the
           whole layout, which looks like the page "jumps". The page keeps
           its scrollbar and scroll position behind the modal. */
        function openAuthModal(target) {
            const overlay = document.getElementById('authModalOverlay');
            if (!overlay) return;
            const label = document.getElementById('authModalTarget');
            if (label) label.textContent = target || 'Dashboard';
            overlay.classList.add('active');
        }
        function closeAuthModal() {
            const overlay = document.getElementById('authModalOverlay');
            if (!overlay) return;
            overlay.classList.remove('active');
        }
        function requireLogin(event, target) {
            if (event) event.preventDefault();
            openAuthModal(target);
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAuthModal();
        });

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
                if (isNaN(target)) { el.textContent = '0'; return; }
                // No animation needed when there is nothing to count up to.
                if (target === 0) { el.textContent = '0'; return; }
                var isDecimal = target % 1 !== 0;
                var duration = 1500;
                var startTime = performance.now();
                function update(currentTime) {
                    var elapsed = currentTime - startTime;
                    var progress = Math.min(elapsed / duration, 1);
                    var eased = 1 - Math.pow(1 - progress, 3);
                    var current = eased * target;
                    el.textContent = isDecimal ? current.toFixed(1) : Math.floor(current).toLocaleString('en-PH');
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
