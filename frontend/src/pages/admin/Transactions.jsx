import { Fragment, useCallback, useEffect, useState } from 'react';
import Alert, { Loading } from '../../components/Feedback.jsx';
import Pagination from '../../components/Pagination.jsx';
import { downloadFile, get, validationMessages } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

const PER_PAGE = 15;

export default function Transactions() {
    const [filters, setFilters] = useState({ search: '', cashier_id: '', date_from: '', date_to: '' });
    const [page, setPage] = useState(1);
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [expanded, setExpanded] = useState(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await get('/api/dashboard/transactions', {
                page,
                per_page: PER_PAGE,
                ...filters,
            });

            setData(response);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, [filters, page]);

    useEffect(() => {
        load();
    }, [load]);

    function setFilter(name, value) {
        setFilters((current) => ({ ...current, [name]: value }));
        setPage(1);
    }

    function clearFilters() {
        setFilters({ search: '', cashier_id: '', date_from: '', date_to: '' });
        setPage(1);
    }

    return (
        <div className="page">
            <header className="page-head">
                <div>
                    <h2>Transactions</h2>
                    <p>Every completed sale with the staff member who processed it.</p>
                </div>
                <button
                    type="button"
                    className="btn btn-ghost"
                    onClick={() => downloadFile('/api/dashboard/transactions/export', filters)}
                >
                    Export CSV
                </button>
            </header>

            <section className="card padded">
                <div className="filter-grid">
                    <label className="field">
                        <span>Search</span>
                        <input
                            type="search"
                            placeholder="Receipt no, product or SKU…"
                            value={filters.search}
                            onChange={(event) => setFilter('search', event.target.value)}
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
                        <span>From</span>
                        <input type="date" value={filters.date_from} onChange={(event) => setFilter('date_from', event.target.value)} />
                    </label>
                    <label className="field">
                        <span>To</span>
                        <input type="date" value={filters.date_to} onChange={(event) => setFilter('date_to', event.target.value)} />
                    </label>
                    <div className="field">
                        <span>&nbsp;</span>
                        <button type="button" className="btn btn-ghost" onClick={clearFilters}>
                            Clear
                        </button>
                    </div>
                </div>
            </section>

            {error && <Alert tone="error">{error}</Alert>}

            <section className="card table-card">
                {loading && <Loading label="Loading transactions…" />}

                {!loading && !error && (
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Receipt</th>
                                    <th>Date</th>
                                    <th>Cashier</th>
                                    <th className="text-right">Items</th>
                                    <th className="text-right">Total</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {(data?.data ?? []).map((sale) => (
                                    <Fragment key={sale.sale_id}>
                                        <tr
                                            className="row-clickable"
                                            onClick={() => setExpanded(expanded === sale.sale_id ? null : sale.sale_id)}
                                        >
                                            <td className="mono">{sale.receipt_no}</td>
                                            <td>
                                                {sale.date}
                                                <small className="muted"> · {sale.date_relative}</small>
                                            </td>
                                            <td>{sale.cashier_name}</td>
                                            <td className="text-right">{sale.items_count}</td>
                                            <td className="text-right">
                                                <strong>{formatPeso(sale.total_amount)}</strong>
                                            </td>
                                            <td className="text-right muted">{expanded === sale.sale_id ? '▲' : '▼'}</td>
                                        </tr>
                                        {expanded === sale.sale_id && (
                                            <tr className="details-row">
                                                <td colSpan={6}>
                                                    <div className="details-inner">
                                                        <p className="muted details-meta">
                                                            Payment {formatPeso(sale.payment_amount)} · Change{' '}
                                                            {formatPeso(sale.change_amount)} · {sale.lines_count} line(s)
                                                        </p>
                                                        <table className="table table-plain">
                                                            <thead>
                                                                <tr>
                                                                    <th>Item</th>
                                                                    <th>SKU</th>
                                                                    <th className="text-right">Qty</th>
                                                                    <th className="text-right">Unit price</th>
                                                                    <th className="text-right">Line total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {sale.items.map((item, index) => (
                                                                    <tr key={index}>
                                                                        <td>{item.product_name}</td>
                                                                        <td className="mono">{item.sku}</td>
                                                                        <td className="text-right">{item.quantity}</td>
                                                                        <td className="text-right">{formatPeso(item.unit_price)}</td>
                                                                        <td className="text-right">{formatPeso(item.line_total)}</td>
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </Fragment>
                                ))}
                            </tbody>
                        </table>

                        {(data?.data ?? []).length === 0 && (
                            <p className="muted padded">No transactions match the filters.</p>
                        )}
                    </div>
                )}

                {!loading && !error && data && (
                    <Pagination
                        page={data.current_page}
                        lastPage={data.last_page}
                        total={data.total}
                        perPage={data.per_page}
                        onPage={setPage}
                    />
                )}
            </section>
        </div>
    );
}
