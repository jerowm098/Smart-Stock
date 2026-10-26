import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

export default function Pos() {
    const navigate = useNavigate();
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [search, setSearch] = useState('');

    useEffect(() => {
        let active = true;

        api('/api/pos/products')
            .then((rows) => {
                if (active) {
                    setItems(Array.isArray(rows) ? rows : []);
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
            return items;
        }

        return items.filter((item) =>
            [item.name, item.sku, item.category]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(needle)),
        );
    }, [items, search]);

    return (
        <div className="page">
            <header className="page-head">
                <div>
                    <h2>Point of Sale</h2>
                    <p>Cashier-safe catalogue. Prices and stock come from the shared inventory.</p>
                </div>
                <input
                    type="search"
                    className="input search"
                    placeholder="Search item name, SKU or ID…"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                />
            </header>

            <div className="notice">
                <strong>Phase 1 preview:</strong> the catalogue is live from the Laravel API. Cart and
                checkout arrive in Phase 3.
            </div>

            {loading && <p className="muted">Loading catalogue…</p>}

            {error && (
                <p className="form-error" role="alert">
                    {error}
                </p>
            )}

            {!loading && !error && (
                <div className="pos-grid">
                    {filtered.map((item) => {
                        const stock = Number(item.current_stock);
                        const stockClass = stock <= 0 ? 'is-out' : stock <= Number(item.reorder_threshold) ? 'is-low' : '';

                        return (
                            <article key={item.id} className={`card pos-card ${stockClass}`}>
                                <header>
                                    <span className="mono muted">{item.sku}</span>
                                    <span className={`pill ${stock <= 0 ? 'pill-danger' : stock <= Number(item.reorder_threshold) ? 'pill-warn' : 'pill-ok'}`}>
                                        {stock <= 0 ? 'Out' : `${stock} left`}
                                    </span>
                                </header>
                                <h3>{item.name}</h3>
                                <p className="muted">{item.category || 'Uncategorised'}</p>
                                <footer>
                                    <strong>{formatPeso(item.price)}</strong>
                                    <span className="muted">per {item.sell_unit}</span>
                                </footer>
                            </article>
                        );
                    })}
                </div>
            )}

            {!loading && !error && filtered.length === 0 && (
                <p className="muted">No items match “{search}”.</p>
            )}
        </div>
    );
}
