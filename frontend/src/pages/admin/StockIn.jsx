import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { usePageLoadbar } from '../../components/PageLoadbar.jsx';
import { FieldError, Toast } from '../../components/Feedback.jsx';
import { get, post, put, del, validationMessages } from '../../lib/api.js';
import { timeAgo } from '../../lib/format.js';
import { clearConnectedNav, notifyConnectedNav } from '../../lib/navBadges.js';
import { prefetchPriceHint } from '../../lib/priceHints.js';
import Modal from '../../components/Modal.jsx';
import ConfirmDialog from '../../components/ConfirmDialog.jsx';
import MessageDialog from '../../components/MessageDialog.jsx';
import Tooltip from '../../components/Tooltip.jsx';
import InfoTip from '../../components/InfoTip.jsx';

const ADJUST_REASONS = [
    { value: 'damaged', label: 'Damaged items' },
    { value: 'lost', label: 'Lost / missing' },
    { value: 'internal_transfer', label: 'Internal transfer' },
    { value: 'correction', label: 'Count correction' },
];

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

const RECEIVING_UNIT_OPTIONS = [
    { value: 'piece', label: 'piece(pc)', suffix: 'pc', sizePlaceholder: 'e.g. 1' },
    { value: 'inch', label: 'inch(in)', suffix: 'in', sizePlaceholder: 'e.g. 1' },
    { value: 'millimeter', label: 'millimeter(mm)', suffix: 'mm', sizePlaceholder: 'e.g. 50' },
    { value: 'centimeter', label: 'centimeter(cm)', suffix: 'cm', sizePlaceholder: 'e.g. 30' },
    { value: 'meter', label: 'meter(m)', suffix: 'm', sizePlaceholder: 'e.g. 3' },
    { value: 'foot', label: 'foot(ft)', suffix: 'ft', sizePlaceholder: 'e.g. 8' },
    { value: 'kilogram', label: 'kilogram(kg)', suffix: 'kg', sizePlaceholder: 'e.g. 40' },
    { value: 'gram', label: 'gram(g)', suffix: 'g', sizePlaceholder: 'e.g. 500' },
    { value: 'ounce', label: 'ounce(oz)', suffix: 'oz', sizePlaceholder: 'e.g. 16' },
    { value: 'liter', label: 'liter(L)', suffix: 'L', sizePlaceholder: 'e.g. 4' },
    { value: 'milliliter', label: 'milliliter(mL)', suffix: 'mL', sizePlaceholder: 'e.g. 500' },
    { value: 'gallon', label: 'gallon(gal)', suffix: 'gal', sizePlaceholder: 'e.g. 1' },
    { value: 'volt', label: 'volt(V)', suffix: 'V', sizePlaceholder: 'e.g. 18' },
    { value: 'watt', label: 'watt(W)', suffix: 'W', sizePlaceholder: 'e.g. 12' },
    { value: 'gauge', label: 'gauge(AWG)', suffix: 'AWG', sizePlaceholder: 'e.g. 12' },
    { value: 'box', label: 'box(bx)', suffix: 'bx', sizePlaceholder: 'e.g. 1' },
    { value: 'sack', label: 'sack(sck)', suffix: 'sck', sizePlaceholder: 'e.g. 40' },
    { value: 'bag', label: 'bag(bag)', suffix: 'bag', sizePlaceholder: 'e.g. 1' },
    { value: 'pack', label: 'pack(pk)', suffix: 'pk', sizePlaceholder: 'e.g. 1' },
    { value: 'set', label: 'set(set)', suffix: 'set', sizePlaceholder: 'e.g. 1' },
    { value: 'roll', label: 'roll(rl)', suffix: 'rl', sizePlaceholder: 'e.g. 1' },
    { value: 'bundle', label: 'bundle(bdl)', suffix: 'bdl', sizePlaceholder: 'e.g. 1' },
    { value: 'can', label: 'can(can)', suffix: 'can', sizePlaceholder: 'e.g. 1' },
    { value: 'bottle', label: 'bottle(btl)', suffix: 'btl', sizePlaceholder: 'e.g. 1' },
    { value: 'tube', label: 'tube(tb)', suffix: 'tb', sizePlaceholder: 'e.g. 1' },
    { value: 'sheet', label: 'sheet(sht)', suffix: 'sht', sizePlaceholder: 'e.g. 1' },
];

const UNIT_SUGGESTION_RULES = [
    {
        pattern: /\b(pako|nail|nails|turnilyo|screw|screws|bolt|bolts|nut|washer|rivet|staple)\b/i,
        defaultUnit: 'inch',
        units: ['inch', 'millimeter', 'kilogram', 'piece', 'box'],
        sizePlaceholder: 'e.g. 1',
    },
    {
        pattern: /\b(pipe|tubo|pvc|lumber|kahoy|plywood|rebar|bakal|conduit|hose|tali|rope)\b/i,
        defaultUnit: 'inch',
        units: ['inch', 'millimeter', 'meter', 'foot', 'piece'],
        sizePlaceholder: 'e.g. 2',
    },
    {
        pattern: /\b(wire|kawad|cable|thhn|cord)\b/i,
        defaultUnit: 'gauge',
        units: ['gauge', 'meter', 'roll', 'millimeter'],
        sizePlaceholder: 'e.g. 12',
    },
    {
        pattern: /\b(brush|brotsa|roller)\b/i,
        defaultUnit: 'inch',
        units: ['inch', 'piece', 'set'],
        sizePlaceholder: 'e.g. 2',
    },
    {
        pattern: /\b(paint|pintura|thinner|primer|varnish|enamel|latex|epoxy|glue|rugby|oil|sealant)\b/i,
        defaultUnit: 'liter',
        units: ['liter', 'gallon', 'milliliter', 'can', 'bottle'],
        sizePlaceholder: 'e.g. 4',
    },
    {
        pattern: /\b(cement|semento|sand|buhangin|gravel|graba|skim|putty|grout|masilya)\b/i,
        defaultUnit: 'kilogram',
        units: ['kilogram', 'sack', 'bag'],
        sizePlaceholder: 'e.g. 40',
    },
    {
        pattern: /\b(hammer|martilyo|mallet|wrench|pliers|screwdriver|chisel|saw|lagari)\b/i,
        defaultUnit: 'ounce',
        units: ['ounce', 'inch', 'piece', 'set'],
        sizePlaceholder: 'e.g. 16',
    },
    {
        pattern: /\b(bulb|led|lamp|ilaw|spotlight|floodlight)\b/i,
        defaultUnit: 'watt',
        units: ['watt', 'volt', 'piece', 'set'],
        sizePlaceholder: 'e.g. 12',
    },
    {
        pattern: /\b(drill|grinder|sander|battery|charger|breaker|outlet|switch)\b/i,
        defaultUnit: 'volt',
        units: ['volt', 'watt', 'piece', 'set'],
        sizePlaceholder: 'e.g. 18',
    },
    {
        pattern: /\b(padlock|kandado|lock|hinge|bisagra)\b/i,
        defaultUnit: 'millimeter',
        units: ['millimeter', 'inch', 'piece', 'set'],
        sizePlaceholder: 'e.g. 50',
    },
];

function matchUnitSuggestion(productName) {
    const text = String(productName ?? '').trim();
    if (!text) return null;
    return UNIT_SUGGESTION_RULES.find((rule) => rule.pattern.test(text)) ?? null;
}

