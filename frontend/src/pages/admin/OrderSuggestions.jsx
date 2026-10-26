import { useCallback, useEffect, useState } from 'react';
import Alert, { Loading } from '../../components/Feedback.jsx';
import { downloadFile, get, post, validationMessages } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

const TABS = [
    { key: 'active', label: 'Active' },
    { key: 'ordered', label: 'Ordered' },
    { key: 'dismissed', label: 'Dismissed' },
    { key: 'all', label: 'All' },
];

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

    return (
        <div className="page">
            <header className="page-head">
                <div>
                    <h2>Order Suggestions</h2>
                    <p>
                        Demand-based restocking from the last {data?.formulas?.window_days ?? 30} days
                        (safety {data?.formulas?.safety_days ?? '—'} days, cover {data?.formulas?.cover_days ?? '—'} days).
                    </p>
                </div>
                <div className="head-actions">
                    <button type="button" className="btn btn-ghost" onClick={() => downloadFile('/api/dashboard/order-suggestions/export', { status })}>
                        Export CSV
                    </button>
                    <button type="button" className="btn btn-primary" onClick={runForecast} disabled={forecasting}>
                        {forecasting ? 'Computing…' : 'Run forecast'}
                    </button>
                </div>
            </header>

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

            {message && <Alert tone="success" onClose={() => setMessage(null)}>{message}</Alert>}
            {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}

            <section className="card table-card">
                {loading && <Loading label="Loading suggestions…" />}

                {!loading && !error && (
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th className="text-right">Stock</th>
                                    <th className="text-right">Avg daily</th>
                                    <th className="text-right">Reorder pt</th>
                                    <th className="text-right">Suggested</th>
                                    <th className="text-right">Est. cost</th>
                                    <th>Urgency</th>
                                    <th>Reason</th>
                                    {status === 'active' && <th />}
                                </tr>
                            </thead>
                            <tbody>
                                {suggestions.map((suggestion) => (
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
                                        <td className="muted reason-cell">{suggestion.reason ?? '—'}</td>
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

                        {suggestions.length === 0 && (
                            <p className="muted padded">
                                {status === 'active'
                                    ? 'Nothing to reorder — every active product is above its reorder point.'
                                    : 'No suggestions with this status.'}
                            </p>
                        )}
                    </div>
                )}
            </section>
        </div>
    );
}
