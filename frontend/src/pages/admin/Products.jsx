import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { usePageLoadbar } from '../../components/PageLoadbar.jsx';
import Modal from '../../components/Modal.jsx';
import ConfirmDialog from '../../components/ConfirmDialog.jsx';
import InfoTip from '../../components/InfoTip.jsx';
import Tooltip from '../../components/Tooltip.jsx';
import { FieldError, Toast } from '../../components/Feedback.jsx';
import MessageDialog from '../../components/MessageDialog.jsx';
import { del, get, post, put, validationMessages } from '../../lib/api.js';
import { formatPeso, timeAgo } from '../../lib/format.js';
import { clearConnectedNav, notifyConnectedNav } from '../../lib/navBadges.js';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as XLSX from 'xlsx';

const EMPTY_PRODUCT = {
    name: '',
    sku: '',
    category: '',
    price: '',
    current_stock: '',
    reorder_threshold: '',
    unit_choice: '',
    unit_other: '',
};

// Units of measure for the product (mirrors the Stock-In receiving units).
const UNITS = [
    'piece', 'box', 'sack', 'bag', 'pack', 'set', 'roll', 'bundle',
    'kilogram', 'gram', 'liter', 'milliliter', 'meter', 'foot', 'gallon',
    'can', 'bottle', 'tube', 'sheet',
];

const ADJUST_REASONS = [
    { value: 'damaged', label: 'Damaged items' },
    { value: 'lost', label: 'Lost / missing' },
    { value: 'internal_transfer', label: 'Internal transfer' },
    { value: 'correction', label: 'Count correction' },
];

// Recent Activity: map an action key to a color tone + a small inline SVG icon.
const ACTIVITY_TONE = {
    product_created: 'ok',
    product_updated: 'info',
    product_deactivated: 'danger',
    product_reactivated: 'ok',
    stock_received: 'ok',
    stock_adjusted: 'warn',
    sale_completed: 'info',
};

// Short verb shown in the collapsed row summary (e.g. "Edited Cordless Drill 18V").
const ACTIVITY_VERB = {
    product_created: 'Added',
    product_updated: 'Edited',
    product_deactivated: 'Deactivated',
    product_reactivated: 'Reactivated',
    stock_received: 'Received stock for',
    stock_adjusted: 'Adjusted',
    sale_completed: 'Completed sale #',
};

// Highlight the search term inside a string — returns an array of plain/marked spans.
function highlight(text, needle) {
    if (!needle) return text;
    const idx = text.toLowerCase().indexOf(needle.toLowerCase());
    if (idx === -1) return text;
    return (
        <>
            {text.slice(0, idx)}
            <mark className="search-highlight">{text.slice(idx, idx + needle.length)}</mark>
            {text.slice(idx + needle.length)}
        </>
    );
}

// Build the short one-line summary for a feed row. Prefers "Verb subject_name";
// falls back to the text before the first ":" of the full description.
function activitySummary(item) {
    const verb = ACTIVITY_VERB[item.action];
    if (verb && item.subject_name) {
        return `${verb} ${item.subject_name}`;
    }
    const full = String(item.description ?? '');
    const colon = full.indexOf(':');
    return colon > 0 ? full.slice(0, colon) : full;
}

const peso = (value) => `₱${(Number(value) || 0).toFixed(2)}`;

/**
 * The "changes made" lines for the detail popup.
 *
 * Newer log rows carry an explicit `meta.changes` array written by the backend
 * ActivityLogger. Older rows (logged before that array existed) only have the
 * raw meta keys, so we derive an equivalent list here — every action type gets
 * a change list, not just product edits.
 */
function activityChanges(item) {
    const explicit = item?.meta?.changes;
    if (Array.isArray(explicit) && explicit.length > 0) {
        return explicit.map((change) => change.text ?? String(change));
    }

    const meta = item?.meta ?? {};
    const lines = [];

    switch (item?.action) {
        case 'product_created':
            if (meta.sku) lines.push(`SKU ${meta.sku}`);
            lines.push(`Category ${meta.category || 'Uncategorized'}`);
            if (meta.stock != null) lines.push(`Starting stock ${meta.stock}`);
            break;
        case 'product_deactivated':
            lines.push('Status Active → Deactivated');
            lines.push('Hidden from Product List and POS catalog');
            break;
        case 'product_reactivated':
            lines.push('Status Deactivated → Active');
            lines.push('Back in Product List and POS catalog');
            break;
        case 'stock_received':
            if (meta.stock_before != null && meta.stock_after != null) {
                lines.push(`Stock ${meta.stock_before} → ${meta.stock_after}`);
            }
            if (meta.quantity != null) lines.push(`Received ${meta.quantity} ${meta.unit ?? 'unit'}`);
            if (meta.piece_delta) lines.push(`Pieces ${meta.piece_delta > 0 ? '+' : ''}${meta.piece_delta}`);
            break;
        case 'stock_adjusted':
            if (meta.stock_before != null && meta.stock_after != null) {
                lines.push(`Stock ${meta.stock_before} → ${meta.stock_after}`);
            }
            if (meta.delta != null) lines.push(`Delta ${meta.delta > 0 ? '+' : ''}${meta.delta}`);
            if (meta.reason_label || meta.reason) lines.push(`Reason ${meta.reason_label ?? meta.reason}`);
            break;
        case 'sale_completed':
            if (meta.item_count != null) lines.push(`Items sold ${meta.item_count}`);
            if (meta.total_amount != null) lines.push(`Total ${peso(meta.total_amount)}`);
            break;
        default:
            break;
    }

    return lines;
}

const ACTIVITY_ICONS = {
    product_created: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 5v14M5 12h14" />
        </svg>
    ),
    product_updated: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
        </svg>
    ),
    product_deactivated: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M8 12h8" />
        </svg>
    ),
    product_reactivated: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M20 6 9 17l-5-5" />
        </svg>
    ),
    stock_received: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 19V5M5 12l7-7 7 7" />
        </svg>
    ),
    stock_adjusted: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 4A8 8 0 0 1 20 12" />
            <path d="M21.72 9.79 20 12 18.28 9.79" />
            <path d="M18.93 16A8 8 0 0 1 8 18.93" />
            <path d="M9.05 21.53 8 18.93 10.77 18.54" />
            <path d="M5.07 16A8 8 0 0 1 8 5.07" />
            <path d="M5.23 4.68 8 5.07 6.95 7.67" />
        </svg>
    ),
    sale_completed: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="9" cy="21" r="1" />
            <circle cx="20" cy="21" r="1" />
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
        </svg>
    ),
    default: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8h.01M11 12h1v4h1" />
        </svg>
    ),
};

// Format a money input string to a fixed 2-decimal value (e.g. "2.5" -> "2.50",
// "" -> ""). Used on blur and when the AI suggestion lands so prices always show
// a proper peso value with cents.
function formatPriceInput(value) {
    const raw = String(value ?? '').trim();
    if (raw === '') {
        return '';
    }
    const num = Number(raw.replace(/[^0-9.]/g, ''));
    if (!Number.isFinite(num)) {
        return '';
    }
    return num.toFixed(2);
}

function stockState(product) {
    if (product.is_active === false || product.is_active === 0) {
        if (product.last_supplier_active === false) {
            return { label: 'Inactive supplier', className: 'pill-danger', key: 'deactivated' };
        }
        return { label: 'Deactivated', className: 'pill-danger', key: 'deactivated' };
    }

    if (Number(product.current_stock) <= 0) {
        return { label: 'Out of stock', className: 'pill-danger', key: 'out' };
    }

    if (Number(product.current_stock) <= Number(product.reorder_threshold)) {
        return { label: 'Low stock', className: 'pill-warn', key: 'low' };
    }

    return { label: 'In stock', className: 'pill-ok', key: 'in' };
}

