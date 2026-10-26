import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

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
    const [search, setSearch] = useState('');

    useEffect(() => {
        let active = true;

        api('/api/inventory/products')
            .then((rows) => {
                if (active) {
                    setProducts(Array.isArray(rows) ? rows : []);
                }
            })
            .catch((err) => {
                if (!active) {
                    return;
                }

                if (err.status === 401) {
                    navigate('/login', { replace: true });

                    return;
                }

                setError(err.message);
            })
            .finally(() => {
                if (active) {
                    setLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, [navigate]);

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

    const stats = useMemo(() => {
        const active = products.filter((product) => product.is_active);
        const out = active.filter((product) => Number(product.current_stock) <= 0);
        const low = active.filter(
            (product) =>
                Number(product.current_stock) > 0 &&
                Number(product.current_stock) <= Number(product.reorder_threshold),
        );

        return {
            total: active.length,
            low: low.length,
            out: out.length,
        };
    }, [products]);

    return (
        <div className="page">
            <header className="page-head">
                <div>
                    <h2>Products</h2>
                    <p>Shared store catalogue. Stock levels update from receiving and sales.</p>
                </div>
                <input
                    type="search"
                    className="input search"
                    placeholder="Search name, SKU or category…"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                />
            </header>

            <div className="stat-grid">
                <article className="card stat">
                    <span>Active products</span>
                    <strong>{stats.total}</strong>
                </article>
                <article className="card stat">
                    <span>Low stock</span>
                    <strong className="text-warn">{stats.low}</strong>
                </article>
                <article className="card stat">
                    <span>Out of stock</span>
                    <strong className="text-danger">{stats.out}</strong>
                </article>
                <article className="card stat">
                    <span>Showing</span>
                    <strong>{filtered.length}</strong>
                </article>
            </div>

            <section className="card table-card">
                {loading && <p className="muted padded">Loading products…</p>}

                {error && (
                    <p className="form-error padded" role="alert">
                        {error}
                    </p>
                )}

                {!loading && !error && (
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th className="text-right">Price</th>
                                    <th className="text-right">Stock</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filtered.map((product) => {
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
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>

                        {filtered.length === 0 && <p className="muted padded">No products match “{search}”.</p>}
                    </div>
                )}
            </section>
        </div>
    );
}
