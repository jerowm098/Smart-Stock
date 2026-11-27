import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { usePageLoadbar } from '../../components/PageLoadbar.jsx';
import ConfirmDialog from '../../components/ConfirmDialog.jsx';
import MessageDialog from '../../components/MessageDialog.jsx';
import Alert, { Toast } from '../../components/Feedback.jsx';
import Modal from '../../components/Modal.jsx';
import { del, exportFile, get, post, validationMessages } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

const PER_PAGE = 6;

// Recent Activity rows shown per page (matches the other admin pages).
const ACTIVITY_PER_PAGE = 3;

// Column → row-field accessors for the client-side table sort.
const SORT_FIELDS = {
    receipt: (sale) => sale.receipt_no,
    channel: (sale) => (sale.channel === 'admin' ? 'Admin POS' : 'Cashier POS'),
    cashier: (sale) => sale.cashier_name,
    items: (sale) => sale.items_count,
    total: (sale) => sale.total_amount,
    date: (sale) => sale.date_raw,
};

// Highlight the search term inside a cell value — same helper as Products.
function highlight(text, needle) {
    if (!needle) return text;
    const idx = String(text).toLowerCase().indexOf(needle.toLowerCase());
    if (idx === -1) return text;
    return (
        <>
            {text.slice(0, idx)}
            <mark className="search-highlight">{text.slice(idx, idx + needle.length)}</mark>
            {text.slice(idx + needle.length)}
        </>
    );
}

// ── Recent Activity (export feed) ────────────────────────────────────────────
// Transactions owns the `transactions_exported` slice of the audit log: one
// row per "Export CSV" click, rendered exactly like the Products page feed.
// Sales themselves are NOT listed here — the table above is the sales record.

// Recent Activity: action → colour tone + short verb, mirroring Products.
const ACTIVITY_TONE = {
    transactions_exported: 'ok',
};

const ACTIVITY_VERB = {
    transactions_exported: 'Exported transactions',
};

const ACTIVITY_ICONS = {
    transactions_exported: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <path d="M7 10l5 5 5-5" />
            <path d="M12 15V3" />
        </svg>
    ),
    default: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8h.01M11 12h1v4h1" />
        </svg>
    ),
};

// One-line row summary, e.g. "Exported transactions (82)".
function activitySummary(item) {
    if (item.action === 'transactions_exported') {
        const rows = item?.meta?.rows;
        return rows != null ? `Exported transactions (${rows})` : 'Exported transactions';
    }

    const verb = ACTIVITY_VERB[item.action];
    if (verb && item.subject_name) {
        return `${verb} ${item.subject_name}`;
    }
    return String(item.description ?? 'Export activity');
}

// The "changes made" lines for the detail popup (file, rows, filters).
function activityChanges(item) {
    const explicit = item?.meta?.changes;
    if (Array.isArray(explicit) && explicit.length > 0) {
        return explicit.map((change) => change.text ?? String(change));
    }

    const meta = item?.meta ?? {};
    const lines = [];
    if (meta.filename) lines.push(`File ${meta.filename}`);
    if (meta.rows != null) lines.push(`${meta.rows} transactions exported`);
    lines.push('CSV file (Excel-compatible)');
    return lines;
}

