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
            height: 100vh;
            display: flex;
            flex-direction: column;
            /* The pane below is the only scroller, so the page itself must not
               scroll — same model as the dashboard layout. */
            overflow: hidden;
        }
        body.light-theme { background: #f3f4f6; color: #334155; }

        /* ── SCROLL PANE ─────────────────────────────────────────
           Mirrors .main from layouts/dashboard-main-frame.blade.php so the homepage and the
           dashboard scroll identically, and the bar starts below the header. */
        .page-pane {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
        }
        .page-pane::-webkit-scrollbar { width: 12px; }
        .page-pane::-webkit-scrollbar-track { background: #f0f0f0; }
        .page-pane::-webkit-scrollbar-thumb { background: #c0c0c0; border: 2px solid #f0f0f0; }
        .page-pane::-webkit-scrollbar-thumb:hover { background: #a0a0a0; }
        .page-pane::-webkit-scrollbar-thumb:active { background: #808080; }
        body.light-theme .page-pane::-webkit-scrollbar-track { background: #e0e0e0; }
        body.light-theme .page-pane::-webkit-scrollbar-thumb { background: #b0b0b0; border-color: #e0e0e0; }
        body.light-theme .page-pane::-webkit-scrollbar-thumb:hover { background: #909090; }
        body.light-theme .page-pane::-webkit-scrollbar-thumb:active { background: #707070; }

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
            /* Mark colours, defined here so the inline SVG in the header can
               use the same buy/sell palette as the hero mark. */
            --candle-up: #22c55e;
            --candle-down: #ef4444;
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
        .header-brand:hover .header-brand-name,
                .header-brand:hover .header-brand-sub { color: #cbd5e1; }
                body.light-theme .header-brand:hover .header-brand-name,
                body.light-theme .header-brand:hover .header-brand-sub { color: #334155; }
        body.light-theme .site-header {
            --candle-up: #16a34a;
            --candle-down: #dc2626;
        }
        .header-brand-mark {
            width: 50px;
            height: 40px;
            flex: 0 0 auto;
            display: block;
        }
        .header-brand-mark .mk-up { fill: var(--candle-up); }
        .header-brand-mark .mk-down { fill: var(--candle-down); }
        .header-brand-mark .mk-axis { fill: none; stroke: #94a3b8; stroke-width: 4; stroke-linecap: square; }
        .header-brand-mark .mk-arrow { fill: none; stroke: var(--candle-up); stroke-width: 6; stroke-linecap: round; stroke-linejoin: round; }
        body.light-theme .header-brand-mark .mk-axis { stroke: #64748b; }
        /* Both lines share one rule so they can never drift apart again. Only the
                   text content differs — the second line reads "Stock". */
                .header-brand-name,
                .header-brand-sub  { font-size: 17px; font-weight: 700; color: #f8fafc; line-height: 1.05; letter-spacing: 0.01em; }
                body.light-theme .header-brand-name,
                body.light-theme .header-brand-sub  { color: #0f172a; }

        .header-right { display: flex; align-items: center; gap: 12px; }

        /* ── NAV BAR ─────────────────────────────────────────────
           Absolutely positioned so the group sits on the header's true
           centre line. With `justify-content: space-between` the nav only
           lands in the middle when the logo and the right-hand controls
           happen to be the same width, which they never are. */
        .header-center {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .header-center-btn {
            display: inline-flex; align-items: center; padding: 0 16px; height: 36px; border-radius: 8px;
            border: none; color: #94a3b8; font-size: 14px; font-weight: 500;
            text-decoration: none; transition: color 0.15s, background 0.15s, font-weight 0.15s; background: none; cursor: pointer;
            font-family: 'Inter', sans-serif;
        }
        /* Dark theme (default): the active tab is near-white so it reads on
           the dark header, and inactive tabs sit back in muted gray. The
           light theme below keeps the usual dark-on-light treatment. */
        .header-center-btn:hover { color: #e2e8f0; }
        .header-center-btn.active { color: #ffffff; font-weight: 700; background: rgba(255,255,255,0.10); }
        body.light-theme .header-center-btn { color: #64748b; }
        body.light-theme .header-center-btn:hover { color: #0f172a; }
        body.light-theme .header-center-btn.active { color: #0f172a; font-weight: 700; background: rgba(15,23,42,0.06); }

        /* Theme toggle - matches dashboard header-btn style */
        .header-btn { cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); color: #94a3b8; font-size: 14px; font-weight: 500; transition: background 0.15s; user-select: none; background: none; min-height: 40px; }
        /* Square the theme toggle. Fixed width and height plus centered
           content stop the SVG's 17x17 box from stretching the button wide. */
        #themeToggleBtn {
            padding: 0;
            width: 40px;
            min-width: 40px;
            height: 40px;
            justify-content: center;
        }
        .header-btn:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
        body.light-theme .header-btn { color: #64748b; border-color: rgba(15,23,42,0.1); }
        body.light-theme .header-btn:hover { background: rgba(15,23,42,0.05); color: #0f172a; }

        /* Header badge for alerts */
        .header-badge { background: #ef4444; color: #fff; border-radius: 8px; min-width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; font-size: 10px; font-weight: 700; }
        .header-badge.hidden { display: none; }

        /* Alerts wrapper */
        .header-alert-wrapper { position: relative; }
        .header-btn-icon { padding: 8px !important; min-width: 40px; justify-content: center; }
        .alert-dropdown {
            position: absolute; top: calc(100% + 10px); right: 0;
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px; width: 388px; max-height: 440px; z-index: 150;
            display: none; flex-direction: column; padding: 10px;
            box-shadow: 0 18px 40px rgba(2,6,23,0.45);
        }
        .alert-dropdown.active { display: flex; }
        .alert-dropdown-header {
            flex: 0 0 auto; padding: 6px 8px 12px; margin-bottom: 2px;
            font-size: 13px; font-weight: 600; color: #f8fafc;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            display: flex; justify-content: space-between; align-items: center;
        }
        .alert-dropdown-clear { font-size: 11px; color: #64748b; cursor: pointer; font-weight: 500; transition: color 0.15s; }
        .alert-dropdown-clear:hover { color: #f8fafc; }
        /* Only the list scrolls, so the header and "Clear all" stay reachable. */
        #headerAlertList {
            flex: 1 1 auto; min-height: 0; overflow-y: auto;
            display: flex; flex-direction: column; gap: 8px;
            padding: 2px; margin: 0 -2px;
        }
        #headerAlertList::-webkit-scrollbar { width: 6px; }
        #headerAlertList::-webkit-scrollbar-track { background: transparent; }
        #headerAlertList::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.25); border-radius: 3px; }
        #headerAlertList::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,0.4); }
        .alert-empty {
            padding: 30px 16px; text-align: center; color: #475569; font-size: 13px;
            border: 1px dashed rgba(255,255,255,0.1); border-radius: 10px; margin: 2px;
        }
        /* Each alert is its own bordered card. Severity drives the border tint,
           so a card reads as a state rather than just carrying a coloured dot. */
        .alert-card {
            flex: 0 0 auto; padding: 12px 13px; display: flex; gap: 10px;
            position: relative; border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px; background: rgba(255,255,255,0.02);
            transition: border-color 0.15s, background 0.15s;
        }
        .alert-card.critical { border-color: rgba(248,113,113,0.28); background: rgba(248,113,113,0.05); }
        .alert-card.low { border-color: rgba(251,191,36,0.26); background: rgba(251,191,36,0.045); }
        .alert-card:hover { border-color: rgba(255,255,255,0.2); background: rgba(255,255,255,0.06); }
        .alert-card.critical:hover { border-color: rgba(248,113,113,0.5); background: rgba(248,113,113,0.10); }
        .alert-card.low:hover { border-color: rgba(251,191,36,0.48); background: rgba(251,191,36,0.09); }
        .alert-card-severity { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
        .alert-card-severity.critical { background: #f87171; }
        .alert-card-severity.low { background: #fbbf24; }
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
        body.light-theme .alert-dropdown { background: #ffffff; border-color: rgba(15,23,42,0.1); }
        body.light-theme .alert-dropdown-header { background: #f8fafc; color: #0f172a; border-bottom-color: rgba(15,23,42,0.08); }
        body.light-theme .alert-dropdown-clear { color: #64748b; }
        body.light-theme .alert-dropdown-clear:hover { color: #0f172a; }
        body.light-theme .alert-empty { color: #94a3b8; }
        body.light-theme #headerAlertList::-webkit-scrollbar-thumb { background: rgba(15,23,42,0.18); }
        body.light-theme #headerAlertList::-webkit-scrollbar-thumb:hover { background: rgba(15,23,42,0.3); }
        body.light-theme .alert-empty { border-color: rgba(15,23,42,0.12); }
        body.light-theme .alert-card { background: rgba(15,23,42,0.015); border-color: rgba(15,23,42,0.08); }
        body.light-theme .alert-card:hover { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.16); }
        body.light-theme .alert-card.critical { border-color: rgba(220,38,38,0.22); background: rgba(239,68,68,0.035); }
        body.light-theme .alert-card.low { border-color: rgba(202,138,4,0.24); background: rgba(251,191,36,0.05); }
        body.light-theme .alert-card.critical:hover { border-color: rgba(220,38,38,0.42); background: rgba(239,68,68,0.07); }
        body.light-theme .alert-card.low:hover { border-color: rgba(202,138,4,0.44); background: rgba(251,191,36,0.1); }
        body.light-theme .alert-card-name { color: #0f172a; }
        body.light-theme .alert-card-sku { color: #94a3b8; }
        body.light-theme .alert-card-stock { color: #64748b; }
        body.light-theme .alert-date-tag { color: #94a3b8; }
        body.light-theme .alert-dismiss-btn { color: #94a3b8; }
        body.light-theme .alert-dismiss-btn:hover { color: #ef4444; background: rgba(239,68,68,0.08); }

        /* Login button (guest) — sized to the signed-in .header-user button
           (8px padding + 32px avatar + 8px, plus its 0.8px border top and
           bottom = 49.6px) so the header row keeps the same height whether
           the visitor is signed in or out. The transparent border reproduces
           the .header-user border without changing the blue fill. */
        .btn-login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 49.6px;
            height: 49.6px;
            padding: 0 18px;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: 0.8px solid transparent;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-login:hover { background: #1d4ed8; }
        /* The person glyph is circled to mirror the signed-in avatar, so the
           two states read as the same control rather than a different one. */
        .btn-login-avatar {
            width: 32px; height: 32px; flex: 0 0 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.24);
        }
        .btn-login-avatar svg { display: block; }

        /* User button (authenticated) - matches dashboard header-user style */
        .header-user { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); cursor: pointer; position: relative; min-height: 40px; }
        .header-user:hover { background: rgba(255,255,255,0.05); }
        body.light-theme .header-user { border-color: rgba(15,23,42,0.1); }
        body.light-theme .header-user:hover { background: rgba(15,23,42,0.03); }
        .user-avatar {
            width: 32px; height: 32px; flex: 0 0 32px; border-radius: 50%;
            /* Flat slate fill — the old blue gradient read as a floating badge. */
            background: #334155;
            border: 1px solid rgba(255,255,255,0.12);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 12px; font-weight: 700; text-transform: uppercase;
        }
        body.light-theme .user-avatar { background: #e2e8f0; border-color: rgba(15,23,42,0.12); color: #334155; }
        .user-name { font-size: 13px; font-weight: 600; color: #f8fafc; }
        .user-email { font-size: 11px; color: #64748b; }
        body.light-theme .user-name { color: #0f172a; }
        body.light-theme .user-email { color: #64748b; }

        /* User dropdown ? each action is its own bordered "form" card inside a
           padded menu, with an identity header on top. Mirrors dashboard-main-frame.blade.php. */
        .user-dropdown {
            position: absolute; top: calc(100% + 10px); right: 0;
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px; width: 258px; z-index: 200;
            display: none; padding: 10px;
            box-shadow: 0 18px 40px rgba(2,6,23,0.45);
        }
        .user-dropdown.open { display: flex; flex-direction: column; gap: 8px; }
        body.light-theme .user-dropdown { background: #ffffff; border-color: rgba(15,23,42,0.1); box-shadow: 0 18px 40px rgba(15,23,42,0.14); }
        .user-dropdown-head {
            display: flex; align-items: center; gap: 11px;
            padding: 4px 6px 12px; margin-bottom: 2px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .user-dropdown-head-avatar {
            width: 36px; height: 36px; flex: 0 0 auto;
            border-radius: 50%; display: grid; place-items: center;
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
            color: #f8fafc; font-size: 14px; font-weight: 700;
        }
        .user-dropdown-head-text { min-width: 0; }
        .user-dropdown-head-name {
            font-size: 13px; font-weight: 700; color: #f8fafc; line-height: 1.3;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        /* No text-transform: the email is user-supplied data, so it must render
                   exactly as entered rather than being force-uppercased. */
                .user-dropdown-head-role {
                    font-size: 11px; font-weight: 500; letter-spacing: 0;
                    color: #94a3b8; line-height: 1.3; margin-top: 2px;
                    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                }
        body.light-theme .user-dropdown-head { border-bottom-color: rgba(15,23,42,0.08); }
        body.light-theme .user-dropdown-head-avatar { background: #e2e8f0; border-color: rgba(15,23,42,0.12); color: #334155; }
        body.light-theme .user-dropdown-head-name { color: #0f172a; }
        body.light-theme .user-dropdown-head-role { color: #64748b; }
        .user-dropdown-form {
            margin: 0; border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px; overflow: hidden; background: rgba(255,255,255,0.02);
            transition: border-color 0.15s, background 0.15s;
        }
        .user-dropdown-form:hover { border-color: rgba(255,255,255,0.18); background: rgba(255,255,255,0.05); }
        .dropdown-item {
            display: flex; align-items: center; gap: 11px;
            padding: 11px 13px;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
            cursor: pointer;
        }
        .dropdown-item svg { flex: 0 0 auto; opacity: 0.85; }
        .dropdown-item:hover { background: rgba(255,255,255,0.06); color: #f8fafc; }
        .dropdown-item.logout { color: #fca5a5; }
        .user-dropdown-form:has(.logout) { border-color: rgba(239,68,68,0.22); background: rgba(239,68,68,0.05); }
        .user-dropdown-form:has(.logout):hover { border-color: rgba(239,68,68,0.45); background: rgba(239,68,68,0.10); }
        .dropdown-item.logout:hover { background: rgba(239,68,68,0.14); color: #f87171; }
        body.light-theme .user-dropdown-form { background: rgba(15,23,42,0.015); border-color: rgba(15,23,42,0.08); }
        body.light-theme .user-dropdown-form:hover { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.16); }
        body.light-theme .dropdown-item { color: #475569; }
        body.light-theme .dropdown-item:hover { background: rgba(15,23,42,0.05); color: #0f172a; }
        body.light-theme .dropdown-item.logout { color: #dc2626; }
        body.light-theme .user-dropdown-form:has(.logout) { border-color: rgba(220,38,38,0.2); background: rgba(239,68,68,0.035); }
        body.light-theme .user-dropdown-form:has(.logout):hover { border-color: rgba(220,38,38,0.4); background: rgba(239,68,68,0.07); }
        body.light-theme .dropdown-item.logout:hover { background: rgba(239,68,68,0.08); color: #ef4444; }

        /* ── HERO — centered, no image, chart backdrop ──────────── */
        .hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 0;
            /* Tighter top padding: the wordmark sits closer to the header.
               The slack goes below the title instead, so only the paragraph
               and everything below it move down. */
            padding: 120px 24px 150px;
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            isolation: isolate;
            overflow: visible;
        }
        /* Full-bleed canvas — solid colour in dark mode, paper in light mode. */
        .hero {
            /* The About/Reviews bands below now carry #1e293b, so the hero
               drops to the body colour and the two read as distinct zones. */
            --hero-surface: #0f172a;
            --hero-text: #f8fafc;
            --hero-text-strong: #fff;
            --hero-muted: #94a3b8;
            --hero-btn-ghost-bg: rgba(255,255,255,0.04);
            --hero-btn-ghost-border: rgba(255,255,255,0.14);
            --hero-btn-ghost-text: #e2e8f0;
            --hero-btn-ghost-bg-hover: rgba(255,255,255,0.08);
            --hero-btn-ghost-border-hover: rgba(255,255,255,0.24);
            --hero-divider: rgba(148,163,184,0.28);
                        /* ── Aurora depth tokens ───────────────────────────────
                                                   Six independently tuned hues composited as light
                                                   sources over the base ramp. Values are deliberately
                                                   desaturated and low-alpha: stacking many layers at
                                                   high alpha turns to mud on #0f172a. */
                                                --hero-glow-top: #1b2942;
                                                --hero-glow-bottom: #050b16;
                                                --hero-blob-a: rgba(56,189,248,0.34);  /* sky   */
                                                --hero-blob-b: rgba(99,102,241,0.30);  /* indigo*/
                                                --hero-blob-c: rgba(168,85,247,0.26);   /* violet*/
                                                --hero-blob-d: rgba(16,185,129,0.20);   /* teal  */
                                                --hero-blob-e: rgba(34,197,94,0.16);    /* brand */
                                                --hero-blob-f: rgba(236,72,153,0.14);   /* rose  */
                                                /* Iridescent sweep: a conic prism blurred to a whisper. */
                                                --hero-iris-a: rgba(56,189,248,0.30);
                                                --hero-iris-b: rgba(168,85,247,0.26);
                                                --hero-iris-c: rgba(236,72,153,0.18);
                                                --hero-iris-blend: soft-light;
                                                --hero-iris-opacity: 0.85;
                                                /* Raking key light + edge shade. */
                                                --hero-streak: rgba(255,255,255,0.55);
                                                --hero-streak-blend: soft-light;
                                                --hero-streak-opacity: 0.50;
                                                --hero-vignette: rgba(1,4,12,0.72);
                                                --hero-top-shade: rgba(1,4,12,0.55);
                        --candle-up: #22c55e;
                        --candle-down: #ef4444;
        }
        body.light-theme .hero {
                    /* The header above is rgba(255,255,255,0.9), so the hero must NOT
                       reach white at its top edge or the two bands merge into one.
                       Every stop below is held at least a full step darker/tinted
                       away from #ffffff, which keeps a visible seam under the header. */
                    --hero-surface: #cdd8ea;
                    --hero-text: #0f172a;
                    --hero-text-strong: #0f172a;
                    --hero-muted: #64748b;
                    --hero-btn-ghost-bg: rgba(255,255,255,0.9);
                    --hero-btn-ghost-border: rgba(15,23,42,0.14);
                    --hero-btn-ghost-text: #334155;
                    --hero-btn-ghost-bg-hover: #fff;
                    --hero-btn-ghost-border-hover: rgba(15,23,42,0.22);
                    --hero-divider: rgba(15,23,42,0.12);
                                /* Light theme: a tinted blue-lavender volume. No stop is
                                                           white, so the band always reads as distinct from
                                                           the white header sitting directly above it. */
                                                        --hero-glow-top: #dfe7f4;
                                                        --hero-glow-bottom: #b4c3da;
                                                        --hero-blob-a: rgba(56,189,248,0.34);
                                                        --hero-blob-b: rgba(99,102,241,0.26);
                                                        --hero-blob-c: rgba(168,85,247,0.24);
                                                        --hero-blob-d: rgba(20,184,166,0.22);
                                                        --hero-blob-e: rgba(34,197,94,0.18);
                                                        --hero-blob-f: rgba(244,114,182,0.16);
                                                        --hero-iris-a: rgba(99,102,241,0.26);
                                                        --hero-iris-b: rgba(56,189,248,0.26);
                                                        --hero-iris-c: rgba(236,72,153,0.16);
                                                        --hero-iris-blend: overlay;
                                                        --hero-iris-opacity: 0.60;
                                                        /* Streak is a sheen, not a fill — kept translucent so
                                                           it can never lift the band back to white. */
                                                        --hero-streak: rgba(255,255,255,0.42);
                                                        --hero-streak-blend: overlay;
                                                        --hero-streak-opacity: 0.55;
                                                        /* Heavier shade than before: this is what actually
                                                           separates the hero from the header at the seam. */
                                                        --hero-vignette: rgba(71,85,105,0.42);
                                                        /* Solid enough to read as a cast shadow under the
                                                           white header — this is the seam that keeps the
                                                           hero a separate band. */
                                                        --hero-top-shade: rgba(51,65,85,0.55);
                                --candle-up: #16a34a;
                        --candle-down: #dc2626;
        }
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
                    /* Layer 1 — the substrate. An asymmetric 4-stop vertical ramp
                       (light enters from the upper left, falls to near-black at the
                       base) gives the band a floor-to-sky axis before any colour is
                       added. */
                    background:
                        linear-gradient(178deg,
                            var(--hero-glow-top) 0%,
                            var(--hero-surface) 38%,
                            color-mix(in srgb, var(--hero-surface) 70%, #000) 68%,
                            var(--hero-glow-bottom) 100%);
                }
                /* Layer 2 — the aurora field. Six radial blobs at asymmetric positions
                   and radii (never mirrored, never evenly spaced) so the result has no
                   repeating rhythm. Each fades to transparent well before the next
                   begins, which keeps them reading as discrete light sources rather
                   than one smear. */
                .hero-bg::before {
                    content: '';
                    position: absolute;
                    inset: -12%;
                    background-image:
                        radial-gradient(46% 40% at 14% 10%,  var(--hero-blob-a) 0%, transparent 70%),
                        radial-gradient(40% 44% at 78% 18%,  var(--hero-blob-b) 0%, transparent 68%),
                        radial-gradient(52% 40% at 62% 42%,  var(--hero-blob-c) 0%, transparent 72%),
                        radial-gradient(44% 46% at 26% 56%,  var(--hero-blob-d) 0%, transparent 70%),
                        radial-gradient(56% 44% at 52% 84%,  var(--hero-blob-e) 0%, transparent 72%),
                        radial-gradient(38% 36% at 90% 66%,  var(--hero-blob-f) 0%, transparent 66%);
                    /* Elliptical feather: strong through the middle, gone at every edge,
                       so the aurora never produces a visible rectangle boundary. */
                    -webkit-mask-image: radial-gradient(108% 92% at 46% 34%, #000 22%, transparent 96%);
                    mask-image: radial-gradient(108% 92% at 46% 34%, #000 22%, transparent 96%);
                    opacity: 0.95;
                }
                /* Layer 3 — prism sweep. A conic gradient (the CSS equivalent of light
                   refracting through a prism) composited in soft-light. It spans the
                   full hue wheel at very low alpha, so it reads as a subtle iridescent
                   film over the aurora instead of banding. */
                .hero-bg::after {
                    content: '';
                    position: absolute;
                    inset: -6%;
                    background:
                        conic-gradient(from 168deg at 46% 26%,
                            transparent 0deg,
                            var(--hero-iris-a) 46deg,
                            var(--hero-iris-b) 108deg,
                            var(--hero-iris-c) 168deg,
                            transparent 236deg,
                            transparent 360deg);
                    -webkit-mask-image: radial-gradient(96% 82% at 48% 32%, #000 12%, transparent 90%);
                    mask-image: radial-gradient(96% 82% at 48% 32%, #000 12%, transparent 90%);
                    mix-blend-mode: var(--hero-iris-blend);
                    opacity: var(--hero-iris-opacity);
                }
                /* Layer 4 — raking key light. Two narrow, tilted bands of light
                                   sweeping across the upper third, the way a spotlight rakes a
                                   curved panel. Drawn as a pair of linear-gradients clipped to a
                                   diagonal so they only cross the lit zone. */
                                .hero::before {
                                    content: '';
                                    position: absolute;
                                    top: -64px;
                                    bottom: -20px;
                                    left: 50%;
                                    transform: translateX(-50%);
                                    width: 100vw;
                                    z-index: 1;
                                    pointer-events: none;
                                    background:
                                        linear-gradient(104deg,
                                            transparent 26%,
                                            var(--hero-streak) 40%,
                                            transparent 52%),
                                        linear-gradient(104deg,
                                            transparent 58%,
                                            var(--hero-streak) 66%,
                                            transparent 74%);
                                    mix-blend-mode: var(--hero-streak-blend);
                                    opacity: var(--hero-streak-opacity);
                                    -webkit-mask-image: radial-gradient(88% 70% at 46% 18%, #000 8%, transparent 76%);
                                    mask-image: radial-gradient(88% 70% at 46% 18%, #000 8%, transparent 76%);
                                }
                                /* Layer 5 — contact shading. Combines an edge vignette (rounds the
                                   volume off) with a bottom contact shadow (seats the band against
                                   the About section below). Without the second term the band ends
                                   abruptly and the depth collapses. */
                                .hero::after {
                                    content: '';
                                    position: absolute;
                                    top: -64px;
                                    bottom: -20px;
                                    left: 50%;
                                    transform: translateX(-50%);
                                    width: 100vw;
                                    z-index: 1;
                                    pointer-events: none;
                                    background:
                                        linear-gradient(to bottom, transparent 72%, var(--hero-vignette) 100%),
                                                                            radial-gradient(96% 82% at 50% 40%, transparent 52%, var(--hero-vignette) 100%),
                                                                            /* Top-edge shade. The header sits directly on top of this
                                                                               band, so a shadow cast downward from the seam guarantees
                                                                               the two never read as one continuous surface — most
                                                                               visible in light theme, where the header is white. */
                                                                            linear-gradient(to bottom, var(--hero-top-shade) 0%, transparent 16%);
                                                                    }

        .hero-left { flex: none; width: 100%; max-width: 780px; position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; }
        /* Mark above the wordmark — the real project logo (axes, bars, rising
           trend arrow) redrawn inline rather than linked as a raster. The PNG
           is a flat near-black graphic (avg RGB ~41,38,39), so dropping it in
           directly would leave an invisible smudge on the dark hero; drawing
           it as SVG keeps the coloured buy/sell treatment. */
        .hero-mark {
            width: 112px;
            height: 90px;
            margin: 0 auto 28px;
            display: block;
        }
        .hero-mark .mk-up { fill: var(--candle-up); }
        .hero-mark .mk-down { fill: var(--candle-down); }
        .hero-mark .mk-axis { fill: none; stroke: var(--hero-muted); stroke-width: 4; stroke-linecap: square; }
        .hero-mark .mk-arrow { fill: none; stroke: var(--candle-up); stroke-width: 6; stroke-linecap: round; stroke-linejoin: round; }
        /* Wordmark — the loudest element on the page, so the product name
           is the first thing a visitor reads. */
        .hero-brand {
            /* Capped so "SMART STOCK" always fits inside the 780px column
               instead of overflowing it on wide screens. */
            font-size: clamp(38px, 6.4vw, 76px);
            /* Segoe UI Historic ships with Windows only, so Georgia leads the
               fallback chain for macOS/Linux — it has a similar old-style
               serif character rather than falling back to a sans face. */
            font-family: 'Segoe UI Historic', 'Segoe UI', Georgia, 'Times New Roman', serif;
            font-weight: 700; line-height: 1; letter-spacing: 0.01em;
            /* Segoe UI Historic only ships 400 and 700, so a heavier weight
               would be synthesised and look uneven. A small text-stroke
               thickens the real 700 glyphs evenly instead. */
            -webkit-text-stroke: 0.02em currentColor;
            paint-order: stroke fill;
            color: var(--hero-text); margin: 0 auto 16px; text-align: center;
            text-transform: uppercase; white-space: nowrap;
        }
        .hero-brand-gap { display: inline-block; width: 0.26em; }
        /* Lead paragraph under the wordmark. The 42ch measure is deliberate:
           it wraps the sentence into two balanced lines at desktop widths
           rather than running it long across the hero. */
        .hero-title {
            font-size: clamp(17px, 1.8vw, 21px);
            font-weight: 500; line-height: 1.5; letter-spacing: -0.01em;
            text-wrap: balance; max-width: 42ch;
            color: var(--hero-muted); margin: 0 auto 30px; text-align: center;
        }
        .hero-actions { display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 14px 28px; border-radius: 12px;
            background: #2563eb;
            color: #fff; font-size: 14.5px; font-weight: 700; text-decoration: none;
            border: 1px solid rgba(255,255,255,0.14);
            transition: background .18s ease;
        }
        .btn-primary:hover { background: #1d4ed8; }
        /* The "→" glyph is only ~5px of ink tall against an 11px cap height,
           so it read as a stub next to the label. An inline SVG lets the
           arrow's height track the cap height instead of the font size, and
           it cannot be nudged by font fallback the way a text glyph can.
           The head deliberately spans y=2..22 of the 24-unit box (all but the
           stroke inset) so it fills the full height rather than sitting in
           the middle third of it, which is what made it look short. */
        .btn-primary .btn-arrow {
            height: 0.82em;
            width: auto;
            display: block;
            flex: none;
        }
        .btn-primary .btn-arrow path,
        .btn-primary .btn-arrow polyline {
            fill: none;
            stroke: currentColor;
            stroke-width: 2.4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .btn-secondary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 14px 26px; border-radius: 12px;
            border: 1px solid var(--hero-btn-ghost-border);
            color: var(--hero-btn-ghost-text); font-size: 14.5px; font-weight: 600; text-decoration: none;
            background: var(--hero-btn-ghost-bg); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            transition: all .18s ease;
        }
        .btn-secondary:hover { background: var(--hero-btn-ghost-bg-hover); border-color: var(--hero-btn-ghost-border-hover); transform: translateY(-2px); }
        /* Plain stats row — no panel, no border, no blur. The numbers sit
           directly on the hero canvas. */
        .hero-counter-row {
            display: flex; gap: 0; margin: 38px auto 0;
            max-width: 520px; width: 100%; justify-content: center;
        }
        .hero-counter-item { flex: 1; display: flex; flex-direction: column; gap: 2px; padding: 0 22px; position: relative; }
        .hero-counter-item + .hero-counter-item::before { content:''; position: absolute; left: 0; top: 6px; bottom: 6px; width: 1px; background: linear-gradient(to bottom, transparent, var(--hero-divider), transparent); }
        .hero-counter-value { font-size: 28px; font-weight: 800; letter-spacing: -0.02em; color: var(--hero-text); font-variant-numeric: tabular-nums; }
        /* The "+" is drawn from the data attribute rather than baked into the
           markup, so the JS that rewrites textContent during the count-up can
           never strip it. Only the hero stats carry the attribute — the 4.5
           rating in the reviews section must stay suffix-free. */
        .hero-counter-value[data-suffix]::after { content: attr(data-suffix); }
        .hero-counter-label { font-size: 11.5px; color: var(--hero-muted); font-weight: 600; letter-spacing: .3px; text-transform: uppercase; }
        /* hero image removed — centered layout only */

        /* ── ABOUT ────────────────────────────────────────────────── */
        .about-section {
            padding: 80px 40px;
            max-width: 1100px;
            margin: 0 auto;
            position: relative;
            isolation: isolate;
        }
        /* Dark theme: this section takes over the hero's band colour so the
           page reads as one continuous surface rather than alternating slabs.
           Painted on a pseudo-element so the colour is full-bleed even though
           the section itself is width-capped for its text column. */
        body:not(.light-theme) .about-section::before {
            content: '';
            position: absolute;
            top: 0; bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100vw;
            z-index: -1;
            background: #1e293b;
        }
        .about-header {
            text-align: center;
            margin-bottom: 48px;
        }
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
            position: relative;
            isolation: isolate;
        }
        /* Matches the hero and About bands above. */
        body:not(.light-theme) .reviews-section::before {
            content: '';
            position: absolute;
            top: 0; bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100vw;
            z-index: -1;
            background: #1e293b;
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
            color: #60a5fa;        }
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
            color: #94a3b8;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        /* Dark theme: the same surface as the sticky header, so the top and
           bottom of the page share one colour. */
        body:not(.light-theme) .site-footer { background: #253347; }
        /* Light theme: the same translucent white as the sticky header. */
        body.light-theme .site-footer {
            background: rgba(255,255,255,0.9);
            border-top-color: rgba(15,23,42,0.08);
            color: #94a3b8;
        }

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
            .header-brand-mark { width: 40px; height: 32px; }
            .header-right { gap: 6px; margin-left: auto; }
            .header-btn { padding: 6px 8px; }
            /* Keep the theme toggle square at this width — the generic
               padding above would otherwise squash it into an oval. */
            #themeToggleBtn { padding: 0; width: 36px; min-width: 36px; height: 36px; }
            .btn-login { height: 49.6px; min-height: 49.6px; padding: 0 14px; }
            .btn-login-avatar { width: 32px; height: 32px; flex-basis: 32px; }
            .header-user { padding: 8px; }
            .user-info { display: none; }
            .user-avatar { width: 32px; height: 32px; }
            .alert-dropdown { width: min(340px, calc(100vw - 24px)); }
            .user-dropdown { width: min(258px, calc(100vw - 24px)); }
        }

        @media (max-width: 1024px) {
            .hero { max-width: 100%; padding: 100px 28px 120px; }
            .hero-left { max-width: 720px; }
        }
        @media (max-width: 768px) {
            .hero { padding: 84px 20px 96px; }
            .hero-left { max-width: 640px; }
            .hero-mark { width: 92px; height: 74px; margin-bottom: 22px; }
            .hero-brand { margin-bottom: 16px; }
            .hero-counter-row { width: 100%; }
            .about-grid, .reviews-grid { grid-template-columns: 1fr; }
            .about-section, .reviews-section { padding: 48px 20px; }
        }

        @media (max-width: 480px) {
            .site-header { padding: 6px 8px; gap: 6px; }
            .mobile-menu-button { width: 34px; height: 34px; }
            .header-btn { padding: 6px; }
            .user-avatar { width: 30px; height: 30px; }
            .btn-login { height: 47.6px; min-height: 47.6px; padding: 0 12px; }
            .btn-login-avatar { width: 30px; height: 30px; flex-basis: 30px; }
            .hero { padding: 68px 16px 80px; }
            .hero-brand { font-size: clamp(30px, 8.4vw, 44px); white-space: normal; margin-bottom: 30px; }
            .hero-title { font-size: 15px; }
            .hero-counter-row { flex-direction: row; }
            .hero-counter-item { padding: 0 12px; }
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
            .btn-login { height: 45.6px; min-height: 45.6px; padding: 0 10px; }
            .btn-login-avatar { width: 28px; height: 28px; flex-basis: 28px; }
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
            animation: authModalPop 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }
        body.light-theme .auth-modal { background: #ffffff; border-color: rgba(15,23,42,0.1); }
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
                <svg class="header-brand-mark" viewBox="0 0 100 80" role="img" aria-label="Smart Stock">
                    <path class="mk-axis" d="M6 6v66h88"/>
                    <rect class="mk-up" x="16" y="50" width="9" height="22" rx="1.5"/>
                    <rect class="mk-up" x="29" y="38" width="9" height="34" rx="1.5"/>
                    <rect class="mk-down" x="42" y="45" width="9" height="27" rx="1.5"/>
                    <rect class="mk-up" x="55" y="31" width="9" height="41" rx="1.5"/>
                    <rect class="mk-up" x="68" y="23" width="9" height="49" rx="1.5"/>
                    <polyline class="mk-arrow" points="13,62 30,48 46,55 62,34 78,13"/>
                    <polyline class="mk-arrow" points="67,11 80,11 80,24"/>
                </svg>
                <div>
                    <div class="header-brand-name">SMART</div>
                    <div class="header-brand-sub">Stock</div>
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
                {{-- Guest: show Login button. A neutral person glyph rather than
                     the old sign-in arrow, so the control reads as an account
                     entry point and matches the avatar used once signed in. --}}
                <a href="{{ route('login') }}" class="btn-login" onclick="event.preventDefault(); openLoginModal();">
                    <span class="btn-login-avatar" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </span>
                    Login
                </a>
            @else
                {{-- BRD (Inventory) Security: low-stock alerts are Admin-only data,
                     so the bell is hidden from Staff (who would receive a 403). --}}
                @if(Auth::user()->isAdmin())
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
                @endif

                <!-- User button -->
                <div class="header-user" id="headerUserBtn" onclick="toggleUserDropdown(event)">
                    <div class="user-avatar">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</div>
                    <div class="user-info">
                                            <div class="user-name">{{ Auth::user()->isAdmin() ? 'Admin' : 'Cashier' }} {{ Auth::user()->name ? ucfirst(trim(explode(' ', Auth::user()->name)[0])) : '' }}</div>
                        <div class="user-email">{{ Auth::user()->email }}</div>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                                            <div class="user-dropdown-head">
                                                <div class="user-dropdown-head-avatar">{{ Auth::user()->name ? mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) : 'U' }}</div>
                                                <div class="user-dropdown-head-text">
                                                    <div class="user-dropdown-head-name">{{ Auth::user()->isAdmin() ? 'Admin' : 'Cashier' }} {{ Auth::user()->name ? ucfirst(trim(explode(' ', Auth::user()->name)[0])) : '' }}</div>
                                                    <div class="user-dropdown-head-role">{{ Auth::user()->email }}</div>
                                                </div>
                                            </div>
                                            @if(Auth::user()->isAdmin())
                                            <form class="user-dropdown-form" method="GET" action="{{ route('users') }}">
                                                <button type="submit" class="dropdown-item">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                                    User Accounts
                                                </button>
                                            </form>
                                            @endif
                                            <form class="user-dropdown-form" method="GET" action="{{ route('dashboard') }}">
                                                <button type="submit" class="dropdown-item">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                                    Dashboard
                                                </button>
                                            </form>
                                            <form class="user-dropdown-form" method="POST" action="{{ route('logout') }}" style="margin:0;">
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
                <a href="{{ route('login') }}" class="mobile-nav-item" onclick="event.preventDefault(); openLoginModal(); closeMobileMenu();">
                    <span class="mobile-nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    </span>
                    <span>Login</span>
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

    <!-- Scroll pane. Sits below the sticky header so the page scrolls inside a
         fixed region, matching the dashboard's .main pane and its scrollbar. -->
    <div class="page-pane">

    <!-- HERO SECTION — flat colour band, mark, wordmark and copy -->
    <section class="hero">
        <div class="hero-bg" aria-hidden="true"></div>

        <div class="hero-left">
            {{-- Mark above the wordmark. Drawn inline so the bars can keep real
                 buy/sell colour instead of the flat filter the PNG needs. --}}
            <svg class="hero-mark" viewBox="0 0 100 80" role="img" aria-label="Smart Stock logo mark">
                <path class="mk-axis" d="M6 6v66h88" />
                <rect class="mk-up" x="16" y="50" width="9" height="22" rx="1.5"/>
                <rect class="mk-up" x="29" y="38" width="9" height="34" rx="1.5"/>
                <rect class="mk-down" x="42" y="45" width="9" height="27" rx="1.5"/>
                <rect class="mk-up" x="55" y="31" width="9" height="41" rx="1.5"/>
                <rect class="mk-up" x="68" y="23" width="9" height="49" rx="1.5"/>
                <polyline class="mk-arrow" points="13,62 30,48 46,55 62,34 78,13"/>
                <polyline class="mk-arrow" points="67,11 80,11 80,24"/>
            </svg>
            <h1 class="hero-brand">Smart<span class="hero-brand-gap"></span>Stock</h1>
            <p class="hero-title">
                Manage your hardware products with our secure inventory service.
            </p>
            <div class="hero-actions">
                @guest
                    {{-- BRD (Account Management): accounts are created by an Admin,
                         so there is no public registration entry point. --}}
                    <a href="{{ route('login') }}" class="btn-primary" onclick="event.preventDefault(); openLoginModal();">Get Started
                        <svg class="btn-arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M2 12h13" />
                            <path d="M14 2l9 10-9 10" />
                        </svg>
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn-primary">Open Dashboard
                        <svg class="btn-arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M2 12h13" />
                            <path d="M14 2l9 10-9 10" />
                        </svg>
                    </a>
                @endguest
            </div>
            <div class="hero-counter-row">
                <div class="hero-counter-item">
                    <span class="hero-counter-value" data-count="{{ $heroStats['products'] ?? 0 }}" data-suffix="+">0</span>
                    <span class="hero-counter-label">Products Tracked</span>
                </div>
                <div class="hero-counter-item">
                    <span class="hero-counter-value" data-count="{{ $heroStats['suppliers'] ?? 0 }}" data-suffix="+">0</span>
                    <span class="hero-counter-label">Suppliers</span>
                </div>
                <div class="hero-counter-item">
                    <span class="hero-counter-value" data-count="{{ $heroStats['sales'] ?? 0 }}" data-suffix="+">0</span>
                    <span class="hero-counter-label">Sales Recorded</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ABOUT SECTION -->
    <section class="about-section">
        <div class="about-header">
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

    </div><!-- /.page-pane -->

    <!-- LOGIN REQUIRED MODAL -->
    <div class="auth-modal-overlay" id="authModalOverlay" role="dialog" aria-modal="true" aria-labelledby="authModalTitle" onclick="if (event.target === this) closeAuthModal();">
        <div class="auth-modal">
            <div class="auth-modal-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <h3 class="auth-modal-title" id="authModalTitle">Login Required</h3>
            <p class="auth-modal-text">You must be logged in to access the <span id="authModalTarget">Dashboard</span>. Please log in to continue.</p>
            <div class="auth-modal-actions">
                <a href="{{ route('login') }}" class="auth-modal-btn primary" onclick="event.preventDefault(); closeAuthModal(); openLoginModal();">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    Go to Login
                </a>
                <button type="button" class="auth-modal-btn ghost" onclick="closeAuthModal()">Cancel</button>
            </div>
        </div>
    </div>

    {{-- The homepage's sign-in popup is the REAL login form, not a copy.
             home-login-modal.blade.php is the only login surface in the app; the
             /login route redirects here.

             No backdrop-click handler on purpose: this overlay wraps a form the
             user may be part-way through filling in, so a stray click on the
             dimmed area must not discard it. The X button inside the card is the
             only way to dismiss it. (The Login Required prompt above keeps its
             backdrop click — it holds no user input.) --}}
    <div class="auth-modal-overlay" id="loginModalOverlay" role="dialog" aria-modal="true" aria-labelledby="loginModalHeading">
            @include('home-login-modal', ['asModal' => true])
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

        /* ── Login modal ────────────────────────────────────────────
           Posts to the same route('login.post') as the standalone /login
           page, so credential handling, validation and the role-based
           redirect all stay in AuthController — nothing is duplicated here.

           Deliberately does NOT toggle body overflow: hiding the scrollbar
           changes the viewport width and shifts the page, which reads as a
           jump. The page keeps its scrollbar and position behind the modal. */
        function openLoginModal() {
            const overlay = document.getElementById('loginModalOverlay');
            if (!overlay) return;
            overlay.classList.add('active');
            // Focus the first field so the keyboard user can type straight away.
            // The id comes from the shared login card, not a modal-only copy.
            const field = document.getElementById('username');
            if (field) setTimeout(function () { field.focus(); }, 50);
        }
        function closeLoginModal() {
            const overlay = document.getElementById('loginModalOverlay');
            if (!overlay) return;
            overlay.classList.remove('active');
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeLoginModal();
        });

        // A failed sign-in redirects back here with a validation error, which
        // lands on a freshly rendered page with the modal closed. Reopen it so
        // the user sees the message and can correct the fields in place.
        @if ($errors->any())
            openLoginModal();
        @endif

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
            const isOpen = sidebar.classList.toggle('mobile-open', isOpen);

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
                    return `<div class="alert-card ${severity}" id="alert-card-${a.id}">
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
            // BRD (Account Management): the login screen is username + password +
            // submit only, so no remembered-credential state is kept here.
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

        // Low-stock alerts are Admin-only; skip the request entirely for guests
        // and Staff so no 403 noise appears in the console.
        @if(Auth::check() && Auth::user()->isAdmin())
            loadAlerts();
            setInterval(loadAlerts, 30000);
        @endif
    </script>
</body>
</html>
