import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { get } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

// Restock Suggestions pager: 5 rows per page (same compact chevron pager as
// the Products / Transactions / Stock Inventory tables).
const SUGGESTIONS_PER_PAGE = 5;

export default function Dashboard() {
    const [stats, setStats] = useState(null);
    const [suggestions, setSuggestions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
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

    const activeSuggestions = suggestions.filter((s) => s.status === 'active');

    // Simple client-side pagination over the loaded suggestions (mirrors the
    // Products table). safePage clamps so a shorter list after a reload can
    // never strand the user past the last page.
    const lastPage = Math.max(1, Math.ceil(activeSuggestions.length / SUGGESTIONS_PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const pagedSuggestions = activeSuggestions.slice(
        (safePage - 1) * SUGGESTIONS_PER_PAGE,
        safePage * SUGGESTIONS_PER_PAGE,
    );

    // Reset to the first page whenever fresh suggestions arrive.
    useEffect(() => {
        setPage(1);
    }, [suggestions]);

    return (
        <section className="dashboard">
            {/* Permanent hero header (matches the Stock Inventory page): the
                hero stays put while the body loads and carries its own load
                bar — it is NOT part of the skeleton phase below. */}
            <section className={`page-hero${loading ? ' is-loading' : ''}`}>
                <div className="page-loadbar" aria-hidden="true" />
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
                        {activeSuggestions.length === 0 ? (
                            <p className="muted" style={{ textAlign: 'center', padding: '24px 0' }}>
                                No items need restocking right now.
                            </p>
                        ) : (
                            <>
                                <table className="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Current Stock</th>
                                            <th>Suggested Qty</th>
                                            <th>Urgency</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {pagedSuggestions.map((s) => (
                                            <tr key={s.id}>
                                                <td>{s.name}</td>
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
                            </>
                        )}
                    </section>
                </>
            )}
        </section>
    );
}
