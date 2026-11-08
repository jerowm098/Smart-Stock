import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Modal from './Modal.jsx';
import MessageDialog from './MessageDialog.jsx';
import Alert, { Toast } from './Feedback.jsx';
import { get, post, validationMessages } from '../lib/api.js';
import { formatPeso } from '../lib/format.js';

const QUICK_CASH = [20, 50, 100, 200];
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
    const [gcashNotice, setGcashNotice] = useState(false);
    const [toast, setToast] = useState(null);

    // Transient "+N"/"-N" badges keyed by product id (same idea as the Products
    // page stock-delta badge): pop in over the card, then disappear.
    //
    // The number always describes the change in AVAILABLE stock, never the cart
    // direction — adding to the cart takes stock off the shelf (so "-1" and the
    // "N left" pill drops by one), while removing a line or clearing the cart
    // puts stock back (so "+N" and the pill climbs back).
    const [cartDeltas, setCartDeltas] = useState({});
    const cartDeltaSeq = useRef(0);
    const deltaTimers = useRef(new Map());

    function showCartDelta(productId, delta) {
        if (!delta) {
            return;
        }

        const key = ++cartDeltaSeq.current;
        setCartDeltas((current) => ({ ...current, [productId]: { delta, key } }));

        // Safety net: a card that is not on the current page never fires
        // onAnimationEnd, so clear stale badges on a timer as well.
        const pending = deltaTimers.current.get(productId);

        if (pending) {
            clearTimeout(pending);
        }

        deltaTimers.current.set(
            productId,
            setTimeout(() => {
                deltaTimers.current.delete(productId);
                clearCartDelta(productId, key);
            }, 2100),
        );
    }

    function clearCartDelta(productId, key) {
        setCartDeltas((current) => {
            const entry = current[productId];

            // Ignore a late animation end belonging to an older badge.
            if (!entry || (key !== undefined && entry.key !== key)) {
                return current;
            }

            const next = { ...current };
            delete next[productId];

            return next;
        });
    }

    useEffect(() => () => {
        deltaTimers.current.forEach((timer) => clearTimeout(timer));
        deltaTimers.current.clear();
    }, []);

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

    function inCartQty(productId) {
        return cart.find((line) => line.product.id === productId)?.quantity ?? 0;
    }

    /**
     * Stock still available for new cart lines = on-hand stock minus everything
     * already parked in the cart. This is what the "N left" pill shows, so it
     * moves the moment an item is clicked.
     */
    function availableOf(product) {
        return Math.max(0, (Number(product.current_stock) || 0) - inCartQty(product.id));
    }

    function addToCart(product) {
        const stock = Number(product.current_stock) || 0;

        if (stock <= 0) {
            return;
        }

        const existing = cart.find((line) => line.product.id === product.id);

        if (existing && existing.quantity >= stock) {
            return;
        }

        // One unit leaves the shelf and goes into the cart.
        showCartDelta(product.id, -1);
        setCheckoutError(null);
        setCart((current) => {
            const line = current.find((entry) => entry.product.id === product.id);

            if (line) {
                return current.map((entry) =>
                    entry.product.id === product.id ? { ...entry, quantity: entry.quantity + 1 } : entry,
                );
            }

            return [...current, { product, quantity: 1 }];
        });
    }

    function setQuantity(productId, quantity) {
        const stock = stockOf(productId);
        const before = cart.find((line) => line.product.id === productId)?.quantity ?? 0;
        const next = Math.max(0, Math.min(Number(quantity) || 0, stock));

        if (next !== before) {
            // Cart goes down → stock goes back up, and vice versa.
            showCartDelta(productId, before - next);
        }

        setCart((current) =>
            current
                .map((line) => (line.product.id === productId ? { ...line, quantity: next } : line))
                .filter((line) => line.quantity > 0),
        );
    }

    function removeLine(productId) {
        const before = cart.find((line) => line.product.id === productId)?.quantity ?? 0;

        if (before > 0) {
            // The whole line goes back on the shelf.
            showCartDelta(productId, before);
        }

        setCart((current) => current.filter((line) => line.product.id !== productId));
    }

    function clearCart() {
        // Every line returns to stock, so each product card shows the full
        // return total (+N) for that item — not one combined badge.
        cart.forEach((line) => showCartDelta(line.product.id, line.quantity));

        setCart([]);
        setPayment('');
        setCheckoutError(null);
    }

    function addCash(amount) {
        // Denominations stack instead of replacing: ₱20 then ₱20 again is ₱40.
        setPayment((current) => {
            const base = Number(current) || 0;

            return String(Math.round((base + amount) * 100) / 100);
        });
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
                            ? `, only ${err.payload.available_stock} left.`
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

    /**
     * Dismiss the receipt and confirm the completed sale with a toast. The
     * receipt modal is the transaction summary; this is the "done" signal once
     * the cashier clicks "New sale". The close (X) button only closes.
     */
    function dismissReceipt() {
        const saleId = receipt?.saleId;
        const saleTotal = receipt?.total;

        setReceipt(null);
        setToast(
            saleId
                ? `Sale ${receiptNo(saleId)} completed successfully. Total ${formatPeso(saleTotal ?? 0)}.`
                : 'Purchase completed successfully.',
        );
    }

    return (
        <div className="page">
            <section className={`page-hero${loading ? ' is-loading' : ''}`}>
                <div className="page-loadbar" aria-hidden="true" />
                <header className="page-head">
                    <div>
                        <div className="page-title">
                            <h2>{isAdmin ? 'Point of Sale Checkout (Admin)' : 'Point of Sale (Cashier)'}</h2>
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
                                        <ul className="cart-lines is-skeleton">
                                            {[0, 1, 2].map((i) => (
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
                                Active products available for sale, with price and stock still available to add.
                            </p>
                        </div>
                    </header>
                    <div className="pos-workspace">
                        <div className="pos-catalog-area">
                            {error && <Alert tone="error">{error}</Alert>}

                            {!error && (
                                <div className={`pos-grid${paged.length >= 9 ? ' is-stretch' : ''}`}>
                                    {paged.map((item) => {
                                        const stock = Number(item.current_stock) || 0;
                                        // "left" is what can still be added, so it drops as
                                        // soon as the item is clicked into the cart.
                                        const available = availableOf(item);
                                        const out = stock <= 0;
                                        const allInCart = !out && available <= 0;
                                        const low = !out && !allInCart && available <= Number(item.reorder_threshold);
                                        const delta = cartDeltas[item.id];

                                        return (
                                            <button
                                                key={item.id}
                                                type="button"
                                                className={`card pos-card pos-pick ${out ? 'is-out' : low || allInCart ? 'is-low' : ''}`}
                                                onClick={() => addToCart(item)}
                                                disabled={out || allInCart}
                                                title={
                                                    out
                                                        ? 'Out of stock'
                                                        : allInCart
                                                          ? 'All available stock is already in the cart'
                                                          : `Add ${item.name} to cart`
                                                }
                                            >
                                                {delta && (
                                                    <span
                                                        key={delta.key}
                                                        className={`pos-cart-delta ${delta.delta > 0 ? 'is-up' : 'is-down'}`}
                                                        onAnimationEnd={() => clearCartDelta(item.id, delta.key)}
                                                    >
                                                        {delta.delta > 0 ? `+${delta.delta}` : delta.delta}
                                                    </span>
                                                )}
                                                <header>
                                                    <span className="muted pos-card-category">{item.category || 'Uncategorised'}</span>
                                                </header>
                                                <h3>{item.name}</h3>
                                                <div className="pos-card-meta">
                                                    <span className="mono muted pos-card-sku">{item.sku}</span>
                                                    <span className={`pill ${out ? 'pill-danger' : low || allInCart ? 'pill-warn' : 'pill-ok'}`}>
                                                        {out ? 'Out' : `${available} left`}
                                                    </span>
                                                </div>
                                                <footer>
                                                    <strong>{formatPeso(item.price)}</strong>
                                                    <span className="muted">per {item.sell_unit}</span>
                                                </footer>
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
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-sm"
                                            onClick={() => setPayment('')}
                                            disabled={!payment}
                                        >
                                            Clear
                                        </button>
                                        {QUICK_CASH.map((amount) => (
                                            <button
                                                key={amount}
                                                type="button"
                                                className="btn btn-ghost btn-sm"
                                                onClick={() => addCash(amount)}
                                            >
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

                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-lg btn-block"
                                        onClick={() => setGcashNotice(true)}
                                    >
                                        Process to G-Cash
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
                <Modal
                    title={`Receipt ${receiptNo(receipt.saleId)}`}
                    titleIcon={
                        <span className="modal-title-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 11v5M12 8h.01" />
                            </svg>
                        </span>
                    }
                    className="modal-receipt"
                    onClose={() => setReceipt(null)}
                >
                    <div className="receipt">
                        <p className="receipt-store">Smart-Stock Hardware</p>
                        <p className="muted receipt-id">
                            Sale #{receipt.saleId} · {receipt.channel === 'admin' ? 'Admin POS' : 'Cashier POS'}
                        </p>

                        <ul className="receipt-items">
                            {receipt.items.map((item, index) => (
                                <li key={index} className="receipt-item">
                                    <span className="receipt-item-name">
                                        {item.name}
                                        <small className="muted"> ×{item.quantity} {item.unit}</small>
                                    </span>
                                    <span className="receipt-item-amount">{formatPeso(item.lineTotal)}</span>
                                </li>
                            ))}
                        </ul>

                        <div className="receipt-divider" role="separator" aria-hidden="true" />

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

                        <button type="button" className="btn btn-primary" onClick={dismissReceipt}>
                            New sale
                        </button>
                    </div>
                </Modal>
            )}

            {gcashNotice && (
                <MessageDialog
                    tone="info"
                    title="Process to G-Cash"
                    description="This function is under development. G-Cash payments are not available yet. Please continue with cash payment for now."
                    closeLabel="Got it"
                    onClose={() => setGcashNotice(false)}
                />
            )}

            {toast && (
                <Toast tone="success" onClose={() => setToast(null)}>
                    {toast}
                </Toast>
            )}
        </div>
    );
}

