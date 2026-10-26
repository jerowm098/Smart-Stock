import { useCallback, useEffect, useState } from 'react';
import Alert, { Loading } from '../../components/Feedback.jsx';
import { get, post, validationMessages } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';
import { FieldError } from '../../components/Feedback.jsx';

const UNITS = [
    'piece', 'box', 'sack', 'bag', 'pack', 'set', 'roll', 'bundle',
    'kilogram', 'gram', 'liter', 'milliliter', 'meter', 'foot', 'gallon',
    'can', 'bottle', 'tube', 'sheet',
];

export default function StockIn() {
    const [products, setProducts] = useState([]);
    const [suppliers, setSuppliers] = useState([]);
    const [stats, setStats] = useState(null);
    const [recent, setRecent] = useState([]);

    const [mode, setMode] = useState('existing');
    const [form, setForm] = useState({
        product_id: '',
        supplier_id: '',
        quantity_received: '',
        unit_of_measure: '',
        note: '',
        new_product_name: '',
        custom_sku: '',
        custom_category: '',
    });
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState([]);
    const [result, setResult] = useState(null);

    const refresh = useCallback(async () => {
        const [productsRes, suppliersRes, statsRes, recentRes] = await Promise.allSettled([
            get('/api/inventory/products'),
            get('/api/suppliers/active'),
            get('/api/dashboard/stats'),
            get('/api/dashboard/recent-stockins'),
        ]);

        if (productsRes.status === 'fulfilled') {
            setProducts(Array.isArray(productsRes.value) ? productsRes.value : []);
        }
        if (suppliersRes.status === 'fulfilled') {
            setSuppliers(Array.isArray(suppliersRes.value) ? suppliersRes.value : []);
        }
        if (statsRes.status === 'fulfilled') {
            setStats(statsRes.value);
        }
        if (recentRes.status === 'fulfilled') {
            setRecent(Array.isArray(recentRes.value) ? recentRes.value : []);
        }
    }, []);

    useEffect(() => {
        refresh();
    }, [refresh]);

    const selected = products.find((product) => String(product.id) === String(form.product_id));

    function setField(name, value) {
        setForm((current) => ({ ...current, [name]: value }));
    }

    function switchMode(nextMode) {
        setMode(nextMode);
        setError(null);
        setFieldErrors([]);
        setResult(null);
    }

    async function handleSubmit(event) {
        event.preventDefault();
        setSubmitting(true);
        setError(null);
        setFieldErrors([]);
        setResult(null);

        const payload =
            mode === 'existing'
                ? {
                      product_id: form.product_id,
                      supplier_id: form.supplier_id || null,
                      quantity_received: form.quantity_received,
                      unit_of_measure: form.unit_of_measure || null,
                      note: form.note || null,
                  }
                : {
                      new_product_name: form.new_product_name,
                      custom_sku: form.custom_sku || null,
                      custom_category: form.custom_category || null,
                      unit_of_measure: form.unit_of_measure || null,
                      supplier_id: form.supplier_id || null,
                      quantity_received: form.quantity_received,
                      note: form.note || null,
                  };

        try {
            const response = await post('/api/inventory/stock-in', payload);

            setResult({
                message: response.message,
                productName: response.product?.name ?? response.stock_in?.product_name,
                before: response.stock_in?.stock_before,
                after: response.stock_in?.stock_after,
                added: response.stock_in?.piece_delta,
            });

            setForm((current) => ({
                ...current,
                product_id: '',
                quantity_received: '',
                note: '',
                new_product_name: '',
                custom_sku: '',
                custom_category: '',
            }));

            await refresh();
        } catch (err) {
            setError(err.message);
            setFieldErrors(validationMessages(err));
        } finally {
            setSubmitting(false);
        }
    }

    const input = (name, props = {}) => ({
        value: form[name],
        onChange: (event) => setField(name, event.target.value),
        ...props,
    });

    return (
        <div className="page">
            <header className="page-head">
                <div>
                    <h2>Stock-In</h2>
                    <p>Receive deliveries from suppliers. Stock levels update immediately.</p>
                </div>
            </header>

            {stats && (
                <div className="stat-grid">
                    <article className="card stat">
                        <span>Products</span>
                        <strong>{stats.total_products}</strong>
                    </article>
                    <article className="card stat">
                        <span>Total revenue</span>
                        <strong>{formatPeso(stats.total_revenue)}</strong>
                    </article>
                    <article className="card stat">
                        <span>Transactions</span>
                        <strong>{stats.total_sales}</strong>
                    </article>
                    <article className="card stat">
                        <span>Low stock</span>
                        <strong className={stats.low_stock_count > 0 ? 'text-warn' : ''}>{stats.low_stock_count}</strong>
                    </article>
                    <article className="card stat">
                        <span>Suppliers</span>
                        <strong>{stats.supplier_count}</strong>
                    </article>
                </div>
            )}

            <div className="split-2">
                <section className="card padded">
                    <div className="mode-tabs" role="tablist">
                        <button
                            type="button"
                            className={`mode-tab${mode === 'existing' ? ' is-active' : ''}`}
                            onClick={() => switchMode('existing')}
                        >
                            Existing product
                        </button>
                        <button
                            type="button"
                            className={`mode-tab${mode === 'new' ? ' is-active' : ''}`}
                            onClick={() => switchMode('new')}
                        >
                            New product
                        </button>
                    </div>

                    <form className="form" onSubmit={handleSubmit}>
                        {mode === 'existing' ? (
                            <label className="field">
                                <span>Product *</span>
                                <select className="input" required {...input('product_id')}>
                                    <option value="">Select a product…</option>
                                    {products.map((product) => (
                                        <option key={product.id} value={product.id}>
                                            {product.name} ({product.sku})
                                        </option>
                                    ))}
                                </select>
                            </label>
                        ) : (
                            <>
                                <label className="field">
                                    <span>New product name *</span>
                                    <input type="text" required maxLength={255} placeholder="e.g. Paint Brush 2in" {...input('new_product_name')} />
                                </label>
                                <div className="field-row">
                                    <label className="field">
                                        <span>SKU (optional)</span>
                                        <input type="text" maxLength={50} placeholder="Auto-generated if blank" {...input('custom_sku')} />
                                    </label>
                                    <label className="field">
                                        <span>Category</span>
                                        <input type="text" maxLength={100} placeholder="Uncategorized" {...input('custom_category')} />
                                    </label>
                                </div>
                            </>
                        )}

                        <div className="field-row">
                            <label className="field">
                                <span>Supplier</span>
                                <select className="input" {...input('supplier_id')}>
                                    <option value="">No supplier / walk-in</option>
                                    {suppliers.map((supplier) => (
                                        <option key={supplier.id} value={supplier.id}>
                                            {supplier.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="field">
                                <span>Receiving unit</span>
                                <select className="input" {...input('unit_of_measure')}>
                                    <option value="">{selected ? selected.receiving_unit : 'Default (piece)'}</option>
                                    {UNITS.map((unit) => (
                                        <option key={unit} value={unit}>
                                            {unit}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        </div>

                        <div className="field-row">
                            <label className="field">
                                <span>Quantity received *</span>
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    required
                                    placeholder="e.g. 10"
                                    {...input('quantity_received')}
                                />
                            </label>
                            {selected && (
                                <div className="field">
                                    <span>On hand now</span>
                                    <div className="readonly-value">
                                        {selected.current_stock} {selected.receiving_unit}
                                        <small className="muted"> · {selected.pieces_per_receiving_unit} pcs per {selected.receiving_unit}</small>
                                    </div>
                                </div>
                            )}
                        </div>

                        <label className="field">
                            <span>Note</span>
                            <input type="text" maxLength={255} placeholder="Delivery receipt #, remarks…" {...input('note')} />
                        </label>

                        {error && (
                            <div className="alert alert-error" role="alert">
                                <span>{error}</span>
                                <FieldError messages={fieldErrors} />
                            </div>
                        )}

                        {result && (
                            <div className="alert alert-success" role="status">
                                <span>
                                    {result.message} — <strong>{result.productName}</strong>:{' '}
                                    {result.before} → <strong>{result.after}</strong> (+{result.added} pcs)
                                </span>
                            </div>
                        )}

                        <button type="submit" className="btn btn-primary btn-lg" disabled={submitting}>
                            {submitting ? 'Recording…' : 'Record stock-in'}
                        </button>
                    </form>
                </section>

                <section className="card padded">
                    <h3 className="section-title">Recent stock-ins</h3>
                    {recent.length === 0 && <p className="muted">No receiving history yet.</p>}
                    <ul className="timeline">
                        {recent.map((item) => (
                            <li key={item.id} className="timeline-item">
                                <div>
                                    <strong>{item.product_name}</strong>
                                    <small className="muted">
                                        +{item.quantity} {item.unit} · {item.supplier_name}
                                    </small>
                                </div>
                                <div className="timeline-meta">
                                    <span className="muted">{item.staff_name}</span>
                                    <small className="muted">{item.date}</small>
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </div>
    );
}
