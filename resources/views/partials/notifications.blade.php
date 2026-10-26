{{--
    Header notifications tray — one shared implementation for every header.

    Included by:
      - resources/views/home.blade.php                          (public homepage)
      - resources/views/layouts/dashboard-main-frame.blade.php  (Admin pages)

    Renders nothing at all for guests and Cashiers: low-stock data is
    Admin-only (BRD Inventory Security), so the bell, the request and the poll
    must never exist for those roles.

    Layout follows Facebook's Notifications surface:
      - bold "Notifications" title, with a quiet "Mark all as read" action
      - "All" / "Unread" pill tabs, active pill filled with a blue-tinted navy
      - a "New" section label with a "See all" link on the same line
      - full-bleed rows: 56px circular avatar, two lines of message, then a
        small muted timestamp beneath the text
      - unread rows are marked by a small blue dot at the right edge (not by a
        full-row tint), and the dot gives way to the × on hover
--}}
@auth
@if(Auth::user()->isAdmin())
<style>
    .header-alert-wrapper { position: relative; }
    .header-btn-icon { padding: 8px !important; min-width: 40px; justify-content: center; }

    /* Facebook's dark card colour rather than the slate panel used elsewhere in
       the app — the tray is meant to read as a notification surface. */
    .alert-dropdown {
        position: absolute; top: calc(100% + 8px); right: 0;
        background: #252728; border: 1px solid rgba(255,255,255,0.07);
        border-radius: 14px; width: 380px; max-height: 480px; z-index: 150;
        display: none; flex-direction: column; overflow: hidden;
        box-shadow: 0 12px 28px rgba(0,0,0,0.55);
    }
    .alert-dropdown.active { display: flex; animation: notifOpen 0.14s ease; }
    @keyframes notifOpen { from { opacity: 0; transform: translateY(-6px) scale(0.98); } to { opacity: 1; transform: none; } }

    .notif-header {
        flex: 0 0 auto; padding: 14px 16px 10px;
        display: flex; justify-content: space-between; align-items: center; gap: 10px;
    }
    .notif-title { font-size: 20px; font-weight: 700; color: #e4e6eb; letter-spacing: -0.01em; }
    .notif-readall {
        border: none; background: transparent; padding: 5px 8px; border-radius: 6px;
        font-family: inherit; font-size: 12px; font-weight: 600;
        color: #4599ff; cursor: pointer; white-space: nowrap;
        transition: background 0.15s, color 0.15s;
    }
    .notif-readall:hover { background: rgba(69,153,255,0.12); color: #74b4ff; }

    /* "All" / "Unread" pills. The active one is a blue-tinted navy fill with a
       brighter blue label — the inversion Facebook uses on its dark theme. */
    .notif-tabs { flex: 0 0 auto; display: flex; gap: 8px; padding: 0 16px 10px; }
    .notif-tab {
        border: none; font-family: inherit; font-size: 13px; font-weight: 600;
        line-height: 1; padding: 9px 15px; border-radius: 999px;
        background: transparent; color: #b0b3b8; cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .notif-tab:hover { background: rgba(255,255,255,0.06); }
    .notif-tab.active { background: #243a52; color: #6aa9ff; }
    .notif-tab.active:hover { background: #2a4461; }

    /* The list still scrolls (wheel / touch / keyboard), but no track or thumb
       is drawn. Keeping a 380px panel clean beats showing a scrollbar that
       would cut into the row avatars. */
    #headerAlertList {
        flex: 1 1 auto; min-height: 0; overflow-y: auto;
        padding-bottom: 6px;
        scrollbar-width: none;        /* Firefox */
        -ms-overflow-style: none;     /* old Edge / IE */
        overscroll-behavior: contain; /* keep wheel scrolling inside the tray */
    }
    #headerAlertList::-webkit-scrollbar { width: 0; height: 0; display: none; } /* Chrome / Safari / Edge */

    /* "New" / "Earlier" group labels, with "See all" pinned to the right of the
       first group exactly like the reference. */
    .notif-section {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; padding: 4px 16px 2px;
    }
    .notif-section-label { font-size: 15px; font-weight: 600; color: #b0b3b8; }
    .notif-section-link {
        font-size: 13px; font-weight: 600; color: #4599ff;
        text-decoration: none; padding: 3px 6px; border-radius: 6px;
        transition: background 0.15s, color 0.15s;
    }
    .notif-section-link:hover { background: rgba(69,153,255,0.1); color: #74b4ff; }

    .alert-empty {
        display: flex; flex-direction: column; align-items: center; gap: 10px;
        padding: 34px 20px 40px; text-align: center;
        color: #8a8d91; font-size: 13px; font-weight: 500;
    }
    .alert-empty svg { width: 42px; height: 42px; opacity: 0.5; }

    /* One row per notification: avatar, message, timestamp. Full-bleed with a
       hover wash only — a card border or an unread tint would break the look. */
    .alert-card {
        position: relative; flex: 0 0 auto; width: 100%;
        display: flex; align-items: flex-start; gap: 12px;
        padding: 9px 44px 9px 16px; border: none; border-radius: 0;
        background: transparent; text-align: left; cursor: pointer;
        transition: background 0.12s ease;
    }
    .alert-card:hover { background: rgba(255,255,255,0.05); }

    .alert-card-avatar {
        width: 56px; height: 56px; flex: 0 0 56px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: #3a3b3c; color: #b0b3b8; overflow: hidden;
    }
    .alert-card-avatar svg { width: 26px; height: 26px; display: block; }
    .alert-card-avatar.critical { background: rgba(248,113,113,0.17); color: #f87171; }
    .alert-card-avatar.low { background: rgba(251,191,36,0.17); color: #fbbf24; }

    .alert-card-body { flex: 1 1 auto; min-width: 0; padding-top: 2px; }
    .alert-card-text {
        font-size: 14px; line-height: 1.4; color: #b0b3b8;
        margin-bottom: 3px; overflow-wrap: anywhere;
    }
    .alert-card-text strong { color: #e4e6eb; font-weight: 700; }
    .alert-card-meta {
        font-size: 12.5px; line-height: 1.35; color: #8a8d91;
        margin-bottom: 3px; overflow-wrap: anywhere;
    }
    .alert-card-time { font-size: 12px; font-weight: 500; color: #b0b3b8; }

    /* Unread marker, matching the reference's small blue dot at the right edge.
       It occupies the same slot as the dismiss button, so hover swaps one for
       the other instead of stacking two affordances. */
    .alert-unread-dot {
        position: absolute; top: 50%; right: 16px; transform: translateY(-50%);
        width: 11px; height: 11px; border-radius: 50%; background: #4599ff;
        transition: opacity 0.12s ease;
    }
    .alert-card:hover .alert-unread-dot,
    .alert-card:focus-within .alert-unread-dot { opacity: 0; }

    .alert-dismiss-btn {
        position: absolute; top: 50%; right: 10px; transform: translateY(-50%);
        width: 28px; height: 28px; border: none; border-radius: 50%;
        background: transparent; color: #8a8d91; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.12s, color 0.12s, background 0.12s;
    }
    .alert-dismiss-btn svg { width: 14px; height: 14px; display: block; }
    .alert-dismiss-btn:hover { color: #f87171; background: rgba(248,113,113,0.12); }
    .alert-card:hover .alert-dismiss-btn,
    .alert-card:focus-within .alert-dismiss-btn { opacity: 1; }
    @media (hover: none) { .alert-dismiss-btn { opacity: 0.6; } }

    .alert-card { animation: alertSlideIn 0.18s ease; }
    @keyframes alertSlideIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
    .alert-card.removing { animation: alertSlideOut 0.18s ease forwards; }
    @keyframes alertSlideOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(12px); } }

    /* ---- Light theme: Facebook's light notifications surface ---- */
    body.light-theme .alert-dropdown { background: #ffffff; border-color: rgba(0,0,0,0.1); box-shadow: 0 12px 28px rgba(15,23,42,0.18); }
    body.light-theme .notif-title { color: #050505; }
    body.light-theme .notif-readall { color: #1877f2; }
    body.light-theme .notif-readall:hover { background: rgba(24,119,242,0.08); }
    body.light-theme .notif-tab { color: #65676b; }
    body.light-theme .notif-tab:hover { background: rgba(0,0,0,0.05); }
    body.light-theme .notif-tab.active { background: #e7f0ff; color: #1877f2; }
    body.light-theme .notif-tab.active:hover { background: #dbe8fd; }
    body.light-theme .notif-section-label { color: #65676b; }
    body.light-theme .notif-section-link { color: #1877f2; }
    body.light-theme .notif-section-link:hover { background: rgba(24,119,242,0.07); }
    body.light-theme .alert-empty { color: #8a8d91; }
    body.light-theme .alert-card:hover { background: rgba(0,0,0,0.04); }
    body.light-theme .alert-card-avatar { background: #e4e6eb; color: #65676b; }
    body.light-theme .alert-card-avatar.critical { background: rgba(239,68,68,0.12); color: #dc2626; }
    body.light-theme .alert-card-avatar.low { background: rgba(217,119,6,0.13); color: #b45309; }
    body.light-theme .alert-card-text { color: #65676b; }
    body.light-theme .alert-card-text strong { color: #050505; }
    body.light-theme .alert-card-meta { color: #8a8d91; }
    body.light-theme .alert-card-time { color: #65676b; }
    body.light-theme .alert-unread-dot { background: #1877f2; }
    body.light-theme .alert-dismiss-btn { color: #8a8d91; }
    body.light-theme .alert-dismiss-btn:hover { color: #dc2626; background: rgba(239,68,68,0.1); }

    @media (max-width: 768px) {
        .alert-dropdown { width: min(360px, calc(100vw - 24px)); }
    }
</style>

<div class="header-alert-wrapper">
    <button type="button" class="header-btn header-btn-icon" id="headerAlertBtn"
            onclick="toggleHeaderAlerts()" title="Notifications"
            aria-haspopup="dialog" aria-expanded="false" aria-controls="headerAlertDropdown">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        {{-- Unread count only: hidden (not "0") when everything has been read,
             the way a notification badge usually behaves. --}}
        <span class="header-badge hidden" id="headerAlertBadge">0</span>
    </button>

    <div class="alert-dropdown" id="headerAlertDropdown" role="dialog" aria-label="Notifications">
        <div class="notif-header">
            <span class="notif-title">Notifications</span>
            <button type="button" class="notif-readall" onclick="markAllAlertsRead(event)">Mark all as read</button>
        </div>

        <div class="notif-tabs" role="tablist" aria-label="Notification filters">
            <button type="button" class="notif-tab active" data-notif-tab="all" role="tab"
                    aria-selected="true" onclick="setNotifTab('all')">All</button>
            <button type="button" class="notif-tab" data-notif-tab="unread" role="tab"
                    aria-selected="false" onclick="setNotifTab('unread')">Unread</button>
        </div>

        <div id="headerAlertList"></div>
    </div>
</div>

<script>
    /* ==================================================================
       NOTIFICATIONS (low-stock alerts) — Admin only.

       Read state is tracked with a single "last seen at" timestamp in
       localStorage rather than a set of IDs: anything whose last stock
       movement is newer than that stamp counts as unread. That way a product
       that gets worse after being read shows up unread again, while
       notifications already acknowledged stay quiet.
       ================================================================== */
    (function () {
        'use strict';

        const STORAGE_DISMISSED = 'dismissedAlertIds';
        const STORAGE_SEEN_AT = 'alertsSeenAt';
        const ACTION_URL = '{{ route('stock-in') }}';
        const SEE_ALL_URL = '{{ route('products') }}';

        const ICON_CRITICAL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
        const ICON_LOW = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>';
        const ICON_EMPTY = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path><path d="m9 11 2 2 4-4"></path></svg>';

        function readJson(key, fallback) {
            try { return JSON.parse(localStorage.getItem(key)) ?? fallback; }
            catch (e) { return fallback; }
        }

        function writeStorage(key, value) {
            try {
                if (value === null) localStorage.removeItem(key);
                else localStorage.setItem(key, value);
            } catch (e) { /* storage disabled — the tray still works per page load */ }
        }

        // Products the Admin dismissed with the row's × (client-side only).
        let dismissedAlertIds = new Set(readJson(STORAGE_DISMISSED, []));
        // Timestamp of the last "Mark all as read".
        let alertsSeenAt = (() => { try { return localStorage.getItem(STORAGE_SEEN_AT) || ''; } catch (e) { return ''; } })();
        // Latest payload, so read-state changes can repaint without refetching.
        let alertCache = [];
        // Fingerprint of the last render — the 30s poll skips repainting when
        // nothing changed, so rows do not re-animate under the pointer.
        let alertsSignature = '';
        // 'all' | 'unread' — the pill tab in the header.
        let activeNotifTab = 'all';

        function escapeHtml(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        function alertSeverity(alert) {
            return Number(alert.current_stock) <= 5 ? 'critical' : 'low';
        }

        function alertActivityTime(alert) {
            // Prefer the newest stock-in, then the newest manual adjustment,
            // then the product row itself.
            const raw = alert.last_received_at || alert.last_adjusted_at || alert.updated_at;
            const time = raw ? new Date(raw).getTime() : 0;
            return Number.isNaN(time) ? 0 : time;
        }

        function isAlertUnread(alert) {
            if (!alertsSeenAt) return true;
            const seen = new Date(alertsSeenAt).getTime();
            if (Number.isNaN(seen)) return true;
            return alertActivityTime(alert) > seen;
        }

        /* Compact relative stamp, the way a notification tray shows it:
           "Just now", "5m", "3h", "2d", then "Oct 12". */
        function notifTime(alert) {
            const raw = alert.last_received_at || alert.last_adjusted_at || alert.updated_at;
            if (!raw) return 'No activity yet';
            const d = new Date(raw);
            if (Number.isNaN(d.getTime())) return 'No activity yet';

            const min = Math.floor((Date.now() - d.getTime()) / 60000);
            if (min < 1) return 'Just now';
            if (min < 60) return min + 'm';
            const hr = Math.floor(min / 60);
            if (hr < 24) return hr + 'h';
            const day = Math.floor(hr / 24);
            if (day < 7) return day + 'd';
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        function updateAlertBadge() {
            const badge = document.getElementById('headerAlertBadge');
            if (!badge) return;
            const unread = alertCache.filter(isAlertUnread).length;
            badge.textContent = unread;
            badge.classList.toggle('hidden', unread === 0);
        }

        function alertRow(a) {
            const severity = alertSeverity(a);
            const unread = isAlertUnread(a);
            const name = escapeHtml(a.name || 'Unnamed item');
            const sku = escapeHtml(a.sku || '\u2014');
            const stockLeft = Number(a.current_stock) || 0;
            const threshold = Number(a.reorder_threshold) || 0;

            return `<div class="alert-card ${severity}${unread ? ' unread' : ''}" id="alert-card-${a.id}"
                         role="button" tabindex="0" title="Open Stock-In"
                         onclick="openAlertItem(${a.id}, event)"
                         onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openAlertItem(${a.id}, event); }">
                    <div class="alert-card-avatar ${severity}">${severity === 'critical' ? ICON_CRITICAL : ICON_LOW}</div>
                    <div class="alert-card-body">
                        <div class="alert-card-text"><strong>${name}</strong> is running low — only ${stockLeft} left in stock</div>
                        <div class="alert-card-meta">${sku} · reorder at ${threshold}</div>
                        <div class="alert-card-time">${notifTime(a)}</div>
                    </div>
                    ${unread ? '<span class="alert-unread-dot" aria-label="Unread"></span>' : ''}
                    <button type="button" class="alert-dismiss-btn" onclick="dismissAlert(${a.id}, event)" title="Remove notification" aria-label="Remove notification">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>`;
        }

        // The reference groups rows under a "New" label with "See all" opposite
        // it. Read items fall into a quieter "Earlier" group underneath.
        function notifSection(label, rows, withSeeAll) {
            const link = withSeeAll
                ? `<a class="notif-section-link" href="${SEE_ALL_URL}">See all</a>`
                : '<span></span>';
            return `<div class="notif-section">
                        <span class="notif-section-label">${label}</span>
                        ${link}
                    </div>${rows}`;
        }

        function emptyState(message) {
            return `<div class="alert-empty">${ICON_EMPTY}<span>${message}</span></div>`;
        }

        function renderAlerts() {
            const list = document.getElementById('headerAlertList');
            if (!list) return;

            if (activeNotifTab === 'unread') {
                const unread = alertCache.filter(isAlertUnread);
                list.innerHTML = unread.length
                    ? notifSection('New', unread.map(alertRow).join(''), true)
                    : emptyState("No unread notifications");
                updateAlertBadge();
                return;
            }

            if (alertCache.length === 0) {
                list.innerHTML = emptyState("You're all caught up");
                updateAlertBadge();
                return;
            }

            const unread = alertCache.filter(isAlertUnread);
            const read = alertCache.filter(a => !isAlertUnread(a));

            let html = '';
            if (unread.length) {
                html += notifSection('New', unread.map(alertRow).join(''), true);
            }
            if (read.length) {
                html += notifSection('Earlier', read.map(alertRow).join(''), unread.length === 0);
            }

            list.innerHTML = html;
            updateAlertBadge();
        }

        async function loadAlerts() {
            try {
                const res = await fetch('/api/inventory/alerts', { headers: { Accept: 'application/json' } });
                // A lapsed session (401) must not wipe what is already on screen.
                if (!res.ok) return;
                const alerts = await res.json();
                const next = (Array.isArray(alerts) ? alerts : [])
                    .filter(a => !dismissedAlertIds.has(a.id));

                const signature = JSON.stringify(
                    next.map(a => [a.id, a.current_stock, a.reorder_threshold, alertActivityTime(a)])
                );
                if (signature === alertsSignature) {
                    updateAlertBadge();
                    return;
                }

                alertsSignature = signature;
                alertCache = next;
                renderAlerts();
            } catch (e) { console.error(e); }
        }

        function setNotifTab(tab) {
            activeNotifTab = tab === 'unread' ? 'unread' : 'all';
            document.querySelectorAll('.notif-tab').forEach(btn => {
                const on = btn.dataset.notifTab === activeNotifTab;
                btn.classList.toggle('active', on);
                btn.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            renderAlerts();
        }

        function toggleHeaderAlerts() {
            const dropdown = document.getElementById('headerAlertDropdown');
            const button = document.getElementById('headerAlertBtn');
            if (!dropdown) return;

            const isOpen = dropdown.classList.toggle('active');
            button?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            if (isOpen) {
                // Refresh on open so a stock change made a second ago is already
                // reflected, instead of waiting for the 30s poll.
                loadAlerts();
                // The user menu and the tray never stay open together.
                const userMenu = document.getElementById('userDropdown');
                userMenu?.classList.remove('active', 'open');
            }
        }

        function closeHeaderAlerts() {
            document.getElementById('headerAlertDropdown')?.classList.remove('active');
            document.getElementById('headerAlertBtn')?.setAttribute('aria-expanded', 'false');
        }

        // A row opens Stock-In, which is where a low-stock alert gets fixed.
        function openAlertItem(productId, event) {
            if (event) event.stopPropagation();
            window.location.href = ACTION_URL;
        }

        function dismissAlert(productId, event) {
            if (event) event.stopPropagation();
            const card = document.getElementById('alert-card-' + productId);

            dismissedAlertIds.add(productId);
            writeStorage(STORAGE_DISMISSED, JSON.stringify([...dismissedAlertIds]));
            alertCache = alertCache.filter(a => a.id !== productId);

            if (!card) { renderAlerts(); return; }
            card.classList.add('removing');
            setTimeout(renderAlerts, 180);
        }

        // Facebook's header action: everything currently listed stops counting
        // as unread. The rows stay put — only the × removes them.
        function markAllAlertsRead(event) {
            if (event) event.stopPropagation();
            alertsSeenAt = new Date().toISOString();
            writeStorage(STORAGE_SEEN_AT, alertsSeenAt);
            renderAlerts();
        }

        // Inline onclick/onkeydown handlers resolve these from `window`.
        window.toggleHeaderAlerts = toggleHeaderAlerts;
        window.setNotifTab = setNotifTab;
        window.markAllAlertsRead = markAllAlertsRead;
        window.dismissAlert = dismissAlert;
        window.openAlertItem = openAlertItem;

        document.addEventListener('click', function (e) {
            const wrapper = document.querySelector('.header-alert-wrapper');
            const dropdown = document.getElementById('headerAlertDropdown');
            if (wrapper && dropdown && !wrapper.contains(e.target)) closeHeaderAlerts();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeHeaderAlerts();
        });

        function bootNotifications() {
            loadAlerts();
            setInterval(loadAlerts, 30000);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootNotifications);
        } else {
            bootNotifications();
        }
    })();
</script>
@endif
@endauth
