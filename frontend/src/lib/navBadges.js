/**
 * Cross-page sidebar badge store.
 *
 * Strict rule: a sidebar tab ONLY receives a north-east corner badge when the
 * newly created item actually appears as a new row/card in that page's main
 * table or catalog (never for stat card counters or filter dropdowns).
 * Visiting a tab automatically clears its badge.
 */

const STORAGE_KEY = 'smart-stock-nav-badges';
const EVENT_NAME = 'smart-stock:nav-badges';

/**
 * Central map of true table/catalog-level item connections across sidebar tabs.
 * The caller's current route is always excluded automatically.
 */
export const NAV_CONNECTIONS = {
    // New product row appears in Products table, Stock Inventory receiving table, and POS Checkout catalog
    productAdded: ['/products', '/stock-in', '/checkout'],
    // Creating a new product via Stock-In goes to the Products page Queue List first
    stockNewProductReceived: ['/products'],
    // Completing a queued product on Products lists it into the POS Checkout catalog
    productListed: ['/checkout'],
    // Deactivating / reactivating a product updates its status in Products table and removes/restores it in POS Checkout catalog
    productDeactivated: ['/products', '/checkout'],
    productReactivated: ['/products', '/checkout'],
    // Completing a POS sale creates a new receipt row in Transactions table
    saleCompleted: ['/transactions'],
    // New active restock suggestions appear in Overview's Restock Suggestions table
    suggestionAdded: ['/dashboard'],

    // Removing a supplier deactivates linked products across Stock Inventory, Products, and POS Checkout
    supplierRemoved: ['/stock-in', '/products', '/checkout'],

    // Pages whose additions only affect their own table (or dropdowns/stat cards elsewhere)
    categoryAdded: [],
    stockReceived: [],
    supplierAdded: [],
    transactionAdded: [],
    userAdded: [],
};

export function readNavBadges() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) return {};
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch {
        return {};
    }
}

function writeNavBadges(badges) {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(badges));
    } catch {
        // Ignore storage errors (e.g. private browsing restrictions).
    }
    window.dispatchEvent(new CustomEvent(EVENT_NAME, { detail: badges }));
}

/**
 * Update the NE badge on all connected sidebar tabs for the given event key.
 * Accepts positive deltas (e.g. +1 for added/reactivated items) and negative
 * deltas (e.g. -1 for deactivated items). The currently active route is
 * skipped so the user only sees badges on OTHER connected pages.
 */
export function notifyConnectedNav(eventKey, count = 1, currentPath = window.location.pathname) {
    const targets = NAV_CONNECTIONS[eventKey];
    const delta = Number(count);
    if (!Array.isArray(targets) || targets.length === 0 || !Number.isFinite(delta) || delta === 0) return;

    const next = { ...readNavBadges() };
    let changed = false;

    for (const route of targets) {
        if (currentPath && currentPath.startsWith(route)) {
            continue;
        }
        const updated = (Number(next[route]) || 0) + delta;
        if (updated === 0) {
            if (next[route] !== undefined) {
                delete next[route];
                changed = true;
            }
        } else {
            next[route] = updated;
            changed = true;
        }
    }

    if (changed) {
        writeNavBadges(next);
    }
}

/**
 * Clear the badge for a sidebar route once the user visits it.
 */
export function clearNavBadge(routePath) {
    if (!routePath) return;
    const current = readNavBadges();
    if (!current[routePath]) return;
    const next = { ...current };
    delete next[routePath];
    writeNavBadges(next);
}

/**
 * Clear unread badges on all sidebar tabs connected to the given event key
 * (used when the user clears activity or deletes all records on the source page).
 */
export function clearConnectedNav(eventKey) {
    const targets = NAV_CONNECTIONS[eventKey];
    if (!Array.isArray(targets) || targets.length === 0) return;

    const current = readNavBadges();
    const next = { ...current };
    let changed = false;

    for (const route of targets) {
        if (next[route]) {
            delete next[route];
            changed = true;
        }
    }

    if (changed) {
        writeNavBadges(next);
    }
}

/**
 * Subscribe to cross-page badge updates. Returns an unsubscribe function.
 */
export function subscribeNavBadges(listener) {
    const handleEvent = (event) => {
        listener(event.detail ?? readNavBadges());
    };
    window.addEventListener(EVENT_NAME, handleEvent);
    return () => window.removeEventListener(EVENT_NAME, handleEvent);
}
