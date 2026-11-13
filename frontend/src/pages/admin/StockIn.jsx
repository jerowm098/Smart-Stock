import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { FieldError, Toast } from '../../components/Feedback.jsx';
import { get, post, validationMessages } from '../../lib/api.js';
import { timeAgo } from '../../lib/format.js';
import Modal from '../../components/Modal.jsx';
import Tooltip from '../../components/Tooltip.jsx';
import InfoTip from '../../components/InfoTip.jsx';

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

const ACTIVITY_TONE = {
    product_created: 'ok',
    product_updated: 'info',
    product_deactivated: 'danger',
    product_reactivated: 'ok',
    stock_received: 'ok',
    stock_adjusted: 'warn',
    sale_completed: 'info',
};

const ACTIVITY_VERB = {
    product_created: 'Added',
    product_updated: 'Edited',
    product_deactivated: 'Deactivated',
    product_reactivated: 'Reactivated',
    stock_received: 'Received stock for',
    stock_adjusted: 'Adjusted',
    sale_completed: 'Completed sale #',
};

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
            break;
        case 'product_reactivated':
            lines.push('Status Deactivated → Active');
            break;
        case 'stock_received':
            if (meta.stock_before != null && meta.stock_after != null) lines.push(`Stock ${meta.stock_before} → ${meta.stock_after}`);
            if (meta.quantity != null) lines.push(`Received ${meta.quantity} ${meta.unit ?? 'unit'}`);
            if (meta.piece_delta) lines.push(`Pieces ${meta.piece_delta > 0 ? '+' : ''}${meta.piece_delta}`);
            break;
        case 'stock_adjusted':
            if (meta.stock_before != null && meta.stock_after != null) lines.push(`Stock ${meta.stock_before} → ${meta.stock_after}`);
            if (meta.delta != null) lines.push(`Delta ${meta.delta > 0 ? '+' : ''}${meta.delta}`);
            if (meta.reason_label || meta.reason) lines.push(`Reason ${meta.reason_label ?? meta.reason}`);
            break;
        case 'sale_completed':
            if (meta.item_count != null) lines.push(`Items sold ${meta.item_count}`);
            if (meta.total_amount != null) lines.push(`Total ${peso(meta.total_amount)}`);
            break;
        default: break;
    }
    return lines;
}

