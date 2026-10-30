import { useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import Alert from '../../components/Feedback.jsx';
import { downloadFile, get, post, validationMessages } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

const TABS = [
    { key: 'active', label: 'Active' },
    { key: 'ordered', label: 'Ordered' },
    { key: 'dismissed', label: 'Dismissed' },
    { key: 'all', label: 'All' },
];

const PER_PAGE = 6;

// Urgency ranking matches the backend default order (critical first).
const URGENCY_RANK = { critical: 0, low: 1, watch: 2 };

// Column → row-field accessors for the client-side table sort.
const SORT_FIELDS = {
    name: (s) => s.name,
    stock: (s) => Number(s.current_stock ?? 0),
    avg_daily: (s) => Number(s.avg_daily ?? 0),
    reorder_point: (s) => Number(s.reorder_point ?? 0),
    suggested_qty: (s) => Number(s.suggested_qty ?? 0),
    est_cost: (s) => Number(s.est_cost ?? 0),
    urgency: (s) => URGENCY_RANK[s.urgency] ?? 99,
    reason: (s) => s.reason ?? '',
};

function urgencyClass(urgency) {
    if (urgency === 'critical') {
        return 'pill-danger';
    }

    if (urgency === 'medium' || urgency === 'high') {
        return 'pill-warn';
    }

    return 'pill-ok';
}

export default function OrderSuggestions() {
    const [status, setStatus] = useState('active');
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [busyId, setBusyId] = useState(null);
    const [forecasting, setForecasting] = useState(false);
    const [message, setMessage] = useState(null);
    const [error, setError] = useState(null);

    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [page, setPage] = useState(1);

    const tableRef = useRef(null);
    const wrapRef = useRef(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            setData(await get('/api/dashboard/order-suggestions', { status }));
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, [status]);

    useEffect(() => {
        load();
    }, [load]);

    async function act(suggestion, action) {
        setBusyId(suggestion.id);
        setMessage(null);
        setError(null);

        try {
            const response = await post(`/api/dashboard/order-suggestions/${suggestion.id}/${action}`);
            setMessage(response.message);
            await load();
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
                `Forecasts recomputed — ${stats.created ?? 0} created, ${stats.updated ?? 0} updated, ` +
                    `${stats.preserved ?? 0} preserved, ${stats.skipped_fallback ?? 0} on static fallback.`,
            );
            setStatus('active');
            await load();
        } catch (err) {
            setError(err.message);
        } finally {
            setForecasting(false);
        }
    }

    const suggestions = data?.suggestions ?? [];

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

    // Reset to the first page whenever the sort or status tab changes
    // (mirrors Stock Inventory).
    useEffect(() => {
        setPage(1);
    }, [sortBy, sortDir, status]);

    const sortedSuggestions = useMemo(() => {
        const key = SORT_FIELDS[sortBy];
        if (!sortBy || !key) {
            return suggestions;
        }

        const dir = sortDir === 'asc' ? 1 : -1;
        return [...suggestions].sort((a, b) => {
            const av = key(a);
            const bv = key(b);

            if (typeof av === 'number' && typeof bv === 'number') {
                return (av - bv) * dir;
            }

            const as = String(av).toLowerCase();
            const bs = String(bv).toLowerCase();
            if (as < bs) return -1 * dir;
            if (as > bs) return 1 * dir;
            return 0;
        });
    }, [suggestions, sortBy, sortDir]);

    const lastPage = Math.max(1, Math.ceil(sortedSuggestions.length / PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const pagedSuggestions = useMemo(
        () => sortedSuggestions.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE),
        [sortedSuggestions, safePage]
    );

    // Reserve exactly one full page (6 rows) of height, mirroring the other
    // admin tables. Measured from the rendered table so it stays accurate
    // across fonts, zoom levels and action-button wrapping.
    useLayoutEffect(() => {
        const table = tableRef.current;
        const wrap = wrapRef.current;
        if (!table || !wrap) {
            return;
        }

        const head = table.querySelector('thead');
        const row = table.querySelector('tbody tr');

        // Nothing to measure (empty list / loading): let the wrap size itself.
        if (!head || !row) {
            wrap.style.minHeight = '';
            return;
        }

        wrap.style.minHeight = `${head.getBoundingClientRect().height + row.getBoundingClientRect().height * PER_PAGE}px`;
    }, [pagedSuggestions.length, loading]);

    return (
        <div className="page">
            <section className={`page-hero${loading ? ' is-loading' : ''}`}>
                <div className="page-loadbar" aria-hidden="true" />
                <header className="page-head">
                    <div>
                        <h2>Order Suggestions</h2>
                        <p>Manage demand-based restock suggestions.</p>
                    </div>
                    </header>
            </section>

            {loading ? (
                /* Loading phase: the hero above stays put (its loadbar is the
                   progress UI) while these sections paint skeletons mirroring
                   the real page — no spinners anywhere. */
                <div className="page-loading-phase" aria-busy="true" aria-live="polite">
                    {/* Export Suggestions panel */}
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
                                {[0, 1, 2].map((i) => (
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

                    {/* Status tabs */}
                    <section className="card padded tabs-card">
                        <div className="mode-tabs" aria-hidden="true">
                            {[0, 1, 2].map((i) => (
                                <span key={i} className="skeleton" style={{ width: 86, height: 32, borderRadius: 8 }} />
                            ))}
                        </div>
                    </section>

                    {/* Order Suggestions table + pagination */}
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
                                        {[0, 1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
                                            <th key={i} className={i >= 1 && i <= 5 || i === 8 ? 'text-right' : undefined}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4, 5, 6, 7, 8].map((c) => (
                                                <td key={c} className={c >= 1 && c <= 5 || c === 8 ? 'text-right' : undefined}>
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
                </div>
            ) : (
            <>
            <section className="card panel panel-add">
                <header className="panel-head">
                    <div>
                        <h3>Export Suggestions</h3>
                        <p className="muted">Download the current order suggestions as a CSV file.</p>
                    </div>
                    <button type="button" className="btn btn-primary" onClick={() => downloadFile('/api/dashboard/order-suggestions/export', { status })}>
                        Export CSV
                    </button>
                </header>
            </section>

            <form className="order-suggestions-form" onSubmit={(e) => e.preventDefault()}>
                <section className="card padded">
                    <div className="filter-head">
                        <div>
                            <h3>Search &amp; Filter Options</h3>
                            <p className="muted">Narrow order suggestions by product name or SKU and status.</p>
                        </div>
                    </div>
                    <div className="filter-shell">
                        <div className="filter-grid">
                            <label className="field">
                                <span>Search</span>
                                <input
                                    type="search"
                                    placeholder="Search product name or SKU…"
                                    value=""
                                    onChange={() => {}}
                                />
                            </label>
                            <label className="field">
                                <span>Urgency</span>
                                <select className="input" value="" onChange={() => {}}>
                                    <option value="">All urgencies</option>
                                    <option value="critical">Critical</option>
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                </select>
                            </label>
                            <label className="field">
                                <span>Status</span>
                                <select className="input" value="" onChange={() => {}}>
                                    <option value="">All statuses</option>
                                    <option value="active">Active</option>
                                    <option value="ordered">Ordered</option>
                                    <option value="dismissed">Dismissed</option>
                                </select>
                            </label>
                            <div className="field">
                                <span className="sr-only">Clear filters</span>
                                <button type="button" className="btn btn-ghost" disabled>
                                    Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="card padded tabs-card">
                    <div className="mode-tabs" role="tablist">
                        {TABS.map((tab) => (
                            <button
                                key={tab.key}
                                type="button"
                                className={`mode-tab${status === tab.key ? ' is-active' : ''}`}
                                onClick={() => setStatus(tab.key)}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>
                </section>

                {message && <Alert tone="success" onClose={() => setMessage(null)}>{message}</Alert>}
                {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}

                <section className="card padded">
                    <div className="filter-head">
                        <div>
                            <h3>Order Suggestions</h3>
                            <p className="muted">Suggested restocks with demand, urgency and estimated cost.</p>
                        </div>
                        <button type="button" className="btn btn-primary" onClick={runForecast} disabled={forecasting}>
                            {forecasting ? 'Computing…' : 'Run forecast'}
                        </button>
                    </div>
                    {!loading && !error && (
                        <div className="table-shell">
                            <div className="table-wrap data-rows-6" ref={wrapRef}>
                                <table className="table" ref={tableRef}>
                                    <thead>
                                        <tr>
                                            <th className="sort-header" onClick={() => toggleSort('name')}>
                                                Product{sortBy === 'name' && sortDir === 'asc' ? ' ▲' : sortBy === 'name' ? ' ▼' : ''}
                                            </th>
                                            <th className="sort-header text-right" onClick={() => toggleSort('stock')}>
                                                Stock{sortBy === 'stock' && sortDir === 'asc' ? ' ▲' : sortBy === 'stock' ? ' ▼' : ''}
                                            </th>
                                            <th className="sort-header text-right" onClick={() => toggleSort('avg_daily')}>
                                                Avg daily{sortBy === 'avg_daily' && sortDir === 'asc' ? ' ▲' : sortBy === 'avg_daily' ? ' ▼' : ''}
                                            </th>
                                            <th className="sort-header text-right" onClick={() => toggleSort('reorder_point')}>
                                                Reorder pt{sortBy === 'reorder_point' && sortDir === 'asc' ? ' ▲' : sortBy === 'reorder_point' ? ' ▼' : ''}
                                            </th>
                                            <th className="sort-header text-right" onClick={() => toggleSort('suggested_qty')}>
                                                Suggested{sortBy === 'suggested_qty' && sortDir === 'asc' ? ' ▲' : sortBy === 'suggested_qty' ? ' ▼' : ''}
                                            </th>
                                            <th className="sort-header text-right" onClick={() => toggleSort('est_cost')}>
                                                Est. cost{sortBy === 'est_cost' && sortDir === 'asc' ? ' ▲' : sortBy === 'est_cost' ? ' ▼' : ''}
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('urgency')}>
                                                Urgency{sortBy === 'urgency' && sortDir === 'asc' ? ' ▲' : sortBy === 'urgency' ? ' ▼' : ''}
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('reason')}>
                                                Reason{sortBy === 'reason' && sortDir === 'asc' ? ' ▲' : sortBy === 'reason' ? ' ▼' : ''}
                                            </th>
                                            {status === 'active' && <th className="text-right">Actions</th>}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {pagedSuggestions.map((suggestion) => (
                                            <tr key={suggestion.id}>
                                                <td>
                                                    <strong>{suggestion.name}</strong>
                                                    <small className="muted mono"> {suggestion.sku}</small>
                                                </td>
                                                <td className="text-right">{suggestion.current_stock}</td>
                                                <td className="text-right">{suggestion.avg_daily}</td>
                                                <td className="text-right">{suggestion.reorder_point}</td>
                                                <td className="text-right">
                                                    <strong>{suggestion.suggested_qty}</strong>
                                                </td>
                                                <td className="text-right">{suggestion.est_cost !== null ? formatPeso(suggestion.est_cost) : '—'}</td>
                                                <td>
                                                    <span className={`pill ${urgencyClass(suggestion.urgency)}`}>{suggestion.urgency}</span>
                                                </td>
                                                <td className="muted reason-cell">
                                                    <span className="reason-text" title={suggestion.reason ?? undefined}>
                                                        {suggestion.reason ?? '—'}
                                                    </span>
                                                </td>
                                                {status === 'active' && (
                                                    <td className="text-right actions-cell">
                                                        <button
                                                            type="button"
                                                            className="btn btn-primary btn-sm"
                                                            disabled={busyId === suggestion.id}
                                                            onClick={() => act(suggestion, 'ordered')}
                                                        >
                                                            Ordered
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="btn btn-ghost btn-sm"
                                                            disabled={busyId === suggestion.id}
                                                            onClick={() => act(suggestion, 'dismiss')}
                                                        >
                                                            Dismiss
                                                        </button>
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>

                                {sortedSuggestions.length === 0 && (
                                    <p className="muted padded">
                                        {status === 'active'
                                            ? 'Nothing to reorder — every active product is above its reorder point.'
                                            : 'No suggestions with this status.'}
                                    </p>
                                )}
                            </div>
                        </div>
                    )}

                    {!loading && !error && sortedSuggestions.length > 0 && (
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
                        </div>
                    )}
                </section>
            </form>
            </>
            )}
        </div>
    );
}