export default function Transactions() {
    const [filters, setFilters] = useState({ search: '', cashier_id: '', channel: '', date_from: '', date_to: '' });
    const [searchInput, setSearchInput] = useState('');
    const [page, setPage] = useState(1);
    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    usePageLoadbar(loading);
    const [error, setError] = useState(null);
    const [viewing, setViewing] = useState(null);

    // Recent Activity List (sales feed) state.
    const [activity, setActivity] = useState([]);
    const [activityDetail, setActivityDetail] = useState(null);
    const [activityPage, setActivityPage] = useState(1);
    const [activityOptionsOpen, setActivityOptionsOpen] = useState(false);
    const activityOptionsRef = useRef(null);
    const [txnOptionsOpen, setTxnOptionsOpen] = useState(false);
    const txnOptionsRef = useRef(null);
    const [seeding, setSeeding] = useState(false);
    const [deleteAllConfirm, setDeleteAllConfirm] = useState(false);
    const [deletingAll, setDeletingAll] = useState(false);
    const [deleteAllError, setDeleteAllError] = useState(null);
    const [emptyNotice, setEmptyNotice] = useState(null);
    const [clearConfirm, setClearConfirm] = useState(false);
    const [clearing, setClearing] = useState(false);
    const [clearError, setClearError] = useState(null);
    const [exporting, setExporting] = useState(false);
    const [toast, setToast] = useState(null);

    // Guards against out-of-order responses: only the newest request may write
    // state, otherwise a slow earlier keystroke can overwrite fresh results.
    const requestId = useRef(0);

    const lastPage = Math.max(1, data?.last_page ?? 1);
    const safePage = Math.min(page, lastPage);
    const needle = filters.search.trim();

    // Client-side column sort over the rows of the current page (mirrors the
    // Stock Inventory table). Sorting re-orders what is already rendered
    // instead of re-fetching, so clicking a header no longer flashes the
    // table like a page reload.
    const sortedRows = useMemo(() => {
        const rows = data?.data ?? [];
        const field = sortBy ? SORT_FIELDS[sortBy] : null;

        if (!field) {
            return rows;
        }

        const dir = sortDir === 'asc' ? 1 : -1;
        return [...rows].sort((a, b) => {
            let av = field(a);
            let bv = field(b);

            // Numeric comparison for items and total
            if (sortBy === 'items' || sortBy === 'total') {
                return (Number(av ?? 0) - Number(bv ?? 0)) * dir;
            }

            // Date comparison for the transaction date
            if (sortBy === 'date') {
                return (new Date(av ?? 0).getTime() - new Date(bv ?? 0).getTime()) * dir;
            }

            // String comparison for receipt, type and cashier
            av = String(av ?? '').toLowerCase();
            bv = String(bv ?? '').toLowerCase();
            if (av < bv) return -1 * dir;
            if (av > bv) return 1 * dir;
            return 0;
        });
    }, [data, sortBy, sortDir]);

    // Recent Activity pagination (3 rows per page, matching Products).
    const activityLastPage = Math.max(1, Math.ceil(activity.length / ACTIVITY_PER_PAGE));
    const activitySafePage = Math.min(activityPage, activityLastPage);
    const pagedActivity = useMemo(
        () => activity.slice((activitySafePage - 1) * ACTIVITY_PER_PAGE, activitySafePage * ACTIVITY_PER_PAGE),
        [activity, activitySafePage],
    );

    // Keep the activity page in range when the feed shrinks.
    useEffect(() => {
        setActivityPage(1);
    }, [activity.length]);

    const load = useCallback(async (silent = false) => {
        const id = ++requestId.current;
        if (!silent) {
            setLoading(true);
        }
        setError(null);

        try {
            const [response, feed] = await Promise.all([
                get('/api/dashboard/transactions', {
                    page,
                    per_page: PER_PAGE,
                    ...filters,
                }),
                // Transactions owns the transactions_exported slice of the
                // audit feed; a feed failure must never blank the table.
                get('/api/activity/recent', {
                    limit: 12,
                    actions: 'transactions_exported',
                }).catch(() => null),
            ]);

            if (id !== requestId.current) return;
            setData(response);
            setActivity(Array.isArray(feed) ? feed : []);
        } catch (err) {
            if (id !== requestId.current) return;
            setError(err.message);
        } finally {
            if (id === requestId.current) setLoading(false);
        }
    }, [filters, page]);

    const refresh = useCallback(() => load(true), [load]);

    useEffect(() => {
        load();
    }, [load]);

    // Options dropdowns (kebabs) on Transaction List and Recent Activity card
    // headers: close on outside click and Escape, matching the other admin pages.
    useEffect(() => {
        if (!activityOptionsOpen && !txnOptionsOpen) {
            return undefined;
        }

        const onDown = (event) => {
            if (activityOptionsRef.current && !activityOptionsRef.current.contains(event.target)) {
                setActivityOptionsOpen(false);
            }
            if (txnOptionsRef.current && !txnOptionsRef.current.contains(event.target)) {
                setTxnOptionsOpen(false);
            }
        };

        const onKey = (event) => {
            if (event.key === 'Escape') {
                setActivityOptionsOpen(false);
                setTxnOptionsOpen(false);
            }
        };

        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [activityOptionsOpen, txnOptionsOpen]);

    // Export CSV with real feedback (same convention as the other pages):
    // loading state on the button, success/error toast, then a silent refresh
    // of the Recent Activity feed so the new export row appears immediately —
    // no page reload, no skeleton phase, no scroll jump.
    async function handleExport() {
        if (exporting) return;
        setExporting(true);

        try {
            await exportFile('/api/dashboard/transactions/export', filters);
            setToast({ tone: 'success', text: 'Transactions exported successfully.' });

            // Silent feed refresh — never flips the page-level loading flag.
            const feed = await get('/api/activity/recent', {
                limit: 12,
                actions: 'transactions_exported',
            }).catch(() => null);

            if (Array.isArray(feed)) {
                setActivity(feed);
                setActivityPage(1);
            }
        } catch (err) {
            setToast({
                tone: 'error',
                text: validationMessages(err)[0] ?? err.message ?? 'Could not export transactions.',
            });
        } finally {
            setExporting(false);
        }
    }

    async function handleClearActivity() {
        setClearing(true);
        setClearError(null);

        try {
            const response = await del('/api/activity/recent?actions=transactions_exported');

            setClearConfirm(false);
            setActivity([]);
            setActivityPage(1);
            setToast({ tone: 'success', text: response?.message ?? 'Activity list cleared.' });
        } catch (err) {
            setClearError(validationMessages(err)[0] ?? err.message ?? 'Could not clear the activity list.');
        } finally {
            setClearing(false);
        }
    }

    async function handleSeedTransactions() {
        if (seeding) return;
        setSeeding(true);

        try {
            const response = await post('/api/dashboard/transactions/seed');
            setToast({ tone: 'success', text: response?.message ?? '7 sample transactions added.' });
            await refresh();
        } catch (err) {
            setToast({
                tone: 'error',
                text: validationMessages(err)[0] ?? err.message ?? 'Could not add the sample transactions.',
            });
        } finally {
            setSeeding(false);
        }
    }

    async function handleDeleteAllConfirm() {
        if (deletingAll) return;
        setDeletingAll(true);
        setDeleteAllError(null);

        try {
            const response = await del('/api/dashboard/transactions');
            setDeleteAllConfirm(false);
            setToast({ tone: 'success', text: response?.message ?? 'All transactions deleted.' });
            await refresh();
        } catch (err) {
            setDeleteAllError(validationMessages(err)[0] ?? err.message ?? 'Could not delete transactions.');
        } finally {
            setDeletingAll(false);
        }
    }

    // Debounce the search box so typing doesn't fire a request per keystroke.
    useEffect(() => {
        const term = searchInput.trim();

        if (term === filters.search) {
            return undefined;
        }

        const timer = setTimeout(() => {
            setFilters((current) => (current.search === term ? current : { ...current, search: term }));
            setPage(1);
        }, 300);

        return () => clearTimeout(timer);
    }, [searchInput, filters.search]);

    function setFilter(name, value) {
        setFilters((current) => ({ ...current, [name]: value }));
        setPage(1);
    }

    function clearFilters() {
        setSearchInput('');
        setFilters({ search: '', cashier_id: '', channel: '', date_from: '', date_to: '' });
        setPage(1);
    }

    /** Column sort: 1st click asc → 2nd desc → 3rd back to the default order. */
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

    function sortArrow(field) {
        if (sortBy !== field) return '';
        return sortDir === 'asc' ? ' ▲' : ' ▼';
    }

    return (
        <div className="page">
            <section className="page-hero">
                <header className="page-head">
                    <div>
                        <h2>Transactions</h2>
                        <p>Review completed sales and their cashiers.</p>
                    </div>
                </header>
            </section>

            {loading ? (
                /* Loading phase: the hero above stays put (its loadbar is the
                   progress UI) while these sections paint skeletons mirroring
                   the real page — no spinners anywhere. */
                <div className="page-loading-phase" aria-busy="true" aria-live="polite">
                    {/* Export Sales Records panel */}
                    <section className="card panel panel-add">
                        <div className="panel-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                            <span className="skeleton" style={{ width: 118, height: 38, borderRadius: 8 }} />
                        </div>
                    </section>

                    {/* Search & Filter Options */}
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                        </div>
                        <div className="filter-shell">
                            <div className="filter-grid">
                                {[0, 1, 2, 3].map((i) => (
                                    <div key={i} className="field">
                                        <span className="skeleton skeleton-sm" />
                                        <span className="skeleton" style={{ height: 40 }} />
                                    </div>
                                ))}
                                <div className="field">
                                    <span className="sr-only">Clear filters</span>
                                    <span className="skeleton" style={{ height: 40 }} />
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Transaction List table + pagination */}
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                        </div>
                        <div className="table-shell">
                            <table className="table" aria-hidden="true">
                                <thead>
                                    <tr>
                                        {[0, 1, 2, 3, 4, 5, 6].map((i) => (
                                            <th key={i} className={i === 3 || i === 4 || i === 6 ? 'text-right' : undefined}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4, 5, 6].map((c) => (
                                                <td key={c} className={c === 3 || c === 4 || c === 6 ? 'text-right' : undefined}>
                                                    <span className="skeleton skeleton-line" style={{ width: c === 6 ? '62%' : '85%' }} />
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
                            <span className="skeleton" style={{ width: 38, height: 38, borderRadius: 9 }} />
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
                    </section>
                </div>
            ) : (
            <>
            <section className="card panel panel-add">
                <div className="panel-head">
                    <div>
                        <h3>Export Sales Records</h3>
                        <p className="muted">Download the current transaction history as a CSV file.</p>
                    </div>
                    <button
                        type="button"
                        className="btn btn-primary"
                        onClick={handleExport}
                        disabled={exporting}
                    >
                        {exporting ? 'Exporting…' : 'Export CSV'}
                    </button>
                </div>
            </section>

            <section className="card padded">
                <div className="filter-head">
                    <div>
                        <h3>Search &amp; Filter Options</h3>
                        <p className="muted">Narrow transactions by receipt, product, cashier or date.</p>
                    </div>
                </div>
                <div className="filter-shell">
                    <div className="filter-grid">
                        <label className="field">
                            <span>Search</span>
                            <input
                                type="search"
                                placeholder="Receipt, cashier, product…"
                                value={searchInput}
                                onChange={(event) => setSearchInput(event.target.value)}
                            />
                        </label>
                        <label className="field">
                            <span>Cashier</span>
                            <select
                                className="input"
                                value={filters.cashier_id}
                                onChange={(event) => setFilter('cashier_id', event.target.value)}
                            >
                                <option value="">All cashiers</option>
                                {(data?.cashiers ?? []).map((cashier) => (
                                    <option key={cashier.id} value={cashier.id}>
                                        {cashier.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="field">
                            <span>Transaction Type</span>
                            <select
                                className="input"
                                value={filters.channel}
                                onChange={(event) => setFilter('channel', event.target.value)}
                            >
                                <option value="">All types</option>
                                <option value="admin">Admin POS</option>
                                <option value="cashier">Cashier POS</option>
                            </select>
                        </label>
                        <label className="field">
                            <span>Date</span>
                            <input
                                type="date"
                                value={filters.date_from}
                                onChange={(event) => setFilters((current) => ({ ...current, date_from: event.target.value, date_to: event.target.value }))}
                            />
                        </label>
                        <div className="field">
                            <span className="sr-only">Clear filters</span>
                            <button
                                type="button"
                                className="btn btn-ghost"
                                onClick={clearFilters}
                                disabled={!searchInput && !filters.cashier_id && !filters.channel && !filters.date_from && !filters.date_to}
                            >
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            {error && <Alert tone="error">{error}</Alert>}
            {toast && <Toast tone={toast.tone} onClose={() => setToast(null)}>{toast.text}</Toast>}

            <section className="card padded">
                <div className="filter-head">
                    <div>
                        <h3>Transaction List</h3>
                        <p className="muted">Overview of every completed sale and the items on each receipt.</p>
                    </div>
                    <div className="activity-options" ref={txnOptionsRef}>
                        <button
                            type="button"
                            className="icon-btn is-outlined"
                            onClick={() => setTxnOptionsOpen((open) => !open)}
                            aria-haspopup="menu"
                            aria-expanded={txnOptionsOpen}
                            aria-label="Transaction list options"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                                <circle cx="12" cy="5" r="1.7" />
                                <circle cx="12" cy="12" r="1.7" />
                                <circle cx="12" cy="19" r="1.7" />
                            </svg>
                        </button>
                        {txnOptionsOpen && (
                            <div className="activity-options-panel" role="menu" aria-label="Transaction list options">
                                <button
                                    type="button"
                                    className="activity-options-item"
                                    role="menuitem"
                                    disabled={seeding || deletingAll}
                                    onClick={() => {
                                        setTxnOptionsOpen(false);
                                        handleSeedTransactions();
                                    }}
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="M6 3h12a1 1 0 0 1 1 1v17l-3-2-2 2-2-2-2 2-2-2-3 2V4a1 1 0 0 1 1-1z" />
                                        <path d="M9 8h6" />
                                        <path d="M9 12h6" />
                                    </svg>
                                    <span>Add 7 sample transactions</span>
                                </button>
                                <button
                                    type="button"
                                    className="activity-options-item activity-options-danger"
                                    role="menuitem"
                                    disabled={seeding || deletingAll}
                                    onClick={() => {
                                        setTxnOptionsOpen(false);

                                        if ((data?.total ?? sortedRows.length) === 0) {
                                            setEmptyNotice({
                                                title: 'No transactions to delete',
                                                description: 'The transaction list is already empty. Complete a sale or add the 7 sample transactions first.',
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
                                    <span>Delete all transactions</span>
                                </button>
                            </div>
                        )}
                    </div>
                </div>

                <div className="table-shell">
                    {!loading && !error && (
                        <div className="table-wrap">
                            <table className="table">
                                <thead>
                                    <tr>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('receipt')}
                                        >
                                            Receipt
                                            <span className={`sort-indicator${sortBy === 'receipt' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'receipt' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('channel')}
                                        >
                                            Type
                                            <span className={`sort-indicator${sortBy === 'channel' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'channel' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('cashier')}
                                        >
                                            Name
                                            <span className={`sort-indicator${sortBy === 'cashier' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'cashier' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header text-right"
                                            onClick={() => toggleSort('items')}
                                        >
                                            Items
                                            <span className={`sort-indicator${sortBy === 'items' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'items' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header text-right"
                                            onClick={() => toggleSort('total')}
                                        >
                                            Total
                                            <span className={`sort-indicator${sortBy === 'total' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'total' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header txn-date"
                                            onClick={() => toggleSort('date')}
                                        >
                                            Date
                                            <span className={`sort-indicator${sortBy === 'date' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'date' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th className="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sortedRows.map((sale) => (
                                        <tr
                                            key={sale.sale_id}
                                            className="row-clickable"
                                            onClick={() => setViewing(sale)}
                                        >
                                            <td className="mono">{highlight(sale.receipt_no, needle)}</td>
                                            <td>
                                                <span className={`pill ${sale.channel === 'admin' ? 'pill-info' : 'pill-neutral'}`}>
                                                    {sale.channel === 'admin' ? 'Admin POS' : 'Cashier POS'}
                                                </span>
                                            </td>
                                            <td>{highlight(sale.cashier_name, needle)}</td>
                                            <td className="text-right">{sale.items_count}</td>
                                            <td className="text-right">
                                                <strong>{formatPeso(sale.total_amount)}</strong>
                                            </td>
                                            <td className="muted txn-date">{sale.date_relative}</td>
                                            <td className="text-right actions-cell">
                                                <button
                                                    type="button"
                                                    className="btn btn-ghost btn-sm"
                                                    onClick={(event) => {
                                                        event.stopPropagation();
                                                        setViewing(sale);
                                                    }}
                                                >
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {sortedRows.length === 0 && (
                                <div className="table-empty table-empty-solid">
                                    <span className="table-empty-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="M21 21l-4.35-4.35" />
                                            <path d="M11 8v6M8 11h6" />
                                        </svg>
                                    </span>
                                    <p className="table-empty-title">
                                        {filters.search || filters.cashier_id || filters.channel || filters.date_from || filters.date_to
                                            ? 'No results found'
                                            : 'No transactions yet'}
                                    </p>
                                    <p className="table-empty-sub">
                                        {filters.search || filters.cashier_id || filters.channel || filters.date_from || filters.date_to
                                            ? 'Try adjusting your search or filters.'
                                            : 'Complete a sale or add the 7 sample transactions to populate this list.'}
                                    </p>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {!loading && !error && data && (data.total ?? sortedRows.length) > 0 && (
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
                        <span className="page-detail">{Math.min(safePage * PER_PAGE, data.total ?? sortedRows.length)} of {data.total ?? sortedRows.length}</span>
                    </div>
                )}
            </section>

            {/* Recent Activity List — export feed (transactions_exported rows only) */}
            <section className="card padded recent-activity">
                <div className="filter-head">
                    <div>
                        <h3>Recent Activity List</h3>
                        <p className="muted">See the transaction exports you made recently.</p>
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
                                                description: 'The activity list is already empty. Export transactions and its activity will show up here.',
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
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <path d="M7 10l5 5 5-5" />
                                <path d="M12 15V3" />
                            </svg>
                        </span>
                        <p className="table-empty-title">No export activity yet</p>
                        <p className="table-empty-sub">Export the transaction list as CSV and it will show up here.</p>
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
            </>
            )}

            {viewing && (
                <Modal
                    title={(
                        <span className="activity-detail-title">
                            <span className="activity-detail-infoicon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 11v5M12 8h.01" />
                                </svg>
                            </span>
                            Transaction details
                        </span>
                    )}
                    className="modal-activity"
                    ariaLabel="Transaction details"
                    noClose
                    onClose={() => setViewing(null)}
                >
                    <dl className="activity-detail-list">
                        <div className="activity-detail-item">
                            <dt>Receipt</dt>
                            <dd className="mono">{viewing.receipt_no}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Type</dt>
                            <dd>
                                <span className={`pill ${viewing.channel === 'admin' ? 'pill-info' : 'pill-neutral'}`}>
                                    {viewing.channel === 'admin' ? 'Admin POS' : 'Cashier POS'}
                                </span>
                            </dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Name</dt>
                            <dd>{viewing.cashier_name}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>When</dt>
                            <dd>{viewing.date}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Total</dt>
                            <dd><strong>{formatPeso(viewing.total_amount)}</strong></dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Payment</dt>
                            <dd>{formatPeso(viewing.payment_amount)}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Change</dt>
                            <dd>{formatPeso(viewing.change_amount)}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Items</dt>
                            <dd>
                                <ul className="activity-detail-changes">
                                    {viewing.items.map((item, index) => (
                                        <li key={`${item.sku}-${index}`}>
                                            {item.quantity}× {highlight(item.product_name, needle)} @ {formatPeso(item.unit_price)}
                                        </li>
                                    ))}
                                </ul>
                            </dd>
                        </div>
                    </dl>

                    <div className="modal-actions modal-actions-center">
                        <button type="button" className="btn btn-ghost" onClick={() => setViewing(null)}>Close</button>
                    </div>
                </Modal>
            )}

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
                                <dt>File</dt>
                                <dd className="mono">{activityDetail.subject_name}</dd>
                            </div>
                        )}
                        {activityChanges(activityDetail).length > 0 && (
                            <div className="activity-detail-item">
                                <dt>Export made</dt>
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
                    title="Delete all transactions?"
                    description={`This permanently deletes all ${data?.total ?? sortedRows.length} transaction(s) and their line items. This action cannot be undone.`}
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
                    description="This permanently deletes the export activity history from the activity log. Exported CSV files are not affected. This action cannot be undone."
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
        </div>
    );
}
