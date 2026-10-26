import { useCallback, useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Modal from '../../components/Modal.jsx';
import Alert, { Loading } from '../../components/Feedback.jsx';
import { get, post, validationMessages } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

const QUICK_CASH = [20, 50, 100, 200, 500, 1000];

function receiptNo(saleId) {
    return 'SS-' + String(saleId ?? 0).padStart(6, '0');
}

export default function Pos() {
    const navigate = useNavigate();

    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const [search, setSearch] = useState('');
    const [category, setCategory] = useState('');

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

        return items.filter((item) => {
            if (category && item.category !== category) {
                return false;
            }

            if (!needle) {
                return true;
            }

            return [item.name, item.sku, String(item.id)]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(needle));
        });
    }, [items, search, category]);

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
            <header className="page-head">
                <div>
                    <h2>Point of Sale</h2>
                    <p>Click an item to add it to the cart, then take payment.</p>
                </div>
            </header>

            <div className="pos-layout">
                <section className="pos-catalog-col">
                    <div className="filter-grid pos-filters">
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
                    </div>

                    {loading && <Loading label="Loading catalogue…" />}

                    {error && <Alert tone="error">{error}</Alert>}

                    {!loading && !error && (
                        <div className="pos-grid">
                            {filtered.map((item) => {
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

                    {!loading && !error && filtered.length === 0 && (
                        <p className="muted">No items match “{search}”.</p>
                    )}
                </section>

                <aside className="card cart-panel">
                    <div className="cart-head">
                        <h3>Cart</h3>
                        {cart.length > 0 && (
                            <button type="button" className="btn btn-ghost btn-sm" onClick={clearCart}>
                                Clear
                            </button>
                        )}
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
                </aside>
            </div>

            {receipt && (
                <Modal title={`Receipt ${receiptNo(receipt.saleId)}`} onClose={() => setReceipt(null)}>
                    <div className="receipt">
                        <p className="receipt-store">Smart-Stock Hardware</p>
                        <p className="muted receipt-id">Sale #{receipt.saleId}</p>

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
