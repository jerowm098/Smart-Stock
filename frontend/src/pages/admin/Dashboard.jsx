import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { usePageLoadbar } from '../../components/PageLoadbar.jsx';
import { Link } from 'react-router-dom';
import { Toast } from '../../components/Feedback.jsx';
import { get, post, validationMessages } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';
import { notifyConnectedNav } from '../../lib/navBadges.js';

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
    const [revenueChart, setRevenueChart] = useState({ labels: [], values: [] });
    const [topProducts, setTopProducts] = useState([]);
    const [recentSales, setRecentSales] = useState([]);
    const [recentStockIns, setRecentStockIns] = useState([]);
    const [loading, setLoading] = useState(true);
    usePageLoadbar(loading);
    const [error, setError] = useState(null);
    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [page, setPage] = useState(1);
    const [suggestionsOptionsOpen, setSuggestionsOptionsOpen] = useState(false);
    const suggestionsOptionsRef = useRef(null);
    const [seedingSuggestions, setSeedingSuggestions] = useState(false);
    const [toast, setToast] = useState(null);

    const loadStats = useCallback(async (silent = false) => {
        try {
            if (!silent) setLoading(true);
            const [statsData, suggestData, chartData, topData, salesData, stockInsData] = await Promise.all([
                get('/api/dashboard/stats'),
                get('/api/dashboard/restock-suggestions', { status: 'active' }),
                get('/api/dashboard/revenue-chart').catch(() => ({ labels: [], values: [] })),
                get('/api/dashboard/top-products').catch(() => []),
                get('/api/dashboard/recent-sales').catch(() => []),
                get('/api/dashboard/recent-stockins').catch(() => []),
            ]);
            setStats(statsData);
            setSuggestions(suggestData.suggestions ?? []);
            setRevenueChart({
                labels: Array.isArray(chartData?.labels) ? chartData.labels : [],
                values: Array.isArray(chartData?.values) ? chartData.values.map(Number) : [],
            });
            setTopProducts(Array.isArray(topData) ? topData : []);
            setRecentSales(Array.isArray(salesData) ? salesData : []);
            setRecentStockIns(Array.isArray(stockInsData) ? stockInsData : []);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        loadStats();
    }, [loadStats]);

    useEffect(() => {
        if (!suggestionsOptionsOpen) return;
        const onDown = (event) => {
            if (suggestionsOptionsRef.current && !suggestionsOptionsRef.current.contains(event.target)) {
                setSuggestionsOptionsOpen(false);
            }
        };
        const onKey = (event) => {
            if (event.key === 'Escape') setSuggestionsOptionsOpen(false);
        };
        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [suggestionsOptionsOpen]);

    async function handleSeedSuggestions() {
        if (seedingSuggestions) return;
        setSeedingSuggestions(true);
        try {
            const response = await post('/api/dashboard/order-suggestions/seed');
            setToast({ tone: 'success', text: response?.message ?? '7 sample order suggestions added.' });
            notifyConnectedNav('suggestionAdded', response?.created ?? 7);
            if ((response?.created_products ?? 0) > 0) {
                notifyConnectedNav('productAdded', response.created_products);
            }
            if ((response?.created_sales ?? 0) > 0) {
                notifyConnectedNav('saleCompleted', response.created_sales);
            }
            if ((response?.created_categories ?? 0) > 0) {
                notifyConnectedNav('categoriesSynced', response.created_categories);
            }
            if ((response?.created_suppliers ?? 0) > 0) {
                notifyConnectedNav('suppliersSynced', response.created_suppliers);
            }
            await loadStats(true);
        } catch (err) {
            setToast({
                tone: 'error',
                text: validationMessages(err)[0] ?? err.message ?? 'Could not add sample suggestions.',
            });
        } finally {
            setSeedingSuggestions(false);
        }
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

            {toast && <Toast tone={toast.tone} onClose={() => setToast(null)}>{toast.text}</Toast>}

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

                    {/* SS-190: Revenue Chart & Top-Selling Products */}
                    <div className="dashboard-grid-2">
                        <section className="card padded">
                            <div className="filter-head">
                                <div>
                                    <h3>Revenue Trend (30 Days)</h3>
                                    <p className="muted">Daily sales revenue over the last 30 days.</p>
                                </div>
                                <span className="pill pill-info">
                                    {formatPeso(revenueChart.values.reduce((sum, v) => sum + (Number(v) || 0), 0))}
                                </span>
                            </div>
                            {revenueChart.values.some((v) => v > 0) ? (
                                <div className="revenue-chart-wrap">
                                    {(() => {
                                        const vals = revenueChart.values;
                                        const labels = revenueChart.labels;
                                        const maxVal = Math.max(...vals, 1);
                                        const W = 520;
                                        const H = 180;
                                        const padX = 12;
                                        const padTop = 14;
                                        const padBottom = 26;
                                        const plotW = W - padX * 2;
                                        const plotH = H - padTop - padBottom;
                                        const barStep = plotW / Math.max(vals.length, 1);
                                        const barW = Math.max(4, barStep - 4);
                                        const tickIndices = [0, Math.floor(vals.length / 2), vals.length - 1];
                                        return (
                                            <svg viewBox={`0 0 ${W} ${H}`} className="revenue-chart-svg" role="img" aria-label="30-day revenue bar chart">
                                                {[0, 0.5, 1].map((ratio) => {
                                                    const y = padTop + plotH * (1 - ratio);
                                                    return (
                                                        <line
                                                            key={ratio}
                                                            x1={padX}
                                                            y1={y}
                                                            x2={W - padX}
                                                            y2={y}
                                                            className="revenue-chart-grid"
                                                        />
                                                    );
                                                })}
                                                {vals.map((val, idx) => {
                                                    const h = Math.max(val > 0 ? 4 : 0, (val / maxVal) * plotH);
                                                    const x = padX + idx * barStep + (barStep - barW) / 2;
                                                    const y = padTop + plotH - h;
                                                    return (
                                                        <rect
                                                            key={idx}
                                                            x={x}
                                                            y={y}
                                                            width={barW}
                                                            height={h}
                                                            rx="3"
                                                            className="revenue-chart-bar"
                                                        >
                                                            <title>{`${labels[idx] ?? ''}: ${formatPeso(val)}`}</title>
                                                        </rect>
                                                    );
                                                })}
                                                {tickIndices.map((idx) => (
                                                    labels[idx] ? (
                                                        <text
                                                            key={idx}
                                                            x={padX + idx * barStep + barStep / 2}
                                                            y={H - 6}
                                                            textAnchor={idx === 0 ? 'start' : idx === vals.length - 1 ? 'end' : 'middle'}
                                                            className="revenue-chart-label"
                                                        >
                                                            {labels[idx]}
                                                        </text>
                                                    ) : null
                                                ))}
                                            </svg>
                                        );
                                    })()}
                                </div>
                            ) : (
                                <div className="table-shell">
                                    <div className="table-empty table-empty-solid">
                                        <p className="table-empty-title">No revenue data yet</p>
                                        <p className="table-empty-sub">Completed POS sales over the last 30 days will plot here.</p>
                                    </div>
                                </div>
                            )}
                        </section>

                        <section className="card padded">
                            <div className="filter-head">
                                <div>
                                    <h3>Top-Selling Products</h3>
                                    <p className="muted">Best sellers ranked by total units sold.</p>
                                </div>
                                <Link to="/products" className="icon-btn is-outlined" aria-label="View products" title="View products">
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
                                                <th>Product</th>
                                                <th>SKU</th>
                                                <th>Sold</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {topProducts.map((p) => (
                                                <tr key={p.id}>
                                                    <td><strong>{p.name}</strong></td>
                                                    <td className="mono">{p.sku}</td>
                                                    <td>{p.total_qty}</td>
                                                    <td><strong>{formatPeso(p.total_revenue)}</strong></td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                    {topProducts.length === 0 && (
                                        <div className="table-empty table-empty-solid">
                                            <p className="table-empty-title">No product sales yet</p>
                                            <p className="table-empty-sub">Top-selling hardware items will rank here once sales are recorded.</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </section>
                    </div>

                    {/* Restock suggestions widget */}
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <h3>Restock Suggestions</h3>
                                <p className="muted">Demand-based restocking recommendations.</p>
                            </div>
                            <div className="head-actions">
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
                                <div className="activity-options" ref={suggestionsOptionsRef}>
                                    <button
                                        type="button"
                                        className="icon-btn is-outlined"
                                        onClick={() => setSuggestionsOptionsOpen((open) => !open)}
                                        aria-haspopup="menu"
                                        aria-expanded={suggestionsOptionsOpen}
                                        aria-label="Restock suggestion options"
                                    >
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.7" />
                                            <circle cx="12" cy="12" r="1.7" />
                                            <circle cx="12" cy="19" r="1.7" />
                                        </svg>
                                    </button>
                                    {suggestionsOptionsOpen && (
                                        <div className="activity-options-panel" role="menu" aria-label="Restock suggestion options">
                                            <button
                                                type="button"
                                                className="activity-options-item"
                                                role="menuitem"
                                                disabled={seedingSuggestions}
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
                                        </div>
                                    )}
                                </div>
                            </div>
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
                                                <span className="sort-header-inner">
                                                    <span>Current<br />Stock</span>
                                                    <span className={`sort-indicator${sortBy === 'stock' ? ' is-active' : ''}`} aria-hidden="true">
                                                        {sortBy === 'stock' && sortDir === 'desc' ? '▼' : '▲'}
                                                    </span>
                                                </span>
                                            </th>
                                            <th className="sort-header" onClick={() => toggleSort('suggested_qty')}>
                                                <span className="sort-header-inner">
                                                    <span>Suggested<br />Qty</span>
                                                    <span className={`sort-indicator${sortBy === 'suggested_qty' ? ' is-active' : ''}`} aria-hidden="true">
                                                        {sortBy === 'suggested_qty' && sortDir === 'desc' ? '▼' : '▲'}
                                                    </span>
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

                    {/* SS-191: Recent Sales & Recent Stock-Ins Feeds */}
                    <div className="dashboard-grid-2">
                        <section className="card padded">
                            <div className="filter-head">
                                <div>
                                    <h3>Recent Sales</h3>
                                    <p className="muted">Latest items checked out through POS.</p>
                                </div>
                                <Link to="/transactions" className="icon-btn is-outlined" aria-label="View all transactions" title="View all transactions">
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
                                                <th>Product</th>
                                                <th>Qty</th>
                                                <th>Total</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {recentSales.map((s) => (
                                                <tr key={s.id}>
                                                    <td><strong>{s.product_name}</strong></td>
                                                    <td>{s.quantity}</td>
                                                    <td><strong>{formatPeso(s.line_total)}</strong></td>
                                                    <td className="txn-date">{s.date}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                    {recentSales.length === 0 && (
                                        <div className="table-empty table-empty-solid">
                                            <p className="table-empty-title">No recent sales</p>
                                            <p className="table-empty-sub">Completed POS checkouts will appear here.</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </section>

                        <section className="card padded">
                            <div className="filter-head">
                                <div>
                                    <h3>Recent Stock-Ins</h3>
                                    <p className="muted">Latest incoming stock deliveries logged.</p>
                                </div>
                                <Link to="/stock-in" className="icon-btn is-outlined" aria-label="View stock inventory" title="View stock inventory">
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
                                                <th>Product</th>
                                                <th>Supplier</th>
                                                <th>Qty</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {recentStockIns.map((si) => (
                                                <tr key={si.id}>
                                                    <td><strong>{si.product_name}</strong></td>
                                                    <td>{si.supplier_name}</td>
                                                    <td>+{si.quantity} {si.unit}</td>
                                                    <td className="txn-date">{si.date}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                    {recentStockIns.length === 0 && (
                                        <div className="table-empty table-empty-solid">
                                            <p className="table-empty-title">No recent stock-ins</p>
                                            <p className="table-empty-sub">Logged supplier deliveries will appear here.</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </section>
                    </div>
                </>
            )}
        </section>
    );
}
