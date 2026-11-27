import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { usePageLoadbar } from '../../components/PageLoadbar.jsx';
import Modal, { MODAL_INFO_ICON } from '../../components/Modal.jsx';
import ConfirmDialog from '../../components/ConfirmDialog.jsx';
import MessageDialog from '../../components/MessageDialog.jsx';
import InfoTip from '../../components/InfoTip.jsx';
import Tooltip from '../../components/Tooltip.jsx';
import Alert, { FieldError, Toast } from '../../components/Feedback.jsx';
import { del, get, post, put, validationMessages } from '../../lib/api.js';
import { formatDate } from '../../lib/format.js';

const EMPTY = { name: '', description: '' };
const PER_PAGE = 6;
const ACTIVITY_PER_PAGE = 3;

const SORT_FIELDS = {
    name: (c) => c.name,
    description: (c) => c.description,
    products: (c) => Number(c.products_count ?? 0),
    updated: (c) => c.updated_at,
};

const ACTIVITY_TONE = {
    category_created: 'ok',
    category_updated: 'info',
    category_deleted: 'danger',
};

const ACTIVITY_VERB = {
    category_created: 'Added category',
    category_updated: 'Updated category',
    category_deleted: 'Deleted category',
};

const ACTIVITY_ICONS = {
    category_created: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 5v14M5 12h14" />
        </svg>
    ),
    category_updated: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
        </svg>
    ),
    category_deleted: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M8 12h8" />
        </svg>
    ),
    default: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8h.01M11 12h1v4h1" />
        </svg>
    ),
};

function activitySummary(item) {
    const verb = ACTIVITY_VERB[item.action];
    if (verb && item.subject_name) {
        return `${verb} ${item.subject_name}`;
    }
    return String(item.description ?? 'Category activity');
}

function activityChanges(item) {
    const explicit = item?.meta?.changes;
    if (Array.isArray(explicit) && explicit.length > 0) {
        return explicit.map((change) => change.text ?? String(change));
    }
    return [];
}

