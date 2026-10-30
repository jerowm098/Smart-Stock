import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import Alert from '../../components/Feedback.jsx';
import Modal from '../../components/Modal.jsx';
import { downloadFile, get } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

const PER_PAGE = 6;

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

export default function Transactions() {
    const [filters, setFilters] = useState({ search: '', cashier_id: '', channel: '', date_from: '', date_to: '' });
    const [searchInput, setSearchInput] = useState('');
    const [page, setPage] = useState(1);
    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [viewing, setViewing] = useState(null);

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

    const load = useCallback(async () => {
        const id = ++requestId.current;
        setLoading(true);
        setError(null);

        try {
            const response = await get('/api/dashboard/transactions', {
                page,
                per_page: PER_PAGE,
                ...filters,
            });

            if (id !== requestId.current) return;
            setData(response);
        } catch (err) {
            if (id !== requestId.current) return;
            setError(err.message);
        } finally {
            if (id === requestId.current) setLoading(false);
        }
    }, [filters, page]);

    useEffect(() => {
        load();
    }, [load]);

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
            <section className={`page-hero${loading ? ' is-loading' : ''}`}>
                <div className="page-loadbar" aria-hidden="true" />
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
                        onClick={() => downloadFile('/api/dashboard/transactions/export', filters)}
                    >
                        Export CSV
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

            <section className="card padded">
                <div className="filter-head">
                    <div>
                        <h3>Transaction List</h3>
                        <p className="muted">Overview of every completed sale and the items on each receipt.</p>
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
                                            Receipt{sortArrow('receipt')}
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('channel')}
                                        >
                                            Type{sortArrow('channel')}
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('cashier')}
                                        >
                                            Name{sortArrow('cashier')}
                                        </th>
                                        <th
                                            className="sort-header text-right"
                                            onClick={() => toggleSort('items')}
                                        >
                                            Items{sortArrow('items')}
                                        </th>
                                        <th
                                            className="sort-header text-right"
                                            onClick={() => toggleSort('total')}
                                        >
                                            Total{sortArrow('total')}
                                        </th>
                                        <th
                                            className="sort-header txn-date"
                                            onClick={() => toggleSort('date')}
                                        >
                                            Date{sortArrow('date')}
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
                                <div className="table-empty">
                                    <span className="table-empty-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="M21 21l-4.35-4.35" />
                                            <path d="M11 8v6M8 11h6" />
                                        </svg>
                                    </span>
                                    <p className="table-empty-title">No results found</p>
                                    <p className="table-empty-sub">Try adjusting your search or filters.</p>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {!loading && !error && data && (data.last_page ?? 0) > 1 && (
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
        </div>
    );
}
