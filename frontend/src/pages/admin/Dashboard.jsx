import { useCallback, useEffect, useMemo, useState } from 'react';
import { usePageLoadbar } from '../../components/PageLoadbar.jsx';
import { Link } from 'react-router-dom';
import { get } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

// Restock Suggestions pager: 5 rows per page (same compact chevron pager as
// the Products / Transactions / Stock Inventory tables).
const SUGGESTIONS_PER_PAGE = 5;

const URGENCY_RANK = { critical: 0, low: 1 };

const SORT_FIELDS = {
    name: (s) => s.name,
    sku: (s) => s.sku,
    stock: (s) => Number(s.current_stock ?? 0),
    suggested_qty: (s) => Number(s.suggested_qty ?? 0),
    urgency: (s) => URGENCY_RANK[s.urgency] ?? 99,
};

export default function Dashboard() {
    const [stats, setStats] = useState(null);
    const [suggestions, setSuggestions] = useState([]);
    const [loading, setLoading] = useState(true);
    usePageLoadbar(loading);
    const [error, setError] = useState(null);
    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [page, setPage] = useState(1);

    const loadStats = useCallback(async () => {
        try {
            setLoading(true);
            const [statsData, suggestData] = await Promise.all([
                get('/api/dashboard/stats'),
                get('/api/dashboard/restock-suggestions', { status: 'active' }),
            ]);
            setStats(statsData);
            setSuggestions(suggestData.suggestions ?? []);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        loadStats();
    }, [loadStats]);

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

    const activeSuggestions = useMemo(
        () => suggestions.filter((s) => s.status === 'active'),
        [suggestions],
    );

    const sortedSuggestions = useMemo(() => {
        if (!sortBy) {
            return activeSuggestions;
        }

        const key = SORT_FIELDS[sortBy];
        if (!key) {
            return activeSuggestions;
        }

        const dir = sortDir === 'asc' ? 1 : -1;
        return [...activeSuggestions].sort((a, b) => {
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
    }, [activeSuggestions, sortBy, sortDir]);

    // Simple client-side pagination over the loaded suggestions (mirrors the
    // Products table). safePage clamps so a shorter list after a reload can
    // never strand the user past the last page.
    const lastPage = Math.max(1, Math.ceil(sortedSuggestions.length / SUGGESTIONS_PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const pagedSuggestions = sortedSuggestions.slice(
        (safePage - 1) * SUGGESTIONS_PER_PAGE,
        safePage * SUGGESTIONS_PER_PAGE,
    );

    // Reset to the first page whenever fresh suggestions arrive or sort changes.
    useEffect(() => {
        setPage(1);
    }, [suggestions, sortBy, sortDir]);

    return (
        <section className="dashboard">
            {/* Permanent hero header (matches the Stock Inventory page): the
                hero stays put while the body loads and carries its own load
                bar — it is NOT part of the skeleton phase below. */}
            <section className="page-hero">
                <header className="page-head">
                    <div>
                        <h2>Dashboard</h2>
                        <p>Centralized overview of stock inventory and sales metrics.</p>
                    </div>
                </header>
            </section>

            {loading && (
                /* Loading phase: the hero above stays put (its loadbar is the
                   progress UI) while the body paints skeletons mirroring the
                   real page — 4 stat cards + the Restock Suggestions table.
                   No spinners anywhere. */
                <>
                    <div className="stat-grid">
                        {[0, 1, 2, 3].map((i) => (
                            <article key={i} className="card stat skeleton-card">
                                <span className="skeleton skeleton-sm" />
                                <span className="skeleton skeleton-lg" />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </article>
                        ))}
                    </div>

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
                                        {[0, 1, 2, 3, 4].map((i) => (
                                            <th key={i}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4].map((c) => (
                                                <td key={c}>
                                                    <span className="skeleton skeleton-line" style={{ width: c === 4 ? '62%' : '85%' }} />
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
                </>
            )}

            {error && (
                <section className="card padded">
                    <div className="alert alert-error" role="alert">
                        <span>{error}</span>
                    </div>
                </section>
            )}

            {!loading && !error && (
                <>
                    {/* 4 stat cards */}
                    <div className="stat-grid">
                        <article className="card stat">
                            <span>Total Inventory Value</span>
                            <strong>{formatPeso(stats.total_revenue ?? 0)}</strong>
                            <small className="stat-hint">Total revenue from all sales.</small>
                        </article>
                        <article className="card stat">
                            <span>Low Stock Items</span>
                            <strong className={stats.low_stock_count > 0 ? 'text-warn' : ''}>
                                {stats.low_stock_count ?? 0}
                            </strong>
                            <small className="stat-hint">At or below reorder threshold.</small>
                        </article>
                        <article className="card stat">
                            <span>Recent Transactions</span>
                            <strong>{stats.total_sales ?? 0}</strong>
                            <small className="stat-hint">Completed sales transactions.</small>
                        </article>
                        <article className="card stat">
                            <span>Restock Suggestions</span>
                            <strong className={activeSuggestions.length > 0 ? 'text-warn' : ''}>
                                {activeSuggestions.length}
                            </strong>
                            <small className="stat-hint">Items due for restocking.</small>
                        </article>
                    </div>

                    {/* Restock suggestions widget */}
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <h3>Restock Suggestions</h3>
                                <p className="muted">Demand-based restocking recommendations.</p>
                            </div>
                            {/* View-all shortcut: icon-only northeast arrow pinned to
                                the header's right corner (filter-head is
                                space-between). Replaces the inline "View all" text. */}
                            <Link
                                to="/order-suggestions"
                                className="icon-btn is-outlined"
                                aria-label="View all restock suggestions"
                                title="View all"
                            >
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                    <path d="M7 17L17 7" />
                                    <path d="M7 7h10v10" />
                                </svg>
                            </Link>
                        </div>
                        <div className="table-shell">
                            <div className="table-wrap">
                                <table className="table">
                                    <thead>
                                        <tr>
                                            <th className="sort-header" onClick={() => toggleSort('name')}>
                                                Product
                                                <span className={`sort-indicator${sortBy === 'name' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'name' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('sku')}>
                                                SKU
                                                <span className={`sort-indicator${sortBy === 'sku' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'sku' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('stock')}>
                                                Current Stock
                                                <span className={`sort-indicator${sortBy === 'stock' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'stock' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('suggested_qty')}>
                                                Suggested Qty
                                                <span className={`sort-indicator${sortBy === 'suggested_qty' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'suggested_qty' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('urgency')}>
                                                Urgency
                                                <span className={`sort-indicator${sortBy === 'urgency' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'urgency' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {pagedSuggestions.map((s) => (
                                            <tr key={s.id}>
                                                <td><strong>{s.name}</strong></td>
                                                <td className="mono">{s.sku}</td>
                                                <td>{s.current_stock}</td>
                                                <td>{s.suggested_qty}</td>
                                                <td>
                                                    <span
                                                        className={`urgency-badge ${
                                                            s.urgency === 'critical' ? 'is-critical' : 'is-low'
                                                        }`}
                                                    >
                                                        {s.urgency}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>

                                {activeSuggestions.length === 0 && (
                                    <div className="table-empty table-empty-solid">
                                        <span className="table-empty-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                                <path d="M9 12h6m-6 3.75h6M9 4.5h6M6.75 6.75h10.5a1.5 1.5 0 011.5 1.5v10.5a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5z" />
                                            </svg>
                                        </span>
                                        <p className="table-empty-title">No restock suggestions yet</p>
                                        <p className="table-empty-sub">All products are above their reorder threshold right now.</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {activeSuggestions.length > 0 && (
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
                                <span className="page-detail">
                                    {Math.min(safePage * SUGGESTIONS_PER_PAGE, activeSuggestions.length)} of {activeSuggestions.length}
                                </span>
                            </div>
                        )}
                    </section>
                </>
            )}
        </section>
    );
}