const ACTIVITY_ICONS = {
    product_created: (<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14M5 12h14" /></svg>),
    product_updated: (<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9" /><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>),
    product_deactivated: (<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="9" /><path d="M8 12h8" /></svg>),
    product_reactivated: (<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6 9 17l-5-5" /></svg>),
    stock_received: (<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 3v13" /><path d="M8 12l4 4 4-4" /><path d="M3 17v2a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2" /></svg>),
    stock_adjusted: (<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 4A8 8 0 0 1 20 12" /><path d="M21.72 9.79 20 12 18.28 9.79" /><path d="M18.93 16A8 8 0 0 1 8 18.93" /><path d="M9.05 21.53 8 18.93 10.77 18.54" /><path d="M5.07 16A8 8 0 0 1 8 5.07" /><path d="M5.23 4.68 8 5.07 6.95 7.67" /></svg>),
    sale_completed: (<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" /></svg>),
    default: (<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="9" /><path d="M12 8h.01M11 12h1v4h1" /></svg>),
};

const UNITS = [
    'piece', 'box', 'sack', 'bag', 'pack', 'set', 'roll', 'bundle',
    'kilogram', 'gram', 'liter', 'milliliter', 'meter', 'foot', 'gallon',
    'can', 'bottle', 'tube', 'sheet',
];

const EMPTY_RECEIVE_FORM = {
    product_id: '',
    product_name: '',
    sku: '',
    supplier_id: '',
    unit_of_measure: '',
    quantity_received: '',
    note: '',
};

const EMPTY_ADD_FORM = {
    name: '',
    sku: '',
    category: '',
    supplier_id: '',
    unit_choice: '',
    unit_other: '',
    quantity_received: '',
    note_type: '',
    note: '',
};

const PRODUCTS_PER_PAGE = 6;

function stockStateKey(product) {
    if (Number(product.current_stock) <= 0) {
        return 'out';
    }

    if (Number(product.current_stock) <= Number(product.reorder_threshold)) {
        return 'low';
    }

    return 'in';
}

export default function StockIn() {
    const [products, setProducts] = useState([]);
    const [suppliers, setSuppliers] = useState([]);
    const [stats, setStats] = useState(null);
    // Drives the hero's load bar only; the sections below render as data arrives.
    const [loading, setLoading] = useState(true);

    const [toast, setToast] = useState(null);

    // Receive Stock — the per-row Receive button opens the modal with the
    // product pre-selected.
    const [receiveOpen, setReceiveOpen] = useState(false);
    const [receiveForm, setReceiveForm] = useState(EMPTY_RECEIVE_FORM);
    const [receiveSubmitting, setReceiveSubmitting] = useState(false);
    const [receiveError, setReceiveError] = useState(null);
    const [receiveFieldErrors, setReceiveFieldErrors] = useState([]);

    // Receive New Stock (create + first stock-in) — modal via its panel
    // button. SKU + Category hints show up as ghost placeholders while the
    // product name is typed; they are never labelled in the UI.
    const [addOpen, setAddOpen] = useState(false);
    const [addForm, setAddForm] = useState(EMPTY_ADD_FORM);
    const [addSubmitting, setAddSubmitting] = useState(false);
    const [addError, setAddError] = useState(null);
    const [addFieldErrors, setAddFieldErrors] = useState([]);
    const [suggestions, setSuggestions] = useState({});
    const [suggesting, setSuggesting] = useState(false);
    const suggestTimer = useRef(null);
    const lastSuggestedName = useRef('');
    const currentNameRef = useRef('');

    // Search & filter over the receiving table.
    const [search, setSearch] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('');
    const [unitFilter, setUnitFilter] = useState('');
    const [levelFilter, setLevelFilter] = useState('');
    const [page, setPage] = useState(1);
    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);

    const [activity, setActivity] = useState([]);
    const [activityDetail, setActivityDetail] = useState(null);
    const [activityPage, setActivityPage] = useState(1);
    const ACTIVITY_PER_PAGE = 3;

    const refresh = useCallback(async () => {
        const [productsRes, suppliersRes, statsRes, feedRes] = await Promise.allSettled([
            get('/api/inventory/products'),
            get('/api/suppliers/active'),
            get('/api/dashboard/stats'),
            get('/api/activity/recent', { limit: 12, actions: 'stock_received' }),
        ]);

        if (productsRes.status === 'fulfilled') {
            setProducts(Array.isArray(productsRes.value) ? productsRes.value : []);
        }
        if (suppliersRes.status === 'fulfilled') {
            setSuppliers(Array.isArray(suppliersRes.value) ? suppliersRes.value : []);
        }
        if (statsRes.status === 'fulfilled') {
            setStats(statsRes.value);
        }
        if (feedRes.status === 'fulfilled') {
            setActivity(Array.isArray(feedRes.value) ? feedRes.value : []);
        }

        setLoading(false);
    }, []);

    useEffect(() => {
        refresh();
    }, [refresh]);

    // Ghost hints for the new-product form: debounce the name, then ask the
    // suggestion endpoint for a code + category. Values are only ever shown as
    // placeholders (never committed), and a failure is silent.
    const runSuggest = useCallback(async (name) => {
        const clean = name.trim();
        if (clean.length < 3) {
            return;
        }

        setSuggesting(true);

        try {
            const response = await post('/api/inventory/suggest', { name: clean });

            // Ignore stale responses: only apply if the box still holds this name.
            if (currentNameRef.current.trim() !== clean) {
                return;
            }

            const s = response.suggestion || {};
            setSuggestions({ sku: s.sku || '', category: s.category || '' });
            lastSuggestedName.current = clean;
        } catch {
            if (currentNameRef.current.trim() === clean) {
                setSuggestions({});
            }
        } finally {
            if (currentNameRef.current.trim() === clean) {
                setSuggesting(false);
            }
        }
    }, []);

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
            lastSuggestedName.current = '';
            return undefined;
        }

        if (name !== lastSuggestedName.current) {
            clearTimeout(suggestTimer.current);
            suggestTimer.current = setTimeout(() => runSuggest(name), 500);
        }

        return () => clearTimeout(suggestTimer.current);
    }, [addForm.name, addOpen, runSuggest]);

    const categories = useMemo(
        () => [...new Set(products.map((product) => product.category).filter(Boolean))]
            .sort((a, b) => a.localeCompare(b)),
        [products]
    );

    const unitOptions = useMemo(
        () => [...new Set(products.map((product) => product.receiving_unit).filter(Boolean))]
            .sort((a, b) => a.localeCompare(b)),
        [products]
    );

    const filteredProducts = useMemo(() => {
        const q = search.trim().toLowerCase();

        return products.filter((product) => {
            const matchesSearch = !q
                || String(product.name ?? '').toLowerCase().includes(q)
                || String(product.sku ?? '').toLowerCase().includes(q)
                || String(product.category ?? '').toLowerCase().includes(q);
            const matchesCategory = !categoryFilter || product.category === categoryFilter;
            const matchesUnit = !unitFilter || product.receiving_unit === unitFilter;
            const matchesLevel = !levelFilter || stockStateKey(product) === levelFilter;

            return matchesSearch && matchesCategory && matchesUnit && matchesLevel;
        });
    }, [products, search, categoryFilter, unitFilter, levelFilter]);

    const hasActiveFilters = Boolean(search.trim() || categoryFilter || unitFilter || levelFilter);

    const clearFilters = () => {
        setSearch('');
        setCategoryFilter('');
        setUnitFilter('');
        setLevelFilter('');
    };

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

    // Reset to the first page whenever the filter or sort inputs change.
    useEffect(() => {
        setPage(1);
    }, [search, categoryFilter, unitFilter, levelFilter, sortBy, sortDir]);

    const sortedProducts = useMemo(() => {
        if (!sortBy) {
            return filteredProducts;
        }

        const dir = sortDir === 'asc' ? 1 : -1;
        return [...filteredProducts].sort((a, b) => {
            let av = a[sortBy];
            let bv = b[sortBy];

            // Numeric comparison for stock
            if (sortBy === 'current_stock') {
                av = Number(av ?? 0);
                bv = Number(bv ?? 0);
                return (av - bv) * dir;
            }

            // Date comparison for latest received (falls back to created date)
            if (sortBy === 'last_received_at') {
                av = new Date(a.last_received_at ?? a.created_at ?? 0).getTime();
                bv = new Date(b.last_received_at ?? b.created_at ?? 0).getTime();
                return (av - bv) * dir;
            }

            // String comparison for name, sku, category, receiving unit
            av = String(av ?? '').toLowerCase();
            bv = String(bv ?? '').toLowerCase();
            if (av < bv) return -1 * dir;
            if (av > bv) return 1 * dir;
            return 0;
        });
    }, [filteredProducts, sortBy, sortDir]);

    const lastPage = Math.max(1, Math.ceil(sortedProducts.length / PRODUCTS_PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const pagedProducts = useMemo(
        () => sortedProducts.slice((safePage - 1) * PRODUCTS_PER_PAGE, safePage * PRODUCTS_PER_PAGE),
        [sortedProducts, safePage]
    );

    const activityLastPage = Math.max(1, Math.ceil(activity.length / ACTIVITY_PER_PAGE));
    const activitySafePage = Math.min(activityPage, activityLastPage);
    const pagedActivity = useMemo(
        () => activity.slice((activitySafePage - 1) * ACTIVITY_PER_PAGE, activitySafePage * ACTIVITY_PER_PAGE),
        [activity, activitySafePage],
    );

    useEffect(() => {
        setActivityPage(1);
    }, [activity.length]);

    const selected = products.find((product) => String(product.id) === String(receiveForm.product_id));

    function setReceiveField(name, value) {
        setReceiveForm((current) => ({ ...current, [name]: value }));
    }

    function openReceive(product) {
        setReceiveForm({
            ...EMPTY_RECEIVE_FORM,
            product_id: String(product.id),
            product_name: product.name || '',
            sku: product.sku || '',
            unit_of_measure: product.receiving_unit || '',
        });
        setReceiveError(null);
        setReceiveFieldErrors([]);
        setReceiveOpen(true);
    }

    function resetReceive() {
        setReceiveOpen(false);
        setReceiveError(null);
        setReceiveFieldErrors([]);
    }

    function openAdd() {
        setAddForm(EMPTY_ADD_FORM);
        setSuggestions({});
        setSuggesting(false);
        lastSuggestedName.current = '';
        currentNameRef.current = '';
        setAddError(null);
        setAddFieldErrors([]);
        setAddOpen(true);
    }

    function resetAdd() {
        clearTimeout(suggestTimer.current);
        setAddOpen(false);
        setAddForm(EMPTY_ADD_FORM);
        setSuggestions({});
        setSuggesting(false);
        lastSuggestedName.current = '';
        currentNameRef.current = '';
        setAddError(null);
        setAddFieldErrors([]);
    }

    // Merge the ghost hints into any field left blank, so the preview values
    // are used on submit even though they were only placeholders. Anything the
    // admin typed always wins.
    function mergeSuggestions() {
        return {
            ...addForm,
            sku: addForm.sku || suggestions.sku || '',
            category: addForm.category || suggestions.category || '',
        };
    }

    // Fill ONE field from its hint (Tab inside that field). Never overwrites
    // something the admin already typed.
    function acceptSuggestion(field) {
        const value = suggestions[field];
        if (value == null || value === '') {
            return;
        }

        setAddForm((current) => {
            if (String(current[field] ?? '').trim() !== '') {
                return current;
            }
            return { ...current, [field]: value };
        });
    }

    // Tab on the product-name field commits both hints at once.
    function acceptAllSuggestions() {
        setAddForm((current) => ({
            ...current,
            sku: current.sku || suggestions.sku || '',
            category: current.category || suggestions.category || '',
        }));
    }

    // Tab-to-accept: fills the hint and lets focus move on naturally. Typed
    // values are never overwritten.
    function handleSuggestionTab(field) {
        return (event) => {
            if (event.key !== 'Tab' || event.shiftKey) {
                return;
            }
            if (field !== 'sku' && field !== 'category') {
                return;
            }
            if (String(addForm[field] ?? '').trim() !== '' || !suggestions[field]) {
                return;
            }
            acceptSuggestion(field);
        };
    }

    function handleNameTab(event) {
        if (event.key !== 'Tab' || event.shiftKey) {
            return;
        }
        if (!suggestions.sku && !suggestions.category) {
            return;
        }
        acceptAllSuggestions();
    }

    function stockInToast(response) {
        const name = response.product?.name ?? response.stock_in?.product_name;
        const { stock_before: before, stock_after: after, piece_delta: added } = response.stock_in ?? {};

        return `${response.message} — ${name}: ${before} → ${after} (+${added} pcs)`;
    }

    async function handleReceive(event) {
        event.preventDefault();
        setReceiveSubmitting(true);
        setReceiveError(null);
        setReceiveFieldErrors([]);

        try {
            const response = await post('/api/inventory/stock-in', {
                product_id: receiveForm.product_id || null,
                product_name: receiveForm.product_name || null,
                custom_sku: receiveForm.sku || null,
                supplier_id: receiveForm.supplier_id || null,
                quantity_received: receiveForm.quantity_received,
                unit_of_measure: receiveForm.unit_of_measure || null,
                note: receiveForm.note || null,
            });

            setToast(stockInToast(response));
            setReceiveOpen(false);
            await refresh();
        } catch (err) {
            setReceiveError(err.message);
            setReceiveFieldErrors(validationMessages(err));
        } finally {
            setReceiveSubmitting(false);
        }
    }

    async function handleAdd(event) {
        event.preventDefault();
        setAddSubmitting(true);
        setAddError(null);
        setAddFieldErrors([]);

        try {
            const merged = mergeSuggestions();

            const response = await post('/api/inventory/stock-in', {
                new_product_name: merged.name,
                custom_sku: merged.sku || null,
                custom_category: merged.category || null,
                unit_of_measure: merged.unit_choice === 'other' ? (merged.unit_other.trim() || null) : merged.unit_choice,
                supplier_id: merged.supplier_id || null,
                quantity_received: merged.quantity_received,
                note: merged.note_type === 'others' ? (merged.note || null) : 'Received',
            });

            clearTimeout(suggestTimer.current);
            setToast(stockInToast(response));
            setAddOpen(false);
            setAddForm(EMPTY_ADD_FORM);
            setSuggestions({});
            setSuggesting(false);
            lastSuggestedName.current = '';
            currentNameRef.current = '';
            await refresh();
        } catch (err) {
            setAddError(err.message);
            setAddFieldErrors(validationMessages(err));
        } finally {
            setAddSubmitting(false);
        }
    }

    const receiveInput = (name, props = {}) => ({
        value: receiveForm[name],
        onChange: (event) => setReceiveField(name, event.target.value),
        ...props,
    });

    const addInput = (name, props = {}) => ({
        value: addForm[name],
        onChange: (event) => setAddForm((current) => ({ ...current, [name]: event.target.value })),
        ...props,
    });

    return (
        <div className="page">
            {/* Permanent header region (matches the Products page): the hero
                stays put while the body loads, and carries its own load bar. */}
            <section className={`page-hero${loading ? ' is-loading' : ''}`}>
                <div className="page-loadbar" aria-hidden="true" />
                <header className="page-head">
                    <div>
                        <h2>Stock Inventory</h2>
                        <p>Receive deliveries and update stock levels.</p>
                    </div>
                </header>
            </section>

            {toast && <Toast tone="success" onClose={() => setToast(null)}>{toast}</Toast>}

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

                    {/* Receive New Stock panel */}
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

                    {/* Stock Receiving table + pagination */}
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
                                            <th key={i}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4, 5, 6].map((c) => (
                                                <td key={c}>
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

            {!loading && (
                <>
                {stats && (
                    <div className="stat-grid">
                        <article className="card stat">
                            <span>Products</span>
                            <strong>{stats.total_products}</strong>
                            <small className="stat-hint">Items in the catalogue.</small>
                        </article>
                        <article className="card stat">
                            <span>Low stock</span>
                            <strong className={stats.low_stock_count > 0 ? 'text-warn' : ''}>{stats.low_stock_count}</strong>
                            <small className="stat-hint">At or below reorder threshold.</small>
                        </article>
                        <article className="card stat">
                            <span>Critical stock</span>
                            <strong className={stats.critical_stock_count > 0 ? 'text-danger' : ''}>{stats.critical_stock_count ?? 0}</strong>
                            <small className="stat-hint">Out of stock or zero on hand.</small>
                        </article>
                        <article className="card stat">
                            <span>Suppliers</span>
                            <strong>{stats.supplier_count}</strong>
                            <small className="stat-hint">Active suppliers.</small>
                        </article>
                    </div>
                )}

                {/* New-product entry point: the button opens the create + first
                    stock-in form in its own modal (mirrors the Products page). */}
                <section className="card panel panel-add">
                    <div className="panel-head">
                        <div>
                            <h3>Receive New Stock</h3>
                            <p className="muted">Create a catalogue entry and record its first received stock.</p>
                        </div>
                        <button type="button" className="btn btn-primary" onClick={openAdd}>
                            Receive Stock
                        </button>
                    </div>
                </section>

                <section className="card padded">
                    <div className="filter-head">
                        <div>
                            <h3>Search & Filter Options</h3>
                            <p className="muted">Narrow products by name, SKU, category, unit or stock level.</p>
                        </div>
                    </div>
                    <div className="filter-shell">
                        <div className="filter-grid">
                            <label className="field">
                                <span>Search</span>
                                <input
                                    type="search"
                                    placeholder="Search product name or SKU…"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                />
                            </label>
                            <label className="field">
                                <span>Category</span>
                                <select className="input" value={categoryFilter} onChange={(event) => setCategoryFilter(event.target.value)}>
                                    <option value="">All categories</option>
                                    {categories.map((category) => (
                                        <option key={category} value={category}>{category}</option>
                                    ))}
                                </select>
                            </label>
                            <label className="field">
                                <span>Unit</span>
                                <select className="input" value={unitFilter} onChange={(event) => setUnitFilter(event.target.value)}>
                                    <option value="">All units</option>
                                    {unitOptions.map((unit) => (
                                        <option key={unit} value={unit}>{unit}</option>
                                    ))}
                                </select>
                            </label>
                            <label className="field">
                                <span>Stock level</span>
                                <select className="input" value={levelFilter} onChange={(event) => setLevelFilter(event.target.value)}>
                                    <option value="">All levels</option>
                                    <option value="in">In stock</option>
                                    <option value="low">Low stock</option>
                                    <option value="out">Critical / out of stock</option>
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

                {/* One row per catalogue product (mirrors the products/receiving
                    data): the Receive action opens the stock-in modal with the
                    product pre-selected. */}
                <section className="card padded">
                    <div className="filter-head">
                        <div>
                            <h3>Stock Receiving</h3>
                            <p className="muted">Overview of all products and their receiving status.</p>
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
                                            title="Stock Keeping Unit"
                                        >
                                            SKU{sortBy === 'sku' && sortDir === 'asc' ? ' ▲' : sortBy === 'sku' ? ' ▼' : ''}
                                            <span className="th-sub">Stock Keeping Unit</span>
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('category')}
                                        >
                                            Category{sortBy === 'category' && sortDir === 'asc' ? ' ▲' : sortBy === 'category' ? ' ▼' : ''}
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('current_stock')}
                                        >
                                            Current stock{sortBy === 'current_stock' && sortDir === 'asc' ? ' ▲' : sortBy === 'current_stock' ? ' ▼' : ''}
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('receiving_unit')}
                                        >
                                            Unit{sortBy === 'receiving_unit' && sortDir === 'asc' ? ' ▲' : sortBy === 'receiving_unit' ? ' ▼' : ''}
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('last_received_at')}
                                        >
                                            Latest received{sortBy === 'last_received_at' && sortDir === 'asc' ? ' ▲' : sortBy === 'last_received_at' ? ' ▼' : ''}
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pagedProducts.map((product) => (
                                        <tr key={product.id}>
                                            <td>
                                                {highlight(product.name, search.trim())}
                                            </td>
                                            <td className="mono">{product.sku}</td>
                                            <td>{product.category || '—'}</td>
                                            <td>{product.current_stock}</td>
                                            <td>{product.receiving_unit || '—'}</td>
                                            <td>
                                                {product.last_received_at
                                                    ? timeAgo(product.last_received_at)
                                                    : timeAgo(product.created_at)
                                                }
                                            </td>
                                            <td className="actions-cell">
                                                <Tooltip
                                                    label="Edit stock received"
                                                    className="btn btn-ghost btn-sm action-icon-btn"
                                                    onClick={() => openReceive(product)}
                                                >
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                    </svg>
                                                </Tooltip>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {filteredProducts.length === 0 && (
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

                    {filteredProducts.length > 0 && (
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
                            <span className="page-detail">{Math.min(safePage * PRODUCTS_PER_PAGE, filteredProducts.length)} of {filteredProducts.length}</span>
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
                        <div className="table-empty">
                            <span className="table-empty-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M12 3v13" />
                                    <path d="M8 12l4 4 4-4" />
                                    <path d="M3 17v2a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2" />
                                </svg>
                            </span>
                            <p className="table-empty-title">No stock activity yet</p>
                            <p className="table-empty-sub">Receive stock and it will show up here.</p>
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
                </>
            )}

            {receiveOpen && (
                <Modal bare noClose className="modal-edit" onClose={resetReceive}>
                    <form className="form" onSubmit={handleReceive}>
                        <div className="confirm-dialog confirm-dialog-info">
                            <span className="confirm-dialog-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5Z" />
                                </svg>
                            </span>
                            <div className="confirm-dialog-body">
                                <p className="confirm-dialog-title">Edit Stock</p>
                                <p className="muted confirm-dialog-text">Update the receiving details for this product.</p>
                            </div>
                        </div>
                        {/* Both fields are editable: the modal opens prefilled from
                            the clicked row, and a corrected name or SKU is saved
                            together with the receive. */}
                        <div className="field-row">
                            <label className="field">
                                <span>Product name</span>
                                <input
                                    className="input"
                                    type="text"
                                    required
                                    maxLength={255}
                                    value={receiveForm.product_name}
                                    onChange={(event) => setReceiveField('product_name', event.target.value)}
                                />
                            </label>
                        </div>

                        <div className="field-row">
                            <label className="field">
                                <span>SKU code</span>
                                <input
                                    className="input mono"
                                    type="text"
                                    maxLength={50}
                                    value={receiveForm.sku}
                                    onChange={(event) => setReceiveField('sku', event.target.value)}
                                />
                            </label>
                            {selected && (
                                <div className="field">
                                    <span>On hand now</span>
                                    <div className="readonly-value">{selected.current_stock} {selected.receiving_unit}</div>
                                </div>
                            )}
                        </div>

                        <div className="field-row">
                            <label className="field">
                                <span>Supplier</span>
                                <select className="input" {...receiveInput('supplier_id')}>
                                    <option value="">No supplier</option>
                                    {suppliers.map((supplier) => (
                                        <option key={supplier.id} value={supplier.id}>
                                            {supplier.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="field">
                                <span>Receiving unit</span>
                                <input
                                    type="text"
                                    className="input"
                                    maxLength={20}
                                    placeholder="e.g. Per piece"
                                    {...receiveInput('unit_of_measure')}
                                />
                            </label>
                        </div>

                        <div className="field-row-split">
                            <label className="field">
                                <span>Received qty *</span>
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    required
                                    autoFocus
                                    placeholder="e.g. 10"
                                    {...receiveInput('quantity_received')}
                                />
                            </label>
                            <label className="field">
                                <span>Note</span>
                                <input type="text" maxLength={255} placeholder="Delivery receipt #, remarks…" {...receiveInput('note')} />
                            </label>
                        </div>

                        {receiveError && (
                            <div className="alert alert-error" role="alert">
                                <span>{receiveError}</span>
                                <FieldError messages={receiveFieldErrors} />
                            </div>
                        )}

                        <div className="modal-actions modal-actions-center">
                            <button type="button" className="btn btn-ghost" onClick={resetReceive}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={receiveSubmitting}>
                                {receiveSubmitting ? 'Receiving…' : 'Receive'}
                            </button>
                        </div>
                    </form>
                </Modal>
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
                        New Product
                    </span>
                )} onClose={resetAdd} noClose loading={suggesting}>
                    <form className="form" onSubmit={handleAdd}>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">New product name <InfoTip text="The product name as it will appear in the catalogue." /><span className="text-danger">*</span></span>
                                <input
                                    type="text"
                                    required
                                    maxLength={255}
                                    placeholder="e.g. Paint Brush"
                                    autoFocus
                                    value={addForm.name}
                                    onChange={(event) => setAddForm((current) => ({ ...current, name: event.target.value }))}
                                    onKeyDown={handleNameTab}
                                />
                            </label>
                            <label className="field">
                                <span className="field-label">Quantity received <InfoTip text="How many units were received in this stock-in." /><span className="text-danger">*</span></span>
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    required
                                    placeholder="e.g. 10"
                                    {...addInput('quantity_received')}
                                />
                            </label>
                        </div>
                        <div className="choice-field">
                            <span className="field-label">Receiving unit <InfoTip text="The unit each quantity is counted in, such as per piece or per kilo." /></span>
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
                                <span className="field-label">SKU <InfoTip text="Item code used to identify the product." /></span>
                                <input
                                    type="text"
                                    className="mono"
                                    maxLength={50}
                                    placeholder={suggestions.sku || 'e.g. PAI-BRU'}
                                    value={addForm.sku}
                                    onChange={(event) => setAddForm((current) => ({ ...current, sku: event.target.value.toUpperCase() }))}
                                    onKeyDown={handleSuggestionTab('sku')}
                                    disabled={suggesting}
                                />
                            </label>
                            <label className="field">
                                <span className="field-label">Category <InfoTip text="Groups products for easier filtering." /></span>
                                <input
                                    type="text"
                                    maxLength={100}
                                    placeholder={suggestions.category || 'e.g. Paint & Sundries'}
                                    value={addForm.category}
                                    onChange={(event) => setAddForm((current) => ({ ...current, category: event.target.value }))}
                                    onKeyDown={handleSuggestionTab('category')}
                                    disabled={suggesting}
                                />
                            </label>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Supplier <InfoTip text="Supplier who delivered the stock. Choose 'No supplier' if none." /></span>
                                <select className="input" {...addInput('supplier_id')}>
                                    <option value="">No supplier</option>
                                    {suppliers.map((supplier) => (
                                        <option key={supplier.id} value={supplier.id}>
                                            {supplier.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            {/* Spacer keeps Supplier at its half-width column now
                                that Note moved to its own row below. */}
                            <span aria-hidden="true" />
                        </div>
                        <div className="choice-field">
                            <span className="field-label">Note <InfoTip text="Optional remarks such as delivery receipt number." /></span>
                            <div className="choice-list">
                                <label className="choice-item">
                                    <input
                                        type="checkbox"
                                        name="note_type"
                                        value="received"
                                        checked={addForm.note_type === 'received'}
                                        onChange={() => setAddForm((current) => ({ ...current, note_type: current.note_type === 'received' ? '' : 'received' }))}
                                    />
                                    <span>Received</span>
                                </label>
                                <div className="choice-item choice-item-other">
                                    <label className="choice-item-radio">
                                        <input
                                            type="checkbox"
                                            name="note_type"
                                            value="others"
                                            checked={addForm.note_type === 'others'}
                                            onChange={() => setAddForm((current) => ({ ...current, note_type: current.note_type === 'others' ? '' : 'others' }))}
                                        />
                                        <span>Others:</span>
                                    </label>
                                    <input
                                        type="text"
                                        className="input choice-item-input"
                                        maxLength={255}
                                        placeholder="Delivery receipt #, remarks…"
                                        value={addForm.note}
                                        onChange={(event) => setAddForm((current) => ({ ...current, note: event.target.value }))}
                                        disabled={addForm.note_type !== 'others'}
                                    />
                                </div>
                            </div>
                        </div>

                        {addError && (
                            <div className="alert alert-error" role="alert">
                                <span>{addError}</span>
                                <FieldError messages={addFieldErrors} />
                            </div>
                        )}

                        <div className="modal-actions modal-actions-center">
                            <button type="button" className="btn btn-ghost" onClick={resetAdd}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={addSubmitting}>
                                {addSubmitting ? 'Receiving…' : 'Receive'}
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
        </div>
    );
}