export default function Products() {
    const [products, setProducts] = useState([]);
    const [suppliers, setSuppliers] = useState([]);
    const [categoriesList, setCategoriesList] = useState([]);
    const [defectCount, setDefectCount] = useState(null);
    const [activity, setActivity] = useState([]);
    // The activity row whose detail popup is currently open (null = closed).
    const [activityDetail, setActivityDetail] = useState(null);
    // Recent Activity & Product Queue pagination (3 rows per page).
    const [activityPage, setActivityPage] = useState(1);
    const [queuePage, setQueuePage] = useState(1);
    const [editFromQueue, setEditFromQueue] = useState(false);
    const QUEUE_PER_PAGE = 3;
    const [clearConfirm, setClearConfirm] = useState(false);
    const [clearing, setClearing] = useState(false);
    const [clearError, setClearError] = useState(null);
    const [activityOptionsOpen, setActivityOptionsOpen] = useState(false);
    const activityOptionsRef = useRef(null);
    const [productsOptionsOpen, setProductsOptionsOpen] = useState(false);
    const productsOptionsRef = useRef(null);
    const [seeding, setSeeding] = useState(false);
    const [deleteAllConfirm, setDeleteAllConfirm] = useState(false);
    const [deletingAll, setDeletingAll] = useState(false);
    const [deleteAllError, setDeleteAllError] = useState(null);
    const [emptyNotice, setEmptyNotice] = useState(null);
    const ACTIVITY_PER_PAGE = 3;
    const [loading, setLoading] = useState(true);
    usePageLoadbar(loading);
    const [error, setError] = useState(null);
    const [message, setMessage] = useState(null);
    const [search, setSearch] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [activeFilter, setActiveFilter] = useState('');
    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [page, setPage] = useState(1);
    const PER_PAGE = 6;

    // Id of the product just created — used to scroll to + flash-highlight its
    // table row after the add modal closes.
    const [highlightId, setHighlightId] = useState(null);

    // Transient "+N" / "-N" stock-change badges keyed by product id. Populated by
    // comparing each reload against the previous stock counts, then auto-cleared.
    const [stockDeltas, setStockDeltas] = useState({});
    const prevStockRef = useRef(new Map());
    const hasLoadedRef = useRef(false);
    const stockDeltaTimer = useRef(null);

    const [addOpen, setAddOpen] = useState(false);
    const [addForm, setAddForm] = useState(EMPTY_PRODUCT);
    const [saving, setSaving] = useState(false);
    const [formError, setFormError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState([]);
    // AI suggestions are shown as *placeholder* (ghost) text only — they never
    // become real field values, so they vanish the moment the user clears the
    // product name and refresh on the next name they type.
    const [suggestions, setSuggestions] = useState({});
    // Field-specific price suggestion based on (name + unit of measure)
    const [priceSuggestion, setPriceSuggestion] = useState('');
    // Auto-suggest reorder threshold as half of starting stock
    const [reorderSuggestion, setReorderSuggestion] = useState('');
    // True while the AI request is in flight, so the suggested fields can show a
    // loading/shimmer state to indicate work is happening.
    const [suggesting, setSuggesting] = useState(false);
    const [priceSuggesting, setPriceSuggesting] = useState(false);
    // Tracks the name we last suggested for so we don't re-fire needlessly.
    const lastAutoSuggestedName = useRef('');
    const currentNameRef = useRef('');
    const suggestTimer = useRef(null);
    const priceSuggestTimer = useRef(null);

    const [editProduct, setEditProduct] = useState(null);
    const [editForm, setEditForm] = useState(null);
    const [editSaving, setEditSaving] = useState(false);
    const [editError, setEditError] = useState(null);

    const [adjustProduct, setAdjustProduct] = useState(null);
    const [adjustForm, setAdjustForm] = useState({ delta: '', reason: 'correction', reason_note: '' });
    const [adjustSaving, setAdjustSaving] = useState(false);
    const [adjustError, setAdjustError] = useState(null);

    const [deactivateProduct, setDeactivateProduct] = useState(null);
    const [deactivateSaving, setDeactivateSaving] = useState(false);
    const [deactivateError, setDeactivateError] = useState(null);

    const [reactivateProduct, setReactivateProduct] = useState(null);
    const [reactivateSaving, setReactivateSaving] = useState(false);
    const [reactivateError, setReactivateError] = useState(null);

    const load = useCallback(async (silent = false) => {
        // A "silent" reload (after add/edit/adjust) refreshes data in place
        // without flipping to the full loading phase — so the page never jumps
        // to the top or unmounts the current content mid-action.
        if (!silent) {
            setLoading(true);
        }
        setError(null);

        // The backend occasionally answers 500 on a cold start (e.g. the app
        // key/config not ready yet on the very first request). Retry a couple of
        // times before surfacing the error, so a transient hiccup never flashes.
        const fetchProducts = async (attempt = 0) => {
            try {
                const [rows, defects, feed, supplierRows, categoryRes] = await Promise.all([
                    get('/api/inventory/products'),
                    get('/api/inventory/defects').catch(() => null),
                    // Products page feed is scoped to product actions only —
                    // stock receipts live on Stock Inventory, sales on Transactions.
                    get('/api/activity/recent', {
                        limit: 12,
                        actions: 'product_created,product_updated,product_deactivated,product_reactivated,stock_adjusted',
                    }).catch(() => null),
                    get('/api/suppliers/active').catch(() => []),
                    get('/api/categories').catch(() => null),
                ]);
                return { rows, defects, feed, supplierRows, categoryRes };
            } catch (err) {
                const retryable = !err.status || err.status >= 500;
                if (retryable && attempt < 2) {
                    await new Promise((resolve) => setTimeout(resolve, 300 * (attempt + 1)));
                    return fetchProducts(attempt + 1);
                }
                throw err;
            }
        };

        try {
            const { rows, defects, feed, supplierRows, categoryRes } = await fetchProducts();
            setProducts(Array.isArray(rows) ? rows : []);
            setSuppliers(Array.isArray(supplierRows) ? supplierRows : []);
            const rawCats = categoryRes?.categories ?? categoryRes;
            setCategoriesList(Array.isArray(rawCats) ? rawCats : []);
            if (defects && typeof defects.defect_count === 'number') {
                setDefectCount(defects.defect_count);
            }
            setActivity(Array.isArray(feed) ? feed : []);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    // Silent variant used after mutations so the current view stays in place.
    const refresh = useCallback(() => load(true), [load]);

    useEffect(() => {
        load();
    }, [load]);

    // Options dropdown (kebab) on the Recent Activity card header: closes on
    // outside click and Escape, same behaviour as the topbar menus.
    useEffect(() => {
        if (!activityOptionsOpen && !productsOptionsOpen) {
            return;
        }

        const onDown = (event) => {
            if (activityOptionsRef.current && !activityOptionsRef.current.contains(event.target)) {
                setActivityOptionsOpen(false);
            }
            if (productsOptionsRef.current && !productsOptionsRef.current.contains(event.target)) {
                setProductsOptionsOpen(false);
            }
        };

        const onKey = (event) => {
            if (event.key === 'Escape') {
                setActivityOptionsOpen(false);
                setProductsOptionsOpen(false);
            }
        };

        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [activityOptionsOpen, productsOptionsOpen]);

    // After each successful load, diff current stock against the previous
    // snapshot to surface a transient "+N" / "-N" badge on rows that changed.
    useEffect(() => {
        if (loading) {
            return;
        }

        const prev = prevStockRef.current;
        const next = new Map();
        const deltas = {};

        products.forEach((product) => {
            const id = product.id;
            const stock = Number(product.current_stock) || 0;
            next.set(id, stock);

            if (hasLoadedRef.current && prev.has(id)) {
                const diff = stock - Number(prev.get(id));
                if (diff !== 0) {
                    deltas[id] = diff;
                }
            }
        });

        prevStockRef.current = next;
        hasLoadedRef.current = true;

        if (Object.keys(deltas).length === 0) {
            return;
        }

        setStockDeltas(deltas);
        clearTimeout(stockDeltaTimer.current);
        stockDeltaTimer.current = setTimeout(() => setStockDeltas({}), 2600);
    }, [products, loading]);

    const queuedProducts = useMemo(
        () => products.filter((product) => product.is_listed === false && !product.is_queue_dismissed),
        [products],
    );

    const listedProducts = useMemo(
        () => products.filter((product) => product.is_listed !== false),
        [products],
    );

    const queueLastPage = Math.max(1, Math.ceil(queuedProducts.length / QUEUE_PER_PAGE));
    const queueSafePage = Math.min(queuePage, queueLastPage);
    const pagedQueue = useMemo(
        () => queuedProducts.slice((queueSafePage - 1) * QUEUE_PER_PAGE, queueSafePage * QUEUE_PER_PAGE),
        [queuedProducts, queueSafePage],
    );

    useEffect(() => {
        setQueuePage(1);
    }, [queuedProducts.length]);

    const categories = useMemo(() => {
        const set = new Set();
        listedProducts.forEach((product) => {
            if (product.category) {
                set.add(product.category);
            }
        });
        return Array.from(set).sort((a, b) => a.localeCompare(b));
    }, [listedProducts]);

    const filtered = useMemo(() => {
        const needle = search.trim().toLowerCase();

        return listedProducts.filter((product) => {
            if (needle) {
                const matches = [product.name, product.sku, product.category]
                    .filter(Boolean)
                    .some((value) => String(value).toLowerCase().includes(needle));
                if (!matches) {
                    return false;
                }
            }

            if (categoryFilter && product.category !== categoryFilter) {
                return false;
            }

            if (activeFilter) {
                const isActive = Boolean(product.is_active);
                if (activeFilter === 'active' ? !isActive : isActive) {
                    return false;
                }
            }

            if (statusFilter) {
                const state = stockState(product);
                if (state.key !== statusFilter) {
                    return false;
                }
            }

            return true;
        });
    }, [products, search, categoryFilter, activeFilter, statusFilter]);

    const hasActiveFilters = Boolean(search.trim() || categoryFilter || statusFilter || activeFilter);

    const clearFilters = () => {
        setSearch('');
        setCategoryFilter('');
        setStatusFilter('');
        setActiveFilter('');
    };

    const sorted = useMemo(() => {
        if (!sortBy) {
            return filtered;
        }

        const dir = sortDir === 'asc' ? 1 : -1;
        return [...filtered].sort((a, b) => {
            let av = a[sortBy];
            let bv = b[sortBy];

            // Status comparison: Deactivated (0) -> Out of stock (1) -> Low stock (2) -> In stock (3)
            if (sortBy === 'status') {
                const rank = (p) => {
                    const key = stockState(p).key;
                    if (key === 'deactivated') return 0;
                    if (key === 'out') return 1;
                    if (key === 'low') return 2;
                    return 3;
                };
                return (rank(a) - rank(b)) * dir;
            }

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

    // Simple pagination over the sorted/filtered list (7 rows per page).
    const lastPage = Math.max(1, Math.ceil(sorted.length / PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const paged = useMemo(
        () => sorted.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE),
        [sorted, safePage],
    );

    // Reset to the first page whenever the filters/sort change, so we never
    // strand the user on an out-of-range page.
    useEffect(() => {
        setPage(1);
    }, [search, categoryFilter, statusFilter, activeFilter, sortBy, sortDir]);

    // After adding a product: jump to the page holding its row, smooth-scroll
    // to it and play a flash-highlight animation. If active filters would hide
    // the row, clear them first so the row is always reachable.
    useEffect(() => {
        if (loading || highlightId == null) {
            return undefined;
        }

        const idx = sorted.findIndex((p) => p.id === highlightId);
        if (idx === -1) {
            clearFilters();
            return undefined;
        }

        const targetPage = Math.floor(idx / PER_PAGE) + 1;
        if (targetPage !== safePage) {
            setPage(targetPage);
            return undefined;
        }

        const row = document.querySelector(`tr[data-product-id="${highlightId}"]`);
        if (!row) {
            return undefined;
        }

        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.add('row-flash');

        // Park the "+1 new" badge on the column BOUNDARY nearest the row's
        // horizontal center, so it sits in the free space BETWEEN column
        // headers instead of visually aligning with one (e.g. Price).
        const cells = row.cells;
        if (cells.length > 0) {
            const mid = row.clientWidth / 2;
            let acc = 0;
            let best = null;
            for (const cell of cells) {
                const boundary = acc + cell.offsetWidth;
                if (best === null || Math.abs(boundary - mid) < Math.abs(best - mid)) {
                    best = boundary;
                }
                acc = boundary;
            }
            if (best != null) {
                row.style.setProperty('--badge-x', `${best}px`);
            }
        }

        const timer = setTimeout(() => {
            row.classList.remove('row-flash');
            setHighlightId(null);
        }, 2400);

        return () => {
            clearTimeout(timer);
            row.classList.remove('row-flash');
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [loading, highlightId, sorted, safePage]);

    // Recent Activity pagination (3 rows per page).
    const activityLastPage = Math.max(1, Math.ceil(activity.length / ACTIVITY_PER_PAGE));
    const activitySafePage = Math.min(activityPage, activityLastPage);
    const pagedActivity = useMemo(
        () => activity.slice((activitySafePage - 1) * ACTIVITY_PER_PAGE, activitySafePage * ACTIVITY_PER_PAGE),
        [activity, activitySafePage],
    );

    // Keep the activity page in range when the feed shrinks.
    useEffect(() => {
        setActivityPage(1);
    }, [activity.length]);

    const stats = useMemo(() => {
        const active = listedProducts.filter((product) => product.is_active);
        const out = active.filter((product) => Number(product.current_stock) <= 0);
        const low = active.filter(
            (product) =>
                Number(product.current_stock) > 0 &&
                Number(product.current_stock) <= Number(product.reorder_threshold),
        );

        return { total: active.length, low: low.length, out: out.length };
    }, [listedProducts]);

    // Fetch AI suggestions for the given name. These are shown ONLY as ghost
    // placeholder text — they are never committed to the form fields, so they
    // disappear as soon as the admin clears the product name.
    // Field-specific: only Item Code (sku) and Category are suggested from product name.
    const runSuggest = useCallback(async (name) => {
        const clean = name.trim();
        if (clean.length < 3) {
            return;
        }

        setFormError(null);
        setSuggesting(true);

        try {
            const response = await post('/api/inventory/suggest', { name: clean });
            const s = response.suggestion || {};

            // Ignore stale responses: only apply if the box still holds this name.
            if (currentNameRef.current.trim() !== clean) {
                return;
            }

            setSuggestions({
                sku: s.sku || '',
                category: s.category || '',
            });
            lastAutoSuggestedName.current = clean;
        } catch (err) {
            setFormError(err.message);
        } finally {
            // Clear the loading state only if this run is still the active name.
            if (currentNameRef.current.trim() === clean) {
                setSuggesting(false);
            }
        }
    }, []);

    const runPriceSuggest = useCallback(async (name, unit) => {
        const cleanName = name.trim();
        const cleanUnit = (unit || '').trim();
        if (cleanName.length < 3 || !cleanUnit) {
            setPriceSuggestion('');
            setPriceSuggesting(false);
            return;
        }

        setPriceSuggesting(true);

        try {
            const response = await post('/api/inventory/suggest', { name: cleanName, unit: cleanUnit });
            const s = response.suggestion || {};
            if (currentNameRef.current.trim() !== cleanName) {
                return;
            }
            if ((addForm.unit_choice || '').trim() !== cleanUnit) {
                return;
            }
            if (s.price != null && s.price !== '') {
                const raw = String(s.price).replace(/[^0-9.]/g, '');
                if (raw === '' || raw === '.') {
                    setPriceSuggestion('');
                } else {
                    let num = Number(raw);
                    const unitLower = cleanUnit.toLowerCase();
                    if ((unitLower === 'piece' || unitLower === 'pc' || unitLower === 'pcs') && num < 2) {
                        num = 2;
                    }
                    if (!Number.isFinite(num) || num < 2) {
                        num = 2;
                    }
                    num = Math.round(num);
                    setPriceSuggestion(`${num.toFixed(2)}`);
                }
            } else {
                setPriceSuggestion('');
            }
        } catch (err) {
            setPriceSuggestion('');
        } finally {
            if (currentNameRef.current.trim() === cleanName && (addForm.unit_choice || '').trim() === cleanUnit) {
                setPriceSuggesting(false);
            }
        }
    }, [addForm.unit_choice]);

    // Auto-suggest while typing: debounce the product name, then call the AI.
    // Clearing the name wipes all ghost suggestions so the fields go blank
    // again; typing a fresh word re-fires the suggestion.
    useEffect(() => {
        currentNameRef.current = addForm.name;

        if (!addOpen) {
            return undefined;
        }

        const name = addForm.name.trim();

        if (name.length < 3) {
            clearTimeout(suggestTimer.current);
            setSuggestions({});
            setSuggesting(false);
            lastAutoSuggestedName.current = '';
            setPriceSuggestion('');
            setPriceSuggesting(false);
            clearTimeout(priceSuggestTimer.current);
            return undefined;
        }

        // Don't re-fire for the same value we just suggested for.
        if (name !== lastAutoSuggestedName.current) {
            // Name changed: mark the fields as "working" right away so the user sees
            // something is happening during the debounce + request window.
            setSuggesting(true);

            clearTimeout(suggestTimer.current);
            suggestTimer.current = setTimeout(() => {
                runSuggest(name);
            }, 900);
        }

        // Price suggestion based on (name + unit)
        clearTimeout(priceSuggestTimer.current);
        if (addForm.unit_choice) {
            setPriceSuggesting(true);
            priceSuggestTimer.current = setTimeout(() => {
                runPriceSuggest(name, addForm.unit_choice);
            }, 700);
        } else {
            setPriceSuggestion('');
            setPriceSuggesting(false);
        }

        return () => {
            clearTimeout(suggestTimer.current);
            clearTimeout(priceSuggestTimer.current);
        };
    }, [addForm.name, addOpen, addForm.unit_choice, runSuggest, runPriceSuggest]);

    // Suggest reorder threshold as half of starting stock
    useEffect(() => {
        if (!addOpen) {
            setReorderSuggestion('');
            return;
        }

        const raw = String(addForm.current_stock ?? '').trim();
        if (raw === '') {
            setReorderSuggestion('');
            return;
        }

        const num = Number(raw.replace(/[^0-9.-]/g, ''));
        if (!Number.isFinite(num) || num < 0) {
            setReorderSuggestion('');
            return;
        }

        if (num === 0) {
            setReorderSuggestion('0');
            return;
        }

        const half = Math.floor(num / 2);
        setReorderSuggestion(String(half));
    }, [addForm.current_stock, addOpen]);

    // Merge ghost suggestions into any field the admin left blank, so the
    // preview values are used on submit even though they were shown as
    // placeholders. Values the admin typed always win.
    //
    // NOTE: Only Item Code (SKU) and Category are AI-suggested from the product name.
    // Price, Reorder threshold, Unit of measure and Starting stock are manual only.
    function mergeSuggestions() {
        return {
            ...addForm,
            sku: addForm.sku || suggestions.sku || '',
            category: addForm.category || suggestions.category || '',
            price: addForm.price,
            current_stock: addForm.current_stock,
            reorder_threshold: addForm.reorder_threshold,
            receiving_unit: addForm.unit_choice === 'other' ? (addForm.unit_other.trim() || null) : addForm.unit_choice,
        };
    }

    // Accept the ghost suggestion for ONE field (used when the admin hits Tab
    // inside that field). Only fills it if the admin hasn't typed anything yet.
    function acceptSuggestion(field) {
        if (field === 'price') {
            const value = priceSuggestion;
            if (value == null || value === '') {
                return;
            }
            setAddForm((c) => {
                if (String(c[field] ?? '').trim() !== '') {
                    return c;
                }
                return { ...c, [field]: value };
            });
            return;
        }

        if (field === 'reorder_threshold') {
            const value = reorderSuggestion;
            if (value == null || value === '') {
                return;
            }
            setAddForm((c) => {
                if (String(c[field] ?? '').trim() !== '') {
                    return c;
                }
                return { ...c, [field]: value };
            });
            return;
        }

        const value = suggestions[field];
        if (value == null || value === '') {
            return;
        }

        setAddForm((c) => {
            // Never overwrite something the admin already typed.
            if (String(c[field] ?? '').trim() !== '') {
                return c;
            }
            return { ...c, [field]: value };
        });
    }

    // Accept ALL ghost suggestions at once (Tab on the product-name field).
    // Field-specific: only Item Code and Category are suggested.
    function acceptAllSuggestions() {
        setAddForm((c) => ({
            ...c,
            sku: c.sku || suggestions.sku || '',
            category: c.category || suggestions.category || '',
        }));
    }

    // Tab-to-autocomplete: if the field is empty and has a ghost suggestion,
    // Tab commits it (and lets focus move on naturally). Otherwise Tab behaves
    // as usual. Typed values are never overwritten.
    function handleSuggestionTab(field) {
        return (event) => {
            if (event.key !== 'Tab' || event.shiftKey) {
                return;
            }

            if (field !== 'sku' && field !== 'category' && field !== 'price' && field !== 'reorder_threshold') {
                return;
            }

            if (field === 'price') {
                const typed = String(addForm[field] ?? '').trim() !== '';
                if (typed || !priceSuggestion) {
                    return;
                }
                acceptSuggestion(field);
                return;
            }

            if (field === 'reorder_threshold') {
                const typed = String(addForm[field] ?? '').trim() !== '';
                if (typed || !reorderSuggestion) {
                    return;
                }
                acceptSuggestion(field);
                return;
            }

            const typed = String(addForm[field] ?? '').trim() !== '';

            if (typed || !suggestions[field]) {
                return;
            }

            // Don't preventDefault: fill in the value AND move focus forward.
            acceptSuggestion(field);
        };
    }

    // Tab on the product-name field accepts ALL suggestions in one go (SKU + Category only).
    function handleNameTab(event) {
        if (event.key !== 'Tab' || event.shiftKey) {
            return;
        }

        const hasAny = ['sku', 'category'].some((key) => suggestions[key]);
        if (!hasAny) {
            return;
        }

        acceptAllSuggestions();
    }

    async function handleAdd(event) {
        event.preventDefault();
        setSaving(true);
        setFormError(null);
        setFieldErrors([]);

        try {
            const merged = mergeSuggestions();

            const response = await post('/api/inventory/add', {
                ...merged,
                sku: String(merged.sku || '').trim() || null,
                category: merged.category || null,
                price: Number(merged.price || 0),
                current_stock: Number(merged.current_stock || 0),
                reorder_threshold: Number(merged.reorder_threshold || 0),
            });

            setMessage(response.message);
            setAddForm(EMPTY_PRODUCT);
            setSuggestions({});
            setPriceSuggestion('');
            setSuggesting(false);
            setPriceSuggesting(false);
            lastAutoSuggestedName.current = '';
            // Remember the new product so the list view can scroll to and
            // flash-highlight its row.
            setHighlightId(response.product?.id ?? null);
            setAddOpen(false);
            notifyConnectedNav('productAdded', 1);
            await refresh();
        } catch (err) {
            setFormError(err.message);
            setFieldErrors(validationMessages(err));
        } finally {
            setSaving(false);
        }
    }

    // Full reset of the Add-product form + suggestion state. Called on Cancel,
    // close (X), and overlay dismiss so reopening starts clean.
    function resetAdd() {
        clearTimeout(suggestTimer.current);
        clearTimeout(priceSuggestTimer.current);
        setAddOpen(false);
        setAddForm(EMPTY_PRODUCT);
        setSuggestions({});
        setPriceSuggestion('');
        setReorderSuggestion('');
        setSuggesting(false);
        setPriceSuggesting(false);
        setFormError(null);
        setFieldErrors([]);
        lastAutoSuggestedName.current = '';
    }

    function openEdit(product, fromQueue = false) {
        setEditProduct(product);
        setEditFromQueue(fromQueue);
        const rawCategory = product.category ?? '';
        setEditForm({
            name: product.name ?? '',
            sku: product.sku ?? '',
            category: fromQueue && rawCategory === 'Uncategorized' ? '' : rawCategory,
            price: fromQueue && Number(product.price) <= 0 ? '' : product.price,
            current_stock: product.current_stock,
            reorder_threshold: fromQueue && Number(product.reorder_threshold) <= 0 ? '' : product.reorder_threshold,
            receiving_unit: product.receiving_unit ?? 'piece',
            supplier_id: product.last_supplier_id && product.last_supplier_active !== false
                ? String(product.last_supplier_id)
                : '',
        });
        setEditError(null);
    }

    async function handleEdit(event) {
        event.preventDefault();

        if (editFromQueue) {
            const nameVal = String(editForm.name ?? '').trim();
            const skuVal = String(editForm.sku ?? '').trim();
            const categoryVal = String(editForm.category ?? '').trim();
            const unitVal = String(editForm.receiving_unit ?? '').trim();
            const priceVal = Number(editForm.price);
            const stockVal = editForm.current_stock === '' ? NaN : Number(editForm.current_stock);
            const reorderVal = editForm.reorder_threshold === '' ? NaN : Number(editForm.reorder_threshold);

            if (!nameVal || !skuVal || !categoryVal || !unitVal) {
                setEditError('Please fill in all required fields before adding this product to the Product List.');
                return;
            }
            if (!Number.isFinite(priceVal) || priceVal <= 0) {
                setEditError('Please enter a valid selling price greater than ₱0.00.');
                return;
            }
            if (!Number.isFinite(stockVal) || stockVal < 0) {
                setEditError('Please enter a valid current stock count.');
                return;
            }
            if (!Number.isFinite(reorderVal) || reorderVal < 0) {
                setEditError('Please enter a valid reorder threshold.');
                return;
            }
        }

        setEditSaving(true);
        setEditError(null);

        try {
            const payload = {
                id: editProduct.id,
                name: String(editForm.name ?? '').trim(),
                sku: String(editForm.sku ?? '').trim(),
                category: String(editForm.category ?? '').trim() || null,
                price: Number(editForm.price),
                current_stock: Number(editForm.current_stock),
                reorder_threshold: Number(editForm.reorder_threshold),
                receiving_unit: editForm.receiving_unit || 'piece',
                supplier_id: editForm.supplier_id ? Number(editForm.supplier_id) : null,
            };
            if (editFromQueue) {
                payload.is_listed = true;
            }

            const response = await put('/api/inventory/update', payload);

            setMessage(response.message);
            const listedId = editFromQueue ? (response.product?.id ?? editProduct.id) : null;
            setEditProduct(null);
            setEditFromQueue(false);
            if (listedId) {
                setHighlightId(listedId);
                notifyConnectedNav('productListed', 1);
            }
            await refresh();
        } catch (err) {
            setEditError(validationMessages(err)[0] ?? err.message);
        } finally {
            setEditSaving(false);
        }
    }

    async function handleDiscontinueQueue() {
        if (!editProduct || editSaving) return;
        setEditSaving(true);
        setEditError(null);

        try {
            const response = await del(`/api/inventory/${editProduct.id}?discontinue_queue=1`);
            setMessage(response?.message ?? 'Product marked as discontinued and removed from queue.');
            setEditProduct(null);
            setEditFromQueue(false);
            await refresh();
        } catch (err) {
            setEditError(validationMessages(err)[0] ?? err.message ?? 'Could not discontinue product.');
        } finally {
            setEditSaving(false);
        }
    }

    const getQueueBlockReason = useCallback((product) => {
        if (!product) return null;
        if (product.last_supplier_active === false) return 'Inactive supplier';
        if (product.is_active === false || product.is_active === 0) return 'Deactivated';
        const cat = String(product.category ?? '').trim();
        if (
            cat &&
            cat.toLowerCase() !== 'uncategorized' &&
            categoriesList.length > 0 &&
            !categoriesList.some((c) => String(c?.name ?? '').trim().toLowerCase() === cat.toLowerCase())
        ) {
            return 'Removed category';
        }
        return null;
    }, [categoriesList]);

    const queueBlockReason = useMemo(
        () => (editFromQueue ? getQueueBlockReason(editProduct) : null),
        [editFromQueue, editProduct, getQueueBlockReason],
    );

    function toggleSort(field) {
        if (sortBy === field) {
            if (sortDir === 'asc') {
                setSortDir('desc');
            } else {
                setSortBy(null);
                setSortDir(null);
            }
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
            await refresh();
        } catch (err) {
            setAdjustError(validationMessages(err)[0] ?? err.message);
        } finally {
            setAdjustSaving(false);
        }
    }

    async function handleDeactivate(event) {
        event?.preventDefault();
        if (!deactivateProduct) {
            return;
        }

        const product = deactivateProduct;
        setDeactivateSaving(true);
        setDeactivateError(null);

        try {
            const response = await del(`/api/inventory/${product.id}`);
            setMessage(response.message);
            setDeactivateProduct(null);
            await refresh();
        } catch (err) {
            setDeactivateError(err.message);
        } finally {
            setDeactivateSaving(false);
        }
    }

    async function handleClearActivity() {
        setClearing(true);
        setClearError(null);

        try {
            const response = await del(
                '/api/activity/recent?actions=product_created,product_updated,product_deactivated,product_reactivated,stock_adjusted',
            );

            setClearConfirm(false);
            setActivity([]);
            clearConnectedNav('productAdded');
            setActivityPage(1);
            setMessage(response?.message ?? 'Activity list cleared.');
            await refresh();
        } catch (err) {
            setClearError(validationMessages(err)[0] ?? err.message ?? 'Could not clear the activity list.');
        } finally {
            setClearing(false);
        }
    }

    async function handleSeedProducts() {
        if (seeding) return;
        setSeeding(true);

        try {
            const response = await post('/api/inventory/seed');
            setMessage(response?.message ?? '7 sample products added.');
            notifyConnectedNav('productAdded', response?.created ?? 7);
            await refresh();
        } catch (err) {
            setError(validationMessages(err)[0] ?? err.message ?? 'Could not add the sample products.');
        } finally {
            setSeeding(false);
        }
    }

    async function handleDeleteAllConfirm() {
        if (deletingAll) return;
        setDeletingAll(true);
        setDeleteAllError(null);

        try {
            const response = await del('/api/inventory');
            setDeleteAllConfirm(false);
            const deleted = Number(response?.deleted) || 0;
            const deletedListed = Number(response?.deleted_listed) || 0;
            clearConnectedNav('productAdded');
            if (deleted > 0) {
                notifyConnectedNav('productAdded', -deleted, '/products');
            }
            if (deletedListed !== deleted) {
                // Only show the actually-listed count on POS Checkout.
                clearConnectedNav('productListed');
                if (deletedListed > 0) {
                    notifyConnectedNav('productListed', -deletedListed);
                }
            }
            setMessage(response?.message ?? 'All products deleted.');
            await refresh();
        } catch (err) {
            setDeleteAllError(validationMessages(err)[0] ?? err.message ?? 'Could not delete products.');
        } finally {
            setDeletingAll(false);
        }
    }

    // SS-25 / SS-110 / SS-111: Export Inventory Catalogue to PDF & Excel
    function handleExportPdf() {
        if (sorted.length === 0) return;

        const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
        const generatedAt = new Date().toLocaleString();

        doc.setFontSize(16);
        doc.text('Smart-Stock — Inventory Catalogue Report', 40, 42);
        doc.setFontSize(9.5);
        doc.setTextColor(100);
        doc.text(`Generated: ${generatedAt}  |  Total items: ${sorted.length}`, 40, 58);

        autoTable(doc, {
            startY: 72,
            head: [['SKU', 'Product', 'Category', 'Unit', 'Price (PHP)', 'Stock', 'Reorder At', 'Status']],
            body: sorted.map((p) => [
                p.sku ?? '—',
                p.name ?? '—',
                p.category || '—',
                p.receiving_unit || 'piece',
                Number(p.price ?? 0).toFixed(2),
                String(p.current_stock ?? 0),
                String(p.reorder_threshold ?? 0),
                stockState(p).label,
            ]),
            styles: { fontSize: 9, cellPadding: 6 },
            headStyles: { fillColor: [37, 99, 235] },
        });

        const stamp = new Date().toISOString().slice(0, 10);
        doc.save(`smart-stock-inventory-${stamp}.pdf`);
        setMessage(`Exported ${sorted.length} product(s) to PDF.`);
    }

    function handleExportExcel() {
        if (sorted.length === 0) return;

        const rows = sorted.map((p) => ({
            SKU: p.sku ?? '',
            Product: p.name ?? '',
            Category: p.category || '',
            Unit: p.receiving_unit || 'piece',
            'Price (PHP)': Number(p.price ?? 0),
            'Current Stock': Number(p.current_stock ?? 0),
            'Reorder Threshold': Number(p.reorder_threshold ?? 0),
            Status: stockState(p).label,
            Active: p.is_active ? 'Active' : 'Inactive',
        }));

        const worksheet = XLSX.utils.json_to_sheet(rows);
        worksheet['!cols'] = [
            { wch: 16 }, { wch: 30 }, { wch: 20 }, { wch: 12 },
            { wch: 14 }, { wch: 14 }, { wch: 18 }, { wch: 14 }, { wch: 10 },
        ];
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Inventory');
        const stamp = new Date().toISOString().slice(0, 10);
        XLSX.writeFile(workbook, `smart-stock-inventory-${stamp}.xlsx`);
        setMessage(`Exported ${sorted.length} product(s) to Excel.`);
    }

    async function handleReactivate(event) {
        event?.preventDefault();
        if (!reactivateProduct) {
            return;
        }

        const product = reactivateProduct;
        setReactivateSaving(true);
        setReactivateError(null);

        try {
            // Same endpoint as deactivate — `?reactivate=1` flips the flag back on.
            const response = await del(`/api/inventory/${product.id}?reactivate=1`);
            setMessage(response.message);
            setReactivateProduct(null);
            await refresh();
        } catch (err) {
            setReactivateError(err.message);
        } finally {
            setReactivateSaving(false);
        }
    }

    return (
        <div className="page">
            <section className="page-hero">
                <header className="page-head">
                    <div>
                        <h2>Products</h2>
                        <p>Manage products, stock counts, and reorder thresholds.</p>
                    </div>
                </header>
            </section>

            {message && <Toast tone="success" onClose={() => setMessage(null)}>{message}</Toast>}
            {error && <Toast tone="error" onClose={() => setError(null)}>{error}</Toast>}

            {loading && (
                /* Loading phase: the hero above stays put (its loadbar is the
                   progress UI) while these sections paint skeletons mirroring
                   the real page — no spinners anywhere. */
                <div className="page-loading-phase" aria-busy="true" aria-live="polite">
                    <div className="stat-grid">
                        {[0, 1, 2, 3].map((i) => (
                            <article key={i} className="card stat skeleton-card">
                                <span className="skeleton skeleton-sm" />
                                <span className="skeleton skeleton-lg" />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </article>
                        ))}
                    </div>

                    {/* Product Queue List */}
                    <section className="card padded recent-activity">
                        <div className="filter-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                        </div>
                        <div className="activity-shell">
                            <ul className="activity-list">
                                {[0, 1].map((i) => (
                                    <li key={i}>
                                        <div className="activity-row" aria-hidden="true">
                                            <span className="skeleton" style={{ width: 32, height: 32, borderRadius: 9, flexShrink: 0 }} />
                                            <div className="activity-body">
                                                <span className="skeleton skeleton-line" style={{ width: '55%', marginBottom: 8 }} />
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '35%' }} />
                                            </div>
                                            <span className="skeleton skeleton-line" style={{ width: 90, height: 10, flexShrink: 0 }} />
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </section>

                    {/* Search & Filter Options */}
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                        </div>
                        <div className="filter-shell">
                            <div className="filter-grid">
                                {[0, 1, 2, 3].map((i) => (
                                    <div key={i} className="field">
                                        <span className="skeleton skeleton-sm" />
                                        <span className="skeleton" style={{ height: 40 }} />
                                    </div>
                                ))}
                                <div className="field">
                                    <span className="sr-only">Clear filters</span>
                                    <span className="skeleton" style={{ height: 40 }} />
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Product List table + pagination */}
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                        </div>
                        <div className="table-shell">
                            <table className="table" aria-hidden="true">
                                <thead>
                                    <tr>
                                        {[0, 1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
                                            <th key={i} className={i === 4 || i === 5 || i === 6 || i === 8 ? 'text-right' : undefined}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4, 5, 6, 7, 8].map((c) => (
                                                <td key={c} className={c === 4 || c === 5 || c === 6 || c === 8 ? 'text-right' : undefined}>
                                                    <span className="skeleton skeleton-line" style={{ width: c === 8 ? '62%' : '85%' }} />
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="pagination" aria-hidden="true">
                            <span className="skeleton" style={{ width: 34, height: 34 }} />
                            <span className="skeleton" style={{ width: 20, height: 12 }} />
                            <span className="skeleton" style={{ width: 34, height: 34 }} />
                        </div>
                    </section>

                    {/* Recent Activity List + pagination */}
                    <section className="card padded recent-activity">
                        <div className="filter-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                        </div>
                        <div className="activity-shell">
                            <ul className="activity-list">
                                {[0, 1, 2].map((i) => (
                                    <li key={i}>
                                        <div className="activity-row" aria-hidden="true">
                                            <span className="skeleton" style={{ width: 32, height: 32, borderRadius: 9, flexShrink: 0 }} />
                                            <div className="activity-body">
                                                <span className="skeleton skeleton-line" style={{ width: '55%', marginBottom: 8 }} />
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '35%' }} />
                                            </div>
                                            <span className="skeleton skeleton-line" style={{ width: 90, height: 10, flexShrink: 0 }} />
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="pagination" aria-hidden="true">
                            <span className="skeleton" style={{ width: 34, height: 34 }} />
                            <span className="skeleton" style={{ width: 20, height: 12 }} />
                            <span className="skeleton" style={{ width: 34, height: 34 }} />
                        </div>
                    </section>
                </div>
            )}

            {!loading && !error && (
                <div className="page-body">
                    <div className="stat-grid">
                        <article className="card stat">
                            <span>Active products</span>
                            <strong>{stats.total}</strong>
                            <small className="stat-hint">Items currently in the catalogue.</small>
                        </article>
                        <article className="card stat">
                            <span>Low stock</span>
                            <strong className={stats.low > 0 ? 'text-warn' : ''}>{stats.low}</strong>
                            <small className="stat-hint">At or below reorder threshold.</small>
                        </article>
                        <article className="card stat">
                            <span>Out of stock</span>
                            <strong className={stats.out > 0 ? 'text-danger' : ''}>{stats.out}</strong>
                            <small className="stat-hint">Zero units on hand.</small>
                        </article>
                        <article className="card stat">
                            <span>Defect Items</span>
                            <strong className={defectCount > 0 ? 'text-danger' : ''}>{defectCount ?? 0}</strong>
                            <small className="stat-hint">Damage recorded in stock adjustments.</small>
                        </article>
                    </div>

                    <section className="card padded recent-activity">
                        <div className="filter-head">
                            <div>
                                <h3>Product Queue List</h3>
                                <p className="muted">Received stock waiting for complete product details before being listed.</p>
                            </div>
                        </div>
                        {queuedProducts.length === 0 ? (
                            <div className="table-empty">
                                <span className="table-empty-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5" />
                                        <path d="M3.75 7.5h16.5" />
                                        <path d="M9 7.5V4.5h6v3" />
                                    </svg>
                                </span>
                                <p className="table-empty-title">No products in queue</p>
                                <p className="table-empty-sub">New stock received on Stock Inventory will appear here to complete and add to the Product List.</p>
                            </div>
                        ) : (
                            <>
                                <div className="activity-shell">
                                    <ul className="activity-list">
                                        {pagedQueue.map((product) => {
                                            const reason = getQueueBlockReason(product);
                                            return (
                                                <li key={product.id}>
                                                    <button
                                                        type="button"
                                                        className="activity-row"
                                                        onClick={() => openEdit(product, true)}
                                                        aria-label={`Complete and add ${product.name}`}
                                                    >
                                                        <span className={`activity-icon ${reason ? 'activity-icon-danger' : 'activity-icon-warn'}`} aria-hidden="true">
                                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                                                <path d="m3.3 7 8.7 5 8.7-5" />
                                                                <path d="M12 22V12" />
                                                            </svg>
                                                        </span>
                                                        <div className="activity-body">
                                                            <p className="activity-desc" style={{ display: 'inline-flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
                                                                <span>{product.name}</span>
                                                                <span className="activity-role">{product.current_stock} {product.receiving_unit || 'piece'}</span>
                                                            </p>
                                                            <p className="activity-meta muted">
                                                                <span className="mono">{product.sku}</span>
                                                            </p>
                                                        </div>
                                                        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 4, flexShrink: 0 }}>
                                                            <span className={`pill ${reason ? 'pill-danger' : 'pill-warn'}`}>
                                                                {reason || 'Pending'}
                                                            </span>
                                                            <time
                                                                className="activity-time muted"
                                                                dateTime={product.last_received_at ?? product.created_at ?? undefined}
                                                            >
                                                                {timeAgo(product.last_received_at ?? product.created_at)}
                                                            </time>
                                                        </div>
                                                    </button>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </div>
                                {queuedProducts.length > QUEUE_PER_PAGE && (
                                    <div className="pagination">
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-sm page-btn"
                                            disabled={queueSafePage <= 1}
                                            onClick={() => setQueuePage((p) => Math.max(1, p - 1))}
                                            aria-label="Previous queue page"
                                        >
                                            <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                                <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                            </svg>
                                        </button>
                                        <span className="page-count">{queueSafePage}</span>
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-sm page-btn"
                                            disabled={queueSafePage >= queueLastPage}
                                            onClick={() => setQueuePage((p) => Math.min(queueLastPage, p + 1))}
                                            aria-label="Next queue page"
                                        >
                                            <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                                <path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                            </svg>
                                        </button>
                                        <span className="page-detail">{Math.min(queueSafePage * QUEUE_PER_PAGE, queuedProducts.length)} of {queuedProducts.length}</span>
                                    </div>
                                )}
                            </>
                        )}
                    </section>

                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <h3>Search &amp; Filter Options</h3>
                                <p className="muted">Narrow the catalogue by name, category, stock status or availability.</p>
                            </div>
                        </div>
                        <div className="filter-shell">
                            <div className="filter-grid">
                                <label className="field">
                                    <span>Search</span>
                                    <input
                                        type="search"
                                        placeholder="Search name, SKU or category…"
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                    />
                                </label>
                                <label className="field">
                                    <span>Category</span>
                                    <select
                                        className="input"
                                        value={categoryFilter}
                                        onChange={(event) => setCategoryFilter(event.target.value)}
                                    >
                                        <option value="">All categories</option>
                                        {categories.map((category) => (
                                            <option key={category} value={category}>{category}</option>
                                        ))}
                                    </select>
                                </label>
                                <label className="field">
                                    <span>Status</span>
                                    <select
                                        className="input"
                                        value={statusFilter}
                                        onChange={(event) => setStatusFilter(event.target.value)}
                                    >
                                        <option value="">All statuses</option>
                                        <option value="in">In stock</option>
                                        <option value="low">Low stock</option>
                                        <option value="out">Out of stock</option>
                                        <option value="deactivated">Deactivated</option>
                                    </select>
                                </label>
                                <label className="field">
                                    <span>Availability</span>
                                    <select
                                        className="input"
                                        value={activeFilter}
                                        onChange={(event) => setActiveFilter(event.target.value)}
                                    >
                                        <option value="">All products</option>
                                        <option value="active">Active only</option>
                                        <option value="inactive">Deactivated only</option>
                                    </select>
                                </label>
                                <div className="field">
                                    <span className="sr-only">Clear filters</span>
                                    <button
                                        type="button"
                                        className="btn btn-ghost"
                                        onClick={clearFilters}
                                        disabled={!hasActiveFilters}
                                    >
                                        Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <h3>Product List</h3>
                                <p className="muted">Overview of all registered products and their current stock levels.</p>
                            </div>
                            <div className="head-actions">
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm"
                                    onClick={handleExportPdf}
                                    disabled={sorted.length === 0}
                                >
                                    Export PDF
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm"
                                    onClick={handleExportExcel}
                                    disabled={sorted.length === 0}
                                >
                                    Export Excel
                                </button>
                                <div className="activity-options" ref={productsOptionsRef}>
                                <button
                                    type="button"
                                    className="icon-btn is-outlined"
                                    onClick={() => setProductsOptionsOpen((open) => !open)}
                                    aria-haspopup="menu"
                                    aria-expanded={productsOptionsOpen}
                                    aria-label="Product list options"
                                >
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                                        <circle cx="12" cy="5" r="1.7" />
                                        <circle cx="12" cy="12" r="1.7" />
                                        <circle cx="12" cy="19" r="1.7" />
                                    </svg>
                                </button>
                                {productsOptionsOpen && (
                                    <div className="activity-options-panel" role="menu" aria-label="Product list options">
                                        <button
                                            type="button"
                                            className="activity-options-item"
                                            role="menuitem"
                                            disabled={seeding || deletingAll}
                                            onClick={() => {
                                                setProductsOptionsOpen(false);
                                                handleSeedProducts();
                                            }}
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                                <path d="m3.3 7 8.7 5 8.7-5" />
                                                <path d="M12 22V12" />
                                            </svg>
                                            <span>Add 7 sample products</span>
                                        </button>
                                        <button
                                            type="button"
                                            className="activity-options-item activity-options-danger"
                                            role="menuitem"
                                            disabled={seeding || deletingAll}
                                            onClick={() => {
                                                setProductsOptionsOpen(false);

                                                if (products.length === 0) {
                                                    setEmptyNotice({
                                                        title: 'No products to delete',
                                                        description: 'The product list is already empty. Add a product or add the 7 sample products first.',
                                                    });
                                                    return;
                                                }

                                                setDeleteAllError(null);
                                                setDeleteAllConfirm(true);
                                            }}
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                <path d="M10 11v6" />
                                                <path d="M14 11v6" />
                                            </svg>
                                            <span>Delete all products</span>
                                        </button>
                                    </div>
                                )}
                                </div>
                            </div>
                        </div>
                        <div className="table-shell">
                        <div className="table-wrap">
                            <table className="table">
                                <thead>
                                    <tr>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('name')}
                                        >
                                            Product
                                            <span className={`sort-indicator${sortBy === 'name' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'name' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('sku')}
                                            title="Stock Keeping Unit"
                                        >
                                            SKU
                                            <span className={`sort-indicator${sortBy === 'sku' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'sku' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                            <span className="th-sub">Stock Keeping Unit</span>
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('category')}
                                        >
                                            Category
                                            <span className={`sort-indicator${sortBy === 'category' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'category' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('receiving_unit')}
                                        >
                                            Unit
                                            <span className={`sort-indicator${sortBy === 'receiving_unit' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'receiving_unit' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header text-right"
                                            onClick={() => toggleSort('price')}
                                        >
                                            Price
                                            <span className={`sort-indicator${sortBy === 'price' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'price' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header text-right"
                                            onClick={() => toggleSort('current_stock')}
                                        >
                                            Current<br />stock
                                            <span className={`sort-indicator${sortBy === 'current_stock' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'current_stock' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header text-right"
                                            onClick={() => toggleSort('reorder_threshold')}
                                        >
                                            Reorder<br />at
                                            <span className={`sort-indicator${sortBy === 'reorder_threshold' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'reorder_threshold' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('status')}
                                        >
                                            Status
                                            <span className={`sort-indicator${sortBy === 'status' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'status' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th className="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {paged.map((product) => {
                                        const state = stockState(product);

                                        return (
                                            <tr key={product.id} data-product-id={product.id} className={product.is_active ? undefined : 'row-inactive'}>
                                                <td>
                                                    <span className="stock-cell-value">
                                                        {highlight(product.name, search.trim())}
                                                    </span>
                                                    {highlightId === product.id && (
                                                        <span className="stock-delta is-up new-item-badge" aria-label="Newly added product">
                                                            +1
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="mono">{product.sku}</td>
                                                <td>{product.category || '—'}</td>
                                                <td>{product.receiving_unit || 'piece'}</td>
                                                <td className="text-right">{formatPeso(product.price)}</td>
                                                <td className="text-right">
                                                    <span className="stock-cell-value">
                                                        {product.current_stock}
                                                        {stockDeltas[product.id] != null && (
                                                            <span className={`stock-delta ${stockDeltas[product.id] > 0 ? 'is-up' : 'is-down'}`}>
                                                                {stockDeltas[product.id] > 0 ? '+' : '−'}{Math.abs(stockDeltas[product.id])}
                                                            </span>
                                                        )}
                                                    </span>
                                                </td>
                                                <td className="text-right">{product.reorder_threshold}</td>
                                                <td>
                                                    <span className={`pill ${state.className}`}>{state.label}</span>
                                                </td>
                                                <td className="text-right actions-cell">
                                                    <Tooltip
                                                        label="Edit"
                                                        className="btn btn-ghost btn-sm action-icon-btn"
                                                        onClick={() => openEdit(product)}
                                                    >
                                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                            <path d="M12 20h9" />
                                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                        </svg>
                                                    </Tooltip>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>

                            {filtered.length === 0 && (
                                <div className="table-empty table-empty-solid">
                                    <span className="table-empty-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="M21 21l-4.35-4.35" />
                                            <path d="M11 8v6M8 11h6" />
                                        </svg>
                                    </span>
                                    <p className="table-empty-title">
                                        {hasActiveFilters ? 'No results found' : 'No products yet'}
                                    </p>
                                    <p className="table-empty-sub">
                                        {hasActiveFilters ? 'Try adjusting your search or filters.' : 'Add a product or add the 7 sample products to populate this list.'}
                                    </p>
                                </div>
                            )}
                        </div>
                        </div>

                        {filtered.length > 0 && (
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
                                <span className="page-detail">{Math.min(safePage * PER_PAGE, filtered.length)} of {filtered.length}</span>
                            </div>
                        )}
                    </section>

                    <section className="card padded recent-activity">
                        <div className="filter-head">
                            <div>
                                <h3>Recent Activity List</h3>
                                <p className="muted">See the things you did recently.</p>
                            </div>
                            <div className="activity-options" ref={activityOptionsRef}>
                                <button
                                    type="button"
                                    className="icon-btn is-outlined"
                                    onClick={() => setActivityOptionsOpen((open) => !open)}
                                    aria-haspopup="menu"
                                    aria-expanded={activityOptionsOpen}
                                    aria-label="Activity options"
                                >
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                                        <circle cx="12" cy="5" r="1.7" />
                                        <circle cx="12" cy="12" r="1.7" />
                                        <circle cx="12" cy="19" r="1.7" />
                                    </svg>
                                </button>
                                {activityOptionsOpen && (
                                    <div className="activity-options-panel" role="menu" aria-label="Activity options">
                                        <button
                                            type="button"
                                            className="activity-options-item activity-options-danger"
                                            role="menuitem"
                                            onClick={() => {
                                                setActivityOptionsOpen(false);

                                                if (activity.length === 0) {
                                                    setEmptyNotice({
                                                        title: 'Nothing to clear',
                                                        description: 'The activity list is already empty. Add, edit or adjust a product and its activity will show up here.',
                                                    });
                                                    return;
                                                }

                                                setClearError(null);
                                                setClearConfirm(true);
                                            }}
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                <path d="M10 11v6" />
                                                <path d="M14 11v6" />
                                            </svg>
                                            <span>Clear activity</span>
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                        {activity.length === 0 ? (
                            <div className="table-empty">
                                <span className="table-empty-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5" />
                                        <path d="M3.75 7.5h16.5" />
                                        <path d="M9 7.5V4.5h6v3" />
                                    </svg>
                                </span>
                                <p className="table-empty-title">No product activity yet</p>
                                <p className="table-empty-sub">Add, edit or adjust a product and it will show up here.</p>
                            </div>
                        ) : (
                            <>
                                <div className="activity-shell">
                                    <ul className="activity-list">
                                        {pagedActivity.map((item) => {
                                            const tone = ACTIVITY_TONE[item.action] ?? 'neutral';
                                            return (
                                                <li key={item.id}>
                                                    <button
                                                        type="button"
                                                        className="activity-row"
                                                        onClick={() => setActivityDetail(item)}
                                                        aria-label={`View details: ${activitySummary(item)}`}
                                                    >
                                                        <span className={`activity-icon activity-icon-${tone}`} aria-hidden="true">
                                                            {ACTIVITY_ICONS[item.action] ?? ACTIVITY_ICONS.default}
                                                        </span>
                                                        <div className="activity-body">
                                                            <p className="activity-desc">{activitySummary(item)}</p>
                                                            <p className="activity-meta muted">
                                                                <span className="activity-user">{item.user_name}</span>
                                                                {item.user_role && <span className="activity-role">{item.user_role}</span>}
                                                            </p>
                                                        </div>
                                                        <time
                                                            className="activity-time muted"
                                                            dateTime={item.created_at ?? undefined}
                                                            title={item.created_at ? new Date(item.created_at).toLocaleString() : undefined}
                                                        >
                                                            {item.created_at_human ?? ''}
                                                        </time>
                                                    </button>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </div>

                                {activity.length > ACTIVITY_PER_PAGE && (
                                    <div className="pagination">
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-sm page-btn"
                                            disabled={activitySafePage <= 1}
                                            onClick={() => setActivityPage((p) => Math.max(1, p - 1))}
                                            aria-label="Previous page"
                                        >
                                            <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                                <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                            </svg>
                                        </button>
                                        <span className="page-count">{activitySafePage}</span>
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-sm page-btn"
                                            disabled={activitySafePage >= activityLastPage}
                                            onClick={() => setActivityPage((p) => Math.min(activityLastPage, p + 1))}
                                            aria-label="Next page"
                                        >
                                            <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                                <path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                            </svg>
                                        </button>
                                        <span className="page-detail">{Math.min(activitySafePage * ACTIVITY_PER_PAGE, activity.length)} of {activity.length}</span>
                                    </div>
                                )}
                            </>
                        )}
                    </section>
                </div>
            )}

            {addOpen && (
                <Modal title={(
                    <span className="activity-detail-title">
                        <span className="activity-detail-infoicon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 11v5M12 8h.01" />
                            </svg>
                        </span>
                        Add product
                    </span>
                )} onClose={resetAdd} noClose loading={suggesting}>
                    <form className="form" onSubmit={handleAdd}>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Product name <InfoTip text="What the product is called in the catalogue." /><span className="text-danger">*</span></span>
                                <input type="text" required maxLength={255} placeholder="e.g. Cordless Drill 18V" value={addForm.name} onChange={(event) => setAddForm((c) => ({ ...c, name: event.target.value }))} onKeyDown={handleNameTab} autoFocus />
                            </label>
                            <label className="field">
                                <span className="field-label">Starting stock <InfoTip text="How many units are on hand right now. The reorder threshold defaults to half this amount." /></span>
                                <input type="number" min="0" step="1" placeholder="0" value={addForm.current_stock} onChange={(event) => setAddForm((c) => ({ ...c, current_stock: event.target.value.replace(/[^0-9]/g, '') }))} />
                            </label>
                        </div>
                        <div className="choice-field">
                            <span className="field-label">Unit of measure <InfoTip text="How this item is counted or sold." /><span className="text-danger">*</span></span>
                            <div className="choice-list">
                                <label className="choice-item">
                                    <input
                                        type="checkbox"
                                        name="unit_choice"
                                        checked={addForm.unit_choice === 'piece'}
                                        onChange={() => setAddForm((current) => ({ ...current, unit_choice: current.unit_choice === 'piece' ? '' : 'piece' }))}
                                    />
                                    <span>Per piece</span>
                                </label>
                                <label className="choice-item">
                                    <input
                                        type="checkbox"
                                        name="unit_choice"
                                        checked={addForm.unit_choice === 'kilogram'}
                                        onChange={() => setAddForm((current) => ({ ...current, unit_choice: current.unit_choice === 'kilogram' ? '' : 'kilogram' }))}
                                    />
                                    <span>Per kilo</span>
                                </label>
                                <div className="choice-item choice-item-other">
                                    <label className="choice-item-radio">
                                        <input
                                            type="checkbox"
                                            name="unit_choice"
                                            checked={addForm.unit_choice === 'other'}
                                            onChange={() => setAddForm((current) => ({ ...current, unit_choice: current.unit_choice === 'other' ? '' : 'other' }))}
                                        />
                                        <span>Other:</span>
                                    </label>
                                    <input
                                        type="text"
                                        className="input choice-item-input"
                                        maxLength={20}
                                        placeholder="Please specify"
                                        value={addForm.unit_other}
                                        onChange={(event) => setAddForm((current) => ({ ...current, unit_other: event.target.value }))}
                                        disabled={addForm.unit_choice !== 'other'}
                                    />
                                </div>
                            </div>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Item Code <InfoTip text="A code is assigned if left blank." /></span>
                                <input type="text" className="mono" maxLength={50} placeholder={suggestions.sku || 'e.g. COR-DRI-18V'} value={addForm.sku} onChange={(event) => setAddForm((c) => ({ ...c, sku: event.target.value.toUpperCase() }))} onKeyDown={handleSuggestionTab('sku')} disabled={suggesting} />
                            </label>
                            <label className="field">
                                <span className="field-label">Category <InfoTip text="Groups products for easier filtering." /></span>
                                <input type="text" maxLength={100} placeholder={suggestions.category || 'e.g. Power Tools'} value={addForm.category} onChange={(event) => setAddForm((c) => ({ ...c, category: event.target.value }))} onKeyDown={handleSuggestionTab('category')} disabled={suggesting} />
                            </label>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Price <InfoTip text="Selling price per unit." /><span className="text-danger">*</span></span>
                                <div className="input-prefix-wrap">
                                    <span className="input-prefix" aria-hidden="true">₱</span>
                                    <input type="text" inputMode="decimal" className="input" placeholder={priceSuggestion || '0.00'} value={addForm.price} onChange={(event) => {
                                        const val = event.target.value.replace(/[^0-9.]/g, '');
                                        setAddForm((c) => ({ ...c, price: val }));
                                    }} onBlur={(event) => {
                                        const val = event.target.value.replace(/[^0-9.]/g, '');
                                        if (val === '' || val === '.') {
                                            setAddForm((c) => ({ ...c, price: '' }));
                                            return;
                                        }
                                        let num = Number(val);
                                        const unitLower = (addForm.unit_choice || '').toLowerCase();
                                        if ((unitLower === 'piece' || unitLower === 'pc' || unitLower === 'pcs') && num < 2) {
                                            num = 2;
                                        }
                                        if (!Number.isFinite(num) || num < 2) {
                                            num = 2;
                                        }
                                        num = Math.round(num);
                                        setAddForm((c) => ({ ...c, price: num.toFixed(2) }));
                                    }} onKeyDown={handleSuggestionTab('price')} disabled={priceSuggesting && !addForm.price} />
                                </div>
                            </label>
                            <label className="field">
                                <span className="field-label">Reorder threshold <InfoTip text="A low-stock alert fires when stock falls to this number." /></span>
                                <input type="number" min="0" step="1" placeholder={reorderSuggestion || '0'} value={addForm.reorder_threshold} onChange={(event) => setAddForm((c) => ({ ...c, reorder_threshold: event.target.value.replace(/[^0-9]/g, '') }))} onKeyDown={handleSuggestionTab('reorder_threshold')} />
                            </label>
                        </div>

                        {formError && (
                            <div className="alert alert-error" role="alert">
                                <span>{formError}</span>
                                <FieldError messages={fieldErrors} />
                            </div>
                        )}

                        <div className="modal-actions modal-actions-center">
                            <button type="button" className="btn btn-ghost" onClick={resetAdd}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={saving}>
                                {saving ? 'Saving…' : 'Add product'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {editProduct && editForm && (
                <Modal
                    bare
                    noClose
                    ariaLabel={editFromQueue ? 'Add product' : 'Edit product'}
                    className="modal-edit"
                    onClose={() => { setEditProduct(null); setEditFromQueue(false); }}
                >
                    <form className="form" onSubmit={handleEdit}>
                        <div className="confirm-dialog confirm-dialog-info">
                            <span className="confirm-dialog-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5Z" />
                                </svg>
                            </span>
                            <div className="confirm-dialog-body">
                                <p className="confirm-dialog-title">{editFromQueue ? 'Add Product' : 'Edit'}</p>
                                <p className="muted confirm-dialog-text">
                                    {editFromQueue
                                        ? 'Fill in all required fields to list this received item in the Product List.'
                                        : "Update this product's details."}
                                </p>
                            </div>
                        </div>
                        <label className="field">
                            <span className="field-label">Product name <span className="text-danger">*</span></span>
                            <input type="text" required maxLength={255} placeholder="e.g. Cordless Drill 18V" value={editForm.name} onChange={(event) => setEditForm((c) => ({ ...c, name: event.target.value }))} />
                        </label>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Item Code <span className="text-danger">*</span></span>
                                <input type="text" className="mono" required maxLength={50} placeholder="Item code" value={editForm.sku} onChange={(event) => setEditForm((c) => ({ ...c, sku: event.target.value.toUpperCase() }))} />
                            </label>
                            <label className="field">
                                <span className="field-label">Category{editFromQueue && <> <span className="text-danger">*</span></>}</span>
                                <input type="text" required={editFromQueue} maxLength={100} placeholder="e.g. Power Tools" value={editForm.category} onChange={(event) => setEditForm((c) => ({ ...c, category: event.target.value }))} />
                            </label>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Price <span className="text-danger">*</span></span>
                                <div className="input-prefix-wrap">
                                    <span className="input-prefix" aria-hidden="true">₱</span>
                                    <input type="text" inputMode="decimal" required className="input" placeholder="0.00" value={editForm.price} onChange={(event) => setEditForm((c) => ({ ...c, price: event.target.value }))} onBlur={(event) => setEditForm((c) => ({ ...c, price: formatPriceInput(event.target.value) }))} />
                                </div>
                            </label>
                            <label className="field">
                                <span className="field-label">Unit of measure{editFromQueue && <> <span className="text-danger">*</span></>}</span>
                                <select className="input" required={editFromQueue} value={editForm.receiving_unit} onChange={(event) => setEditForm((c) => ({ ...c, receiving_unit: event.target.value }))}>
                                    {UNITS.map((unit) => (
                                        <option key={unit} value={unit}>{unit}</option>
                                    ))}
                                </select>
                            </label>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Current stock <span className="text-danger">*</span></span>
                                <input type="number" required min="0" step="1" placeholder="0" value={editForm.current_stock} onChange={(event) => setEditForm((c) => ({ ...c, current_stock: event.target.value }))} />
                            </label>
                            <label className="field">
                                <span className="field-label">Reorder threshold <span className="text-danger">*</span></span>
                                <input type="number" required min="0" step="1" placeholder="0" value={editForm.reorder_threshold} onChange={(event) => setEditForm((c) => ({ ...c, reorder_threshold: event.target.value }))} />
                            </label>
                        </div>
                        <label className="field">
                            <span className="field-label">Supplier</span>
                            <select
                                className="input"
                                value={editForm.supplier_id ?? ''}
                                onChange={(event) => setEditForm((c) => ({ ...c, supplier_id: event.target.value }))}
                            >
                                <option value="" disabled>No supplier</option>
                                {editProduct.last_supplier_id &&
                                    editProduct.last_supplier_name &&
                                    editProduct.last_supplier_active !== false &&
                                    !suppliers.some((sup) => String(sup.id) === String(editProduct.last_supplier_id)) && (
                                        <option value={String(editProduct.last_supplier_id)}>
                                            {editProduct.last_supplier_name}
                                        </option>
                                    )}
                                {suppliers.map((sup) => (
                                    <option key={sup.id} value={String(sup.id)}>
                                        {sup.name}
                                    </option>
                                ))}
                            </select>
                            {editProduct.last_supplier_active === false && editProduct.last_supplier_name && (
                                <small className="text-danger">
                                    Previous supplier &ldquo;{editProduct.last_supplier_name}&rdquo; was removed.
                                </small>
                            )}
                        </label>

                        {queueBlockReason && (
                            <div className="alert alert-error" role="alert">
                                <span>
                                    Cannot add to Product List ({queueBlockReason}). Click <strong>Discontinued</strong> to remove it from the queue.
                                </span>
                            </div>
                        )}

                        {editError && <div className="alert alert-error" role="alert"><span>{editError}</span></div>}

                        <div className="modal-actions modal-actions-center">
                            <button type="button" className="btn btn-ghost" onClick={() => { setEditProduct(null); setEditFromQueue(false); }}>Cancel</button>
                            {queueBlockReason ? (
                                <button
                                    type="button"
                                    className="btn btn-danger"
                                    disabled={editSaving}
                                    onClick={handleDiscontinueQueue}
                                >
                                    {editSaving ? 'Discontinuing…' : 'Discontinued'}
                                </button>
                            ) : (
                                <button type="submit" className="btn btn-primary" disabled={editSaving}>
                                    {editFromQueue
                                        ? (editSaving ? 'Adding…' : 'Add product')
                                        : (editSaving ? 'Saving…' : 'Save changes')}
                                </button>
                            )}
                        </div>
                    </form>
                </Modal>
            )}

            {adjustProduct && (
                <Modal
                    bare
                    noClose
                    ariaLabel="Adjust stock"
                    className="modal-adjust"
                    onClose={() => setAdjustProduct(null)}
                >
                    <form className="form" onSubmit={handleAdjust}>
                        <div className="confirm-dialog confirm-dialog-info">
                            <span className="confirm-dialog-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="4" y1="6" x2="20" y2="6" />
                                    <line x1="4" y1="12" x2="20" y2="12" />
                                    <line x1="4" y1="18" x2="20" y2="18" />
                                    <circle cx="9" cy="6" r="2" fill="currentColor" stroke="none" />
                                    <circle cx="15" cy="12" r="2" fill="currentColor" stroke="none" />
                                    <circle cx="7" cy="18" r="2" fill="currentColor" stroke="none" />
                                </svg>
                            </span>
                            <div className="confirm-dialog-body">
                                <p className="confirm-dialog-title">Adjust stock</p>
                                <p className="muted confirm-dialog-text">Manually correct the on-hand count for this product.</p>
                            </div>
                        </div>
                        <div className="field">
                            <span className="field-label">Product</span>
                            <div className="readonly-value readonly-static">{adjustProduct.name}</div>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">
                                    Delta <span className="text-danger">*</span>
                                </span>
                                <input
                                    type="number"
                                    className="input"
                                    required
                                    step="1"
                                    placeholder="e.g. -2 or 5"
                                    value={adjustForm.delta}
                                    onChange={(event) => setAdjustForm((c) => ({ ...c, delta: event.target.value }))}
                                />
                            </label>
                            <label className="field">
                                <span className="field-label">
                                    Reason <span className="text-danger">*</span>
                                </span>
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
                            <span className="field-label">
                                Note
                            </span>
                            <input
                                type="text"
                                maxLength={255}
                                placeholder="e.g. Dented items found in box"
                                value={adjustForm.reason_note}
                                onChange={(event) => setAdjustForm((c) => ({ ...c, reason_note: event.target.value }))}
                            />
                        </label>

                        {adjustError && <div className="alert alert-error" role="alert"><span>{adjustError}</span></div>}

                        <div className="modal-actions modal-actions-center">
                            <button type="button" className="btn btn-ghost" onClick={() => setAdjustProduct(null)}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={adjustSaving}>
                                {adjustSaving ? 'Applying…' : 'Apply adjustment'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {activityDetail && (
                <Modal
                    title={(
                        <span className="activity-detail-title">
                            <span className="activity-detail-infoicon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 11v5M12 8h.01" />
                                </svg>
                            </span>
                            Activity details
                        </span>
                    )}
                    className="modal-activity"
                    noClose
                    onClose={() => setActivityDetail(null)}
                >
                    <dl className="activity-detail-list">
                        <div className="activity-detail-item">
                            <dt>Action</dt>
                            <dd>{ACTIVITY_VERB[activityDetail.action] ?? activityDetail.action}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>By</dt>
                            <dd>
                                {activityDetail.user_name}
                                {activityDetail.user_role && <span className="activity-role">{activityDetail.user_role}</span>}
                            </dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>When</dt>
                            <dd>
                                {activityDetail.created_at
                                    ? new Date(activityDetail.created_at).toLocaleString()
                                    : activityDetail.created_at_human}
                            </dd>
                        </div>
                        {activityDetail.subject_name && (
                            <div className="activity-detail-item">
                                <dt>Product</dt>
                                <dd>{activityDetail.subject_name}</dd>
                            </div>
                        )}
                        {activityChanges(activityDetail).length > 0 && (
                            <div className="activity-detail-item">
                                <dt>Changes made</dt>
                                <dd>
                                    <ul className="activity-detail-changes">
                                        {activityChanges(activityDetail).map((text, index) => (
                                            <li key={`${text}-${index}`}>{text}</li>
                                        ))}
                                    </ul>
                                </dd>
                            </div>
                        )}
                    </dl>

                    <div className="modal-actions modal-actions-center">
                        <button type="button" className="btn btn-ghost" onClick={() => setActivityDetail(null)}>Close</button>
                    </div>
                </Modal>
            )}

            {deactivateProduct && (
                <ConfirmDialog
                    tone="danger"
                    title={<>Deactivate <strong>{deactivateProduct.name}</strong>?</>}
                    description="This removes the product from the active catalogue. Its stock history and transactions are preserved, and the SKU stays reserved."
                    confirmLabel="Deactivate"
                    loadingLabel="Deactivating…"
                    loading={deactivateSaving}
                    error={deactivateError}
                    onConfirm={handleDeactivate}
                    onCancel={() => setDeactivateProduct(null)}
                />
            )}

            {reactivateProduct && (
                <ConfirmDialog
                    tone="info"
                    title={<>Activate <strong>{reactivateProduct.name}</strong>?</>}
                    description="This puts the product back into the active catalogue so it shows in the Product List and the POS catalog again."
                    confirmLabel="Activate"
                    loadingLabel="Activating…"
                    loading={reactivateSaving}
                    error={reactivateError}
                    onConfirm={handleReactivate}
                    onCancel={() => setReactivateProduct(null)}
                />
            )}
            {emptyNotice && (
                <MessageDialog
                    tone="info"
                    title={emptyNotice.title}
                    description={emptyNotice.description}
                    onClose={() => setEmptyNotice(null)}
                />
            )}

            {deleteAllConfirm && (
                <ConfirmDialog
                    tone="danger"
                    title="Delete all products?"
                    description={`This permanently deletes all ${products.length} product(s) in the catalogue. Any products linked to transaction or stock history are kept. This action cannot be undone.`}
                    confirmLabel="Delete all"
                    loadingLabel="Deleting…"
                    loading={deletingAll}
                    error={deleteAllError}
                    onConfirm={handleDeleteAllConfirm}
                    onCancel={() => {
                        if (!deletingAll) {
                            setDeleteAllConfirm(false);
                            setDeleteAllError(null);
                        }
                    }}
                />
            )}

            {clearConfirm && (
                <ConfirmDialog
                    tone="danger"
                    title="Clear activity list?"
                    description="This permanently deletes the product activity history from the activity log. This action cannot be undone."
                    confirmLabel="Clear"
                    loadingLabel="Clearing…"
                    loading={clearing}
                    error={clearError}
                    onConfirm={handleClearActivity}
                    onCancel={() => {
                        if (!clearing) {
                            setClearConfirm(false);
                            setClearError(null);
                        }
                    }}
                />
            )}
        </div>
    );
}