const CATEGORY_SUGGESTION_RULES = [
    {
        pattern: /\b(pako|nail|nails|turnilyo|screw|screws|bolt|bolts|nut|nuts|washer|rivet|staple|anchor)\b/i,
        category: 'Fasteners',
    },
    {
        pattern: /\b(pipe|tubo|pvc|faucet|gripo|valve|elbow|coupling|hose|drain|teflon|plumbing)\b/i,
        category: 'Plumbing',
    },
    {
        pattern: /\b(wire|kawad|cable|thhn|cord|bulb|led|lamp|ilaw|spotlight|floodlight|breaker|outlet|switch|socket|fuse|conduit)\b/i,
        category: 'Electrical',
    },
    {
        pattern: /\b(paint|pintura|thinner|primer|varnish|enamel|latex|epoxy|glue|rugby|sealant|brush|brotsa|roller|lacquer)\b/i,
        category: 'Paints & Coatings',
    },
    {
        pattern: /\b(cement|semento|sand|buhangin|gravel|graba|lumber|kahoy|plywood|rebar|bakal|yero|hollow|block|skim|putty|grout|masilya|tile|tiles)\b/i,
        category: 'Building Materials',
    },
    {
        pattern: /\b(drill|grinder|sander|jigsaw|blower|welder|welding|compressor|impact|rotary|cordless)\b/i,
        category: 'Power Tools',
    },
    {
        pattern: /\b(hammer|martilyo|mallet|wrench|liyabe|pliers|plais|screwdriver|distornilyador|chisel|pait|saw|lagari|shovel|pala|crowbar|bareta|trowel|paleta|clamp|cutter|padlock|kandado|hinge|bisagra)\b/i,
        category: 'Hand Tools',
    },
];

function matchCategorySuggestion(productName, availableCategories = []) {
    const text = String(productName ?? '').trim();
    if (!text) return null;
    const rule = CATEGORY_SUGGESTION_RULES.find((r) => r.pattern.test(text));
    if (!rule) return null;
    const exact = availableCategories.find(
        (cat) => String(cat).trim().toLowerCase() === rule.category.toLowerCase(),
    );
    return exact ?? rule.category;
}

const EMPTY_RECEIVE_FORM = {
    product_id: '',
    product_name: '',
    sku: '',
    category: '',
    price: 0,
    current_stock: '',
    reorder_threshold: '',
    supplier_id: '',
    unit_of_measure: '',
    quantity_received: '',
    note_type: '',
    note: '',
};

const EMPTY_ADD_STOCK_FORM = {
    product_id: '',
    product_name: '',
    current_stock: '',
    supplier_id: '',
    unit_of_measure: '',
    quantity_received: '',
    note_type: '',
    note: '',
};

const EMPTY_ADD_FORM = {
    name: '',
    spec_value: '',
    sku: '',
    category: '',
    supplier_id: '',
    unit_choice: '',
    unit_other: '',
    quantity_received: '',
    reorder_threshold: '',
    note_type: '',
    note: '',
};

const PRODUCTS_PER_PAGE = 6;

const STOCK_TABS = [
    {
        key: 'receive',
        label: 'Receive Stock',
        title: 'Stock Receiving',
        description: 'Active items ready for incoming stock deliveries.',
        emptyTitle: 'No active stock items yet',
        emptySub: 'Receive a new stock item to populate this list.',
    },
    {
        key: 'in',
        label: 'In Stock',
        title: 'In Stock Items',
        description: 'Active items currently above their reorder threshold.',
        emptyTitle: 'No in-stock items',
        emptySub: 'Active items with stock above their reorder threshold will appear here.',
    },
    {
        key: 'low',
        label: 'Low Stock',
        title: 'Low Stock Items',
        description: 'Active items at or below their reorder threshold.',
        emptyTitle: 'No low-stock items',
        emptySub: 'Items that drop to or below their reorder threshold will appear here.',
    },
    {
        key: 'out',
        label: 'Critical Stock',
        title: 'Critical Stock Items',
        description: 'Active items with zero stock on hand.',
        emptyTitle: 'No critical-stock items',
        emptySub: 'Active items that reach zero stock will appear here.',
    },
    {
        key: 'deactivated',
        label: 'Deactivated',
        title: 'Deactivated Items',
        description: 'Items manually deactivated from receiving and active catalogue.',
        emptyTitle: 'No deactivated items',
        emptySub: 'Deactivated items will appear here and can be reactivated anytime.',
    },
    {
        key: 'no_supplier',
        label: 'No Supplier',
        title: 'Items Without Supplier',
        description: 'Stock items not currently linked to any supplier.',
        emptyTitle: 'No items without a supplier',
        emptySub: 'Items with no assigned supplier will appear here.',
    },
    {
        key: 'inactive_supplier',
        label: 'Inactive Supplier',
        title: 'Inactive Supplier Items',
        description: 'Items deactivated because their linked supplier was removed.',
        emptyTitle: 'No inactive-supplier items',
        emptySub: 'Items whose supplier was removed will appear here.',
    },
    {
        key: 'defect',
        label: 'Defect Items',
        title: 'Defect Items',
        description: 'Damaged items deducted via stock adjustments.',
        emptyTitle: 'No defect items yet',
        emptySub: 'Damaged items deducted via stock adjustments will appear here.',
    },
    {
        key: 'all',
        label: 'All',
        title: 'All Stock Items',
        description: 'Complete overview of every stock item across all statuses.',
        emptyTitle: 'No products yet',
        emptySub: 'Receive a new product to populate this list.',
    },
];

function stockStateKey(product) {
    if (product.is_defect) {
        return 'defect';
    }

    if (product.is_active === false || product.is_active === 0) {
        return 'inactive';
    }

    if (Number(product.current_stock) <= 0) {
        return 'out';
    }

    if (Number(product.current_stock) <= Number(product.reorder_threshold)) {
        return 'low';
    }

    return 'in';
}

function stockStatusPill(product) {
    if (product.is_defect) {
        return { label: 'Defect items', className: 'pill-danger', rank: 0 };
    }

    if (product.is_active === false || product.is_active === 0) {
        if (product.last_supplier_active === false) {
            return { label: 'Inactive supplier', className: 'pill-danger', rank: 1 };
        }
        return { label: 'Deactivated', className: 'pill-danger', rank: 2 };
    }

    if (Number(product.current_stock) <= 0) {
        return { label: 'Critical stock', className: 'pill-danger', rank: 3 };
    }

    if (Number(product.current_stock) <= Number(product.reorder_threshold)) {
        return { label: 'Low stock', className: 'pill-warn', rank: 4 };
    }

    return { label: 'In stock', className: 'pill-ok', rank: 5 };
}

