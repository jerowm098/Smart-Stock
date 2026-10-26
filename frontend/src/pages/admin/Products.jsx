import { useCallback, useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Modal from '../../components/Modal.jsx';
import Alert, { FieldError, Loading } from '../../components/Feedback.jsx';
import { del, get, post, put, validationMessages } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

const EMPTY_PRODUCT = {
    name: '',
    sku: '',
    category: '',
    price: '',
    current_stock: '',
    reorder_threshold: '',
};

const ADJUST_REASONS = [
    { value: 'damaged', label: 'Damaged items' },
    { value: 'lost', label: 'Lost / missing' },
    { value: 'internal_transfer', label: 'Internal transfer' },
    { value: 'correction', label: 'Count correction' },
];

function stockState(product) {
    if (Number(product.current_stock) <= 0) {
        return { label: 'Out of stock', className: 'pill-danger' };
    }

    if (Number(product.current_stock) <= Number(product.reorder_threshold)) {
        return { label: 'Low stock', className: 'pill-warn' };
    }

    return { label: 'In stock', className: 'pill-ok' };
}

export default function Products() {
    const navigate = useNavigate();

    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [message, setMessage] = useState(null);
    const [search, setSearch] = useState('');
    const [busyId, setBusyId] = useState(null);
    const [sortBy, setSortBy] = useState('updated_at');
    const [sortDir, setSortDir] = useState('desc');

    const [addOpen, setAddOpen] = useState(false);
    const [addForm, setAddForm] = useState(EMPTY_PRODUCT);
    const [saving, setSaving] = useState(false);
    const [formError, setFormError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState([]);

    const [editProduct, setEditProduct] = useState(null);
    const [editForm, setEditForm] = useState(null);
    const [editSaving, setEditSaving] = useState(false);
    const [editError, setEditError] = useState(null);

    const [adjustProduct, setAdjustProduct] = useState(null);
    const [adjustForm, setAdjustForm] = useState({ delta: '', reason: 'correction', reason_note: '' });
    const [adjustSaving, setAdjustSaving] = useState(false);
    const [adjustError, setAdjustError] = useState(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const rows = await get('/api/inventory/products');
            setProducts(Array.isArray(rows) ? rows : []);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const filtered = useMemo(() => {
        const needle = search.trim().toLowerCase();

        if (!needle) {
            return products;
        }

        return products.filter((product) =>
            [product.name, product.sku, product.category]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(needle)),
        );
    }, [products, search]);

    const sorted = useMemo(() => {
        const dir = sortDir === 'asc' ? 1 : -1;
        return [...filtered].sort((a, b) => {
            let av = a[sortBy];
            let bv = b[sortBy];

            // Numeric comparison for stock, price, reorder_threshold
            if (sortBy === 'current_stock' || sortBy === 'price' || sortBy === 'reorder_threshold') {
                av = Number(av ?? 0);
                bv = Number(bv ?? 0);
                return (av - bv) * dir;
            }

            // Date comparison for updated_at
            if (sortBy === 'updated_at') {
                av = new Date(av ?? 0).getTime();
                bv = new Date(bv ?? 0).getTime();
                return (av - bv) * dir;
            }

            // String comparison for name, sku, category
            av = String(av ?? '').toLowerCase();
            bv = String(bv ?? '').toLowerCase();
            if (av < bv) return -1 * dir;
            if (av > bv) return 1 * dir;
            return 0;
        });
    }, [filtered, sortBy, sortDir]);

    const stats = useMemo(() => {
        const active = products.filter((product) => product.is_active);
        const out = active.filter((product) => Number(product.current_stock) <= 0);
        const low = active.filter(
            (product) =>
                Number(product.current_stock) > 0 &&
                Number(product.current_stock) <= Number(product.reorder_threshold),
        );

        return { total: active.length, low: low.length, out: out.length };
    }, [products]);

    async function handleAdd(event) {
        event.preventDefault();
        setSaving(true);
        setFormError(null);
        setFieldErrors([]);

        try {
            const response = await post('/api/inventory/add', {
                ...addForm,
                category: addForm.category || null,
                price: Number(addForm.price),
                current_stock: Number(addForm.current_stock || 0),
                reorder_threshold: Number(addForm.reorder_threshold || 0),
            });

            setMessage(response.message);
            setAddForm(EMPTY_PRODUCT);
            setAddOpen(false);
            await load();
        } catch (err) {
            setFormError(err.message);
            setFieldErrors(validationMessages(err));
        } finally {
            setSaving(false);
        }
    }

    function openEdit(product) {
        setEditProduct(product);
        setEditForm({
            name: product.name,
            sku: product.sku,
            category: product.category ?? '',
            price: product.price,
            current_stock: product.current_stock,
            reorder_threshold: product.reorder_threshold,
        });
        setEditError(null);
    }

    async function handleEdit(event) {
        event.preventDefault();
        setEditSaving(true);
        setEditError(null);

        try {
            const response = await put('/api/inventory/update', {
                id: editProduct.id,
                name: editForm.name,
                sku: editForm.sku,
                category: editForm.category || null,
                price: Number(editForm.price),
                current_stock: Number(editForm.current_stock),
                reorder_threshold: Number(editForm.reorder_threshold),
            });

            setMessage(response.message);
            setEditProduct(null);
            await load();
        } catch (err) {
            setEditError(validationMessages(err)[0] ?? err.message);
        } finally {
            setEditSaving(false);
        }
    }

    function toggleSort(field) {
        if (sortBy === field) {
            setSortDir(sortDir === 'asc' ? 'desc' : 'asc');
        } else {
            setSortBy(field);
            setSortDir('asc');
        }
    }

    async function handleAdjust(event) {
        event.preventDefault();
        setAdjustSaving(true);
        setAdjustError(null);

        try {
            const response = await post('/api/inventory/adjust', {
                product_id: adjustProduct.id,
                delta: Number(adjustForm.delta),
                reason: adjustForm.reason,
                reason_note: adjustForm.reason_note || null,
            });

            setMessage(
                `${response.message}: ${response.adjustment.stock_before} → ${response.adjustment.stock_after}.`,
            );
            setAdjustProduct(null);
            await load();
        } catch (err) {
            setAdjustError(validationMessages(err)[0] ?? err.message);
        } finally {
            setAdjustSaving(false);
        }
    }

    async function handleDeactivate(product) {
        if (!window.confirm(`Deactivate "${product.name}"? Its history is preserved.`)) {
            return;
        }

        setBusyId(product.id);
        setMessage(null);
        setError(null);

        try {
            const response = await del(`/api/inventory/${product.id}`);
            setMessage(response.message);
            await load();
        } catch (err) {
            setError(err.message);
        } finally {
            setBusyId(null);
        }
    }

    return (
        <div className="page">
            <header className="page-head">
                <div>
                    <h2>Products</h2>
                    <p>Shared store catalogue. Add items, fix counts, and set reorder thresholds.</p>
                </div>
                <div className="head-actions">
                    <input
                        type="search"
                        className="input search"
                        placeholder="Search name, SKU or category…"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                    <button type="button" className="btn btn-primary" onClick={() => setAddOpen(true)}>
                        Add product
                    </button>
                </div>
            </header>

            {message && <Alert tone="success" onClose={() => setMessage(null)}>{message}</Alert>}
            {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}

            <div className="stat-grid">
                <article className="card stat">
                    <span>Active products</span>
                    <strong>{stats.total}</strong>
                </article>
                <article className="card stat">
                    <span>Low stock</span>
                    <strong className={stats.low > 0 ? 'text-warn' : ''}>{stats.low}</strong>
                </article>
                <article className="card stat">
                    <span>Out of stock</span>
                    <strong className={stats.out > 0 ? 'text-danger' : ''}>{stats.out}</strong>
                </article>
                <article className="card stat">
                    <span>Showing</span>
                    <strong>{filtered.length}</strong>
                </article>
            </div>

            <section className="card table-card">
                {loading && <Loading label="Loading products…" />}

                {!loading && !error && (
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th
                                        className="sort-header"
                                        onClick={() => toggleSort('name')}
                                    >
                                        Product{sortBy === 'name' && sortDir === 'asc' ? ' ▲' : sortBy === 'name' ? ' ▼' : ''}
                                    </th>
                                    <th
                                        className="sort-header"
                                        onClick={() => toggleSort('sku')}
                                    >
                                        SKU{sortBy === 'sku' && sortDir === 'asc' ? ' ▲' : sortBy === 'sku' ? ' ▼' : ''}
                                    </th>
                                    <th
                                        className="sort-header"
                                        onClick={() => toggleSort('category')}
                                    >
                                        Category{sortBy === 'category' && sortDir === 'asc' ? ' ▲' : sortBy === 'category' ? ' ▼' : ''}
                                    </th>
                                    <th
                                        className="sort-header text-right"
                                        onClick={() => toggleSort('price')}
                                    >
                                        Price{sortBy === 'price' && sortDir === 'asc' ? ' ▲' : sortBy === 'price' ? ' ▼' : ''}
                                    </th>
                                    <th
                                        className="sort-header text-right"
                                        onClick={() => toggleSort('current_stock')}
                                    >
                                        Stock{sortBy === 'current_stock' && sortDir === 'asc' ? ' ▲' : sortBy === 'current_stock' ? ' ▼' : ''}
                                    </th>
                                    <th>Status</th>
                                    <th className="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sorted.map((product) => {
                                    const state = stockState(product);

                                    return (
                                        <tr key={product.id}>
                                            <td>
                                                <strong>{product.name}</strong>
                                                {!product.is_active && <span className="tag">Inactive</span>}
                                            </td>
                                            <td className="mono">{product.sku}</td>
                                            <td>{product.category || '—'}</td>
                                            <td className="text-right">{formatPeso(product.price)}</td>
                                            <td className="text-right">
                                                {product.current_stock}
                                                <span className="muted"> / reorder at {product.reorder_threshold}</span>
                                            </td>
                                            <td>
                                                <span className={`pill ${state.className}`}>{state.label}</span>
                                            </td>
                                            <td className="text-right actions-cell">
                                                <button type="button" className="btn btn-ghost btn-sm" onClick={() => openEdit(product)}>
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    className="btn btn-ghost btn-sm"
                                                    onClick={() => {
                                                        setAdjustProduct(product);
                                                        setAdjustForm({ delta: '', reason: 'correction', reason_note: '' });
                                                        setAdjustError(null);
                                                    }}
                                                >
                                                    Adjust
                                                </button>
                                                {product.is_active && (
                                                    <button
                                                        type="button"
                                                        className="btn btn-ghost btn-sm btn-danger-text"
                                                        disabled={busyId === product.id}
                                                        onClick={() => handleDeactivate(product)}
                                                    >
                                                        Deactivate
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>

                        {filtered.length === 0 && <p className="muted padded">No products match “{search}”.</p>}
                    </div>
                )}
            </section>

            {addOpen && (
                <Modal title="Add product" onClose={() => setAddOpen(false)}>
                    <form className="form" onSubmit={handleAdd}>
                        <label className="field">
                            <span>Product name *</span>
                            <input type="text" required maxLength={255} value={addForm.name} onChange={(event) => setAddForm((c) => ({ ...c, name: event.target.value }))} autoFocus />
                        </label>
                        <div className="field-row">
                            <label className="field">
                                <span>SKU *</span>
                                <input type="text" required maxLength={50} value={addForm.sku} onChange={(event) => setAddForm((c) => ({ ...c, sku: event.target.value }))} />
                            </label>
                            <label className="field">
                                <span>Category</span>
                                <input type="text" maxLength={100} value={addForm.category} onChange={(event) => setAddForm((c) => ({ ...c, category: event.target.value }))} />
                            </label>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span>Price *</span>
                                <input type="number" required min="0" step="0.01" value={addForm.price} onChange={(event) => setAddForm((c) => ({ ...c, price: event.target.value }))} />
                            </label>
                            <label className="field">
                                <span>Starting stock</span>
                                <input type="number" min="0" step="1" value={addForm.current_stock} onChange={(event) => setAddForm((c) => ({ ...c, current_stock: event.target.value }))} />
                            </label>
                            <label className="field">
                                <span>Reorder threshold</span>
                                <input type="number" min="0" step="1" value={addForm.reorder_threshold} onChange={(event) => setAddForm((c) => ({ ...c, reorder_threshold: event.target.value }))} />
                            </label>
                        </div>

                        {formError && (
                            <div className="alert alert-error" role="alert">
                                <span>{formError}</span>
                                <FieldError messages={fieldErrors} />
                            </div>
                        )}

                        <div className="modal-actions">
                            <button type="button" className="btn btn-ghost" onClick={() => setAddOpen(false)}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={saving}>
                                {saving ? 'Saving…' : 'Add product'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {editProduct && editForm && (
                <Modal title={`Edit — ${editProduct.name}`} onClose={() => setEditProduct(null)}>
                    <form className="form" onSubmit={handleEdit}>
                        <label className="field">
                            <span>Product name *</span>
                            <input type="text" required maxLength={255} value={editForm.name} onChange={(event) => setEditForm((c) => ({ ...c, name: event.target.value }))} />
                        </label>
                        <div className="field-row">
                            <label className="field">
                                <span>SKU *</span>
                                <input type="text" required maxLength={50} value={editForm.sku} onChange={(event) => setEditForm((c) => ({ ...c, sku: event.target.value }))} />
                            </label>
                            <label className="field">
                                <span>Category</span>
                                <input type="text" maxLength={100} value={editForm.category} onChange={(event) => setEditForm((c) => ({ ...c, category: event.target.value }))} />
                            </label>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span>Price *</span>
                                <input type="number" required min="0" step="0.01" value={editForm.price} onChange={(event) => setEditForm((c) => ({ ...c, price: event.target.value }))} />
                            </label>
                            <label className="field">
                                <span>Current stock *</span>
                                <input type="number" required min="0" step="1" value={editForm.current_stock} onChange={(event) => setEditForm((c) => ({ ...c, current_stock: event.target.value }))} />
                            </label>
                            <label className="field">
                                <span>Reorder threshold *</span>
                                <input type="number" required min="0" step="1" value={editForm.reorder_threshold} onChange={(event) => setEditForm((c) => ({ ...c, reorder_threshold: event.target.value }))} />
                            </label>
                        </div>

                        {editError && <div className="alert alert-error" role="alert"><span>{editError}</span></div>}

                        <div className="modal-actions">
                            <button type="button" className="btn btn-ghost" onClick={() => setEditProduct(null)}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={editSaving}>
                                {editSaving ? 'Saving…' : 'Save changes'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {adjustProduct && (
                <Modal title={`Adjust stock — ${adjustProduct.name}`} onClose={() => setAdjustProduct(null)}>
                    <p className="muted modal-note">
                        On hand: <strong>{adjustProduct.current_stock}</strong>. Use a negative delta to
                        subtract (e.g. -3) or a positive one to add.
                    </p>
                    <form className="form" onSubmit={handleAdjust}>
                        <div className="field-row">
                            <label className="field">
                                <span>Delta (±) *</span>
                                <input
                                    type="number"
                                    required
                                    step="1"
                                    placeholder="e.g. -2 or 5"
                                    value={adjustForm.delta}
                                    onChange={(event) => setAdjustForm((c) => ({ ...c, delta: event.target.value }))}
                                />
                            </label>
                            <label className="field">
                                <span>Reason *</span>
                                <select
                                    className="input"
                                    value={adjustForm.reason}
                                    onChange={(event) => setAdjustForm((c) => ({ ...c, reason: event.target.value }))}
                                >
                                    {ADJUST_REASONS.map((reason) => (
                                        <option key={reason.value} value={reason.value}>{reason.label}</option>
                                    ))}
                                </select>
                            </label>
                        </div>
                        <label className="field">
                            <span>Note</span>
                            <input
                                type="text"
                                maxLength={255}
                                placeholder="Optional explanation…"
                                value={adjustForm.reason_note}
                                onChange={(event) => setAdjustForm((c) => ({ ...c, reason_note: event.target.value }))}
                            />
                        </label>

                        {adjustError && <div className="alert alert-error" role="alert"><span>{adjustError}</span></div>}

                        <div className="modal-actions">
                            <button type="button" className="btn btn-ghost" onClick={() => setAdjustProduct(null)}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={adjustSaving}>
                                {adjustSaving ? 'Applying…' : 'Apply adjustment'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </div>
    );
}
