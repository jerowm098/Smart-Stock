import { post } from './api.js';

const STORAGE_KEY = 'smart-stock-price-hints';

function readAll() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : {};
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch {
        return {};
    }
}

function writeAll(map) {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(map));
    } catch {
        // Ignore storage quota / privacy errors.
    }
}

export function getPriceHint(productId) {
    if (productId == null) return '';
    return readAll()[String(productId)] ?? '';
}

export function setPriceHint(productId, formattedPrice) {
    if (productId == null || !formattedPrice) return;
    const map = readAll();
    map[String(productId)] = formattedPrice;
    writeAll(map);
}

export function clearPriceHint(productId) {
    if (productId == null) return;
    const map = readAll();
    if (map[String(productId)] !== undefined) {
        delete map[String(productId)];
        writeAll(map);
    }
}

/**
 * Pre-fetch and cache an AI price suggestion in the background as soon as a
 * new product is received on Stock Inventory, so the Products -> Add Price
 * modal already has the hint ready with zero wait time.
 */
export function prefetchPriceHint(productId, name, unit = 'piece') {
    const trimmed = String(name ?? '').trim();
    if (productId == null || trimmed.length < 2) {
        return Promise.resolve('');
    }

    return post('/api/inventory/suggest', { name: trimmed, unit: unit || 'piece' })
        .then((res) => {
            const raw = res?.suggestion?.price;
            if (raw != null && raw !== '') {
                const num = Number(raw);
                if (Number.isFinite(num) && num > 0) {
                    const formatted = Math.max(2, Math.round(num)).toFixed(2);
                    setPriceHint(productId, formatted);
                    return formatted;
                }
            }
            return '';
        })
        .catch(() => '');
}