export default function StockIn() {
    const navigate = useNavigate();
    const stockTableRef = useRef(null);
    const statGridRef = useRef(null);
    const [activeStat, setActiveStat] = useState(null);
    const [viewingStock, setViewingStock] = useState(null);
    const [stockTab, setStockTab] = useState('receive');
    const [products, setProducts] = useState([]);
    const [suppliers, setSuppliers] = useState([]);
    const [categoriesList, setCategoriesList] = useState([]);
    const [stats, setStats] = useState(null);
    // Drives the hero's load bar only; the sections below render as data arrives.
    const [loading, setLoading] = useState(true);
    usePageLoadbar(loading);

    const [toast, setToast] = useState(null);

    // Receive Stock — the per-row Receive button opens the modal with the
    // product pre-selected.
    const [receiveOpen, setReceiveOpen] = useState(false);
    const [receiveForm, setReceiveForm] = useState(EMPTY_RECEIVE_FORM);
    const [receiveSubmitting, setReceiveSubmitting] = useState(false);
    const [receiveError, setReceiveError] = useState(null);
    const [receiveFieldErrors, setReceiveFieldErrors] = useState([]);

    // Compact Add Stock modal state (quantity + note only)
    const [addStockOpen, setAddStockOpen] = useState(false);
    const [addStockForm, setAddStockForm] = useState(EMPTY_ADD_STOCK_FORM);
    const [addStockSubmitting, setAddStockSubmitting] = useState(false);
    const [addStockError, setAddStockError] = useState(null);
    const [addStockFieldErrors, setAddStockFieldErrors] = useState([]);

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
    const reorderTouchedRef = useRef(false);
    const unitTouchedRef = useRef(false);
    const categoryTouchedRef = useRef(false);
    const [showAllUnits, setShowAllUnits] = useState(false);
    const [showAllCategories, setShowAllCategories] = useState(false);

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
    const [clearConfirm, setClearConfirm] = useState(false);
    const [clearing, setClearing] = useState(false);
    const [clearError, setClearError] = useState(null);
    const [activityOptionsOpen, setActivityOptionsOpen] = useState(false);
    const activityOptionsRef = useRef(null);
    const [stockOptionsOpen, setStockOptionsOpen] = useState(false);
    const stockOptionsRef = useRef(null);
    const [seeding, setSeeding] = useState(false);
    const [deleteAllConfirm, setDeleteAllConfirm] = useState(false);
    const [deletingAll, setDeletingAll] = useState(false);
    const [deleteAllError, setDeleteAllError] = useState(null);
    const [emptyNotice, setEmptyNotice] = useState(null);
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
    const ACTIVITY_PER_PAGE = 3;

    // Options dropdowns (kebabs) on Stock Receiving and Recent Activity card
    // headers: close on outside click and Escape, matching the other admin pages.
    useEffect(() => {
        if (!activityOptionsOpen && !stockOptionsOpen) {
            return;
        }

        const onDown = (event) => {
            if (activityOptionsRef.current && !activityOptionsRef.current.contains(event.target)) {
                setActivityOptionsOpen(false);
            }
            if (stockOptionsRef.current && !stockOptionsRef.current.contains(event.target)) {
                setStockOptionsOpen(false);
            }
        };

        const onKey = (event) => {
            if (event.key === 'Escape') {
                setActivityOptionsOpen(false);
                setStockOptionsOpen(false);
            }
        };

        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [activityOptionsOpen, stockOptionsOpen]);

    const refresh = useCallback(async () => {
        const [productsRes, suppliersRes, categoriesRes, statsRes, feedRes] = await Promise.allSettled([
            get('/api/inventory/products'),
            get('/api/suppliers/active'),
            get('/api/categories'),
            get('/api/dashboard/stats'),
            get('/api/activity/recent', { limit: 12, actions: 'stock_received,stock_adjusted,product_deactivated,product_reactivated' }),
        ]);

        if (productsRes.status === 'fulfilled') {
            setProducts(Array.isArray(productsRes.value) ? productsRes.value : []);
        }
        if (suppliersRes.status === 'fulfilled') {
            setSuppliers(Array.isArray(suppliersRes.value) ? suppliersRes.value : []);
        }
        if (categoriesRes.status === 'fulfilled') {
            const rawCats = categoriesRes.value?.categories ?? categoriesRes.value;
            setCategoriesList(Array.isArray(rawCats) ? rawCats : []);
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
    const runSuggest = useCallback(async (name, spec = '') => {
        const clean = name.trim();
        if (clean.length < 2) {
            return;
        }

        const key = `${clean}|${spec}`;
        setSuggesting(true);

        try {
            const response = await post('/api/inventory/suggest', {
                name: clean,
                spec: spec || undefined,
            });

            // Ignore stale responses: only apply if the box still holds this name+spec.
            if (currentNameRef.current !== key) {
                return;
            }

            const s = response.suggestion || {};
            setSuggestions({ sku: s.sku || '', category: s.category || '' });
            if (s.category && !categoryTouchedRef.current) {
                setAddForm((current) => {
                    if (categoryTouchedRef.current) return current;
                    return { ...current, category: s.category };
                });
            }
            lastSuggestedName.current = key;
        } catch {
            if (currentNameRef.current === key) {
                setSuggestions({});
            }
        } finally {
            if (currentNameRef.current === key) {
                setSuggesting(false);
            }
        }
    }, []);

    useEffect(() => {
        const baseName = addForm.name.trim();
        const specDigits = String(addForm.spec_value ?? '').replace(/[^0-9]/g, '');
        const unitMeta = RECEIVING_UNIT_OPTIONS.find((opt) => opt.value === addForm.unit_choice);
        const unitSuffix = addForm.unit_choice === 'other'
            ? String(addForm.unit_other ?? '').trim()
            : (unitMeta?.suffix ?? '');
        const specSuffix = specDigits ? `${specDigits}${unitSuffix}` : '';
        const key = `${baseName}|${specSuffix}`;

        currentNameRef.current = key;

        if (!addOpen) {
            return undefined;
        }

        if (baseName.length < 2) {
            clearTimeout(suggestTimer.current);
            setSuggestions({});
            setSuggesting(false);
            lastSuggestedName.current = '';
            return undefined;
        }

        if (key !== lastSuggestedName.current) {
            clearTimeout(suggestTimer.current);
            suggestTimer.current = setTimeout(() => runSuggest(baseName, specSuffix), 250);
        }

        return () => clearTimeout(suggestTimer.current);
    }, [addForm.name, addForm.spec_value, addForm.unit_choice, addForm.unit_other, addOpen, runSuggest]);

    const categories = useMemo(
        () => [...new Set(products.map((product) => product.category).filter(Boolean))]
            .sort((a, b) => a.localeCompare(b)),
        [products]
    );

    const dropdownCategories = useMemo(() => {
        const set = new Set();
        categoriesList.forEach((cat) => {
            if (cat?.name) set.add(cat.name);
        });
        products.forEach((product) => {
            if (product?.category && product.category !== 'Uncategorized') set.add(product.category);
        });
        return [...set].sort((a, b) => a.localeCompare(b));
    }, [categoriesList, products]);

    const unitOptions = useMemo(
        () => [...new Set(products.map((product) => product.receiving_unit).filter(Boolean))]
            .sort((a, b) => a.localeCompare(b)),
        [products]
    );

    const activeTabMeta = STOCK_TABS.find((tab) => tab.key === stockTab) ?? STOCK_TABS[0];

    const tabProducts = useMemo(() => {
        switch (stockTab) {
            case 'receive':
                return products.filter((p) => Boolean(p.is_active) && !p.is_defect);
            case 'in':
                return products.filter((p) => stockStateKey(p) === 'in');
            case 'low':
                return products.filter((p) => stockStateKey(p) === 'low');
            case 'out':
                return products.filter((p) => stockStateKey(p) === 'out');
            case 'deactivated':
                return products.filter((p) => !p.is_active && !p.is_defect && p.last_supplier_active !== false);
            case 'no_supplier':
                return products.filter((p) => !p.is_defect && !p.last_supplier_id && !p.last_supplier_name);
            case 'inactive_supplier':
                return products.filter((p) => !p.is_active && !p.is_defect && p.last_supplier_active === false);
            case 'defect':
                return products.filter((p) => Boolean(p.is_defect));
            case 'all':
            default:
                return products;
        }
    }, [products, stockTab]);

    const filteredProducts = useMemo(() => {
        const q = search.trim().toLowerCase();

        return tabProducts.filter((product) => {
            const matchesSearch = !q
                || String(product.name ?? '').toLowerCase().includes(q)
                || String(product.sku ?? '').toLowerCase().includes(q)
                || String(product.category ?? '').toLowerCase().includes(q)
                || String(product.last_supplier_name ?? '').toLowerCase().includes(q);
            const matchesCategory = !categoryFilter || product.category === categoryFilter;
            const matchesUnit = !unitFilter || product.receiving_unit === unitFilter;
            const matchesLevel = !levelFilter || stockStateKey(product) === levelFilter;

            return matchesSearch && matchesCategory && matchesUnit && matchesLevel;
        });
    }, [tabProducts, search, categoryFilter, unitFilter, levelFilter]);

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

            // Numeric comparison for stock & reorder threshold
            if (sortBy === 'current_stock' || sortBy === 'reorder_threshold') {
                av = Number(av ?? 0);
                bv = Number(bv ?? 0);
                return (av - bv) * dir;
            }

            // Status comparison
            if (sortBy === 'status') {
                return (stockStatusPill(a).rank - stockStatusPill(b).rank) * dir;
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

    useEffect(() => {
        if (!activeStat) return undefined;
        const onMouseDown = (event) => {
            if (!statGridRef.current?.contains(event.target)) {
                setActiveStat(null);
            }
        };
        document.addEventListener('mousedown', onMouseDown);
        return () => document.removeEventListener('mousedown', onMouseDown);
    }, [activeStat]);

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
            category: product.category && product.category !== 'Uncategorized' ? product.category : '',
            price: Number(product.price ?? 0),
            current_stock: String(product.current_stock ?? 0),
            reorder_threshold: String(product.reorder_threshold ?? 0),
            supplier_id: product.last_supplier_id && product.last_supplier_active !== false
                ? String(product.last_supplier_id)
                : '',
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

    function openAddStock(product) {
        setAddStockForm({
            ...EMPTY_ADD_STOCK_FORM,
            product_id: String(product.id),
            product_name: product.name || '',
            current_stock: String(product.current_stock ?? 0),
            supplier_id: product.last_supplier_id && product.last_supplier_active !== false
                ? String(product.last_supplier_id)
                : '',
            unit_of_measure: product.receiving_unit || '',
        });
        setAddStockError(null);
        setAddStockFieldErrors([]);
        setAddStockOpen(true);
    }

    function resetAddStock() {
        setAddStockOpen(false);
        setAddStockError(null);
        setAddStockFieldErrors([]);
    }

    function openAdd() {
        setAddForm(EMPTY_ADD_FORM);
        setSuggestions({});
        setSuggesting(false);
        lastSuggestedName.current = '';
        currentNameRef.current = '';
        reorderTouchedRef.current = false;
        unitTouchedRef.current = false;
        categoryTouchedRef.current = false;
        setShowAllUnits(false);
        setShowAllCategories(false);
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
        reorderTouchedRef.current = false;
        unitTouchedRef.current = false;
        categoryTouchedRef.current = false;
        setShowAllUnits(false);
        setShowAllCategories(false);
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
            category: addForm.category || '',
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

    async function handleClearActivity() {
        setClearing(true);
        setClearError(null);

        try {
            const response = await del('/api/activity/recent?actions=stock_received,stock_adjusted,product_deactivated,product_reactivated');

            setClearConfirm(false);
            setActivity([]);
            clearConnectedNav('productAdded');
            setActivityPage(1);
            setToast(response?.message ?? 'Activity list cleared.');
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
            setToast(response?.message ?? '7 sample products added.');
            notifyConnectedNav('productAdded', response?.created ?? 7);
            if (Number(response?.created_categories) > 0) {
                notifyConnectedNav('categoriesSynced', Number(response.created_categories));
            }
            if (Number(response?.created_suppliers) > 0) {
                notifyConnectedNav('suppliersSynced', Number(response.created_suppliers));
            }
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
            clearConnectedNav('productAdded');
            if (deleted > 0) {
                notifyConnectedNav('productAdded', -deleted, '/stock-in');
            }
            setToast(response?.message ?? 'All products deleted.');
            await refresh();
        } catch (err) {
            setDeleteAllError(validationMessages(err)[0] ?? err.message ?? 'Could not delete products.');
        } finally {
            setDeletingAll(false);
        }
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
            const response = await put('/api/inventory/update', {
                id: Number(receiveForm.product_id),
                name: receiveForm.product_name.trim(),
                sku: receiveForm.sku.trim(),
                category: receiveForm.category || null,
                price: Number(receiveForm.price ?? 0),
                current_stock: Number(receiveForm.current_stock ?? 0),
                reorder_threshold: Number(receiveForm.reorder_threshold ?? 0),
                receiving_unit: receiveForm.unit_of_measure || null,
                supplier_id: receiveForm.supplier_id ? Number(receiveForm.supplier_id) : null,
            });

            setToast(response?.message ?? 'Stock details updated successfully.');
            setReceiveOpen(false);
            await refresh();
        } catch (err) {
            setReceiveError(err.message);
            setReceiveFieldErrors(validationMessages(err));
        } finally {
            setReceiveSubmitting(false);
        }
    }

    async function handleAddStock(event) {
        event.preventDefault();
        setAddStockSubmitting(true);
        setAddStockError(null);
        setAddStockFieldErrors([]);

        try {
            const resolvedNote = addStockForm.note_type === 'other'
                ? (addStockForm.note.trim() || null)
                : (addStockForm.note_type || null);

            const response = await post('/api/inventory/stock-in', {
                product_id: addStockForm.product_id || null,
                supplier_id: addStockForm.supplier_id || null,
                quantity_received: addStockForm.quantity_received,
                unit_of_measure: addStockForm.unit_of_measure || null,
                note: resolvedNote,
            });

            setToast(stockInToast(response));
            setAddStockOpen(false);
            notifyConnectedNav('stockReceived', 1);
            await refresh();
        } catch (err) {
            setAddStockError(err.message);
            setAddStockFieldErrors(validationMessages(err));
        } finally {
            setAddStockSubmitting(false);
        }
    }

    async function handleAdjust(event) {
        event.preventDefault();
        if (!adjustProduct) return;
        setAdjustSaving(true);
        setAdjustError(null);

        try {
            const response = await post('/api/inventory/adjust', {
                product_id: adjustProduct.id,
                delta: Number(adjustForm.delta),
                reason: adjustForm.reason,
                reason_note: adjustForm.reason_note || null,
            });

            setToast(`${response.message}: ${response.adjustment.stock_before} → ${response.adjustment.stock_after}.`);
            const adjustedDelta = Number(response.adjustment?.delta ?? adjustForm.delta);
            if (adjustedDelta < 0) {
                notifyConnectedNav('productDeactivated', adjustedDelta);
            } else if (adjustedDelta > 0) {
                notifyConnectedNav('productReactivated', adjustedDelta);
            }
            setAdjustProduct(null);
            await refresh();
        } catch (err) {
            setAdjustError(validationMessages(err)[0] ?? err.message ?? 'Could not adjust stock.');
        } finally {
            setAdjustSaving(false);
        }
    }

    async function handleDeactivate(event) {
        event?.preventDefault();
        if (!deactivateProduct) return;
        setDeactivateSaving(true);
        setDeactivateError(null);

        try {
            const response = await del(`/api/inventory/${deactivateProduct.id}`);
            setToast(response?.message ?? 'Product deactivated.');
            setDeactivateProduct(null);
            notifyConnectedNav('productDeactivated', -1);
            await refresh();
        } catch (err) {
            setDeactivateError(validationMessages(err)[0] ?? err.message ?? 'Could not deactivate product.');
        } finally {
            setDeactivateSaving(false);
        }
    }

    async function handleReactivate(event) {
        event?.preventDefault();
        if (!reactivateProduct) return;
        setReactivateSaving(true);
        setReactivateError(null);

        try {
            const response = await del(`/api/inventory/${reactivateProduct.id}?reactivate=1`);
            setToast(response?.message ?? 'Product reactivated.');
            setReactivateProduct(null);
            notifyConnectedNav('productReactivated', 1);
            await refresh();
        } catch (err) {
            setReactivateError(validationMessages(err)[0] ?? err.message ?? 'Could not activate product.');
        } finally {
            setReactivateSaving(false);
        }
    }

    async function handleAdd(event) {
        event.preventDefault();
        setAddSubmitting(true);
        setAddError(null);
        setAddFieldErrors([]);

        try {
            const merged = mergeSuggestions();
            const baseName = String(merged.name ?? '').trim();
            const specDigits = String(merged.spec_value ?? '').replace(/[^0-9]/g, '');
            const unitMeta = RECEIVING_UNIT_OPTIONS.find((opt) => opt.value === merged.unit_choice);
            const unitSuffix = merged.unit_choice === 'other'
                ? String(merged.unit_other ?? '').trim()
                : (unitMeta?.suffix ?? '');
            const specSuffix = specDigits
                ? (unitSuffix === 'AWG'
                    ? `${specDigits} ${unitSuffix}`
                    : `${specDigits}${unitSuffix}`)
                : '';
            const fullProductName = specSuffix && !baseName.toLowerCase().endsWith(specSuffix.toLowerCase())
                ? `${baseName} ${specSuffix}`
                : baseName;

            const response = await post('/api/inventory/stock-in', {
                new_product_name: fullProductName,
                custom_sku: merged.sku || null,
                custom_category: merged.category || null,
                unit_of_measure: merged.unit_choice === 'other' ? (merged.unit_other.trim() || null) : merged.unit_choice,
                supplier_id: merged.supplier_id || null,
                quantity_received: merged.quantity_received,
                reorder_threshold: merged.reorder_threshold !== '' ? Number(merged.reorder_threshold) : 0,
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
            notifyConnectedNav('stockReceivedNew', 1);
            if (response?.product?.id) {
                // Pre-fetch AI price suggestion in the background so Products -> Add Price is instant.
                prefetchPriceHint(
                    response.product.id,
                    response.product.name || fullProductName,
                    response.product.receiving_unit || resolvedUnit || 'piece',
                );
            }
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
            <section className="page-hero">
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
                                        {[0, 1, 2, 3, 4, 5].map((i) => (
                                            <th key={i}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4, 5].map((c) => (
                                                <td key={c}>
                                                    <span className="skeleton skeleton-line" style={{ width: c === 5 ? '62%' : '85%' }} />
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
                    <div className="stat-grid" ref={statGridRef}>
                        <button
                            type="button"
                            className={`card stat stat-clickable${activeStat === 'products' ? ' is-active' : ''}`}
                            onClick={() => {
                                setActiveStat('products');
                                setStockTab('all');
                                clearFilters();
                                setProductPage(1);
                                stockTableRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }}
                        >
                            <span>Overall stocks</span>
                            <strong>{products.length}</strong>
                            <small className="stat-hint">All stock items across every status.</small>
                        </button>
                        <button
                            type="button"
                            className={`card stat stat-clickable${activeStat === 'low' ? ' is-active' : ''}`}
                            onClick={() => {
                                const next = stockTab === 'low' ? 'receive' : 'low';
                                setActiveStat(next === 'low' ? 'low' : null);
                                setStockTab(next);
                                clearFilters();
                                setProductPage(1);
                                stockTableRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }}
                        >
                            <span>Low stock</span>
                            <strong className={stats.low_stock_count > 0 ? 'text-warn' : ''}>{stats.low_stock_count}</strong>
                            <small className="stat-hint">At or below reorder threshold.</small>
                        </button>
                        <button
                            type="button"
                            className={`card stat stat-clickable${activeStat === 'out' ? ' is-active' : ''}`}
                            onClick={() => {
                                const next = stockTab === 'out' ? 'receive' : 'out';
                                setActiveStat(next === 'out' ? 'out' : null);
                                setStockTab(next);
                                clearFilters();
                                setProductPage(1);
                                stockTableRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }}
                        >
                            <span>Critical stock</span>
                            <strong className={stats.critical_stock_count > 0 ? 'text-danger' : ''}>{stats.critical_stock_count ?? 0}</strong>
                            <small className="stat-hint">Out of stock or zero on hand.</small>
                        </button>
                        <button
                            type="button"
                            className="card stat stat-clickable"
                            onClick={() => navigate('/suppliers', { state: { fromStockIn: true } })}
                        >
                            <span>Suppliers</span>
                            <strong>{stats.supplier_count}</strong>
                            <small className="stat-hint">Active suppliers.</small>
                        </button>
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
                            Receive New Stock
                        </button>
                    </div>
                </section>

                <section className="card padded">
                    <div className="filter-head">
                        <div>
                            <h3>Search & Filter Options</h3>
                            <p className="muted">Narrow products by name, SKU, category, unit or stock status.</p>
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
                                <span>Stock status</span>
                                <select className="input" value={levelFilter} onChange={(event) => setLevelFilter(event.target.value)}>
                                    <option value="">All statuses</option>
                                    <option value="in">In stock</option>
                                    <option value="low">Low stock</option>
                                    <option value="out">Critical / out of stock</option>
                                    <option value="defect">Defect items</option>
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

                <section className="card padded tabs-card">
                    <div className="mode-tabs" role="tablist">
                        {STOCK_TABS.map((tab) => (
                            <button
                                key={tab.key}
                                type="button"
                                role="tab"
                                aria-selected={stockTab === tab.key}
                                className={`mode-tab${stockTab === tab.key ? ' is-active' : ''}`}
                                onClick={() => {
                                    setStockTab(tab.key);
                                    setProductPage(1);
                                }}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>
                </section>

                {/* One row per catalogue product (mirrors the products/receiving
                    data): the Receive action opens the stock-in modal with the
                    product pre-selected. */}
                <section className="card padded" ref={stockTableRef}>
                    <div className="filter-head">
                        <div>
                            <h3>{activeTabMeta.title}</h3>
                            <p className="muted">{activeTabMeta.description}</p>
                        </div>
                        <div className="activity-options" ref={stockOptionsRef}>
                            <button
                                type="button"
                                className="icon-btn is-outlined"
                                onClick={() => setStockOptionsOpen((open) => !open)}
                                aria-haspopup="menu"
                                aria-expanded={stockOptionsOpen}
                                aria-label="Stock receiving options"
                            >
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                                    <circle cx="12" cy="5" r="1.7" />
                                    <circle cx="12" cy="12" r="1.7" />
                                    <circle cx="12" cy="19" r="1.7" />
                                </svg>
                            </button>
                            {stockOptionsOpen && (
                                <div className="activity-options-panel" role="menu" aria-label="Stock receiving options">
                                    <button
                                        type="button"
                                        className="activity-options-item"
                                        role="menuitem"
                                        disabled={seeding || deletingAll}
                                        onClick={() => {
                                            setStockOptionsOpen(false);
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
                                            setStockOptionsOpen(false);

                                            if (products.length === 0) {
                                                setEmptyNotice({
                                                    title: 'No products to delete',
                                                    description: 'The stock list is already empty. Receive a new product first.',
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
                                            onClick={() => toggleSort('last_supplier_name')}
                                        >
                                            Supplier
                                            <span className={`sort-indicator${sortBy === 'last_supplier_name' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'last_supplier_name' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('current_stock')}
                                        >
                                            <span className="sort-header-inner">
                                                <span>Current<br />stock</span>
                                                <span className={`sort-indicator${sortBy === 'current_stock' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'current_stock' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
                                            </span>
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('last_received_at')}
                                        >
                                            <span className="sort-header-inner">
                                                <span>Latest<br />received</span>
                                                <span className={`sort-indicator${sortBy === 'last_received_at' ? ' is-active' : ''}`} aria-hidden="true">
                                                    {sortBy === 'last_received_at' && sortDir === 'desc' ? '▼' : '▲'}
                                                </span>
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
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pagedProducts.map((product) => (
                                        <tr
                                            key={product.id}
                                            className={`row-clickable${!product.is_active ? ' row-inactive' : ''}`}
                                            onClick={() => setViewingStock(product)}
                                        >
                                            <td>
                                                {highlight(product.name, search.trim())}
                                            </td>
                                            <td>{product.last_supplier_name || '—'}</td>
                                            <td>{product.current_stock}</td>
                                            <td className="txn-date" style={{ fontSize: '0.82em' }}>
                                                {product.last_received_at
                                                    ? timeAgo(product.last_received_at)
                                                    : timeAgo(product.created_at)
                                                }
                                            </td>
                                            <td>
                                                {(() => {
                                                    const st = stockStatusPill(product);
                                                    return st.label
                                                        ? <span className={`pill ${st.className}`}>{st.label}</span>
                                                        : <span className="muted">—</span>;
                                                })()}
                                            </td>
                                            <td
                                                className="actions-cell"
                                                onClick={(e) => e.stopPropagation()}
                                            >
                                                <Tooltip
                                                    label={product.is_defect ? 'Defect items cannot add stock' : 'Add stock'}
                                                    className="btn btn-ghost btn-sm action-icon-btn"
                                                    disabled={Boolean(product.is_defect)}
                                                    onClick={() => openAddStock(product)}
                                                >
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                        <path d="M12 5v14M5 12h14" />
                                                    </svg>
                                                </Tooltip>
                                                <Tooltip
                                                    label={product.is_defect ? 'Defect items cannot be edited' : 'Edit stock'}
                                                    className="btn btn-ghost btn-sm action-icon-btn"
                                                    disabled={Boolean(product.is_defect)}
                                                    onClick={() => openReceive(product)}
                                                >
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                    </svg>
                                                </Tooltip>
                                                <Tooltip
                                                    label={product.is_defect ? 'Defect items cannot be adjusted' : 'Adjust stock'}
                                                    className="btn btn-ghost btn-sm action-icon-btn"
                                                    disabled={Boolean(product.is_defect)}
                                                    onClick={() => {
                                                        setAdjustProduct(product);
                                                        setAdjustForm({ delta: '', reason: 'damaged', reason_note: '' });
                                                        setAdjustError(null);
                                                    }}
                                                >
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                        <line x1="21" x2="14" y1="4" y2="4" />
                                                        <line x1="10" x2="3" y1="4" y2="4" />
                                                        <line x1="21" x2="12" y1="12" y2="12" />
                                                        <line x1="8" x2="3" y1="12" y2="12" />
                                                        <line x1="21" x2="16" y1="20" y2="20" />
                                                        <line x1="12" x2="3" y1="20" y2="20" />
                                                        <line x1="14" x2="14" y1="2" y2="6" />
                                                        <line x1="8" x2="8" y1="10" y2="14" />
                                                        <line x1="16" x2="16" y1="18" y2="22" />
                                                    </svg>
                                                </Tooltip>
                                                {product.is_active ? (
                                                    <Tooltip
                                                        label="Deactivate"
                                                        className="btn btn-ghost btn-sm btn-danger-text action-toggle action-icon-btn"
                                                        onClick={() => {
                                                            setDeactivateProduct(product);
                                                            setDeactivateError(null);
                                                        }}
                                                    >
                                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                            <circle cx="12" cy="12" r="9" />
                                                            <path d="m5.6 5.6 12.8 12.8" />
                                                        </svg>
                                                    </Tooltip>
                                                ) : (
                                                    <Tooltip
                                                        label="Activate"
                                                        className="btn btn-ghost btn-sm btn-success-text action-toggle action-icon-btn"
                                                        onClick={() => {
                                                            setReactivateProduct(product);
                                                            setReactivateError(null);
                                                        }}
                                                    >
                                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                            <circle cx="12" cy="12" r="9" />
                                                            <path d="m8.5 12 2.5 2.5 4.5-5" />
                                                        </svg>
                                                    </Tooltip>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {filteredProducts.length === 0 && (
                                <div className="table-empty table-empty-solid">
                                    <span className="table-empty-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="M21 21l-4.35-4.35" />
                                            <path d="M11 8v6M8 11h6" />
                                        </svg>
                                    </span>
                                    <p className="table-empty-title">
                                        {hasActiveFilters ? 'No results found' : activeTabMeta.emptyTitle}
                                    </p>
                                    <p className="table-empty-sub">
                                        {hasActiveFilters ? 'Try adjusting your search or filters.' : activeTabMeta.emptySub}
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
                                                    description: 'The activity list is already empty. Receive stock and its activity will show up here.',
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

            {adjustProduct && (
                <Modal
                    title="Adjust stock"
                    titleIcon={(
                        <span className="modal-title-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <line x1="21" x2="14" y1="4" y2="4" />
                                <line x1="10" x2="3" y1="4" y2="4" />
                                <line x1="21" x2="12" y1="12" y2="12" />
                                <line x1="8" x2="3" y1="12" y2="12" />
                                <line x1="21" x2="16" y1="20" y2="20" />
                                <line x1="12" x2="3" y1="20" y2="20" />
                                <line x1="14" x2="14" y1="2" y2="6" />
                                <line x1="8" x2="8" y1="10" y2="14" />
                                <line x1="16" x2="16" y1="18" y2="22" />
                            </svg>
                        </span>
                    )}
                    noClose
                    className="modal-adjust"
                    onClose={() => setAdjustProduct(null)}
                >
                    <form className="form" onSubmit={handleAdjust}>
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
                            <span className="field-label">Note</span>
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

            {receiveOpen && (
                <Modal
                    title="Edit Stock"
                    titleIcon={(
                        <span className="modal-title-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5Z" />
                            </svg>
                        </span>
                    )}
                    noClose
                    className="modal-receive-wide"
                    onClose={resetReceive}
                >
                    <form className="form" onSubmit={handleReceive}>

                        <div className="receive-modal-grid">
                            <div className="receive-modal-main">
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
                                    <label className="field">
                                        <span>SKU code</span>
                                        <input
                                            className="input mono"
                                            type="text"
                                            required
                                            maxLength={50}
                                            value={receiveForm.sku}
                                            onChange={(event) => setReceiveField('sku', event.target.value)}
                                        />
                                    </label>
                                </div>

                                <div className="field-row">
                                    <label className="field">
                                        <span>Receiving unit</span>
                                        <input
                                            type="text"
                                            className="input"
                                            maxLength={20}
                                            placeholder="e.g. piece"
                                            {...receiveInput('unit_of_measure')}
                                        />
                                    </label>
                                    <label className="field">
                                        <span>Reorder threshold</span>
                                        <input
                                            className="input"
                                            type="number"
                                            min="0"
                                            step="1"
                                            required
                                            value={receiveForm.reorder_threshold}
                                            onChange={(event) => setReceiveField('reorder_threshold', event.target.value)}
                                        />
                                    </label>
                                </div>

                                <div className="field-row">
                                    <div className="field">
                                        <span>Current stock</span>
                                        <div className="readonly-field">{receiveForm.current_stock}</div>
                                    </div>
                                    <span aria-hidden="true" />
                                </div>
                            </div>

                            <div className="receive-modal-aside">
                                <label className="field">
                                    <span>Supplier</span>
                                    <select className="input" disabled={suppliers.length === 0} {...receiveInput('supplier_id')}>
                                        <option value="" disabled hidden>No supplier</option>
                                        {suppliers.map((supplier) => (
                                            <option key={supplier.id} value={supplier.id}>
                                                {supplier.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <label className="field">
                                    <span>Category</span>
                                    <select
                                        className="input"
                                        disabled={dropdownCategories.length === 0}
                                        value={receiveForm.category}
                                        onChange={(event) => setReceiveField('category', event.target.value)}
                                    >
                                        <option value="" disabled hidden>No category</option>
                                        {dropdownCategories.map((cat) => (
                                            <option key={cat} value={cat}>
                                                {cat}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            </div>
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
                                {receiveSubmitting ? 'Saving…' : 'Save changes'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {addStockOpen && (
                <Modal
                    title="Add Stock"
                    titleIcon={(
                        <span className="modal-title-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                        </span>
                    )}
                    noClose
                    className="modal-adjust"
                    onClose={resetAddStock}
                >
                    <form className="form" onSubmit={handleAddStock}>
                        <div className="field">
                            <span className="field-label">Product</span>
                            <div className="readonly-field">{addStockForm.product_name}</div>
                        </div>
                        <div className="field">
                            <span className="field-label">Note</span>
                            {addStockForm.note_type === 'other' ? (
                                <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                                    <input
                                        type="text"
                                        className="input"
                                        style={{ paddingRight: 36 }}
                                        maxLength={255}
                                        placeholder="Delivery receipt number, remarks…"
                                        autoFocus
                                        value={addStockForm.note}
                                        onChange={(event) => setAddStockForm((c) => ({ ...c, note: event.target.value }))}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setAddStockForm((c) => ({ ...c, note_type: '', note: '' }))}
                                        title="Back to note list"
                                        aria-label="Back to note list"
                                        style={{
                                            position: 'absolute',
                                            right: 4,
                                            top: '50%',
                                            transform: 'translateY(-50%)',
                                            width: 28,
                                            height: 28,
                                            border: 'none',
                                            background: 'transparent',
                                            color: 'var(--muted)',
                                            cursor: 'pointer',
                                            display: 'inline-flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            borderRadius: 6,
                                        }}
                                    >
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                                            <polyline points="6 9 12 15 18 9" />
                                        </svg>
                                    </button>
                                </div>
                            ) : (
                                <select
                                    className="input"
                                    value={addStockForm.note_type}
                                    onChange={(event) => setAddStockForm((c) => ({
                                        ...c,
                                        note_type: event.target.value,
                                        note: '',
                                    }))}
                                >
                                    <option value="" disabled hidden>Select note</option>
                                    <option value="Received">Received</option>
                                    <option value="other">Other…</option>
                                </select>
                            )}
                        </div>
                        <div className="field-row">
                            <div className="field">
                                <span className="field-label">Current stock</span>
                                <div className="readonly-field">{addStockForm.current_stock}</div>
                            </div>
                            <label className="field">
                                <span className="field-label">Quantity to add <span className="text-danger">*</span></span>
                                <input
                                    type="number"
                                    className="input"
                                    min="1"
                                    step="1"
                                    required
                                    autoFocus
                                    placeholder="e.g. 10"
                                    value={addStockForm.quantity_received}
                                    onChange={(event) => setAddStockForm((c) => ({ ...c, quantity_received: event.target.value }))}
                                />
                            </label>
                        </div>

                        {addStockError && (
                            <div className="alert alert-error" role="alert">
                                <span>{addStockError}</span>
                                <FieldError messages={addStockFieldErrors} />
                            </div>
                        )}

                        <div className="modal-actions modal-actions-center">
                            <button type="button" className="btn btn-ghost" onClick={resetAddStock}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={addStockSubmitting}>
                                {addStockSubmitting ? 'Adding…' : 'Add stock'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {addOpen && (
                <Modal
                    title={(
                        <span className="activity-detail-title">
                            <span className="activity-detail-infoicon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M12 3v9" />
                                    <path d="m9 9 3 3 3-3" />
                                    <rect x="3" y="13" width="18" height="8" rx="2" />
                                </svg>
                            </span>
                            Receive New Stock
                        </span>
                    )}
                    className="modal-receive-wide"
                    onClose={resetAdd}
                    noClose
                    loading={suggesting}
                >
                    <form className="form" onSubmit={handleAdd}>
                        <div className="receive-modal-grid">
                            <div className="receive-modal-main">
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
                                            onChange={(event) => {
                                                const nextName = event.target.value;
                                                const rule = matchUnitSuggestion(nextName);
                                                const catMatch = matchCategorySuggestion(nextName, dropdownCategories);
                                                setShowAllUnits(false);
                                                setShowAllCategories(false);
                                                setAddForm((current) => {
                                                    const next = { ...current, name: nextName };
                                                    if (!unitTouchedRef.current && rule) {
                                                        next.unit_choice = rule.defaultUnit;
                                                    }
                                                    if (!categoryTouchedRef.current && catMatch) {
                                                        next.category = catMatch;
                                                    }
                                                    return next;
                                                });
                                            }}
                                            onKeyDown={handleNameTab}
                                        />
                                    </label>
                                    <span aria-hidden="true" />
                                </div>
                                {(() => {
                                    const rule = matchUnitSuggestion(addForm.name);
                                    const unitOptionsToShow = rule && !showAllUnits
                                        ? RECEIVING_UNIT_OPTIONS.filter((opt) => rule.units.includes(opt.value) || opt.value === addForm.unit_choice)
                                        : RECEIVING_UNIT_OPTIONS;
                                    const activeMeta = RECEIVING_UNIT_OPTIONS.find((opt) => opt.value === addForm.unit_choice);
                                    const sizePlaceholder = activeMeta?.sizePlaceholder ?? rule?.sizePlaceholder ?? 'e.g. 1';

                                    return (
                                        <div className="field-row field-row-reverse">
                                            <label className="field">
                                                <span className="field-label">Product size <InfoTip text="Optional whole number size/spec (numbers only, no fractions). Combined with the unit." /></span>
                                                <input
                                                    type="text"
                                                    inputMode="numeric"
                                                    pattern="[0-9]*"
                                                    className="input"
                                                    maxLength={10}
                                                    placeholder={sizePlaceholder}
                                                    value={addForm.spec_value}
                                                    onChange={(event) => {
                                                        const digitsOnly = event.target.value.replace(/[^0-9]/g, '');
                                                        setAddForm((current) => ({ ...current, spec_value: digitsOnly }));
                                                    }}
                                                />
                                            </label>
                                            <div className="field">
                                                <span className="field-label">Receiving unit <InfoTip text="Unit of measure for this product. Choose 'Other' to specify a custom unit." /></span>
                                                {addForm.unit_choice === 'other' ? (
                                                    <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                                                        <input
                                                            type="text"
                                                            className="input"
                                                            style={{ paddingRight: 36 }}
                                                            maxLength={20}
                                                            placeholder="Specify unit…"
                                                            autoFocus
                                                            value={addForm.unit_other}
                                                            onChange={(event) => setAddForm((current) => ({ ...current, unit_other: event.target.value }))}
                                                        />
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                unitTouchedRef.current = true;
                                                                setAddForm((current) => ({ ...current, unit_choice: '', unit_other: '' }));
                                                            }}
                                                            title="Back to unit list"
                                                            aria-label="Back to unit list"
                                                            style={{
                                                                position: 'absolute',
                                                                right: 4,
                                                                top: '50%',
                                                                transform: 'translateY(-50%)',
                                                                width: 28,
                                                                height: 28,
                                                                border: 'none',
                                                                background: 'transparent',
                                                                color: 'var(--muted)',
                                                                cursor: 'pointer',
                                                                display: 'inline-flex',
                                                                alignItems: 'center',
                                                                justifyContent: 'center',
                                                                borderRadius: 6,
                                                            }}
                                                        >
                                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                                                                <polyline points="6 9 12 15 18 9" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <select
                                                        className="input"
                                                        value={addForm.unit_choice}
                                                        onChange={(event) => {
                                                            const val = event.target.value;
                                                            if (val === '__all__') {
                                                                setShowAllUnits(true);
                                                                return;
                                                            }
                                                            unitTouchedRef.current = val !== '';
                                                            setAddForm((current) => ({ ...current, unit_choice: val }));
                                                        }}
                                                    >
                                                        <option value="" disabled hidden>Select unit</option>
                                                        {unitOptionsToShow.map((opt) => (
                                                            <option key={opt.value} value={opt.value}>
                                                                {opt.label}
                                                            </option>
                                                        ))}
                                                        {rule && !showAllUnits && (
                                                            <option value="__all__">Show all units…</option>
                                                        )}
                                                        <option value="other">Other…</option>
                                                    </select>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })()}
                                <div className="field-row">
                                    <label className="field">
                                        <span className="field-label">Quantity received <InfoTip text="How many units were received in this stock-in." /><span className="text-danger">*</span></span>
                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            required
                                            placeholder="e.g. 10"
                                            value={addForm.quantity_received}
                                            onChange={(event) => {
                                                const raw = event.target.value;
                                                setAddForm((current) => {
                                                    if (reorderTouchedRef.current) {
                                                        return { ...current, quantity_received: raw };
                                                    }
                                                    const num = Number(raw);
                                                    const half = raw !== '' && Number.isFinite(num) && num >= 0
                                                        ? String(Math.floor(num / 2))
                                                        : '';
                                                    return { ...current, quantity_received: raw, reorder_threshold: half };
                                                });
                                            }}
                                        />
                                    </label>
                                    <label className="field">
                                        <span className="field-label">Reorder threshold <InfoTip text="Low-stock alert threshold for this product. Defaults to half of Quantity received." /></span>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            placeholder="e.g. 5"
                                            value={addForm.reorder_threshold}
                                            onChange={(event) => {
                                                const val = event.target.value;
                                                reorderTouchedRef.current = val !== '';
                                                setAddForm((current) => ({ ...current, reorder_threshold: val }));
                                            }}
                                        />
                                    </label>
                                </div>
                                <div className="field-row">
                                    <label className="field">
                                        <span className="field-label">SKU <InfoTip text="Item code used to identify the product." /></span>
                                        <input
                                            type="text"
                                            className="mono"
                                            maxLength={50}
                                            placeholder={suggestions.sku || 'e.g. PAI-BRU-1in'}
                                            value={addForm.sku}
                                            onChange={(event) => setAddForm((current) => ({ ...current, sku: event.target.value }))}
                                            onKeyDown={handleSuggestionTab('sku')}
                                            disabled={suggesting}
                                        />
                                    </label>
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
                            </div>

                            <div className="receive-modal-aside">
                                <label className="field">
                                    <span className="field-label">Supplier <InfoTip text="Supplier who delivered the stock. Choose 'No supplier' if none." /></span>
                                    <select className="input" disabled={suppliers.length === 0} {...addInput('supplier_id')}>
                                        <option value="" disabled hidden>No supplier</option>
                                        {suppliers.map((supplier) => (
                                            <option key={supplier.id} value={supplier.id}>
                                                {supplier.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                {(() => {
                                    const localCat = matchCategorySuggestion(addForm.name, dropdownCategories);
                                    const aiCatRaw = suggestions.category ? String(suggestions.category).trim() : '';
                                    const aiCat = aiCatRaw
                                        ? (dropdownCategories.find((c) => c.toLowerCase() === aiCatRaw.toLowerCase()) ?? aiCatRaw)
                                        : '';
                                    const matchedCat = localCat || aiCat;
                                    const allCats = matchedCat && !dropdownCategories.includes(matchedCat)
                                        ? [matchedCat, ...dropdownCategories]
                                        : dropdownCategories;
                                    const categoryOptionsToShow = matchedCat && !showAllCategories
                                        ? [...new Set([matchedCat, addForm.category].filter(Boolean))]
                                        : allCats;

                                    return (
                                        <label className="field">
                                            <span className="field-label">Category <InfoTip text="Groups products for easier filtering." /></span>
                                            <select
                                                className="input"
                                                disabled={categoryOptionsToShow.length === 0}
                                                value={addForm.category}
                                                onChange={(event) => {
                                                    const val = event.target.value;
                                                    if (val === '__all__') {
                                                        setShowAllCategories(true);
                                                        return;
                                                    }
                                                    categoryTouchedRef.current = val !== '';
                                                    setAddForm((current) => ({ ...current, category: val }));
                                                }}
                                            >
                                                <option value="" disabled hidden>No category</option>
                                                {categoryOptionsToShow.map((cat) => (
                                                    <option key={cat} value={cat}>
                                                        {cat}
                                                    </option>
                                                ))}
                                                {matchedCat && !showAllCategories && (
                                                    <option value="__all__">Show all categories…</option>
                                                )}
                                            </select>
                                        </label>
                                    );
                                })()}
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

            {viewingStock && (() => {
                const st = stockStatusPill(viewingStock);
                const receivedTs = viewingStock.last_received_at || viewingStock.created_at;
                return (
                    <Modal
                        title={(
                            <span className="activity-detail-title">
                                <span className="activity-detail-infoicon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 11v5M12 8h.01" />
                                    </svg>
                                </span>
                                Stock details
                            </span>
                        )}
                        className="modal-detail-wide"
                        noClose
                        onClose={() => setViewingStock(null)}
                    >
                        <dl className="activity-detail-list detail-grid-2">
                            <div className="activity-detail-item">
                                <dt>Product</dt>
                                <dd><strong>{viewingStock.name}</strong></dd>
                            </div>
                            <div className="activity-detail-item">
                                <dt>SKU</dt>
                                <dd className="mono">{viewingStock.sku}</dd>
                            </div>
                            <div className="activity-detail-item">
                                <dt>Category</dt>
                                <dd>{viewingStock.category || '—'}</dd>
                            </div>
                            <div className="activity-detail-item">
                                <dt>Supplier</dt>
                                <dd>{viewingStock.last_supplier_name || '—'}</dd>
                            </div>
                            <div className="activity-detail-item">
                                <dt>Current Stock</dt>
                                <dd>{viewingStock.current_stock}</dd>
                            </div>
                            <div className="activity-detail-item">
                                <dt>Unit</dt>
                                <dd>{viewingStock.receiving_unit || 'piece'}</dd>
                            </div>
                            <div className="activity-detail-item">
                                <dt>Reorder At</dt>
                                <dd>{viewingStock.reorder_threshold}</dd>
                            </div>
                            <div className="activity-detail-item">
                                <dt>Status</dt>
                                <dd>
                                    {st.label
                                        ? <span className={`pill ${st.className}`}>{st.label}</span>
                                        : <span className="muted">—</span>}
                                </dd>
                            </div>
                            {viewingStock.last_received_qty !== null && viewingStock.last_received_qty !== undefined && (
                                <div className="activity-detail-item">
                                    <dt>Last Received Qty</dt>
                                    <dd>{viewingStock.last_received_qty}</dd>
                                </div>
                            )}
                            {viewingStock.last_received_by && (
                                <div className="activity-detail-item">
                                    <dt>Received By</dt>
                                    <dd>{viewingStock.last_received_by}</dd>
                                </div>
                            )}
                            <div className="activity-detail-item detail-grid-full">
                                <dt>Latest Received</dt>
                                <dd>
                                    {receivedTs
                                        ? `${timeAgo(receivedTs)} (${new Date(receivedTs).toLocaleString()})`
                                        : '—'}
                                </dd>
                            </div>
                            <div className="activity-detail-item detail-grid-full">
                                <dt>Note</dt>
                                <dd>{viewingStock.latest_note || '—'}</dd>
                            </div>
                        </dl>
                        <div className="modal-actions modal-actions-center">
                            <button type="button" className="btn btn-ghost" onClick={() => setViewingStock(null)}>
                                Close
                            </button>
                        </div>
                    </Modal>
                );
            })()}

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
                    description="This permanently deletes the stock receiving history from the activity log. This action cannot be undone."
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
