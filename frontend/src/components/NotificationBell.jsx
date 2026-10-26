import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { get } from '../lib/api.js';

/**
 * Low-stock notification bell (SS-20) — Admin only.
 *
 * Katumbas ng lumang Blade partial (partials/notifications.blade.php):
 *   - Bell button sa header na may unread badge
 *   - Dropdown tray: All / Unread tabs, severity cards, Mark all as read
 *   - 30s polling ng /api/inventory/alerts
 *   - Unread tracking via localStorage timestamp: alinmang stock movement
 *     na mas bago kaysa sa huling "mark as read" ay unread ulit.
 *
 * Severity: stock <= 5 = critical, kung hindi = low (pareho ng lumang logic).
 */

const SEEN_KEY = 'smart-stock-alerts-seen-at';
const POLL_MS = 30000;

function movementTime(alert) {
    return alert.updated_at ?? alert.last_received_at ?? alert.last_adjusted_at ?? '';
}

function severityOf(alert) {
    return Number(alert.current_stock) <= 5 ? 'critical' : 'low';
}

function timeAgo(value) {
    if (!value) {
        return '';
    }

    const minutes = Math.floor((Date.now() - new Date(value).getTime()) / 60000);

    if (minutes < 1) {
        return 'just now';
    }

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    return `${Math.floor(hours / 24)}d ago`;
}

const SEVERITY_ORDER = { critical: 0, low: 1 };

export default function NotificationBell() {
    const navigate = useNavigate();
    const [alerts, setAlerts] = useState([]);
    const [open, setOpen] = useState(false);
    const [tab, setTab] = useState('all');
    const [seenAt, setSeenAt] = useState(() => {
        try {
            return window.localStorage.getItem(SEEN_KEY) || '';
        } catch {
            return '';
        }
    });
    const wrapRef = useRef(null);

    const refresh = useCallback(async () => {
        try {
            const rows = await get('/api/inventory/alerts');
            setAlerts(Array.isArray(rows) ? rows : []);
        } catch {
            // 401/expired session — ang badge na lang ang manahimik,
            // ang ProtectedRoute na ang bahala sa redirect.
        }
    }, []);

    useEffect(() => {
        refresh();

        const timer = setInterval(refresh, POLL_MS);

        return () => clearInterval(timer);
    }, [refresh]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onDown = (event) => {
            if (wrapRef.current && !wrapRef.current.contains(event.target)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onDown);

        return () => document.removeEventListener('mousedown', onDown);
    }, [open]);

    const sorted = useMemo(
        () =>
            [...alerts].sort((a, b) => {
                const bySeverity = SEVERITY_ORDER[severityOf(a)] - SEVERITY_ORDER[severityOf(b)];

                if (bySeverity !== 0) {
                    return bySeverity;
                }

                return (movementTime(b) || '').localeCompare(movementTime(a) || '');
            }),
        [alerts],
    );

    const unread = useMemo(
        () => sorted.filter((alert) => (movementTime(alert) || '') > seenAt),
        [sorted, seenAt],
    );

    const visible = tab === 'unread' ? unread : sorted;

    function markAllRead() {
        const stamp = new Date().toISOString();

        setSeenAt(stamp);

        try {
            window.localStorage.setItem(SEEN_KEY, stamp);
        } catch {
            // ignore write failures
        }
    }

    return (
        <div className="notif-wrap" ref={wrapRef}>
            <button
                type="button"
                className="notif-bell"
                onClick={() => setOpen((value) => !value)}
                aria-label={`Notifications${unread.length ? ` (${unread.length} unread)` : ''}`}
                title="Low stock alerts"
            >
                <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                {unread.length > 0 && <span className="notif-badge">{unread.length > 99 ? '99+' : unread.length}</span>}
            </button>

            {open && (
                <div className="notif-panel" role="dialog" aria-label="Low stock notifications">
                    <div className="notif-head">
                        <span className="notif-title">Notifications</span>
                        <button type="button" className="notif-readall" onClick={markAllRead}>
                            Mark all as read
                        </button>
                    </div>

                    <div className="notif-tabs">
                        <button
                            type="button"
                            className={`notif-tab${tab === 'all' ? ' is-active' : ''}`}
                            onClick={() => setTab('all')}
                        >
                            All ({sorted.length})
                        </button>
                        <button
                            type="button"
                            className={`notif-tab${tab === 'unread' ? ' is-active' : ''}`}
                            onClick={() => setTab('unread')}
                        >
                            Unread ({unread.length})
                        </button>
                    </div>

                    <div className="notif-list">
                        {visible.length === 0 && (
                            <p className="notif-empty">
                                {tab === 'unread'
                                    ? 'All caught up — nothing unread.'
                                    : 'No low-stock items. Ang galing ng stockroom!'}
                            </p>
                        )}

                        {visible.map((alert) => {
                            const severity = severityOf(alert);
                            const isUnread = (movementTime(alert) || '') > seenAt;

                            return (
                                <button
                                    key={alert.id}
                                    type="button"
                                    className="notif-card"
                                    onClick={() => {
                                        setOpen(false);
                                        navigate('/stock-in');
                                    }}
                                >
                                    <span className={`notif-avatar ${severity}`}>
                                        {severity === 'critical' ? '!!' : '!'}
                                    </span>
                                    <span className="notif-card-body">
                                        <span className="notif-card-title">
                                            {alert.name}
                                            <span className={`pill ${severity === 'critical' ? 'pill-danger' : 'pill-warn'}`}>
                                                {severity === 'critical' ? 'Critical' : 'Low'}
                                            </span>
                                        </span>
                                        <span className="notif-card-meta">
                                            {alert.sku} · reorder at {alert.reorder_threshold} · {alert.current_stock} left
                                        </span>
                                        <span className="notif-card-time">{timeAgo(movementTime(alert))}</span>
                                    </span>
                                    {isUnread && <span className="notif-unread-dot" aria-label="Unread" />}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
