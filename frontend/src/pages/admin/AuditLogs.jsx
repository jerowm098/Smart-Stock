import { useCallback, useEffect, useRef, useState } from 'react';
import { usePageLoadbar } from '../../components/PageLoadbar.jsx';
import Modal from '../../components/Modal.jsx';
import { get } from '../../lib/api.js';

const PER_PAGE = 6;

const MODULES = [
    { value: '', label: 'All modules' },
    { value: 'product', label: 'Products' },
    { value: 'stock', label: 'Stock Inventory' },
    { value: 'category', label: 'Categories' },
    { value: 'supplier', label: 'Suppliers' },
    { value: 'transaction', label: 'Transactions' },
    { value: 'suggestion', label: 'Order Suggestions' },
    { value: 'user', label: 'Users' },
    { value: 'backup', label: 'Backups' },
];

function activityChanges(item) {
    const explicit = item?.meta?.changes;
    if (Array.isArray(explicit) && explicit.length > 0) {
        return explicit.map((c) => c.text ?? String(c));
    }
    return [];
}

export default function AuditLogs() {
    const [logs, setLogs] = useState([]);
    const [total, setTotal] = useState(0);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loading, setLoading] = useState(true);
    usePageLoadbar(loading);
    const [error, setError] = useState(null);

    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [moduleFilter, setModuleFilter] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [detail, setDetail] = useState(null);

    const reqIdRef = useRef(0);
    const hasLoadedRef = useRef(false);

    // Debounce search input 250ms
    useEffect(() => {
        const timer = setTimeout(() => {
            setSearch(searchInput.trim());
            setPage(1);
        }, 250);
        return () => clearTimeout(timer);
    }, [searchInput]);

    const load = useCallback(async (silent = false) => {
        const reqId = ++reqIdRef.current;
        if (!silent) setLoading(true);
        setError(null);

        try {
            const res = await get('/api/activity/logs', {
                page,
                per_page: PER_PAGE,
                search: search || undefined,
                module: moduleFilter || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            });
            if (reqId !== reqIdRef.current) return;
            setLogs(Array.isArray(res?.logs) ? res.logs : []);
            setTotal(Number(res?.total ?? 0));
            setLastPage(Math.max(1, Number(res?.last_page ?? 1)));
        } catch (err) {
            if (reqId !== reqIdRef.current) return;
            setError(err.message ?? 'Could not load audit logs.');
        } finally {
            if (reqId === reqIdRef.current) {
                hasLoadedRef.current = true;
                setLoading(false);
            }
        }
    }, [page, search, moduleFilter, dateFrom, dateTo]);

    useEffect(() => {
        load(hasLoadedRef.current);
    }, [load]);

    const hasActiveFilters = Boolean(searchInput.trim() || moduleFilter || dateFrom || dateTo);

    function clearFilters() {
        setSearchInput('');
        setSearch('');
        setModuleFilter('');
        setDateFrom('');
        setDateTo('');
        setPage(1);
    }

    return (
        <div className="page">
            <section className="page-hero">
                <header className="page-head">
                    <div>
                        <h2>Audit Logs</h2>
                        <p style={{ maxWidth: 'none' }}>Chronological system audit trail across inventory, sales, suppliers, categories, users, and backups.</p>
                    </div>
                </header>
            </section>

            {loading ? (
                <div className="page-loading-phase" aria-hidden="true">
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                        </div>
                        <div className="filter-shell">
                            <div className="filter-grid">
                                {[0, 1, 2, 3, 4].map((i) => (
                                    <span key={i} className="skeleton" style={{ height: 42 }} />
                                ))}
                            </div>
                        </div>
                    </section>
                    <section className="card padded">
                        <div className="table-shell">
                            <table className="table">
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4].map((c) => (
                                                <td key={c}><span className="skeleton skeleton-line" /></td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            ) : error ? (
                <section className="card padded">
                    <div className="alert alert-error" role="alert"><span>{error}</span></div>
                </section>
            ) : (
                <div className="page-body">
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <h3>Search &amp; Filter Options</h3>
                                <p className="muted">Filter audit entries by keyword, target module, or date range.</p>
                            </div>
                        </div>
                        <div className="filter-shell">
                            <div className="filter-grid">
                                <label className="field">
                                    <span>Search</span>
                                    <input
                                        type="search"
                                        value={searchInput}
                                        onChange={(e) => setSearchInput(e.target.value)}
                                        placeholder="Action, user, or item..."
                                    />
                                </label>
                                <label className="field">
                                    <span>Target module</span>
                                    <select
                                        value={moduleFilter}
                                        onChange={(e) => {
                                            setModuleFilter(e.target.value);
                                            setPage(1);
                                        }}
                                    >
                                        {MODULES.map((m) => (
                                            <option key={m.value} value={m.value}>{m.label}</option>
                                        ))}
                                    </select>
                                </label>
                                <label className="field">
                                    <span>From date</span>
                                    <input
                                        type="date"
                                        value={dateFrom}
                                        onChange={(e) => {
                                            setDateFrom(e.target.value);
                                            setPage(1);
                                        }}
                                    />
                                </label>
                                <label className="field">
                                    <span>To date</span>
                                    <input
                                        type="date"
                                        value={dateTo}
                                        onChange={(e) => {
                                            setDateTo(e.target.value);
                                            setPage(1);
                                        }}
                                    />
                                </label>
                                <div className="field">
                                    <span className="sr-only">Reset filters</span>
                                    <button
                                        type="button"
                                        className="btn btn-ghost"
                                        disabled={!hasActiveFilters}
                                        onClick={clearFilters}
                                    >
                                        Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <h3>System Audit Trail</h3>
                                <p className="muted">Every logged create, update, stock movement, and administrative action.</p>
                            </div>
                        </div>
                        <div className="table-shell">
                            <div className="table-wrap">
                                <table className="table">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>User<br />Account</th>
                                            <th>Action<br />Executed</th>
                                            <th>Target<br />Module</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {logs.map((item) => (
                                            <tr key={item.id} className="row-clickable" onClick={() => setDetail(item)}>
                                                <td className="txn-date">{item.timestamp || item.created_at_human}</td>
                                                <td><strong>{item.user_name}</strong></td>
                                                <td>{item.description || item.action_label}</td>
                                                <td><span className="pill pill-info">{item.module}</span></td>
                                                <td className="actions-cell">
                                                    <button
                                                        type="button"
                                                        className="btn btn-ghost btn-sm"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            setDetail(item);
                                                        }}
                                                    >
                                                        View
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>

                                {logs.length === 0 && (
                                    <div className="table-empty table-empty-solid">
                                        <span className="table-empty-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                                <circle cx="11" cy="11" r="8" />
                                                <path d="M21 21l-4.35-4.35" />
                                            </svg>
                                        </span>
                                        <p className="table-empty-title">
                                            {hasActiveFilters ? 'No matching audit entries' : 'No audit logs yet'}
                                        </p>
                                        <p className="table-empty-sub">
                                            {hasActiveFilters
                                                ? 'Try widening your date range or clearing the filters.'
                                                : 'System actions across products, stock, categories, suppliers, and accounts will appear here.'}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {total > 0 && (
                            <div className="pagination">
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm page-btn"
                                    disabled={page <= 1}
                                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                                    aria-label="Previous page"
                                >
                                    <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                        <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                </button>
                                <span className="page-count">{page}</span>
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm page-btn"
                                    disabled={page >= lastPage}
                                    onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                                    aria-label="Next page"
                                >
                                    <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                        <path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                </button>
                                <span className="page-detail">
                                    {Math.min(page * PER_PAGE, total)} of {total}
                                </span>
                            </div>
                        )}
                    </section>
                </div>
            )}

            {detail && (
                <Modal
                    open={Boolean(detail)}
                    onClose={() => setDetail(null)}
                    title={
                        <span className="activity-detail-title">
                            <span className="activity-detail-infoicon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 8h.01M11 12h1v4h1" />
                                </svg>
                            </span>
                            <span>Audit entry details</span>
                        </span>
                    }
                    className="modal-activity"
                >
                    <dl className="activity-detail-list">
                        <div className="activity-detail-item">
                            <dt>Action</dt>
                            <dd>{detail.action_label}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Module</dt>
                            <dd><span className="pill pill-info">{detail.module}</span></dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>User</dt>
                            <dd>{detail.user_name}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Timestamp</dt>
                            <dd>{detail.timestamp}</dd>
                        </div>
                        {detail.subject_name && (
                            <div className="activity-detail-item">
                                <dt>Target</dt>
                                <dd>{detail.subject_name}</dd>
                            </div>
                        )}
                        <div className="activity-detail-item">
                            <dt>Summary</dt>
                            <dd>{detail.description}</dd>
                        </div>
                        {activityChanges(detail).length > 0 && (
                            <div className="activity-detail-item">
                                <dt>Changes made</dt>
                                <dd>
                                    <ul className="activity-detail-changes">
                                        {activityChanges(detail).map((line, i) => (
                                            <li key={i}>{line}</li>
                                        ))}
                                    </ul>
                                </dd>
                            </div>
                        )}
                    </dl>
                    <div className="modal-actions modal-actions-center">
                        <button type="button" className="btn btn-primary" onClick={() => setDetail(null)}>
                            Close
                        </button>
                    </div>
                </Modal>
            )}
        </div>
    );
}
