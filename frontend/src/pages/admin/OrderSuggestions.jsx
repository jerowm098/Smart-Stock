import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { usePageLoadbar } from '../../components/PageLoadbar.jsx';
import Modal from '../../components/Modal.jsx';
import ConfirmDialog from '../../components/ConfirmDialog.jsx';
import MessageDialog from '../../components/MessageDialog.jsx';
import Tooltip from '../../components/Tooltip.jsx';
import Alert, { Toast } from '../../components/Feedback.jsx';
import { downloadFile, del, get, post, validationMessages } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';
import { clearConnectedNav, notifyConnectedNav } from '../../lib/navBadges.js';

const TABS = [
    { key: 'active', label: 'Active' },
    { key: 'ordered', label: 'Ordered' },
    { key: 'dismissed', label: 'Dismissed' },
    { key: 'all', label: 'All' },
];

const PER_PAGE = 6;

// Urgency ranking matches the backend / Jira SS-35 levels (critical first).
const URGENCY_RANK = { critical: 0, low: 1 };

// Column → row-field accessors for the client-side table sort.
const SORT_FIELDS = {
    name: (s) => s.name,
    sku: (s) => s.sku,
    stock: (s) => Number(s.current_stock ?? 0),
    avg_daily: (s) => Number(s.avg_daily ?? 0),
    reorder_point: (s) => Number(s.reorder_point ?? 0),
    suggested_qty: (s) => Number(s.suggested_qty ?? 0),
    est_cost: (s) => Number(s.est_cost ?? 0),
    urgency: (s) => URGENCY_RANK[s.urgency] ?? 99,
    reason: (s) => s.reason ?? '',
};

function urgencyClass(urgency) {
    if (urgency === 'critical') return 'pill-danger';
    return 'pill-warn';
}

function highlight(text, needle) {
    if (!needle) return text;
    const str = String(text ?? '');
    const idx = str.toLowerCase().indexOf(needle.toLowerCase());
    if (idx === -1) return text;
    return (
        <>
            {str.slice(0, idx)}
            <mark className="search-highlight">{str.slice(idx, idx + needle.length)}</mark>
            {str.slice(idx + needle.length)}
        </>
    );
}

// ── Recent Activity helpers ──────────────────────────────────────────────────

const ACTIVITY_TONE = {
    suggestion_ordered:   'ok',
    suggestion_dismissed: 'warn',
};

const ACTIVITY_VERB = {
    suggestion_ordered:   'Marked as ordered',
    suggestion_dismissed: 'Dismissed suggestion for',
};

const ACTIVITY_ICONS = {
    suggestion_ordered: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M20 6 9 17l-5-5" />
        </svg>
    ),
    suggestion_dismissed: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M8 12h8" />
        </svg>
    ),
    default: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8h.01M11 12h1v4h1" />
        </svg>
    ),
};

function activitySummary(item) {
    const verb = ACTIVITY_VERB[item.action];
    if (verb && item.subject_name) return `${verb} ${item.subject_name}`;
    const full = String(item.description ?? '');
    const colon = full.indexOf(':');
    return colon > 0 ? full.slice(0, colon) : full;
}

function activityChanges(item) {
    const explicit = item?.meta?.changes;
    if (Array.isArray(explicit) && explicit.length > 0) {
        return explicit.map((c) => c.text ?? String(c));
    }
    const meta = item?.meta ?? {};
    const lines = [];
    if (meta.sku)           lines.push(`SKU ${meta.sku}`);
    if (meta.suggested_qty) lines.push(`Suggested qty ${meta.suggested_qty}`);
    if (meta.urgency)       lines.push(`Urgency ${meta.urgency}`);
    return lines;
}