export default function Categories() {
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    usePageLoadbar(loading);
    const [error, setError] = useState(null);
    const [toast, setToast] = useState(null);

    const [showAdd, setShowAdd] = useState(false);
    const [form, setForm] = useState(EMPTY);
    const [saving, setSaving] = useState(false);
    const [formError, setFormError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState([]);

    const [editCategory, setEditCategory] = useState(null);
    const [editForm, setEditForm] = useState(null);
    const [editing, setEditing] = useState(false);
    const [editError, setEditError] = useState(null);

    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deletingId, setDeletingId] = useState(null);
    const [deleteError, setDeleteError] = useState(null);

    // Kebab menus
    const [optionsOpen, setOptionsOpen] = useState(false);
    const optionsRef = useRef(null);
    const [seeding, setSeeding] = useState(false);
    const [deleteAllConfirm, setDeleteAllConfirm] = useState(false);
    const [deletingAll, setDeletingAll] = useState(false);
    const [deleteAllError, setDeleteAllError] = useState(null);
    const [emptyNotice, setEmptyNotice] = useState(null);

    // Recent Activity
    const [activity, setActivity] = useState([]);
    const [activityDetail, setActivityDetail] = useState(null);
    const [activityPage, setActivityPage] = useState(1);
    const [activityOptionsOpen, setActivityOptionsOpen] = useState(false);
    const activityOptionsRef = useRef(null);
    const [clearConfirm, setClearConfirm] = useState(false);
    const [clearing, setClearing] = useState(false);
    const [clearError, setClearError] = useState(null);

    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [page, setPage] = useState(1);

    const load = useCallback(async (silent = false) => {
        if (!silent) {
            setLoading(true);
        }
        setError(null);

        try {
            const [res, feed] = await Promise.all([
                get('/api/categories'),
                get('/api/activity/recent', {
                    limit: 12,
                    actions: 'category_created,category_updated,category_deleted',
                }).catch(() => null),
            ]);

            setCategories(Array.isArray(res?.categories) ? res.categories : []);
            setActivity(Array.isArray(feed) ? feed : []);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    const refresh = useCallback(() => load(true), [load]);

    useEffect(() => {
        load();
    }, [load]);

    useEffect(() => {
        if (!optionsOpen && !activityOptionsOpen) {
            return;
        }

        const onDown = (event) => {
            if (optionsRef.current && !optionsRef.current.contains(event.target)) {
                setOptionsOpen(false);
            }
            if (activityOptionsRef.current && !activityOptionsRef.current.contains(event.target)) {
                setActivityOptionsOpen(false);
            }
        };

        const onKey = (event) => {
            if (event.key === 'Escape') {
                setOptionsOpen(false);
                setActivityOptionsOpen(false);
            }
        };

        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [optionsOpen, activityOptionsOpen]);

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

    useEffect(() => {
        setPage(1);
    }, [sortBy, sortDir]);

    const sortedCategories = useMemo(() => {
        if (!sortBy) return categories;
        const key = SORT_FIELDS[sortBy];
        if (!key) return categories;
        const dir = sortDir === 'asc' ? 1 : -1;

        return [...categories].sort((a, b) => {
            if (sortBy === 'products') {
                return (key(a) - key(b)) * dir;
            }
            if (sortBy === 'updated') {
                return ((Date.parse(key(a) ?? '') || 0) - (Date.parse(key(b) ?? '') || 0)) * dir;
            }
            const av = String(key(a) ?? '').toLowerCase();
            const bv = String(key(b) ?? '').toLowerCase();
            if (av < bv) return -1 * dir;
            if (av > bv) return 1 * dir;
            return 0;
        });
    }, [categories, sortBy, sortDir]);

    const lastPage = Math.max(1, Math.ceil(sortedCategories.length / PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const pagedCategories = useMemo(
        () => sortedCategories.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE),
        [sortedCategories, safePage],
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

    function closeAddModal() {
        if (saving) return;
        setShowAdd(false);
        setForm(EMPTY);
        setFormError(null);
        setFieldErrors([]);
    }

    async function handleCreate(event) {
        event.preventDefault();
        if (saving) return;
        setSaving(true);
        setFormError(null);
        setFieldErrors([]);

        try {
            const response = await post('/api/categories', {
                name: form.name,
                description: form.description || null,
            });
            setToast(response?.message ?? 'Category added successfully.');
            setForm(EMPTY);
            setShowAdd(false);
            await refresh();
        } catch (err) {
            setFormError(err.message);
            setFieldErrors(validationMessages(err));
        } finally {
            setSaving(false);
        }
    }

    function openEdit(category) {
        setEditCategory(category);
        setEditForm({ name: category.name, description: category.description ?? '' });
        setEditError(null);
    }

    async function handleEdit(event) {
        event.preventDefault();
        if (editing || !editCategory) return;
        setEditing(true);
        setEditError(null);

        try {
            const response = await put(`/api/categories/${editCategory.id}`, {
                name: editForm.name,
                description: editForm.description || null,
            });
            setToast(response?.message ?? 'Category updated successfully.');
            setEditCategory(null);
            await refresh();
        } catch (err) {
            setEditError(validationMessages(err)[0] ?? err.message ?? 'Could not update category.');
        } finally {
            setEditing(false);
        }
    }

    async function handleDeleteConfirm() {
        const target = deleteTarget;
        if (!target) return;
        setDeletingId(target.id);
        setDeleteError(null);

        try {
            const response = await del(`/api/categories/${target.id}`);
            setDeleteTarget(null);
            setToast(response?.message ?? 'Category deleted.');
            await refresh();
        } catch (err) {
            setDeleteError(validationMessages(err)[0] ?? err.message ?? 'Could not delete category.');
        } finally {
            setDeletingId(null);
        }
    }

    async function handleSeedCategories() {
        if (seeding) return;
        setSeeding(true);

        try {
            const response = await post('/api/categories/seed');
            setToast(response?.message ?? '7 sample categories added.');
            await refresh();
        } catch (err) {
            setError(validationMessages(err)[0] ?? err.message ?? 'Could not add sample categories.');
        } finally {
            setSeeding(false);
        }
    }

    async function handleDeleteAllConfirm() {
        if (deletingAll) return;
        setDeletingAll(true);
        setDeleteAllError(null);

        try {
            const response = await del('/api/categories');
            setDeleteAllConfirm(false);
            setToast(response?.message ?? 'All unused categories deleted.');
            await refresh();
        } catch (err) {
            setDeleteAllError(validationMessages(err)[0] ?? err.message ?? 'Could not delete categories.');
        } finally {
            setDeletingAll(false);
        }
    }

    async function handleClearActivity() {
        setClearing(true);
        setClearError(null);

        try {
            const response = await del('/api/activity/recent?actions=category_created,category_updated,category_deleted');
            setClearConfirm(false);
            setActivity([]);
            setActivityPage(1);
            setToast(response?.message ?? 'Activity list cleared.');
        } catch (err) {
            setClearError(validationMessages(err)[0] ?? err.message ?? 'Could not clear the activity list.');
        } finally {
            setClearing(false);
        }
    }

    return (
        <div className="page">
            <section className="page-hero">
                <header className="page-head">
                    <div>
                        <h2>Categories</h2>
                        <p>Organize hardware products into standardized categories.</p>
                    </div>
                </header>
            </section>

            {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}
            {toast && <Toast tone="success" onClose={() => setToast(null)}>{toast}</Toast>}

            {loading ? (
                <div className="page-loading-phase" aria-busy="true" aria-live="polite">
                    <section className="card panel panel-add">
                        <div className="panel-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                            <span className="skeleton" style={{ width: 118, height: 38, borderRadius: 8 }} />
                        </div>
                    </section>
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
                                        {[0, 1, 2, 3, 4].map((i) => (
                                            <th key={i}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4].map((c) => (
                                                <td key={c}>
                                                    <span className="skeleton skeleton-line" style={{ width: c === 4 ? '62%' : '85%' }} />
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            ) : (
            <>
            <section className="card panel panel-add">
                <header className="panel-head">
                    <div>
                        <h3>Add Category</h3>
                        <p className="muted">Create a hardware category for grouping products across the catalogue.</p>
                    </div>
                    <button
                        type="button"
                        className="btn btn-primary"
                        onClick={() => {
                            setFormError(null);
                            setFieldErrors([]);
                            setShowAdd(true);
                        }}
                    >
                        Add category
                    </button>
                </header>
            </section>

            <section className="card padded">
                <div className="filter-head">
                    <div>
                        <h3>Category List</h3>
                        <p className="muted">All hardware categories with product counts and descriptions.</p>
                    </div>
                    <div className="activity-options" ref={optionsRef}>
                        <button
                            type="button"
                            className="icon-btn is-outlined"
                            onClick={() => setOptionsOpen((open) => !open)}
                            aria-haspopup="menu"
                            aria-expanded={optionsOpen}
                            aria-label="Category options"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                                <circle cx="12" cy="5" r="1.7" />
                                <circle cx="12" cy="12" r="1.7" />
                                <circle cx="12" cy="19" r="1.7" />
                            </svg>
                        </button>
                        {optionsOpen && (
                            <div className="activity-options-panel" role="menu" aria-label="Category options">
                                <button
                                    type="button"
                                    className="activity-options-item"
                                    role="menuitem"
                                    disabled={seeding || deletingAll}
                                    onClick={() => {
                                        setOptionsOpen(false);
                                        handleSeedCategories();
                                    }}
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="M4 6h16M4 12h16M4 18h10" />
                                    </svg>
                                    <span>Add 7 sample categories</span>
                                </button>
                                <button
                                    type="button"
                                    className="activity-options-item activity-options-danger"
                                    role="menuitem"
                                    disabled={seeding || deletingAll}
                                    onClick={() => {
                                        setOptionsOpen(false);
                                        if (sortedCategories.length === 0) {
                                            setEmptyNotice({
                                                title: 'No categories to delete',
                                                description: 'The category list is already empty. Add a category or add the 7 sample categories first.',
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
                                        <path d="M10 11v6M14 11v6" />
                                    </svg>
                                    <span>Delete all categories</span>
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
                                    <th className="sort-header" onClick={() => toggleSort('name')}>
                                        Category
                                        <span className={`sort-indicator${sortBy === 'name' ? ' is-active' : ''}`} aria-hidden="true">
                                            {sortBy === 'name' && sortDir === 'desc' ? '▼' : '▲'}
                                        </span>
                                    </th>
                                    <th className="sort-header" onClick={() => toggleSort('description')}>
                                        Description
                                        <span className={`sort-indicator${sortBy === 'description' ? ' is-active' : ''}`} aria-hidden="true">
                                            {sortBy === 'description' && sortDir === 'desc' ? '▼' : '▲'}
                                        </span>
                                    </th>
                                    <th className="sort-header" onClick={() => toggleSort('products')}>
                                        Products
                                        <span className={`sort-indicator${sortBy === 'products' ? ' is-active' : ''}`} aria-hidden="true">
                                            {sortBy === 'products' && sortDir === 'desc' ? '▼' : '▲'}
                                        </span>
                                    </th>
                                    <th className="sort-header" onClick={() => toggleSort('updated')}>
                                        Updated
                                        <span className={`sort-indicator${sortBy === 'updated' ? ' is-active' : ''}`} aria-hidden="true">
                                            {sortBy === 'updated' && sortDir === 'desc' ? '▼' : '▲'}
                                        </span>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {pagedCategories.map((category) => (
                                    <tr key={category.id}>
                                        <td><strong>{category.name}</strong></td>
                                        <td className="muted">{category.description || '—'}</td>
                                        <td>
                                            <span className={`pill ${category.products_count > 0 ? 'pill-info' : 'pill-neutral'}`}>
                                                {category.products_count} {category.products_count === 1 ? 'item' : 'items'}
                                            </span>
                                        </td>
                                        <td className="muted">{formatDate(category.updated_at)}</td>
                                        <td className="actions-cell">
                                            <Tooltip
                                                label="Edit"
                                                className="btn btn-ghost btn-sm action-icon-btn"
                                                onClick={() => openEdit(category)}
                                            >
                                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                    <path d="M12 20h9" />
                                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                </svg>
                                            </Tooltip>
                                            <Tooltip
                                                label="Delete"
                                                className="btn btn-ghost btn-sm btn-danger-text action-icon-btn"
                                                disabled={deletingId === category.id}
                                                onClick={() => {
                                                    setDeleteError(null);
                                                    setDeleteTarget(category);
                                                }}
                                            >
                                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                    <path d="M3 6h18" />
                                                    <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                    <path d="M10 11v6M14 11v6" />
                                                </svg>
                                            </Tooltip>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {sortedCategories.length === 0 && (
                            <div className="table-empty table-empty-solid">
                                <span className="table-empty-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                        <circle cx="11" cy="11" r="8" />
                                        <path d="M21 21l-4.35-4.35" />
                                        <path d="M11 8v6M8 11h6" />
                                    </svg>
                                </span>
                                <p className="table-empty-title">No categories yet</p>
                                <p className="table-empty-sub">Add a category or add the 7 sample categories to populate this list.</p>
                            </div>
                        )}
                    </div>
                </div>

                {sortedCategories.length > 0 && (
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
                        <span className="page-detail">{Math.min(safePage * PER_PAGE, sortedCategories.length)} of {sortedCategories.length}</span>
                    </div>
                )}
            </section>

            <section className="card padded recent-activity">
                <div className="filter-head">
                    <div>
                        <h3>Recent Activity List</h3>
                        <p className="muted">See the category changes you made recently.</p>
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
                                                description: 'The activity list is already empty. Add, edit or delete a category and its activity will show up here.',
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
                                        <path d="M10 11v6M14 11v6" />
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
                                <path d="M4 6h16M4 12h16M4 18h10" />
                            </svg>
                        </span>
                        <p className="table-empty-title">No category activity yet</p>
                        <p className="table-empty-sub">Add, edit or delete a category and it will show up here.</p>
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

            {showAdd && (
                <Modal
                    title="Add category"
                    description="Create a hardware category to organize products."
                    className="modal-user-form"
                    loading={saving}
                    onClose={closeAddModal}
                >
                    <form className="form" onSubmit={handleCreate}>
                        <label className="field">
                            <span className="field-label">
                                Category name
                                <InfoTip text="Unique hardware category name, e.g. Power Tools or Plumbing." />
                                <span className="text-danger">*</span>
                            </span>
                            <input
                                type="text"
                                required
                                maxLength={100}
                                placeholder="e.g. Power Tools"
                                value={form.name}
                                onChange={(e) => setForm((c) => ({ ...c, name: e.target.value }))}
                                autoFocus
                            />
                        </label>
                        <label className="field">
                            <span className="field-label">
                                Description
                                <InfoTip text="Optional short note describing the items in this category." />
                            </span>
                            <input
                                type="text"
                                maxLength={255}
                                placeholder="e.g. Drills, grinders, and corded equipment"
                                value={form.description}
                                onChange={(e) => setForm((c) => ({ ...c, description: e.target.value }))}
                            />
                        </label>

                        {formError && (
                            <div className="alert alert-error" role="alert">
                                <span>{formError}</span>
                                <FieldError messages={fieldErrors} />
                            </div>
                        )}

                        <div className="modal-actions modal-actions-center">
                            <button type="button" className="btn btn-ghost" onClick={closeAddModal} disabled={saving}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={saving}>
                                {saving ? 'Saving…' : 'Save category'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {editCategory && editForm && (
                <Modal
                    title="Edit Category"
                    titleIcon={MODAL_INFO_ICON}
                    className="modal-user-form"
                    loading={editing}
                    onClose={() => { if (!editing) setEditCategory(null); }}
                >
                    <form className="form" onSubmit={handleEdit}>
                        <label className="field">
                            <span className="field-label">Category name <span className="text-danger">*</span></span>
                            <input
                                type="text"
                                required
                                maxLength={100}
                                value={editForm.name}
                                onChange={(e) => setEditForm((c) => ({ ...c, name: e.target.value }))}
                                autoFocus
                            />
                        </label>
                        <label className="field">
                            <span className="field-label">Description</span>
                            <input
                                type="text"
                                maxLength={255}
                                placeholder="Optional description"
                                value={editForm.description}
                                onChange={(e) => setEditForm((c) => ({ ...c, description: e.target.value }))}
                            />
                        </label>

                        {editError && <div className="alert alert-error" role="alert"><span>{editError}</span></div>}

                        <div className="modal-actions modal-actions-center">
                            <button type="button" className="btn btn-ghost" onClick={() => setEditCategory(null)} disabled={editing}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={editing}>
                                {editing ? 'Saving…' : 'Save changes'}
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
                                <dt>Category</dt>
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

            {deleteTarget && (
                <ConfirmDialog
                    tone="danger"
                    title={<>Delete <strong>{deleteTarget.name}</strong>?</>}
                    description="Categories assigned to existing products cannot be deleted until those products are moved to another category."
                    confirmLabel="Delete"
                    loadingLabel="Deleting…"
                    loading={deletingId === deleteTarget?.id}
                    error={deleteError}
                    onConfirm={handleDeleteConfirm}
                    onCancel={() => {
                        if (deletingId !== deleteTarget?.id) {
                            setDeleteTarget(null);
                            setDeleteError(null);
                        }
                    }}
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
                    title="Delete all categories?"
                    description={`This deletes all unused categories from the ${sortedCategories.length} listed. Categories assigned to products are kept.`}
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
                    description="This permanently deletes the category activity history from the activity log. The categories themselves are not affected."
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
