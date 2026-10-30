import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Modal from './Modal.jsx';
import Alert from './Feedback.jsx';
import { get, post, validationMessages } from '../lib/api.js';
import { formatPeso } from '../lib/format.js';

const QUICK_CASH = [20, 50, 100, 200, 500, 1000];
const PER_PAGE = 12;

function receiptNo(saleId) {
    return 'SS-' + String(saleId ?? 0).padStart(6, '0');
}

/**
 * Shared POS terminal used by both dashboards.
 *
 * The selling UI is identical on purpose — one cashier-facing workflow, one set
 * of keyboard/mouse habits — but the two terminals differ in what they record:
 *
 *   - `channel="cashier"` → the Cashier dashboard's till. Sales are attributed
 *     to the cashier on duty and show as "Cashier POS" in Transactions.
 *   - `channel="admin"`   → the Admin dashboard's own till. Sales are stamped
 *     as admin transactions (an override/assist sale, e.g. the owner covering
 *     the counter) and show as "Admin POS" in Transactions.
 *
 * The channel is decided by the backend from the authenticated role, so the
 * copy here only frames the screen; it can never fake a channel.
 */
export default function PosTerminal({ channel = 'cashier' }) {
    const isAdmin = channel === 'admin';
    const navigate = useNavigate();

    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const [search, setSearch] = useState('');
    const [category, setCategory] = useState('');
    const [status, setStatus] = useState('');
    const [sort, setSort] = useState('');
    const [page, setPage] = useState(1);

    const [cart, setCart] = useState([]); // [{ product, quantity }]
    const [payment, setPayment] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [checkoutError, setCheckoutError] = useState(null);
    const [receipt, setReceipt] = useState(null);

    useEffect(() => {
        let active = true;

        get('/api/pos/products')
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

    const categories = useMemo(() => {
        const set = new Set(items.map((item) => item.category).filter(Boolean));

        return [...set].sort((a, b) => a.localeCompare(b));
    }, [items]);

    const filtered = useMemo(() => {
        const needle = search.trim().toLowerCase();

        const rows = items.filter((item) => {
            if (category && item.category !== category) {
                return false;
            }

            if (status) {
                // Same stock-state rule as the Products table and the card badges.
                const stock = Number(item.current_stock);
                const out = stock <= 0;
                const low = !out && stock <= Number(item.reorder_threshold);
                const state = out ? 'out' : low ? 'low' : 'in';

                if (state !== status) {
                    return false;
                }
            }

            if (!needle) {
                return true;
            }

            return [item.name, item.sku, String(item.id)]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(needle));
        });

        // Client-side sort; the default keeps the backend's newest-first order.
        const sorted = [...rows];

        if (sort === 'name') {
            sorted.sort((a, b) => String(a.name).localeCompare(String(b.name)));
        } else if (sort === 'price-asc') {
            sorted.sort((a, b) => Number(a.price) - Number(b.price));
        } else if (sort === 'price-desc') {
            sorted.sort((a, b) => Number(b.price) - Number(a.price));
        }

        return sorted;
    }, [items, search, category, status, sort]);

    // 12 cards per page, same compact pager as the Products table.
    const lastPage = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const paged = useMemo(
        () => filtered.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE),
        [filtered, safePage],
    );

    // Never strand the user on an out-of-range page when filters change.
    useEffect(() => {
        setPage(1);
    }, [search, category, status, sort]);

    const subtotal = useMemo(
        () => cart.reduce((sum, line) => sum + Number(line.product.price) * line.quantity, 0),
        [cart],
    );

    const total = Math.round(subtotal * 100) / 100;
    const paymentValue = Number(payment) || 0;
    const change = Math.round((paymentValue - total) * 100) / 100;

    function stockOf(productId) {
        // Prefer the live catalog row; fall back to the cart's snapshot.
        return items.find((item) => item.id === productId)?.current_stock ?? 0;
    }

    function addToCart(product) {
        const stock = Number(product.current_stock) || 0;

        if (stock <= 0) {
            return;
        }

        setCheckoutError(null);
        setCart((current) => {
            const existing = current.find((line) => line.product.id === product.id);

            if (existing) {
                if (existing.quantity >= stock) {
                    return current;
                }

                return current.map((line) =>
                    line.product.id === product.id ? { ...line, quantity: line.quantity + 1 } : line,
                );
            }

            return [...current, { product, quantity: 1 }];
        });
    }

    function setQuantity(productId, quantity) {
        const stock = stockOf(productId);

        setCart((current) =>
            current
                .map((line) =>
                    line.product.id === productId
                        ? { ...line, quantity: Math.max(0, Math.min(Number(quantity) || 0, stock)) }
                        : line,
                )
                .filter((line) => line.quantity > 0),
        );
    }

    function removeLine(productId) {
        setCart((current) => current.filter((line) => line.product.id !== productId));
    }

    function clearCart() {
        setCart([]);
        setPayment('');
        setCheckoutError(null);
    }

    function clearFilters() {
        setSearch('');
        setCategory('');
        setStatus('');
        setSort('');
    }

    async function handleCheckout() {
        if (cart.length === 0) {
            return;
        }

        setSubmitting(true);
        setCheckoutError(null);

        try {
            const response = await post('/api/pos/checkout', {
                cart_items: cart.map((line) => ({
                    product_id: line.product.id,
                    quantity: line.quantity,
                })),
                payment_amount: paymentValue,
            });

            setReceipt({
                saleId: response.sale_id,
                channel: response.channel ?? channel,
                items: cart.map((line) => ({
                    name: line.product.name,
                    sku: line.product.sku,
                    quantity: line.quantity,
                    unit: line.product.sell_unit,
                    unitPrice: Number(line.product.price),
                    lineTotal: Math.round(Number(line.product.price) * line.quantity * 100) / 100,
                })),
                total: response.total_amount,
                payment: response.payment_amount,
                change: response.change_amount ?? response.change,
            });

            // Merge the post-sale stock snapshot so the grid is instantly
            // accurate without a refetch.
            const updates = response.products ?? [];
            setItems((current) =>
                current.map((item) => {
                    const updated = updates.find((row) => row.id === item.id);

                    return updated ? { ...item, ...updated } : item;
                }),
            );

            setCart([]);
            setPayment('');
        } catch (err) {
            if (err.status === 401) {
                navigate('/login', { replace: true });

                return;
            }

            if (err.status === 409) {
                // Stale cart vs live stock: clamp the offending line.
                const productId = err.payload?.product_id;

                if (productId !== undefined) {
                    setQuantity(productId, err.payload?.available_stock ?? 0);
                }

                setCheckoutError(
                    `${err.message}${err.payload?.product_name ? ` (${err.payload.product_name})` : ''}` +
                        (err.payload?.available_stock !== undefined
                            ? ` — only ${err.payload.available_stock} left.`
                            : ''),
                );
            } else if (err.status === 402) {
                setCheckoutError(
                    `${err.message} Total is ${formatPeso(err.payload?.total_amount ?? total)}.`,
                );
            } else {
                setCheckoutError(validationMessages(err)[0] ?? err.message);
            }
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="page">
            <section className={`page-hero${loading ? ' is-loading' : ''}`}>
                <div className="page-loadbar" aria-hidden="true" />
                <header className="page-head">
                    <div>
                        <div className="page-title">
                            <h2>{isAdmin ? 'Point of Sale Checkout (Admin)' : 'Point of Sale'}</h2>
                        </div>
                        <p>
                            {isAdmin
                                ? 'Sell products and take payments.'
                                : 'Click an item to add it to the cart, then take payment.'}
                        </p>
                    </div>
                </header>
            </section>

            {loading && (
                /* Loading phase: the hero above stays put while these sections
                   paint skeletons mirroring the real POS layout — no spinners. */
                <div className="page-loading-phase" aria-busy="true" aria-live="polite">
                    <div className="pos-layout">
                        <section className="pos-catalog-col">
                            <section className="card padded pos-filter-card">
                                <div className="filter-head">
                                    <div style={{ flex: 1, minWidth: 0 }}>
                                        <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                        <span className="skeleton skeleton-line skeleton-sm" />
                                    </div>
                                </div>
                                <div className="filter-shell">
                                    <div className="filter-grid">
                                        {[0, 1, 2, 3].map((i) => (
                                            <div key={i} className="field">
                                                <span className="skeleton skeleton-sm" />
                                                <span className="skeleton" style={{ height: 42 }} />
                                            </div>
                                        ))}
                                        <div className="field">
                                            <span className="sr-only">Clear filters</span>
                                            <span className="skeleton" style={{ height: 42, width: 108 }} />
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </section>

                        <div className="pos-shell pos-grid-shell">
                            <header className="filter-head pos-grid-head">
                                <div style={{ flex: 1, minWidth: 0 }}>
                                    <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                    <span className="skeleton skeleton-line skeleton-sm" />
                                </div>
                            </header>

                            <div className="pos-workspace">
                                <div className="pos-catalog-area">
                                    <div className="pos-grid">
                                        {[0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11].map((i) => (
                                            <article key={i} className="card pos-card skeleton-card" aria-hidden="true">
                                                <header>
                                                    <span className="skeleton" style={{ width: 64, height: 10 }} />
                                                    <span className="skeleton" style={{ width: 52, height: 20, borderRadius: 999 }} />
                                                </header>
                                                <span className="skeleton skeleton-line" style={{ width: '72%', height: 14 }} />
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '45%' }} />
                                                <footer>
                                                    <span className="skeleton" style={{ width: 60, height: 16 }} />
                                                    <span className="skeleton" style={{ width: 42, height: 10 }} />
                                                </footer>
                                            </article>
                                        ))}
                                    </div>
                                </div>

                                <aside className="cart-panel" aria-hidden="true">
                                    <section className="card cart-block">
                                        <div className="cart-block-head">
                                            <span className="skeleton" style={{ width: 48, height: 16 }} />
                                            <span className="skeleton" style={{ width: 42, height: 24 }} />
                                        </div>
                                        <ul className="cart-lines">
                                            {[0, 1, 2, 3].map((i) => (
                                                <li key={i} className="cart-line">
                                                    <div className="cart-line-info">
                                                        <span className="skeleton skeleton-line" style={{ width: '75%', marginBottom: 6 }} />
                                                        <span className="skeleton skeleton-line skeleton-sm" style={{ width: '45%' }} />
                                                    </div>
                                                    <span className="skeleton" style={{ width: 72, height: 28, borderRadius: 8 }} />
                                                    <span className="skeleton" style={{ width: 48, height: 12 }} />
                                                    <span className="skeleton" style={{ width: 22, height: 22, borderRadius: 6 }} />
                                                </li>
                                            ))}
                                        </ul>
                                    </section>

                                    <section className="card cart-block">
                                        <div className="cart-block-head">
                                            <span className="skeleton" style={{ width: 64, height: 16 }} />
                                        </div>
                                        <div className="cart-summary">
                                            <div className="cart-row">
                                                <span className="skeleton" style={{ width: 60, height: 12 }} />
                                                <span className="skeleton" style={{ width: 54, height: 12 }} />
                                            </div>
                                            <div className="field">
                                                <span className="skeleton skeleton-sm" />
                                                <span className="skeleton" style={{ height: 42 }} />
                                            </div>
                                            <div className="quick-cash">
                                                {[0, 1, 2, 3].map((i) => (
                                                    <span key={i} className="skeleton" style={{ width: 54, height: 30, borderRadius: 8 }} />
                                                ))}
                                            </div>
                                            <div className="cart-row">
                                                <span className="skeleton" style={{ width: 44, height: 12 }} />
                                                <span className="skeleton" style={{ width: 56, height: 12 }} />
                                            </div>
                                            <span className="skeleton" style={{ width: '100%', height: 46, borderRadius: 10 }} />
                                        </div>
                                    </section>
                                </aside>
                            </div>

                            <div className="pagination" aria-hidden="true">
                                <span className="skeleton" style={{ width: 34, height: 34 }} />
                                <span className="skeleton" style={{ width: 20, height: 12 }} />
                                <span className="skeleton" style={{ width: 34, height: 34 }} />
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {!loading && (
            <div className="pos-layout">
                <section className="pos-catalog-col">
                    <section className="card padded pos-filter-card">
                        <div className="filter-head">
                            <div>
                                <h3>Search & Filter Options</h3>
                                <p className="muted">Narrow the catalogue by item name, SKU, category or stock status.</p>
                            </div>
                        </div>
                        <div className="filter-shell">
                            <div className="filter-grid">
                                <label className="field">
                                    <span>Search</span>
                                    <input
                                        type="search"
                                        placeholder="Item name, SKU or ID…"
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                    />
                                </label>
                                <label className="field">
                                    <span>Category</span>
                                    <select
                                        className="input"
                                        value={category}
                                        onChange={(event) => setCategory(event.target.value)}
                                    >
                                        <option value="">All categories</option>
                                        {categories.map((cat) => (
                                            <option key={cat} value={cat}>
                                                {cat}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <label className="field">
                                    <span>Status</span>
                                    <select
                                        className="input"
                                        value={status}
                                        onChange={(event) => setStatus(event.target.value)}
                                    >
                                        <option value="">All statuses</option>
                                        <option value="in">In stock</option>
                                        <option value="low">Low stock</option>
                                        <option value="out">Out of stock</option>
                                    </select>
                                </label>
                                <label className="field">
                                    <span>Sort by</span>
                                    <select
                                        className="input"
                                        value={sort}
                                        onChange={(event) => setSort(event.target.value)}
                                    >
                                        <option value="">Newest first</option>
                                        <option value="name">Name A–Z</option>
                                        <option value="price-asc">Price: low to high</option>
                                        <option value="price-desc">Price: high to low</option>
                                    </select>
                                </label>
                                <div className="field">
                                    <span className="sr-only">Clear filters</span>
                                    <button
                                        type="button"
                                        className="btn btn-ghost"
                                        onClick={clearFilters}
                                        disabled={!search.trim() && !category && !status && !sort}
                                    >
                                        Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                </section>

                <div className="pos-shell pos-grid-shell">
                    <header className="filter-head pos-grid-head">
                        <div>
                            <h3>Product Catalogue List</h3>
                            <p className="muted">
                                Active products available for sale, with price and current stock.
                            </p>
                        </div>
                    </header>
                    <div className="pos-workspace">
                        <div className="pos-catalog-area">
                            {error && <Alert tone="error">{error}</Alert>}

                            {!error && (
                                <div className={`pos-grid${paged.length >= 9 ? ' is-stretch' : ''}`}>
                                    {paged.map((item) => {
                                        const stock = Number(item.current_stock);
                                        const out = stock <= 0;
                                        const low = !out && stock <= Number(item.reorder_threshold);
                                        const inCart = cart.find((line) => line.product.id === item.id)?.quantity ?? 0;

                                        return (
                                            <button
                                                key={item.id}
                                                type="button"
                                                className={`card pos-card pos-pick ${out ? 'is-out' : low ? 'is-low' : ''}`}
                                                onClick={() => addToCart(item)}
                                                disabled={out}
                                                title={out ? 'Out of stock' : `Add ${item.name} to cart`}
                                            >
                                                <header>
                                                    <span className="mono muted">{item.sku}</span>
                                                    <span className={`pill ${out ? 'pill-danger' : low ? 'pill-warn' : 'pill-ok'}`}>
                                                        {out ? 'Out' : `${stock} left`}
                                                    </span>
                                                </header>
                                                <h3>{item.name}</h3>
                                                <p className="muted">{item.category || 'Uncategorised'}</p>
                                                <footer>
                                                    <strong>{formatPeso(item.price)}</strong>
                                                    <span className="muted">per {item.sell_unit}</span>
                                                </footer>
                                                {inCart > 0 && <span className="cart-badge">×{inCart} in cart</span>}
                                            </button>
                                        );
                                    })}
                                </div>
                            )}

                            {!error && filtered.length === 0 && (
                                <p className="muted pos-grid-empty">No items match “{search}”.</p>
                            )}
                        </div>

                        <aside className="cart-panel">
                            <section className="card cart-block">
                                <div className="cart-block-head">
                                    <h3>Cart</h3>
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-sm"
                                        onClick={clearCart}
                                        disabled={cart.length === 0}
                                    >
                                        Clear
                                    </button>
                                </div>

                                {cart.length === 0 && <p className="muted cart-empty">Click items on the left to start a sale.</p>}

                                {cart.length > 0 && (
                                    <ul className="cart-lines">
                                        {cart.map((line) => (
                                            <li key={line.product.id} className="cart-line">
                                                <div className="cart-line-info">
                                                    <strong>{line.product.name}</strong>
                                                    <small className="muted">
                                                        {formatPeso(line.product.price)} / {line.product.sell_unit}
                                                    </small>
                                                </div>
                                                <div className="stepper">
                                                    <button type="button" onClick={() => setQuantity(line.product.id, line.quantity - 1)} aria-label="Decrease">
                                                        −
                                                    </button>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        max={stockOf(line.product.id)}
                                                        value={line.quantity}
                                                        onChange={(event) => setQuantity(line.product.id, event.target.value)}
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => addToCart(line.product)}
                                                        disabled={line.quantity >= stockOf(line.product.id)}
                                                        aria-label="Increase"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                                <div className="cart-line-total">
                                                    {formatPeso(Number(line.product.price) * line.quantity)}
                                                </div>
                                                <button
                                                    type="button"
                                                    className="cart-remove"
                                                    onClick={() => removeLine(line.product.id)}
                                                    aria-label={`Remove ${line.product.name}`}
                                                >
                                                    ✕
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}

                            </section>

                            <section className="card cart-block">
                                <div className="cart-block-head">
                                    <h3>Payment</h3>
                                </div>

                                <div className="cart-summary">
                                    <div className="cart-row">
                                        <span>Subtotal</span>
                                        <strong>{formatPeso(total)}</strong>
                                    </div>

                                    <label className="field">
                                        <span>Payment received</span>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                            value={payment}
                                            onChange={(event) => setPayment(event.target.value)}
                                        />
                                    </label>

                                    <div className="quick-cash">
                                        <button type="button" className="btn btn-ghost btn-sm" onClick={() => setPayment(total.toFixed(2))} disabled={cart.length === 0}>
                                            Exact
                                        </button>
                                        {QUICK_CASH.map((amount) => (
                                            <button key={amount} type="button" className="btn btn-ghost btn-sm" onClick={() => setPayment(String(amount))}>
                                                ₱{amount}
                                            </button>
                                        ))}
                                    </div>

                                    <div className={`cart-row change-row ${change < 0 ? 'is-short' : ''}`}>
                                        <span>{change < 0 ? 'Still needed' : 'Change'}</span>
                                        <strong>{formatPeso(Math.abs(change))}</strong>
                                    </div>

                                    {checkoutError && <Alert tone="error">{checkoutError}</Alert>}

                                    <button
                                        type="button"
                                        className="btn btn-primary btn-lg btn-block"
                                        disabled={cart.length === 0 || submitting || paymentValue < total}
                                        onClick={handleCheckout}
                                    >
                                        {submitting ? 'Processing…' : `Checkout · ${formatPeso(total)}`}
                                    </button>
                                </div>
                            </section>
                        </aside>
                    </div>

                    {!error && filtered.length > 0 && (
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
                </div>
            </div>
            )}

            {receipt && (
                <Modal title={`Receipt ${receiptNo(receipt.saleId)}`} onClose={() => setReceipt(null)}>
                    <div className="receipt">
                        <p className="receipt-store">Smart-Stock Hardware</p>
                        <p className="muted receipt-id">
                            Sale #{receipt.saleId} · {receipt.channel === 'admin' ? 'Admin POS' : 'Cashier POS'}
                        </p>

                        <table className="table table-plain receipt-lines">
                            <tbody>
                                {receipt.items.map((item, index) => (
                                    <tr key={index}>
                                        <td>
                                            {item.name}
                                            <small className="muted"> ×{item.quantity} {item.unit}</small>
                                        </td>
                                        <td className="text-right">{formatPeso(item.lineTotal)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="cart-row">
                            <span>Total</span>
                            <strong>{formatPeso(receipt.total)}</strong>
                        </div>
                        <div className="cart-row">
                            <span>Payment</span>
                            <strong>{formatPeso(receipt.payment)}</strong>
                        </div>
                        <div className="cart-row change-row">
                            <span>Change</span>
                            <strong>{formatPeso(receipt.change)}</strong>
                        </div>

                        <button type="button" className="btn btn-primary btn-block" onClick={() => setReceipt(null)}>
                            New sale
                        </button>
                    </div>
                </Modal>
            )}
        </div>
    );
}