export default function OrderSuggestions() {
    const [status, setStatus] = useState('active');
    const [search, setSearch] = useState('');
    const [urgencyFilter, setUrgencyFilter] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('');

    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    usePageLoadbar(loading);
    const hasLoadedRef = useRef(false);

    const [busyId, setBusyId] = useState(null);
    const [forecasting, setForecasting] = useState(false);
    const [message, setMessage] = useState(null);
    const [error, setError] = useState(null);

    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [page, setPage] = useState(1);

    // Recent Activity
    const [activity, setActivity] = useState([]);
    const [activityDetail, setActivityDetail] = useState(null);
    const [activityPage, setActivityPage] = useState(1);
    const [clearConfirm, setClearConfirm] = useState(false);
    const [clearing, setClearing] = useState(false);
    const [clearError, setClearError] = useState(null);
    const [activityOptionsOpen, setActivityOptionsOpen] = useState(false);
    const activityOptionsRef = useRef(null);
    const [suggestionsOptionsOpen, setSuggestionsOptionsOpen] = useState(false);
    const suggestionsOptionsRef = useRef(null);
    const [seeding, setSeeding] = useState(false);
    const [deleteAllConfirm, setDeleteAllConfirm] = useState(false);
    const [deletingAll, setDeletingAll] = useState(false);
    const [deleteAllError, setDeleteAllError] = useState(null);
    const [emptyNotice, setEmptyNotice] = useState(null);
    const ACTIVITY_PER_PAGE = 3;

    const load = useCallback(async (silent = false) => {
        if (!silent) setLoading(true);
        setError(null);

        try {
            const [suggestions, feed] = await Promise.all([
                get('/api/dashboard/order-suggestions', { status: 'all' }),
                get('/api/activity/recent', {
                    limit: 12,
                    actions: 'suggestion_ordered,suggestion_dismissed',
                }).catch(() => null),
            ]);
            setData(suggestions);
            setActivity(Array.isArray(feed) ? feed : []);
            hasLoadedRef.current = true;
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        // Initial load paints the skeleton; tab changes update smoothly in place
        load(hasLoadedRef.current);
    }, [load]);

    const refresh = useCallback(() => load(true), [load]);

    // Options dropdowns (kebabs) on Order Suggestions and Recent Activity card
    // headers: close on outside click and Escape, matching the other admin pages.
    useEffect(() => {
        if (!activityOptionsOpen && !suggestionsOptionsOpen) {
            return;
        }

        const onDown = (event) => {
            if (activityOptionsRef.current && !activityOptionsRef.current.contains(event.target)) {
                setActivityOptionsOpen(false);
            }
            if (suggestionsOptionsRef.current && !suggestionsOptionsRef.current.contains(event.target)) {
                setSuggestionsOptionsOpen(false);
            }
        };

        const onKey = (event) => {
            if (event.key === 'Escape') {
                setActivityOptionsOpen(false);
                setSuggestionsOptionsOpen(false);
            }
        };

        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [activityOptionsOpen, suggestionsOptionsOpen]);

    async function handleClearActivity() {
        setClearing(true);
        setClearError(null);

        try {
            const response = await del(
                '/api/activity/recent?actions=suggestion_ordered,suggestion_dismissed',
            );

            setClearConfirm(false);
            setActivity([]);
            clearConnectedNav('suggestionAdded');
            setActivityPage(1);
            setMessage(response?.message ?? 'Activity list cleared.');
            await refresh();
        } catch (err) {
            setClearError(validationMessages(err)[0] ?? err.message ?? 'Could not clear the activity list.');
        } finally {
            setClearing(false);
        }
    }

    async function handleSeedSuggestions() {
        if (seeding) return;
        setSeeding(true);

        try {
            const response = await post('/api/dashboard/order-suggestions/seed');
            setMessage(response?.message ?? '7 sample order suggestions added.');
            notifyConnectedNav('suggestionAdded', response?.created ?? 7);
            await refresh();
        } catch (err) {
            setError(validationMessages(err)[0] ?? err.message ?? 'Could not add the sample suggestions.');
        } finally {
            setSeeding(false);
        }
    }

    async function handleDeleteAllConfirm() {
        if (deletingAll) return;
        setDeletingAll(true);
        setDeleteAllError(null);

        try {
            const response = await del('/api/dashboard/order-suggestions');
            setDeleteAllConfirm(false);
            const deleted = Number(response?.deleted) || 0;
            clearConnectedNav('suggestionAdded');
            if (deleted > 0) {
                notifyConnectedNav('suggestionAdded', -deleted);
            }
            setMessage(response?.message ?? 'All order suggestions deleted.');
            await refresh();
        } catch (err) {
            setDeleteAllError(validationMessages(err)[0] ?? err.message ?? 'Could not delete order suggestions.');
        } finally {
            setDeletingAll(false);
        }
    }

    async function act(suggestion, action) {
        setBusyId(suggestion.id);
        setMessage(null);
        setError(null);

        try {
            const response = await post(`/api/dashboard/order-suggestions/${suggestion.id}/${action}`);
            setMessage(response.message);
            await refresh();
        } catch (err) {
            setError(validationMessages(err)[0] ?? err.message);
        } finally {
            setBusyId(null);
        }
    }

    async function runForecast() {
        setForecasting(true);
        setMessage(null);
        setError(null);

        try {
            const response = await post('/api/dashboard/order-suggestions/run');
            const stats = response.stats ?? {};
            setMessage(
                `Forecasts recomputed. ${stats.created ?? 0} created, ${stats.updated ?? 0} updated, ` +
                    `${stats.preserved ?? 0} preserved, ${stats.skipped_fallback ?? 0} on static fallback.`,
            );
            setStatus('active');
            if ((stats.created ?? 0) > 0) {
                notifyConnectedNav('suggestionAdded', stats.created);
            }
            await load(true);
        } catch (err) {
            setError(err.message);
        } finally {
            setForecasting(false);
        }
    }

    const allSuggestions = data?.suggestions ?? [];
    const suggestions = useMemo(
        () => (status === 'all' ? allSuggestions : allSuggestions.filter((s) => s.status === status)),
        [allSuggestions, status],
    );

    const categories = useMemo(() => {
        const set = new Set();
        suggestions.forEach((s) => {
            if (s.category && s.category !== '—') {
                set.add(s.category);
            }
        });
        return Array.from(set).sort((a, b) => a.localeCompare(b));
    }, [suggestions]);

    const filteredSuggestions = useMemo(() => {
        const needle = search.trim().toLowerCase();

        return suggestions.filter((s) => {
            if (needle) {
                const matches = [s.name, s.sku]
                    .filter(Boolean)
                    .some((val) => String(val).toLowerCase().includes(needle));
                if (!matches) return false;
            }

            if (urgencyFilter && s.urgency !== urgencyFilter) {
                return false;
            }

            if (categoryFilter && s.category !== categoryFilter) {
                return false;
            }

            return true;
        });
    }, [suggestions, search, urgencyFilter, categoryFilter]);

    const hasActiveFilters = Boolean(search.trim() || urgencyFilter || categoryFilter);

    const clearFilters = () => {
        setSearch('');
        setUrgencyFilter('');
        setCategoryFilter('');
    };

    /** Column sort: 1st click asc → 2nd desc → 3rd back to default order. */
    function toggleSort(field) {
        if (sortBy === field) {
            if (sortDir === 'asc') {
                setSortDir('desc');
            } else {
                setSortBy(null);
                setSortDir(null);
            }
        } else {
            setSortBy(field);
            setSortDir('asc');
        }
    }

    // Reset page to 1 whenever filters, sorting, or tab change
    useEffect(() => {
        setPage(1);
    }, [search, urgencyFilter, categoryFilter, sortBy, sortDir, status]);

    const sortedSuggestions = useMemo(() => {
        const key = SORT_FIELDS[sortBy];
        if (!sortBy || !key) {
            return filteredSuggestions;
        }

        const dir = sortDir === 'asc' ? 1 : -1;
        return [...filteredSuggestions].sort((a, b) => {
            const av = key(a);
            const bv = key(b);

            if (typeof av === 'number' && typeof bv === 'number') {
                return (av - bv) * dir;
            }

            const as = String(av ?? '').toLowerCase();
            const bs = String(bv ?? '').toLowerCase();
            if (as < bs) return -1 * dir;
            if (as > bs) return 1 * dir;
            return 0;
        });
    }, [filteredSuggestions, sortBy, sortDir]);

    const lastPage = Math.max(1, Math.ceil(sortedSuggestions.length / PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const pagedSuggestions = useMemo(
        () => sortedSuggestions.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE),
        [sortedSuggestions, safePage],
    );

    // Activity pagination
    const activityLastPage = Math.max(1, Math.ceil(activity.length / ACTIVITY_PER_PAGE));
    const activitySafePage = Math.min(activityPage, activityLastPage);
    const pagedActivity = useMemo(
        () => activity.slice((activitySafePage - 1) * ACTIVITY_PER_PAGE, activitySafePage * ACTIVITY_PER_PAGE),
        [activity, activitySafePage],
    );
    useEffect(() => { setActivityPage(1); }, [activity.length]);

    const handleTabChange = (key) => {
        if (key === status) return;
        setStatus(key);
    };

    return (
        <>
        <div className="page">
            {/* Permanent hero header — never unmounts, provides loadbar indicator */}
            <section className="page-hero">
                <header className="page-head">
                    <div>
                        <h2>Order Suggestions</h2>
                        <p>Manage demand-based restock suggestions.</p>
                    </div>
                </header>
            </section>

            {message && <Toast tone="success" onClose={() => setMessage(null)}>{message}</Toast>}
            {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}

            {loading ? (
                /* Loading phase: paints skeletons mirroring real page layout */
                <div className="page-loading-phase" aria-busy="true" aria-live="polite">
                    {/* Export Suggestions panel skeleton */}
                    <section className="card panel panel-add">
                        <div className="panel-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                            <span className="skeleton" style={{ width: 118, height: 38, borderRadius: 8 }} />
                        </div>
                    </section>

                    {/* Search & Filter Options skeleton */}
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                        </div>
                        <div className="filter-shell">
                            <div className="filter-grid filter-grid-2">
                                {[0, 1, 2].map((i) => (
                                    <div key={i} className="field">
                                        <span className="skeleton skeleton-sm" />
                                        <span className="skeleton" style={{ height: 42 }} />
                                    </div>
                                ))}
                                <div className="field">
                                    <span className="sr-only">Clear filters</span>
                                    <span className="skeleton" style={{ height: 42 }} />
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Status tabs skeleton */}
                    <section className="card padded tabs-card">
                        <div className="mode-tabs" aria-hidden="true">
                            {[0, 1, 2, 3].map((i) => (
                                <span key={i} className="skeleton" style={{ width: 86, height: 32, borderRadius: 8 }} />
                            ))}
                        </div>
                    </section>

                    {/* Order Suggestions table skeleton */}
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                            <span className="skeleton" style={{ width: 118, height: 38, borderRadius: 8 }} />
                        </div>
                        <div className="table-shell">
                            <table className="table" aria-hidden="true">
                                <thead>
                                    <tr>
                                        {[0, 1, 2, 3, 4, 5, 6, 7, 8, 9].map((i) => (
                                            <th key={i}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4, 5, 6, 7, 8, 9].map((c) => (
                                                <td key={c}>
                                                    <span className="skeleton skeleton-line" style={{ width: c === 8 ? '62%' : '85%' }} />
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="pagination" aria-hidden="true">
                            <span className="skeleton" style={{ width: 34, height: 34 }} />
                            <span className="skeleton" style={{ width: 20, height: 12 }} />
                            <span className="skeleton" style={{ width: 34, height: 34 }} />
                        </div>
                    </section>

                    {/* Recent Activity skeleton */}
                    <section className="card padded recent-activity">
                        <div className="filter-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                        </div>
                        <div className="activity-shell">
                            <ul className="activity-list">
                                {[0, 1, 2].map((i) => (
                                    <li key={i}>
                                        <div className="activity-row" aria-hidden="true">
                                            <span className="skeleton" style={{ width: 32, height: 32, borderRadius: 9, flexShrink: 0 }} />
                                            <div className="activity-body">
                                                <span className="skeleton skeleton-line" style={{ width: '55%', marginBottom: 8 }} />
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '35%' }} />
                                            </div>
                                            <span className="skeleton skeleton-line" style={{ width: 90, height: 10, flexShrink: 0 }} />
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="pagination" aria-hidden="true">
                            <span className="skeleton" style={{ width: 34, height: 34 }} />
                            <span className="skeleton" style={{ width: 20, height: 12 }} />
                            <span className="skeleton" style={{ width: 34, height: 34 }} />
                        </div>
                    </section>
                </div>
            ) : (
                <div className="page-body">
                    {/* Export Suggestions primary action panel */}
                    <section className="card panel panel-add">
                        <header className="panel-head">
                            <div>
                                <h3>Export Suggestions</h3>
                                <p className="muted">Download the current order suggestions as a CSV file.</p>
                            </div>
                            <button
                                type="button"
                                className="btn btn-primary"
                                onClick={() => downloadFile('/api/dashboard/order-suggestions/export', { status })}
                            >
                                Export CSV
                            </button>
                        </header>
                    </section>

                    {/* Search & Filter Options — fully functional */}
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <h3>Search & Filter Options</h3>
                                <p className="muted">Narrow order suggestions by product name, SKU, category or urgency.</p>
                            </div>
                        </div>
                        <div className="filter-shell">
                            <div className="filter-grid filter-grid-2">
                                <label className="field">
                                    <span>Search</span>
                                    <input
                                        type="search"
                                        placeholder="Search product name or SKU…"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                    />
                                </label>
                                <label className="field">
                                    <span>Category</span>
                                    <select
                                        className="input"
                                        value={categoryFilter}
                                        onChange={(e) => setCategoryFilter(e.target.value)}
                                    >
                                        <option value="">All categories</option>
                                        {categories.map((cat) => (
                                            <option key={cat} value={cat}>{cat}</option>
                                        ))}
                                    </select>
                                </label>
                                <label className="field">
                                    <span>Urgency</span>
                                    <select
                                        className="input"
                                        value={urgencyFilter}
                                        onChange={(e) => setUrgencyFilter(e.target.value)}
                                    >
                                        <option value="">All urgencies</option>
                                        <option value="critical">Critical</option>
                                        <option value="low">Low</option>
                                    </select>
                                </label>
                                <div className="field">
                                    <span className="sr-only">Clear filters</span>
                                    <button
                                        type="button"
                                        className="btn btn-ghost"
                                        onClick={clearFilters}
                                        disabled={!hasActiveFilters}
                                    >
                                        Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Status tabs card — tab selection only (Run forecast lives in the table card header) */}
                    <section className="card padded tabs-card">
                        <div className="mode-tabs" role="tablist">
                            {TABS.map((tab) => (
                                <button
                                    key={tab.key}
                                    type="button"
                                    className={`mode-tab${status === tab.key ? ' is-active' : ''}`}
                                    onClick={() => handleTabChange(tab.key)}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>
                    </section>

                    {/* Order Suggestions Table */}
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <h3>Order Suggestions</h3>
                                <p className="muted">Suggested restocks with demand, urgency and estimated cost.</p>
                            </div>
                            <div className="head-actions">
                                <button
                                    type="button"
                                    className="btn btn-primary"
                                    onClick={runForecast}
                                    disabled={forecasting}
                                >
                                    {forecasting ? 'Computing…' : 'Run forecast'}
                                </button>
                                <div className="activity-options" ref={suggestionsOptionsRef}>
                                    <button
                                        type="button"
                                        className="icon-btn is-outlined"
                                        onClick={() => setSuggestionsOptionsOpen((open) => !open)}
                                        aria-haspopup="menu"
                                        aria-expanded={suggestionsOptionsOpen}
                                        aria-label="Order suggestion options"
                                    >
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.7" />
                                            <circle cx="12" cy="12" r="1.7" />
                                            <circle cx="12" cy="19" r="1.7" />
                                        </svg>
                                    </button>
                                    {suggestionsOptionsOpen && (
                                        <div className="activity-options-panel" role="menu" aria-label="Order suggestion options">
                                            {status === 'active' && (
                                                <button
                                                    type="button"
                                                    className="activity-options-item"
                                                    role="menuitem"
                                                    disabled={seeding || deletingAll}
                                                    onClick={() => {
                                                        setSuggestionsOptionsOpen(false);
                                                        handleSeedSuggestions();
                                                    }}
                                                >
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                        <path d="M9 12h6m-6 3.75h6M9 4.5h6M6.75 6.75h10.5a1.5 1.5 0 011.5 1.5v10.5a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5z" />
                                                    </svg>
                                                    <span>Add 7 sample suggestions</span>
                                                </button>
                                            )}
                                            <button
                                                type="button"
                                                className="activity-options-item activity-options-danger"
                                                role="menuitem"
                                                disabled={seeding || deletingAll}
                                                onClick={() => {
                                                    setSuggestionsOptionsOpen(false);

                                                    if (suggestions.length === 0) {
                                                        setEmptyNotice({
                                                            title: 'No suggestions to delete',
                                                            description: 'The order suggestions list is already empty. Run a forecast or add the 7 sample suggestions on the Active tab first.',
                                                        });
                                                        return;
                                                    }

                                                    setDeleteAllError(null);
                                                    setDeleteAllConfirm(true);
                                                }}
                                            >
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                    <path d="M3 6h18" />
                                                    <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                    <path d="M10 11v6" />
                                                    <path d="M14 11v6" />
                                                </svg>
                                                <span>Delete all suggestions</span>
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="table-shell">
                            <div className="table-wrap">
                                <table className="table suggestions-table">
                                    <thead>
                                        <tr>
                                            <th className="sort-header" onClick={() => toggleSort('name')}>
                                                Product
                                                <span className={`sort-indicator${sortBy === 'name' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'name' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('sku')} title="Stock Keeping Unit">
                                                SKU
                                                <span className={`sort-indicator${sortBy === 'sku' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'sku' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                                <span className="th-sub">Stock Keeping Unit</span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('stock')}>
                                                Stock
                                                <span className={`sort-indicator${sortBy === 'stock' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'stock' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('avg_daily')} title="Average daily sales">
                                                Avg Daily
                                                <span className={`sort-indicator${sortBy === 'avg_daily' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'avg_daily' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                                <span className="th-sub">Average daily sales</span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('reorder_point')} title="Reorder threshold point">
                                                Reorder pt
                                                <span className={`sort-indicator${sortBy === 'reorder_point' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'reorder_point' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                                <span className="th-sub">Reorder point</span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('suggested_qty')} title="Suggested order quantity">
                                                Suggested
                                                <span className={`sort-indicator${sortBy === 'suggested_qty' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'suggested_qty' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('est_cost')} title="Estimated restock cost">
                                                Est. Cost
                                                <span className={`sort-indicator${sortBy === 'est_cost' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'est_cost' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                                <span className="th-sub">Estimated cost</span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('urgency')}>
                                                Urgency
                                                <span className={`sort-indicator${sortBy === 'urgency' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'urgency' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('reason')}>
                                                Reason
                                                <span className={`sort-indicator${sortBy === 'reason' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'reason' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                            </th>
                                            {status === 'active' && <th>Actions</th>}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {pagedSuggestions.map((suggestion) => (
                                            <tr key={suggestion.id}>
                                                <td>
                                                    <strong>{highlight(suggestion.name, search)}</strong>
                                                </td>
                                                <td className="muted mono">
                                                    {highlight(suggestion.sku, search)}
                                                </td>
                                                <td className="num-cell">{suggestion.current_stock}</td>
                                                <td className="num-cell">{suggestion.avg_daily}</td>
                                                <td className="num-cell">{suggestion.reorder_point}</td>
                                                <td className="num-cell">
                                                    <strong>{suggestion.suggested_qty}</strong>
                                                </td>
                                                <td className="num-cell est-cost-cell">
                                                    {suggestion.est_cost !== null ? formatPeso(suggestion.est_cost) : '—'}
                                                </td>
                                                <td>
                                                    <span className={`pill ${urgencyClass(suggestion.urgency)}`}>
                                                        {suggestion.urgency}
                                                    </span>
                                                </td>
                                                <td className="muted reason-cell">
                                                    <span className="reason-text" title={suggestion.reason ?? undefined}>
                                                        {suggestion.reason ?? '—'}
                                                    </span>
                                                </td>
                                                {status === 'active' && (
                                                    <td className="actions-cell">
                                                        <Tooltip
                                                            label="Ordered"
                                                            className="btn btn-ghost btn-sm btn-success-text action-icon-btn"
                                                            disabled={busyId === suggestion.id}
                                                            onClick={() => act(suggestion, 'ordered')}
                                                        >
                                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                                <circle cx="12" cy="12" r="9" />
                                                                <path d="m8.5 12 2.5 2.5 4.5-5" />
                                                            </svg>
                                                        </Tooltip>
                                                        <Tooltip
                                                            label="Dismiss"
                                                            className="btn btn-ghost btn-sm btn-danger-text action-icon-btn"
                                                            disabled={busyId === suggestion.id}
                                                            onClick={() => act(suggestion, 'dismiss')}
                                                        >
                                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                                <circle cx="12" cy="12" r="9" />
                                                                <path d="m15 9-6 6M9 9l6 6" />
                                                            </svg>
                                                        </Tooltip>
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>

                                {sortedSuggestions.length === 0 && (
                                    <div className="table-empty table-empty-solid">
                                        <span className="table-empty-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                                <circle cx="11" cy="11" r="8" />
                                                <path d="M21 21l-4.35-4.35" />
                                                <path d="M11 8v6M8 11h6" />
                                            </svg>
                                        </span>
                                        <p className="table-empty-title">
                                            {hasActiveFilters ? 'No results found' : 'No order suggestions yet'}
                                        </p>
                                        <p className="table-empty-sub">
                                            {hasActiveFilters
                                                ? 'Try adjusting your search or filters.'
                                                : status === 'active'
                                                    ? 'Run a forecast or add the 7 sample suggestions to populate this list.'
                                                    : 'No suggestions with this status.'}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {sortedSuggestions.length > 0 && (
                            <div className="pagination">
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm page-btn"
                                    disabled={safePage <= 1}
                                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                                    aria-label="Previous page"
                                >
                                    <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                        <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                </button>
                                <span className="page-count">{safePage}</span>
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm page-btn"
                                    disabled={safePage >= lastPage}
                                    onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                                    aria-label="Next page"
                                >
                                    <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                        <path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                </button>
                                <span className="page-detail">{Math.min(safePage * PER_PAGE, sortedSuggestions.length)} of {sortedSuggestions.length}</span>
                            </div>
                        )}
                    </section>

                    {/* Recent Activity List */}
                    <section className="card padded recent-activity">
                        <div className="filter-head">
                            <div>
                                <h3>Recent Activity List</h3>
                                <p className="muted">See recent order suggestion actions.</p>
                            </div>
                            <div className="activity-options" ref={activityOptionsRef}>
                                <button
                                    type="button"
                                    className="icon-btn is-outlined"
                                    onClick={() => setActivityOptionsOpen((open) => !open)}
                                    aria-haspopup="menu"
                                    aria-expanded={activityOptionsOpen}
                                    aria-label="Activity options"
                                >
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                                        <circle cx="12" cy="5" r="1.7" />
                                        <circle cx="12" cy="12" r="1.7" />
                                        <circle cx="12" cy="19" r="1.7" />
                                    </svg>
                                </button>
                                {activityOptionsOpen && (
                                    <div className="activity-options-panel" role="menu" aria-label="Activity options">
                                        <button
                                            type="button"
                                            className="activity-options-item activity-options-danger"
                                            role="menuitem"
                                            onClick={() => {
                                                setActivityOptionsOpen(false);

                                                if (activity.length === 0) {
                                                    setEmptyNotice({
                                                        title: 'Nothing to clear',
                                                        description: 'The activity list is already empty. Mark a suggestion as ordered or dismiss one and its activity will show up here.',
                                                    });
                                                    return;
                                                }

                                                setClearError(null);
                                                setClearConfirm(true);
                                            }}
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                <path d="M10 11v6" />
                                                <path d="M14 11v6" />
                                            </svg>
                                            <span>Clear activity</span>
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                        {activity.length === 0 ? (
                            <div className="table-empty">
                                <span className="table-empty-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M9 12h6m-6 3.75h6M9 4.5h6M6.75 6.75h10.5a1.5 1.5 0 011.5 1.5v10.5a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5z" />
                                    </svg>
                                </span>
                                <p className="table-empty-title">No suggestion activity yet</p>
                                <p className="table-empty-sub">Mark a suggestion as ordered or dismiss one and it will show up here.</p>
                            </div>
                        ) : (
                            <>
                                <div className="activity-shell">
                                    <ul className="activity-list">
                                        {pagedActivity.map((item) => {
                                            const tone = ACTIVITY_TONE[item.action] ?? 'neutral';
                                            return (
                                                <li key={item.id}>
                                                    <button
                                                        type="button"
                                                        className="activity-row"
                                                        onClick={() => setActivityDetail(item)}
                                                        aria-label={`View details: ${activitySummary(item)}`}
                                                    >
                                                        <span className={`activity-icon activity-icon-${tone}`} aria-hidden="true">
                                                            {ACTIVITY_ICONS[item.action] ?? ACTIVITY_ICONS.default}
                                                        </span>
                                                        <div className="activity-body">
                                                            <p className="activity-desc">{activitySummary(item)}</p>
                                                            <p className="activity-meta muted">
                                                                <span className="activity-user">{item.user_name}</span>
                                                                {item.user_role && <span className="activity-role">{item.user_role}</span>}
                                                            </p>
                                                        </div>
                                                        <time
                                                            className="activity-time muted"
                                                            dateTime={item.created_at ?? undefined}
                                                            title={item.created_at ? new Date(item.created_at).toLocaleString() : undefined}
                                                        >
                                                            {item.created_at_human ?? ''}
                                                        </time>
                                                    </button>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </div>
                                {activity.length > ACTIVITY_PER_PAGE && (
                                    <div className="pagination">
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-sm page-btn"
                                            disabled={activitySafePage <= 1}
                                            onClick={() => setActivityPage((p) => Math.max(1, p - 1))}
                                            aria-label="Previous page"
                                        >
                                            <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                                <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                            </svg>
                                        </button>
                                        <span className="page-count">{activitySafePage}</span>
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-sm page-btn"
                                            disabled={activitySafePage >= activityLastPage}
                                            onClick={() => setActivityPage((p) => Math.min(activityLastPage, p + 1))}
                                            aria-label="Next page"
                                        >
                                            <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                                <path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                            </svg>
                                        </button>
                                        <span className="page-detail">{Math.min(activitySafePage * ACTIVITY_PER_PAGE, activity.length)} of {activity.length}</span>
                                    </div>
                                )}
                            </>
                        )}
                    </section>
                </div>
            )}
        </div>

        {activityDetail && (
            <Modal
                title={(
                    <span className="activity-detail-title">
                        <span className="activity-detail-infoicon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 11v5M12 8h.01" />
                            </svg>
                        </span>
                        Activity details
                    </span>
                )}
                className="modal-activity"
                noClose
                onClose={() => setActivityDetail(null)}
            >
                <dl className="activity-detail-list">
                    <div className="activity-detail-item">
                        <dt>Action</dt>
                        <dd>{ACTIVITY_VERB[activityDetail.action] ?? activityDetail.action}</dd>
                    </div>
                    <div className="activity-detail-item">
                        <dt>By</dt>
                        <dd>
                            {activityDetail.user_name}
                            {activityDetail.user_role && <span className="activity-role">{activityDetail.user_role}</span>}
                        </dd>
                    </div>
                    <div className="activity-detail-item">
                        <dt>When</dt>
                        <dd>
                            {activityDetail.created_at
                                ? new Date(activityDetail.created_at).toLocaleString()
                                : activityDetail.created_at_human}
                        </dd>
                    </div>
                    {activityDetail.subject_name && (
                        <div className="activity-detail-item">
                            <dt>Suggestion</dt>
                            <dd>{activityDetail.subject_name}</dd>
                        </div>
                    )}
                    {activityChanges(activityDetail).length > 0 && (
                        <div className="activity-detail-item">
                            <dt>Changes made</dt>
                            <dd>
                                <ul className="activity-detail-changes">
                                    {activityChanges(activityDetail).map((text, index) => (
                                        <li key={`${text}-${index}`}>{text}</li>
                                    ))}
                                </ul>
                            </dd>
                        </div>
                    )}
                </dl>
                <div className="modal-actions modal-actions-center">
                    <button type="button" className="btn btn-ghost" onClick={() => setActivityDetail(null)}>Close</button>
                </div>
            </Modal>
        )}

        {emptyNotice && (
            <MessageDialog
                tone="info"
                title={emptyNotice.title}
                description={emptyNotice.description}
                onClose={() => setEmptyNotice(null)}
            />
        )}

        {deleteAllConfirm && (
            <ConfirmDialog
                tone="danger"
                title="Delete all suggestions?"
                description={`This permanently deletes all ${suggestions.length} order suggestion(s). Products and sales history are not affected. This action cannot be undone.`}
                confirmLabel="Delete all"
                loadingLabel="Deleting…"
                loading={deletingAll}
                error={deleteAllError}
                onConfirm={handleDeleteAllConfirm}
                onCancel={() => {
                    if (!deletingAll) {
                        setDeleteAllConfirm(false);
                        setDeleteAllError(null);
                    }
                }}
            />
        )}

        {clearConfirm && (
            <ConfirmDialog
                tone="danger"
                title="Clear activity list?"
                description="This permanently deletes the order suggestion activity history from the activity log. This action cannot be undone."
                confirmLabel="Clear"
                loadingLabel="Clearing…"
                loading={clearing}
                error={clearError}
                onConfirm={handleClearActivity}
                onCancel={() => {
                    if (!clearing) {
                        setClearConfirm(false);
                        setClearError(null);
                    }
                }}
            />
        )}
        </>
    );
}
