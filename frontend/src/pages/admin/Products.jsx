import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Modal from '../../components/Modal.jsx';
import ConfirmDialog from '../../components/ConfirmDialog.jsx';
import InfoTip from '../../components/InfoTip.jsx';
import { FieldError, Toast } from '../../components/Feedback.jsx';
import { del, get, post, put, validationMessages } from '../../lib/api.js';
import { formatPeso } from '../../lib/format.js';

const EMPTY_PRODUCT = {
    name: '',
    sku: '',
    category: '',
    price: '',
    current_stock: '',
    reorder_threshold: '',
    receiving_unit: 'piece',
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
            <path d="M21 12a9 9 0 0 0-15-6.7L3 8" />
            <path d="M3 3v5h5" />
            <path d="M3 12a9 9 0 0 0 15 6.7L21 16" />
            <path d="M21 21v-5h-5" />
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
    const [defectCount, setDefectCount] = useState(null);
    const [activity, setActivity] = useState([]);
    // The activity row whose detail popup is currently open (null = closed).
    const [activityDetail, setActivityDetail] = useState(null);
    // Recent Activity pagination (3 rows per page).
    const [activityPage, setActivityPage] = useState(1);
    const ACTIVITY_PER_PAGE = 3;
    const [loading, setLoading] = useState(true);
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
                const [rows, defects, feed] = await Promise.all([
                    get('/api/inventory/products'),
                    get('/api/inventory/defects').catch(() => null),
                    get('/api/activity/recent', { limit: 12 }).catch(() => null),
                ]);
                return { rows, defects, feed };
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
            const { rows, defects, feed } = await fetchProducts();
            setProducts(Array.isArray(rows) ? rows : []);
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

    const categories = useMemo(() => {
        const set = new Set();
        products.forEach((product) => {
            if (product.category) {
                set.add(product.category);
            }
        });
        return Array.from(set).sort((a, b) => a.localeCompare(b));
    }, [products]);

    const filtered = useMemo(() => {
        const needle = search.trim().toLowerCase();

        return products.filter((product) => {
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
                const key = state.className === 'pill-danger'
                    ? 'out'
                    : state.className === 'pill-warn'
                        ? 'low'
                        : 'in';
                if (key !== statusFilter) {
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
        const active = products.filter((product) => product.is_active);
        const out = active.filter((product) => Number(product.current_stock) <= 0);
        const low = active.filter(
            (product) =>
                Number(product.current_stock) > 0 &&
                Number(product.current_stock) <= Number(product.reorder_threshold),
        );

        return { total: active.length, low: low.length, out: out.length };
    }, [products]);

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
            if ((addForm.receiving_unit || '').trim() !== cleanUnit) {
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
            if (currentNameRef.current.trim() === cleanName && (addForm.receiving_unit || '').trim() === cleanUnit) {
                setPriceSuggesting(false);
            }
        }
    }, [addForm.receiving_unit]);

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
        if (addForm.receiving_unit) {
            setPriceSuggesting(true);
            priceSuggestTimer.current = setTimeout(() => {
                runPriceSuggest(name, addForm.receiving_unit);
            }, 700);
        } else {
            setPriceSuggestion('');
            setPriceSuggesting(false);
        }

        return () => {
            clearTimeout(suggestTimer.current);
            clearTimeout(priceSuggestTimer.current);
        };
    }, [addForm.name, addOpen, addForm.receiving_unit, runSuggest, runPriceSuggest]);

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
            receiving_unit: addForm.receiving_unit,
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
            setAddOpen(false);
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

    function openEdit(product) {
        setEditProduct(product);
        setEditForm({
            name: product.name,
            sku: product.sku,
            category: product.category ?? '',
            price: product.price,
            current_stock: product.current_stock,
            reorder_threshold: product.reorder_threshold,
            receiving_unit: product.receiving_unit ?? 'piece',
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
                receiving_unit: editForm.receiving_unit || 'piece',
            });

            setMessage(response.message);
            setEditProduct(null);
            await refresh();
        } catch (err) {
            setEditError(validationMessages(err)[0] ?? err.message);
        } finally {
            setEditSaving(false);
        }
    }

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
            <section className={`page-hero${loading ? ' is-loading' : ''}`}>
                <div className="page-loadbar" aria-hidden="true" />
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

                    {/* Add New Product panel */}
                    <section className="card panel panel-add">
                        <div className="panel-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                            <span className="skeleton" style={{ width: 118, height: 38, borderRadius: 8 }} />
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
                                        {[0, 1, 2, 3, 4, 5, 6].map((i) => (
                                            <th key={i} className={i === 3 || i === 4 || i === 6 ? 'text-right' : undefined}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4, 5, 6].map((c) => (
                                                <td key={c} className={c === 3 || c === 4 || c === 6 ? 'text-right' : undefined}>
                                                    <span className="skeleton skeleton-line" style={{ width: c === 6 ? '62%' : '85%' }} />
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

                    <section className="card panel panel-add">
                        <div className="panel-head">
                            <div>
                                <h3>Add New Product</h3>
                                <p className="muted">Create a product catalogue entry.</p>
                            </div>
                            <button type="button" className="btn btn-primary" onClick={() => { resetAdd(); setAddOpen(true); }}>
                                Add product
                            </button>
                        </div>
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
                                    {paged.map((product) => {
                                        const state = stockState(product);

                                        return (
                                            <tr key={product.id} className={product.is_active ? undefined : 'row-inactive'}>
                                                <td>
                                                    {highlight(product.name, search.trim())}
                                                </td>
                                                <td className="mono">{product.sku}</td>
                                                <td>{product.category || '—'}</td>
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
                                                    {product.is_active ? (
                                                        <button
                                                            type="button"
                                                            className="btn btn-ghost btn-sm btn-danger-text action-toggle"
                                                            onClick={() => {
                                                                setDeactivateProduct(product);
                                                                setDeactivateError(null);
                                                            }}
                                                        >
                                                            Deactivate
                                                        </button>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            className="btn btn-ghost btn-sm btn-success-text action-toggle"
                                                            onClick={() => {
                                                                setReactivateProduct(product);
                                                                setReactivateError(null);
                                                            }}
                                                        >
                                                            Activate
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>

                            {filtered.length === 0 && (
                                <div className="table-empty">
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
                                        {hasActiveFilters ? 'Try adjusting your search or filters.' : 'Add a product to get started.'}
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
                            </div>
                        )}
                    </section>

                    <section className="card padded recent-activity">
                        <div className="filter-head">
                            <div>
                                <h3>Recent Activity List</h3>
                                <p className="muted">See the things you did recently.</p>
                            </div>
                        </div>
                        {activity.length === 0 ? (
                            <p className="muted activity-empty">No activity recorded yet. Add, edit or adjust a product and it will show up here.</p>
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
                )} titleTip="Type a product name to get AI suggestions for Item Code and Category. Choose a unit of measure to get an AI price suggestion for that unit. Press Tab to accept grey suggestions." onClose={resetAdd} noClose loading={suggesting || priceSuggesting}>
                    <form className="form" onSubmit={handleAdd}>
                        <label className="field">
                            <span className="field-label">Product name <InfoTip text="What the product is called in the catalogue." />*</span>
                            <input type="text" required maxLength={255} placeholder="e.g. Cordless Drill 18V" value={addForm.name} onChange={(event) => setAddForm((c) => ({ ...c, name: event.target.value }))} onKeyDown={handleNameTab} autoFocus />
                        </label>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Unit of measure <InfoTip text="How this item is counted or sold. AI uses this to suggest a price." />*</span>
                                <select
                                    className="input"
                                    required
                                    value={addForm.receiving_unit}
                                    onChange={(event) => {
                                        setAddForm((c) => ({ ...c, receiving_unit: event.target.value }));
                                        setPriceSuggestion('');
                                    }}
                                >
                                    <option value="" disabled>Select unit</option>
                                    {UNITS.map((unit) => (
                                        <option key={unit} value={unit}>{unit}</option>
                                    ))}
                                </select>
                            </label>
                            <label className="field">
                                <span className="field-label">Starting stock <InfoTip text="How many units are on hand right now. The reorder threshold will suggest half this amount." /></span>
                                <input type="number" min="0" step="1" placeholder="0" value={addForm.current_stock} onChange={(event) => setAddForm((c) => ({ ...c, current_stock: event.target.value.replace(/[^0-9]/g, '') }))} />
                            </label>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Item Code <InfoTip text="Leave blank to auto-generate on save." /></span>
                                <input type="text" className="mono" maxLength={50} placeholder={suggestions.sku || 'e.g. COR-DRI-18V'} value={addForm.sku} onChange={(event) => setAddForm((c) => ({ ...c, sku: event.target.value.toUpperCase() }))} onKeyDown={handleSuggestionTab('sku')} disabled={suggesting} />
                            </label>
                            <label className="field">
                                <span className="field-label">Category <InfoTip text="Groups products for easier filtering." /></span>
                                <input type="text" maxLength={100} placeholder={suggestions.category || 'e.g. Power Tools'} value={addForm.category} onChange={(event) => setAddForm((c) => ({ ...c, category: event.target.value }))} onKeyDown={handleSuggestionTab('category')} disabled={suggesting} />
                            </label>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Price <InfoTip text="Selling price per unit. AI suggests an amount based on the product name and unit." />*</span>
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
                                        const unitLower = (addForm.receiving_unit || '').toLowerCase();
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
                    ariaLabel="Edit product"
                    className="modal-edit"
                    onClose={() => setEditProduct(null)}
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
                                <p className="confirm-dialog-title">Edit</p>
                                <p className="muted confirm-dialog-text">Update this product's details.</p>
                            </div>
                        </div>
                        <label className="field">
                            <span className="field-label">Product name <InfoTip text="What the product is called in the catalogue." />*</span>
                            <input type="text" required maxLength={255} placeholder="e.g. Cordless Drill 18V" value={editForm.name} onChange={(event) => setEditForm((c) => ({ ...c, name: event.target.value }))} />
                        </label>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Item Code <InfoTip text="Unique identifier for this product." />*</span>
                                <input type="text" className="mono" required maxLength={50} placeholder="Item code" value={editForm.sku} onChange={(event) => setEditForm((c) => ({ ...c, sku: event.target.value.toUpperCase() }))} />
                            </label>
                            <label className="field">
                                <span className="field-label">Category <InfoTip text="Groups products for easier filtering." /></span>
                                <input type="text" maxLength={100} placeholder="e.g. Power Tools" value={editForm.category} onChange={(event) => setEditForm((c) => ({ ...c, category: event.target.value }))} />
                            </label>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Price <InfoTip text="Selling price per unit." />*</span>
                                <div className="input-prefix-wrap">
                                    <span className="input-prefix" aria-hidden="true">₱</span>
                                    <input type="text" inputMode="decimal" required className="input" placeholder="0.00" value={editForm.price} onChange={(event) => setEditForm((c) => ({ ...c, price: event.target.value }))} onBlur={(event) => setEditForm((c) => ({ ...c, price: formatPriceInput(event.target.value) }))} />
                                </div>
                            </label>
                            <label className="field">
                                <span className="field-label">Unit of measure <InfoTip text="How this item is counted or sold." /></span>
                                <select className="input" value={editForm.receiving_unit} onChange={(event) => setEditForm((c) => ({ ...c, receiving_unit: event.target.value }))}>
                                    {UNITS.map((unit) => (
                                        <option key={unit} value={unit}>{unit}</option>
                                    ))}
                                </select>
                            </label>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Current stock <InfoTip text="Units currently on hand." />*</span>
                                <input type="number" required min="0" step="1" placeholder="0" value={editForm.current_stock} onChange={(event) => setEditForm((c) => ({ ...c, current_stock: event.target.value }))} />
                            </label>
                            <label className="field">
                                <span className="field-label">Reorder threshold <InfoTip text="A low-stock alert fires when stock falls to this number." />*</span>
                                <input type="number" required min="0" step="1" placeholder="0" value={editForm.reorder_threshold} onChange={(event) => setEditForm((c) => ({ ...c, reorder_threshold: event.target.value }))} />
                            </label>
                        </div>

                        {editError && <div className="alert alert-error" role="alert"><span>{editError}</span></div>}

                        <div className="modal-actions modal-actions-center">
                            <button type="button" className="btn btn-ghost" onClick={() => setEditProduct(null)}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={editSaving}>
                                {editSaving ? 'Saving…' : 'Save changes'}
                            </button>
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
                            <span className="field-label">Product <InfoTip text="The product whose stock count will be adjusted." /></span>
                            <div className="readonly-value readonly-static">{adjustProduct.name}</div>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">
                                    Delta <InfoTip text={`Currently ${adjustProduct.current_stock} on hand. Enter a positive number to add or a negative number to subtract.`} /> *
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
                                    Reason <InfoTip text="Why the stock count is being changed." /> *
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
                                Note <InfoTip text="Optional note saved with this adjustment." />
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
        </div>
    );
}
