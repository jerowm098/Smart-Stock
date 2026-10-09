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
        /* The frame (sidebar + top header) uses the same surface tone as the
           content cards. It is written as the opaque composite of
           rgba(255,255,255,0.025) over the #0f172a body so the mobile
           slide-in sidebar cannot show page content through itself. */
        .sidebar { width: 240px; min-height: calc(100vh - 64px); background: #151d2f; border-right: 1px solid rgba(255,255,255,0.07); display: flex; flex-direction: column; position: fixed; top: 64px; left: 0; bottom: 0; z-index: 90; }
        .sidebar-nav { flex: 1; padding: 12px; overflow-y: auto; }
        .nav-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; padding: 6px 12px; font-weight: 600; margin-top: 22px; }
        .sidebar-nav > .nav-label:first-child { margin-top: 4px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.15s; margin-bottom: 6px; }
        .nav-item:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
        .nav-item.active { background: rgba(255,255,255,0.09); color: #f1f5f9; }
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
        .mobile-menu-title { color: #e2e8f0; font-size: 16px; font-weight: 700; }
        .mobile-menu-close { width: 32px; height: 32px; padding: 0; border: 0; border-radius: 8px; background: rgba(255,255,255,0.06); color: #f8fafc; font-size: 20px; line-height: 1; cursor: pointer; }
        .mobile-menu-close:hover { background: rgba(255,255,255,0.12); }
        .mobile-menu-open { overflow: auto; }

        /* TOP HEADER - FIXED */
        .top-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 12px 40px; background: #151d2f; border-bottom: 1px solid rgba(255,255,255,0.07); position: fixed; top: 0; left: 0; right: 0; z-index: 100; height: 64px; }
        /* `space-between` only centres the middle child when the left and
           right clusters are the same width. The right cluster is always
           wider, so pin the nav to the true centre instead. */
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
        .header-center-btn.active { color: #f1f5f9; font-weight: 700; background: rgba(255,255,255,0.09); }
        body.light-theme .header-center-btn { color: #64748b; }
        body.light-theme .header-center-btn:hover { color: #0f172a; }
        body.light-theme .header-center-btn.active { color: #0f172a; font-weight: 700; background: rgba(15,23,42,0.06); }
        .header-brand { display: flex; align-items: center; gap: 10px; flex-shrink: 0; text-decoration: none; }
        .header-brand:hover .brand-name { color: #cbd5e1; }
        body.light-theme .header-brand:hover .brand-name { color: #334155; }
        /* Inline SVG mark so it carries real colour on both themes — the old
           PNG was a flat black graphic that needed an invert filter. */
        .header-brand .brand-mark { width: 50px; height: 40px; flex: 0 0 auto; display: block; }
        .header-brand .brand-mark .mk-up { fill: #22c55e; }
        .header-brand .brand-mark .mk-down { fill: #ef4444; }
        .header-brand .brand-mark .mk-axis { fill: none; stroke: #94a3b8; stroke-width: 4; stroke-linecap: square; }
        .header-brand .brand-mark .mk-arrow { fill: none; stroke: #22c55e; stroke-width: 6; stroke-linecap: round; stroke-linejoin: round; }
        body.light-theme .header-brand .brand-mark .mk-up { fill: #16a34a; }
        body.light-theme .header-brand .brand-mark .mk-down { fill: #dc2626; }
        body.light-theme .header-brand .brand-mark .mk-axis { stroke: #64748b; }
        body.light-theme .header-brand .brand-mark .mk-arrow { stroke: #16a34a; }
        /* Both lines share one rule so they can never drift apart again. Only the
                   text content differs — the second line reads "Stock". */
                .header-brand .brand-name,
                .header-brand .brand-subtitle { color: #e2e8f0; font-size: 17px; font-weight: 700; line-height: 1.05; letter-spacing: 0.01em; }
        .header-right { display: flex; align-items: center; gap: 12px; }
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
        .header-badge { background: #ef4444; color: #fff; border-radius: 8px; min-width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; font-size: 10px; font-weight: 700; }
        .header-badge.hidden { display: none; }
        .header-user { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); cursor: pointer; position: relative; min-height: 40px; }
        .header-user:hover { background: rgba(255,255,255,0.05); }
        .user-avatar {
            width: 32px; height: 32px; flex: 0 0 32px; border-radius: 50%;
            /* Flat slate fill — the old blue gradient read as a floating badge. */
            background: #334155;
            border: 1px solid rgba(255,255,255,0.12);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 12px; font-weight: 700; text-transform: uppercase;
            flex-shrink: 0;
        }
        body.light-theme .user-avatar { background: #e2e8f0; border-color: rgba(15,23,42,0.12); color: #334155; }
        .user-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .user-name { font-size: 13px; font-weight: 600; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px; }
        .user-email { font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        /* ALERT WRAPPER - anchors alert dropdown */
        .header-alert-wrapper { position: relative; }
        /* ALERT BUTTON - TAB STYLE */
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

        /* USER DROPDOWN — each action is its own bordered "form" card sitting inside
           a padded menu, with an identity header on top. Wider than the old
           180px so the role + full name in the header has room to breathe. */
        .user-dropdown {
            position: absolute; top: calc(100% + 10px); right: 0;
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px; width: 258px; z-index: 150;
            display: none; padding: 10px;
            box-shadow: 0 18px 40px rgba(2,6,23,0.45);
        }
        .user-dropdown.active { display: flex; flex-direction: column; gap: 8px; }
        /* Identity header — mirrors the trigger so the menu is self-describing
           once it covers the button it was opened from. */
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
        /* Each action gets its own bordered form card. The card owns the border
           and the hover fill; the item inside is borderless so hover never
           collides with the card outline. */
        .user-dropdown-form {
            margin: 0; border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px; overflow: hidden; background: rgba(255,255,255,0.02);
            transition: border-color 0.15s, background 0.15s;
        }
        .user-dropdown-form:hover { border-color: rgba(255,255,255,0.18); background: rgba(255,255,255,0.05); }
        .user-dropdown-item {
            display: flex; align-items: center; gap: 11px; width: 100%;
            padding: 11px 13px; color: #cbd5e1; font-size: 13px; font-weight: 500;
            cursor: pointer; transition: background 0.15s, color 0.15s;
            text-decoration: none; border: none; background: none;
            text-align: left; font-family: inherit;
        }
        .user-dropdown-item:hover { background: rgba(255,255,255,0.05); color: #f8fafc; }
        .user-dropdown-item svg { flex: 0 0 auto; opacity: 0.85; }
        .user-dropdown-item.logout { color: #fca5a5; }
        .user-dropdown-form:has(.logout) { border-color: rgba(239,68,68,0.22); background: rgba(239,68,68,0.05); }
        .user-dropdown-form:has(.logout):hover { border-color: rgba(239,68,68,0.45); background: rgba(239,68,68,0.10); }
        .user-dropdown-item.logout:hover { background: rgba(239,68,68,0.14); color: #f87171; }
        .user-dropdown-divider { display: none; }
        body.light-theme .user-dropdown { background: #ffffff; border-color: rgba(15,23,42,0.1); box-shadow: 0 18px 40px rgba(15,23,42,0.14); }
        body.light-theme .user-dropdown-head { border-bottom-color: rgba(15,23,42,0.08); }
        body.light-theme .user-dropdown-head-avatar { background: #e2e8f0; border-color: rgba(15,23,42,0.12); color: #334155; }
        body.light-theme .user-dropdown-head-name { color: #0f172a; }
        body.light-theme .user-dropdown-head-role { color: #64748b; }
        body.light-theme .user-dropdown-form { background: rgba(15,23,42,0.015); border-color: rgba(15,23,42,0.08); }
        body.light-theme .user-dropdown-form:hover { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.16); }
        body.light-theme .user-dropdown-item { color: #475569; border: none; background: none; width: 100%; text-align: left; font-family: inherit; }
        body.light-theme .user-dropdown-item:hover { background: rgba(15,23,42,0.05); color: #0f172a; }
        body.light-theme .user-dropdown-item.logout { color: #dc2626; }
        body.light-theme .user-dropdown-form:has(.logout) { border-color: rgba(220,38,38,0.2); background: rgba(239,68,68,0.035); }
        body.light-theme .user-dropdown-form:has(.logout):hover { border-color: rgba(220,38,38,0.4); background: rgba(239,68,68,0.07); }
        body.light-theme .user-dropdown-item.logout:hover { background: rgba(239,68,68,0.08); color: #ef4444; }
        body.light-theme .user-dropdown-divider { background: rgba(15,23,42,0.08); }

        /* LIGHT THEME */
        body.light-theme { background: #f3f4f6; color: #334155; }
        body.light-theme .top-header { background: #ffffff; border-bottom-color: rgba(15,23,42,0.08); }
        body.light-theme .header-brand .brand-name,
                body.light-theme .header-brand .brand-subtitle { color: #0f172a; }
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


        /* SPINNER REMOVED */

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
            /* Keep the theme toggle square — the generic padding above would
               otherwise squash it into an oval. */
            #themeToggleBtn { padding: 0; width: 36px; min-width: 36px; height: 36px; }
            .alert-dropdown { width: min(280px, calc(100vw - 24px)); }
            .user-dropdown { width: min(258px, calc(100vw - 24px)); }
            /* Show hamburger menu button on mobile */
            .mobile-menu-button { display: flex; }
            .header-brand { display: flex; padding: 0; }
            .header-brand .brand-name,
            .header-brand .brand-subtitle { display: none; }
            .header-brand .brand-mark { width: 40px; height: 32px; }
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
            #themeToggleBtn { padding: 0; width: 34px; min-width: 34px; height: 34px; }
            .user-avatar { width: 30px; height: 30px; }
            .content { padding: 14px; }
            .sidebar.mobile-open { width: calc(100vw - 40px); }
        }
        @media (max-width: 300px) {
            .top-header { padding: 4px 6px; gap: 4px; }
            .mobile-menu-button { width: 30px; height: 30px; }
            .header-btn { padding: 4px; }
            #themeToggleBtn { padding: 0; width: 32px; min-width: 32px; height: 32px; }
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
    /* ================================================================
       SHARED PAGE PRIMITIVES
       These used to live in each page's own styles stack, so any page
       that forgot the copy rendered as unstyled HTML. They are defined
       once here instead.
       ================================================================ */
    .page-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 6px; }
    .page-subtitle { color: #64748b; font-size: 14px; margin-bottom: 20px; }
    .page-subtitle code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 5px; padding: 1px 6px; font-size: 12px; color: #93c5fd;
    }

    .filter-bar {
        display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;
        background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06);
        border-radius: 12px; padding: 16px; margin-bottom: 12px;
    }
    .filter-group { display: flex; flex-direction: column; gap: 6px; }
    .filter-group label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
    .filter-group input, .filter-group select {
        background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
        color: #e2e8f0; font-size: 13px; border-radius: 8px; padding: 8px 10px;
        outline: none; min-width: 160px;
    }
    .filter-group input:focus, .filter-group select:focus { border-color: #3b82f6; }
    .filter-actions { display: flex; gap: 8px; margin-left: auto; }

    .btn-primary {
        background: #2563eb; color: #fff; border: none;
        border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .btn-primary:hover { filter: brightness(1.08); }
    .btn-ghost {
        background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
        color: #cbd5e1; border-radius: 8px; padding: 9px 16px; font-size: 13px;
        font-weight: 500; cursor: pointer;
    }
    .btn-ghost:hover { background: rgba(255,255,255,0.08); }
    .btn-ghost:disabled, .btn-primary:disabled { opacity: 0.4; cursor: not-allowed; }

    .table-wrap {
        background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06);
        border-radius: 12px; overflow-x: auto;
    }
    .data-table { width: 100%; min-width: 860px; border-collapse: collapse; }
    .data-table thead th {
        background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left;
        font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;
        letter-spacing: 0.5px; white-space: nowrap;
    }
    .data-table tbody tr { border-top: 1px solid rgba(255,255,255,0.04); }
    .data-table tbody tr:hover { background: rgba(255,255,255,0.02); }
    .data-table tbody td { padding: 12px 16px; font-size: 13px; color: #cbd5e1; vertical-align: middle; }
    .data-table td.num, .data-table th.num { text-align: right; font-variant-numeric: tabular-nums; }
    .empty-state { text-align: center; color: #475569; padding: 48px; font-size: 14px; }
    /* SPINNER REMOVED */

    body.light-theme .page-title { color: #0f172a; }
    body.light-theme .page-subtitle { color: #475569; }
    body.light-theme .page-subtitle code { background: rgba(15,23,42,0.06); border-color: rgba(15,23,42,0.12); color: #1d4ed8; }
    body.light-theme .filter-bar { background: #fff; border-color: rgba(15,23,42,0.08); }
    body.light-theme .filter-group input, body.light-theme .filter-group select { background: #fff; border-color: rgba(15,23,42,0.12); color: #0f172a; }
    body.light-theme .table-wrap { background: #fff; border-color: rgba(15,23,42,0.08); }
    body.light-theme .data-table thead th { background: rgba(15,23,42,0.02); color: #64748b; }
    body.light-theme .data-table tbody tr { border-top-color: rgba(15,23,42,0.06); }
    body.light-theme .data-table tbody td { color: #334155; }
    body.light-theme .data-table tbody tr:hover { background: rgba(15,23,42,0.02); }
    body.light-theme .btn-ghost { background: #fff; border-color: rgba(15,23,42,0.12); color: #334155; }
    body.light-theme .empty-state { color: #94a3b8; }

    @media print {
        body { overflow: visible; }
        .top-header, .sidebar, .filter-bar, .mobile-menu-overlay { display: none !important; }
    }

    /* ====================================================================
       SHARED TABLE FRAME — applies to every table on every page
       --------------------------------------------------------------------
       Sits AFTER the shared primitives and immediately BEFORE the styles
       stack, so it beats the per-page `table { ... }` rules without needing
       !important, and a page's own pushed styles can still override.

       The table is treated as ONE outlined form containing a list of
       borderless row-blocks:

         - one outline around the outermost edge of the table
         - one rule under the header row (the bottom of the column header)
         - every data row is a filled, rounded block — no border of any
           colour, and NO vertical rule between its cells, so a record reads
           as a single form rather than a row of separate boxes
       Rows deliberately do not react to hover in a way that suggests a link:
       they are a static list, not buttons, even though they are shaped like
       one.
       ==================================================================== */
    .table-wrap,
    .table-wrapper {
        /* Shared radius. The header band and the row blocks below both key
           off this so they stay concentric with the wrapper outline —
           without it the square header band left the wrapper's fill showing
           through as bright notches at the corners. */
        --table-radius: 10px;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.02);
        /* No padding on the top/bottom. Combined with the table's 6px
           border-spacing this used to leave ~10px of bare wrapper fill
           above the header and below the last row, which read as a much
           thicker "border" than the 1px outline it sat against. Only the
           left/right inset is kept so row blocks do not touch the sides. */
        padding: 0 4px;
        border-radius: var(--table-radius);
    }
    /* Everything inside is explicitly cleared, including the per-page
       `tbody tr { border-top }` rules. This is the load-bearing part. */
    .table-wrap th, .table-wrap td,
    .table-wrapper th, .table-wrapper td { border: none; }
    .table-wrap tbody tr, .table-wrapper tbody tr,
    .table-wrap thead tr, .table-wrapper thead tr,
    .table-wrap tbody td, .table-wrapper tbody td,
    .table-wrap thead td, .table-wrapper thead td { border: none; }
    /* The bottom of the column header — the only inner line that exists.
       The header is its own rounded block with a clearly stronger fill than
       the row blocks, so it reads as the column band and not as another row.
       The radius is inset by one pixel so the fill sits inside the wrapper's
       outline instead of bleeding over its border. */
    .table-wrap thead th,
    .table-wrapper thead th {
        /* No border on the header cells — not even a bottom rule. A rule
           here draws one line per column, which is what split the band into
           separate boxes instead of one form. The band is defined purely by
           its fill, exactly like a row block. */
        border: none;
        background: rgba(71,85,105,0.42);
        color: #e2e8f0;
    }
    /* A table row cannot clip its own overflow, so the radius goes on the
       two corner cells — that is what rounds the band and stops the
       wrapper's lighter fill showing through as notches. */
    .table-wrap thead th:first-child, .table-wrapper thead th:first-child {
        border-top-left-radius: calc(var(--table-radius) - 1px);
    }
    .table-wrap thead th:last-child, .table-wrapper thead th:last-child {
        border-top-right-radius: calc(var(--table-radius) - 1px);
    }
    /* The header is a single filled band, sitting directly on the wrapper
       edge with no rule under it. This replaces the old header underline. */
    .table-wrap thead, .table-wrapper thead { border: none; }
    .table-wrap thead tr, .table-wrapper thead tr { border: none; }
    /* separate + a vertical gap is what turns each row into its own block.
       collapse discards border-spacing, which is why the per-page
       `table { border-collapse: collapse }` rules have to be beaten here.
       The gap is 4px, not 6px: the first and last rows sat 6px off the
       header and the wrapper edge, and on a light page that gap reads as a
       white band above the first row and below the last one. */
    .table-wrap table,
    .table-wrapper table { border-collapse: separate; border-spacing: 0 4px; }
    /* Each data row is one filled, rounded block — the button-like shape.
       No border, no fill change on hover (it is not clickable).
       Selector is scoped to `.table-wrap tbody > tr` / `.table-wrapper
       tbody > tr` so it outranks the bare `tbody tr:hover { background }`
       rules each page sets, which would otherwise tint the block on hover
       and make a static row look interactive. */
    .table-wrap tbody > tr, .table-wrapper tbody > tr,
    .table-wrap tbody > tr:hover, .table-wrapper tbody > tr:hover {
        background: rgba(255,255,255,0.045);
        border: none;
        border-radius: 8px;
        transition: none;
    }
    /* Cells sit inside the block with clear separation between values, but
       no rule is drawn — the spacing alone separates them. */
    .table-wrap tbody td, .table-wrapper tbody td { border: none; }
    /* The header band is one continuous strip, so its own cells stay
       transparent and let the header fill run edge to edge. */
    .table-wrap thead td, .table-wrapper thead td {
        background: transparent;
    }

    body.light-theme .table-wrap,
    body.light-theme .table-wrapper {
        /* Softer than the dark theme's 0.14 — on a white page an outline
           this heavy reads as a thick frame rather than a hairline. */
        border-color: rgba(15,23,42,0.10);
        background: #f8fafc;
        padding: 0 4px;
    }
    body.light-theme .table-wrap thead th,
    body.light-theme .table-wrapper thead th {
        border: none;
        /* Same slate-blue family as dark, at a light-mode strength. */
        background: rgba(100,116,139,0.16);
        color: #1e293b;
    }
    body.light-theme .table-wrap tbody > tr,
    body.light-theme .table-wrapper tbody > tr,
    body.light-theme .table-wrap tbody > tr:hover,
    body.light-theme .table-wrapper tbody > tr:hover { background: rgba(15,23,42,0.04); }
    </style>
    @stack('styles')
</head>
<body>
    @php
        // Role flag resolved once at the top of the layout so both the header
        // (alert bell) and the sidebar nav can gate Admin-only UI.
        //
        // BRD (Inventory) Security: low-stock alerts and the inventory/dashboard
        // data behind them are Admin-only, so Staff never see the bell.
        $isAdmin = auth()->user()?->isAdmin() ?? false;
    @endphp
    <!-- TOP HEADER (FIXED) -->
    <header class="top-header">
        <div class="header-left">
            <a href="{{ route('home') }}" class="header-brand">
                <svg class="brand-mark" viewBox="0 0 100 80" role="img" aria-label="Smart Stock">
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
                    <div class="brand-name">SMART</div>
                    <div class="brand-subtitle">Stock</div>
                </div>
            </a>
            <button type="button" class="mobile-menu-button" id="mobileMenuButton" onclick="toggleMobileMenu(event)" aria-label="Open navigation menu" aria-controls="mobileNavigation" aria-expanded="false">
                <span class="mobile-menu-icon" aria-hidden="true"><span></span><span></span><span></span></span>
            </button>
        </div>
        <div class="header-center" id="headerNav">
            <a href="{{ route('home') }}" class="header-center-btn {{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
            {{-- Shown for every role. /dashboard is Admin-only, so a Staff
                 click is redirected back to the POS screen by the route
                 middleware rather than hitting an error page. The active
                 state intentionally matches Admin: these two buttons are the
                 only top-level sections, so anything that is not Home counts
                 as the Dashboard section. --}}
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
            @if($isAdmin)
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
            <div class="header-user" id="headerUserBtn" onclick="toggleUserDropdown(event)">
                <div class="user-avatar">{{ Auth::user()->name ? mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) : 'U' }}</div>
                <div class="user-info">
                    <div class="user-name">{{ $isAdmin ? 'Admin' : 'Cashier' }} {{ Auth::user()->name ? ucfirst(trim(explode(' ', Auth::user()->name)[0])) : '' }}</div>
                    <div class="user-email">{{ Auth::user()->email }}</div>
                </div>
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-dropdown-head">
                        <div class="user-dropdown-head-avatar">{{ Auth::user()->name ? mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) : 'U' }}</div>
                        <div class="user-dropdown-head-text">
                            <div class="user-dropdown-head-name">{{ $isAdmin ? 'Admin' : 'Cashier' }} {{ Auth::user()->name ? ucfirst(trim(explode(' ', Auth::user()->name)[0])) : '' }}</div>
                            <div class="user-dropdown-head-role">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                    @if($isAdmin)
                    <form class="user-dropdown-form" method="GET" action="{{ route('users') }}">
                        <button type="submit" class="user-dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span>User Accounts</span>
                        </button>
                    </form>
                    @endif
                    <form class="user-dropdown-form" method="GET" action="{{ route('dashboard') }}">
                        <button type="submit" class="user-dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                            <span>Dashboard</span>
                        </button>
                    </form>
                    <form class="user-dropdown-form" method="POST" action="{{ route('logout') }}" id="logoutForm">
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
            <button type="button" class="mobile-menu-close" onclick="closeMobileMenu()" aria-label="Close navigation menu">&times;</button>
        </div>
        @php
            // Role-aware navigation.
            // BRD (Account Management): "Staff Role = POS Access Only." Cashier
            // sees just the POS tab under a single "Sales" heading; the Home
            // heading is Admin-only, so it is not rendered for Staff.
            // Admin:   full set — Overview, Products, Suppliers, Stock-In,
            //          POS Checkout, Transactions, User Accounts, Backups.
            // NOTE: $isAdmin is already resolved at the top of this layout.
        @endphp
        <nav class="sidebar-nav">
            @if($isAdmin)
                <div class="nav-label">Home</div>
                <a href="{{ route('dashboard') }}" class="nav-item" id="navOverview" data-page="overview">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </span>
                    <span>Overview</span>
                </a>
            @endif
            @if($isAdmin)
                <div class="nav-label">Management</div>
                {{-- BRD (Account Management) Business rules:
                     "Staff Role = POS Access Only." / "Admin Role = POS + Inventory +
                     Demand Suggestions + User Management."
                     Inventory (Products) is Admin-only, so Staff see just Overview +
                     POS Checkout. This matches the server: /api/inventory/products
                     is an Admin-only route. --}}
                <a href="{{ route('products') }}" class="nav-item" id="navProducts" data-page="products">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    </span>
                    <span>Products</span>
                </a>
                <a href="{{ route('suppliers') }}" class="nav-item" id="navSuppliers" data-page="suppliers">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </span>
                    <span>Suppliers</span>
                </a>
            @endif
            @if($isAdmin)
                <a href="{{ route('stock-in') }}" class="nav-item" id="navStockIn" data-page="stock-in">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"></path><path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
                    </span>
                    <span>Stock-In</span>
                </a>
            @endif
            @if(auth()->user()?->isCashier() || $isAdmin)
                <div class="nav-label">Sales</div>
                <a href="{{ route('pos') }}" class="nav-item" id="navPos" data-page="pos">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    </span>
                    <span>POS Checkout</span>
                </a>
                @if($isAdmin)
                <a href="{{ route('transactions') }}" class="nav-item" id="navTransactions" data-page="transactions">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    </span>
                    <span>Transactions</span>
                </a>
                <a href="{{ route('order-suggestions') }}" class="nav-item" id="navOrderSuggestions" data-page="order-suggestions">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="M7 15l4-5 3 3 5-7"></path></svg>
                    </span>
                    <span>Order Suggestions</span>
                </a>
                @endif
            @endif
            @if($isAdmin)
                <div class="nav-label">Settings</div>
                <a href="{{ route('users') }}" class="nav-item" id="navUsers" data-page="users">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </span>
                    <span>User Accounts</span>
                </a>
                <a href="{{ route('backups') }}" class="nav-item" id="navBackups" data-page="backups">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    </span>
                    <span>Backups</span>
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

        /* Marks the current sidebar entry. Driven by each link's data-page
           attribute rather than a hardcoded path chain — the chain silently
           fell through to Overview for any route added later. */
        function setActiveNav() {
            const path = window.location.pathname.replace(/\/+$/, '') || '/';
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));

            const links = Array.from(document.querySelectorAll('.nav-item'));
            const pathOf = item => new URL(item.href, window.location.origin).pathname.replace(/\/+$/, '') || '/';

            // Exact match wins, so /order-suggestions can't be swallowed by
            // a longer parent route. Then longest prefix for nested routes
            // such as /products/12/edit.
            let active = links.find(item => pathOf(item) === path)
                      || links.filter(item => pathOf(item) !== '/')
                             .sort((a, b) => pathOf(b).length - pathOf(a).length)
                             .find(item => path.startsWith(pathOf(item)));

            // No nav entry for this route (e.g. /home, /login).
            if (!active) {
                active = links.find(item => item.id === 'navOverview');
            }

            active?.classList.add('active');
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

        // BRD (Account Management): the login screen is username + password +
        // submit only, so no credential pre-fill state is kept in localStorage.
        function handleLogout(e) {
            // nothing to clear — login no longer stores a remembered username.
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
                    const sku = escapeHtml(a.sku || '\u2014');
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
                        <button class="alert-dismiss-btn" onclick="dismissAlert(${a.id}, event)" title="Dismiss">&times;</button>
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

        // Low-stock alerts are Admin-only (BRD Inventory Security). Staff and
        // guests must not trigger the request, so the bell is hidden for them.
        @if($isAdmin)
            loadAlerts();
            setInterval(loadAlerts, 30000);
        @endif
    </script>
    @stack('scripts')
</body>
</html>
