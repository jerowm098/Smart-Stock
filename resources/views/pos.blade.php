@extends('layouts.app')

@section('title', 'POS Checkout - Smart-Stock')

@section('content')
<div class="pos-page">
    <div class="pos-header">
        <div>
            <h1 class="pos-title">POS Checkout</h1>
            <p class="pos-subtitle">Process sales, manage cart, and handle payments</p>
        </div>
        <div class="pos-actions">
            <button type="button" class="btn btn-cancel" onclick="clearCart()">Clear Cart</button>
        </div>
    </div>

    <div class="pos-layout">
        <!-- LEFT PANEL: Product Search & Catalog -->
        <div class="pos-panel pos-search-panel">
            <div class="pos-panel-header">
                <h2>Products</h2>
            </div>
            <div class="pos-search-bar">
                <input type="text" id="posSearchInput" class="pos-search-input" placeholder="Search products by name or SKU..." oninput="filterPosProducts()" autocomplete="off">
            </div>
            <div class="pos-product-list" id="posProductList">
                <div class="pos-loading" id="posLoading">
                    <span class="spinner" aria-hidden="true"></span> Loading products...
                </div>
            </div>
        </div>

        <!-- RIGHT RAIL: Cart + Order Summary -->
        <div class="pos-right-rail">
            <!-- Cart Items -->
            <div class="pos-panel pos-cart-panel">
                <div class="pos-panel-header">
                    <h2>Cart</h2>
                    <span class="cart-count" id="cartCount">0 items</span>
                </div>
                <div class="cart-items" id="cartItems">
                    <div class="cart-empty" id="cartEmpty">
                        <p>No items in cart</p>
                        <p class="cart-empty-hint">Select products from the catalog to add them</p>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="pos-panel pos-summary-panel">
                <div class="pos-panel-header">
                    <h2>Order Summary</h2>
                </div>
                <div class="order-summary" id="orderSummary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="summarySubtotal">₱0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (12%)</span>
                        <span id="summaryTax">₱0.00</span>
                    </div>
                    <div class="summary-row summary-total-row">
                        <span>Total</span>
                        <span id="summaryTotal">₱0.00</span>
                    </div>
                </div>
                <div class="pos-payment-form">
                    <h3>Payment</h3>
                    <form id="pos-form" onsubmit="processCheckout(event)">
                        @csrf
                        <div class="form-group">
                            <label for="payment_amount">Payment Amount (₱)</label>
                            <input type="number" name="payment_amount" id="payment_amount" class="form-control" step="0.01" min="0" placeholder="0.00" required oninput="updateChange()">
                        </div>
                        <div class="summary-row">
                            <span>Change</span>
                            <span id="summaryChange" class="change-amount">₱0.00</span>
                        </div>
                        <button type="submit" class="btn btn-submit" id="checkoutBtn">Process Payment</button>
                    </form>
                    <div class="checkout-message" id="checkoutMessage"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .pos-page { padding: 0; }
    .pos-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .pos-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 6px; }
    .pos-subtitle { color: #64748b; font-size: 14px; margin-bottom: 0; }
    .pos-actions { display: flex; gap: 8px; }
    .pos-layout {
        display: grid;
        grid-template-columns: 3fr 1fr;
        gap: 20px;
        min-height: calc(100vh - 160px);
    }
    .pos-right-rail {
        display: flex;
        flex-direction: column;
        gap: 20px;
        min-height: 0;
    }
    .pos-right-rail .pos-panel {
        flex: 1;
        min-height: 0;
    }
    .pos-panel {
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .pos-panel-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.06);
        background: rgba(255,255,255,0.03);
    }
    .pos-panel-header h2 { font-size: 16px; font-weight: 600; color: #f8fafc; margin: 0; }
    .cart-count { font-size: 12px; color: #64748b; font-weight: 500; }

    /* Search Panel */
    .pos-search-bar { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.04); }
    .pos-search-input {
        width: 100%; padding: 10px 14px; background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.12); border-radius: 8px;
        color: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; outline: none;
        transition: border-color 0.2s;
    }
    .pos-search-input:focus { border-color: #3b82f6; }
    .pos-search-input::placeholder { color: #64748b; }
    .pos-product-list {
        flex: 1; overflow-y: auto; padding: 8px;
    }
    .pos-product-card {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 14px; border-radius: 8px; margin-bottom: 4px;
        transition: background 0.15s; cursor: pointer;
        border: 1px solid transparent;
    }
    .pos-product-card:hover { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.06); }
    .pos-product-card.selected { background: rgba(59,130,246,0.08); border-color: rgba(59,130,246,0.2); }
    .pos-product-info { flex: 1; min-width: 0; }
    .pos-product-name { font-size: 14px; font-weight: 600; color: #e2e8f0; margin-bottom: 2px; }
    .pos-product-meta { font-size: 12px; color: #64748b; }
    .pos-product-price { font-size: 14px; font-weight: 600; color: #60a5fa; margin-left: 12px; white-space: nowrap; }
    .pos-product-stock { font-size: 11px; color: #475569; margin-left: 8px; }
    .pos-product-stock.low { color: #fbbf24; }
    .pos-product-stock.critical { color: #f87171; }
    .pos-product-stock.ok { color: #4ade80; }
    .pos-add-btn {
        background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; border: none;
        border-radius: 6px; padding: 6px 14px; font-size: 12px; font-weight: 600;
        cursor: pointer; font-family: 'Inter', sans-serif; transition: opacity 0.15s; margin-left: 8px;
    }
    .pos-add-btn:hover { opacity: 0.9; }
    .pos-add-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .pos-loading { text-align: center; padding: 48px 16px; color: #475569; font-size: 14px; }
    .pos-empty { text-align: center; padding: 48px 16px; color: #475569; font-size: 14px; }

    /* Cart Panel */
    .cart-items { flex: 1; overflow-y: auto; padding: 8px 12px; }
    .cart-empty { text-align: center; padding: 48px 16px; color: #475569; }
    .cart-empty-hint { font-size: 12px; margin-top: 4px; }
    .cart-item {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .cart-item:last-child { border-bottom: none; }
    .cart-item-info { flex: 1; min-width: 0; }
    .cart-item-name { font-size: 13px; font-weight: 600; color: #e2e8f0; margin-bottom: 2px; }
    .cart-item-price { font-size: 12px; color: #64748b; }
    .cart-item-controls { display: flex; align-items: center; gap: 6px; }
    .cart-qty-btn {
        width: 28px; height: 28px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.05); color: #e2e8f0; font-size: 16px; font-weight: 600;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: background 0.15s; font-family: 'Inter', sans-serif;
    }
    .cart-qty-btn:hover { background: rgba(255,255,255,0.1); }
    .cart-qty-value { min-width: 32px; text-align: center; font-size: 14px; font-weight: 600; color: #f8fafc; }
    .cart-item-subtotal { min-width: 70px; text-align: right; font-size: 13px; font-weight: 600; color: #cbd5e1; }
    .cart-item-remove {
        background: none; border: none; color: #f87171; cursor: pointer; font-size: 16px;
        padding: 4px; margin-left: 4px; transition: opacity 0.15s;
    }
    .cart-item-remove:hover { opacity: 0.8; }

    /* Summary Panel */
    .order-summary { padding: 16px 20px; }
    .summary-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 8px 0; font-size: 14px; color: #94a3b8;
    }
    .summary-total-row {
        border-top: 1px solid rgba(255,255,255,0.06); margin-top: 8px; padding-top: 12px;
        font-size: 16px; font-weight: 700; color: #f8fafc;
    }
    .change-amount { color: #4ade80; font-weight: 600; }
    .change-amount.negative { color: #f87171; }
    .pos-payment-form { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.06); }
    .pos-payment-form h3 { font-size: 14px; font-weight: 600; color: #cbd5e1; margin-bottom: 12px; }
    .pos-payment-form .form-group { margin-bottom: 12px; }
    .pos-payment-form label { display: block; color: #94a3b8; font-size: 12px; font-weight: 500; margin-bottom: 5px; }
    .pos-payment-form .form-control {
        width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.12); border-radius: 7px;
        color: #f8fafc; font-size: 14px; font-family: 'Inter', sans-serif; outline: none;
        transition: border-color 0.2s;
    }
    .pos-payment-form .form-control:focus { border-color: #3b82f6; }
    .pos-payment-form .form-control::placeholder { color: #475569; }
    .btn-submit {
        width: 100%; padding: 12px; background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600;
        cursor: pointer; font-family: 'Inter', sans-serif; transition: opacity 0.15s; margin-top: 4px;
    }
    .btn-submit:hover { opacity: 0.9; }
    .btn-submit:disabled { opacity: 0.4; cursor: not-allowed; }
    .btn-cancel {
        background: rgba(255,255,255,0.1); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.12);
        border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 500;
        cursor: pointer; font-family: 'Inter', sans-serif; transition: background 0.15s;
    }
    .btn-cancel:hover { background: rgba(255,255,255,0.15); }
    .checkout-message { margin-top: 12px; font-size: 13px; font-weight: 500; }
    .checkout-message.success { color: #4ade80; }
    .checkout-message.error { color: #f87171; }

    /* Tax on total */
    .summary-tax-row { border-bottom: 1px dashed rgba(255,255,255,0.04); }

    /* LIGHT THEME */
    body.light-theme .pos-page { color: #0f172a; }
    body.light-theme .pos-panel { background: #ffffff; border-color: rgba(15,23,42,0.08); }
    body.light-theme .pos-panel-header { background: #f8fafc; border-bottom-color: rgba(15,23,42,0.08); }
    body.light-theme .pos-panel-header h2 { color: #0f172a; }
    body.light-theme .cart-count { color: #64748b; }
    body.light-theme .pos-search-input { background: #f8fafc; border-color: rgba(15,23,42,0.14); color: #0f172a; }
    body.light-theme .pos-search-input::placeholder { color: #94a3b8; }
    body.light-theme .pos-product-card:hover { background: rgba(15,23,42,0.025); border-color: rgba(15,23,42,0.06); }
    body.light-theme .pos-product-card.selected { background: rgba(37,99,235,0.06); border-color: rgba(37,99,235,0.15); }
    body.light-theme .pos-product-name { color: #0f172a; }
    body.light-theme .pos-product-meta { color: #64748b; }
    body.light-theme .pos-product-price { color: #2563eb; }
    body.light-theme .cart-item { border-bottom-color: rgba(15,23,42,0.04); }
    body.light-theme .cart-item-name { color: #0f172a; }
    body.light-theme .cart-item-price { color: #64748b; }
    body.light-theme .cart-qty-btn { background: #f1f5f9; border-color: rgba(15,23,42,0.12); color: #334155; }
    body.light-theme .cart-qty-btn:hover { background: #e2e8f0; }
    body.light-theme .cart-qty-value { color: #0f172a; }
    body.light-theme .cart-item-subtotal { color: #334155; }
    body.light-theme .cart-empty { color: #94a3b8; }
    body.light-theme .summary-row { color: #64748b; }
    body.light-theme .summary-total-row { border-top-color: rgba(15,23,42,0.1); color: #0f172a; }
    body.light-theme .pos-payment-form { border-top-color: rgba(15,23,42,0.06); }
    body.light-theme .pos-payment-form h3 { color: #475569; }
    body.light-theme .pos-payment-form label { color: #64748b; }
    body.light-theme .pos-payment-form .form-control { background: #f8fafc; border-color: rgba(15,23,42,0.14); color: #0f172a; }
    body.light-theme .pos-payment-form .form-control:focus { border-color: #3b82f6; }
    body.light-theme .pos-payment-form .form-control::placeholder { color: #94a3b8; }
    body.light-theme .btn-cancel { background: #e2e8f0; color: #334155; border-color: rgba(15,23,42,0.1); }

    /* MOBILE */
    @media (max-width: 1024px) {
        .pos-layout { grid-template-columns: 3fr 1fr; }
    }
    @media (max-width: 900px) {
        .pos-layout { grid-template-columns: 1fr; }
        .pos-right-rail { flex-direction: row; }
        .pos-right-rail .pos-panel { flex: 1; }
    }
    @media (max-width: 768px) {
        .pos-layout { grid-template-columns: 1fr; }
        .pos-right-rail { flex-direction: column; }
        .pos-header { flex-direction: column; align-items: stretch; gap: 12px; }
        .pos-header .pos-actions { justify-content: flex-end; }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    let posProducts = [];
    let cart = [];

    // Load products for POS
    async function loadPosProducts() {
        const list = document.getElementById('posProductList');
        list.innerHTML = '<div class="pos-loading"><span class="spinner" aria-hidden="true"></span> Loading products...</div>';
        try {
            const res = await fetch('/api/inventory/products');
            if (!res.ok) throw new Error('Unable to load products');
            posProducts = await res.json();
            filterPosProducts();
        } catch (e) {
            list.innerHTML = '<div class="pos-empty">Unable to load products. Please try again.</div>';
            showToast('Failed to load products', 'error');
        }
    }

    // Filter products via search
    function filterPosProducts() {
        const t = (document.getElementById('posSearchInput')?.value || '').toLowerCase();
        const filtered = posProducts.filter(p => {
            if (!t) return true;
            return (p.name && p.name.toLowerCase().includes(t)) ||
                   (p.sku && p.sku.toLowerCase().includes(t)) ||
                   (p.category && p.category.toLowerCase().includes(t));
        });
        renderProductList(filtered);
    }

    function renderProductList(products) {
        const list = document.getElementById('posProductList');
        if (products.length === 0) {
            list.innerHTML = '<div class="pos-empty">No products found</div>';
            return;
        }
        list.innerHTML = products.map(p => {
            const stockClass = p.current_stock <= 0 ? 'critical' : (p.current_stock <= p.reorder_threshold ? 'low' : 'ok');
            const isInCart = cart.find(item => item.product_id === p.id);
            const disabled = isInCart || p.current_stock <= 0;
            const stockLabel = p.current_stock + ' in stock';
            return `
                <div class="pos-product-card ${isInCart ? 'selected' : ''}" id="pos-product-${p.id}">
                    <div class="pos-product-info">
                        <div class="pos-product-name">${escapeHtml(p.name)}</div>
                        <div class="pos-product-meta">
                            ${escapeHtml(p.sku || '')} ${p.category ? '• ' + escapeHtml(p.category) : ''}
                            <span class="pos-product-stock ${stockClass}">${stockLabel}</span>
                        </div>
                    </div>
                    <div class="pos-product-price">₱${parseFloat(p.price).toFixed(2)}</div>
                    <button type="button" class="pos-add-btn" onclick="addToCart(${p.id})" ${disabled ? 'disabled' : ''}>
                        ${isInCart ? 'Added' : 'Add'}
                    </button>
                </div>
            `;
        }).join('');
    }

    // Cart operations
    function addToCart(productId) {
        const product = posProducts.find(p => p.id === productId);
        if (!product) return;
        const existing = cart.find(item => item.product_id === productId);
        if (existing) {
            if (existing.quantity >= product.current_stock) {
                showToast('Not enough stock available', 'error');
                return;
            }
            existing.quantity++;
        } else {
            cart.push({
                product_id: product.id,
                name: product.name,
                sku: product.sku || '',
                price: parseFloat(product.price),
                quantity: 1,
            });
        }
        updateCartUI();
    }

    function updateCartQuantity(productId, delta) {
        const item = cart.find(item => item.product_id === productId);
        if (!item) return;
        const newQty = item.quantity + delta;
        if (newQty <= 0) {
            removeFromCart(productId);
            return;
        }
        const product = posProducts.find(p => p.id === productId);
        if (product && newQty > product.current_stock) {
            showToast('Not enough stock available', 'error');
            return;
        }
        item.quantity = newQty;
        updateCartUI();
    }

    function removeFromCart(productId) {
        cart = cart.filter(item => item.product_id !== productId);
        updateCartUI();
    }

    function clearCart() {
        cart = [];
        updateCartUI();
        document.getElementById('payment_amount').value = '';
        document.getElementById('checkoutMessage').textContent = '';
        document.getElementById('checkoutMessage').className = 'checkout-message';
    }

    function updateCartUI() {
        // Update cart items list
        const cartItemsEl = document.getElementById('cartItems');
        const cartEmptyEl = document.getElementById('cartEmpty');
        if (cart.length === 0) {
            cartEmptyEl.style.display = 'block';
            // Remove all cart item rows
            cartItemsEl.querySelectorAll('.cart-item').forEach(el => el.remove());
        } else {
            cartEmptyEl.style.display = 'none';
            // Remove existing cart items
            cartItemsEl.querySelectorAll('.cart-item').forEach(el => el.remove());
            // Add cart items
            cart.forEach(item => {
                const subtotal = item.price * item.quantity;
                const div = document.createElement('div');
                div.className = 'cart-item';
                div.innerHTML = `
                    <div class="cart-item-info">
                        <div class="cart-item-name">${escapeHtml(item.name)}</div>
                        <div class="cart-item-price">₱${item.price.toFixed(2)} each</div>
                    </div>
                    <div class="cart-item-controls">
                        <button type="button" class="cart-qty-btn" onclick="updateCartQuantity(${item.product_id}, -1)">−</button>
                        <span class="cart-qty-value">${item.quantity}</span>
                        <button type="button" class="cart-qty-btn" onclick="updateCartQuantity(${item.product_id}, 1)">+</button>
                    </div>
                    <div class="cart-item-subtotal">₱${subtotal.toFixed(2)}</div>
                    <button type="button" class="cart-item-remove" onclick="removeFromCart(${item.product_id})" title="Remove">×</button>
                `;
                cartItemsEl.appendChild(div);
            });
        }

        // Update summary
        updateSummary();
        // Update product list (highlight added items)
        filterPosProducts();
    }

    function updateSummary() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = subtotal * 0.12;
        const total = subtotal + tax;

        document.getElementById('summarySubtotal').textContent = '₱' + subtotal.toFixed(2);
        document.getElementById('summaryTax').textContent = '₱' + tax.toFixed(2);
        document.getElementById('summaryTotal').textContent = '₱' + total.toFixed(2);

        const count = cart.reduce((sum, item) => sum + item.quantity, 0);
        document.getElementById('cartCount').textContent = count + ' item' + (count !== 1 ? 's' : '');

        updateChange();
    }

    function updateChange() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const total = subtotal * 1.12;
        const payment = parseFloat(document.getElementById('payment_amount').value) || 0;
        const change = payment - total;
        const changeEl = document.getElementById('summaryChange');
        changeEl.textContent = '₱' + change.toFixed(2);
        changeEl.className = 'change-amount' + (change < 0 ? ' negative' : '');
    }

    // Process checkout
    async function processCheckout(e) {
        e.preventDefault();
        if (cart.length === 0) {
            showToast('Cart is empty', 'error');
            return;
        }

        const btn = document.getElementById('checkoutBtn');
        const msgEl = document.getElementById('checkoutMessage');
        btn.disabled = true;
        msgEl.textContent = 'Processing...';
        msgEl.className = 'checkout-message';

        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const total = subtotal * 1.12;
        const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;

        if (paymentAmount < total) {
            msgEl.textContent = 'Insufficient payment amount.';
            msgEl.className = 'checkout-message error';
            btn.disabled = false;
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const items = cart.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
            }));
            const res = await fetch('/api/pos/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    items: items,
                    payment_amount: paymentAmount,
                }),
            });

            if (res.ok) {
                const data = await res.json();
                msgEl.textContent = data.message + ' Change: ₱' + (data.change || 0).toFixed(2);
                msgEl.className = 'checkout-message success';
                clearCart();
            } else {
                const data = await res.json().catch(() => null);
                msgEl.textContent = data?.message || 'Checkout failed.';
                msgEl.className = 'checkout-message error';
            }
        } catch (e) {
            msgEl.textContent = 'Connection error. Please try again.';
            msgEl.className = 'checkout-message error';
        }

        btn.disabled = false;
    }

    // Show toast notification
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = `toast ${type}`;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Escape HTML utility function
    function escapeHtml(text) {
        const map = {
            '&': '&',
            '<': '<',
            '>': '>',
            '"': '"',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Initialize
    loadPosProducts();
</script>
<?php $__env->stopPush(); ?>