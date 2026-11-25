import { useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { usePageLoadbar } from '../../components/PageLoadbar.jsx';
import Modal from '../../components/Modal.jsx';
import ConfirmDialog from '../../components/ConfirmDialog.jsx';
import Alert, { FieldError, Toast } from '../../components/Feedback.jsx';
import { del, get, post, validationMessages } from '../../lib/api.js';
import { formatDate } from '../../lib/format.js';

const EMPTY = { name: '', contact_person: '', phone: '', email: '' };
const PER_PAGE = 6;

// Column → row-field accessors for the client-side table sort.
const SORT_FIELDS = {
    name: (supplier) => supplier.name,
    contact: (supplier) => supplier.contact_person,
    phone: (supplier) => supplier.phone,
    email: (supplier) => supplier.email,
    added: (supplier) => supplier.created_at,
};

// Recent Activity: action → colour tone + short verb, mirroring Products.
const ACTIVITY_TONE = {
    supplier_created: 'ok',
    supplier_removed: 'danger',
};

const ACTIVITY_VERB = {
    supplier_created: 'Added',
    supplier_removed: 'Removed',
};

const ACTIVITY_ICONS = {
    supplier_created: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 5v14M5 12h14" />
        </svg>
    ),
    supplier_removed: (
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

// One-line row summary, e.g. "Added Acme Hardware".
function activitySummary(item) {
    const verb = ACTIVITY_VERB[item.action];
    if (verb && item.subject_name) {
        return `${verb} ${item.subject_name}`;
    }
    const full = String(item.description ?? '');
    const colon = full.indexOf(':');
    return colon > 0 ? full.slice(0, colon) : full;
}

// The "changes made" lines for the detail popup.
function activityChanges(item) {
    const explicit = item?.meta?.changes;
    if (Array.isArray(explicit) && explicit.length > 0) {
        return explicit.map((change) => change.text ?? String(change));
    }

    const meta = item?.meta ?? {};
    const lines = [];

    switch (item?.action) {
        case 'supplier_created':
            if (meta.contact_person) lines.push(`Contact ${meta.contact_person}`);
            if (meta.phone) lines.push(`Phone ${meta.phone}`);
            if (meta.email) lines.push(`Email ${meta.email}`);
            lines.push('Status Active');
            break;
        case 'supplier_removed':
            lines.push('Status Active → Removed');
            lines.push('Hidden from the Supplier List and Stock-In');
            break;
        default:
            break;
    }

    return lines;
}

export default function Suppliers() {
    const [suppliers, setSuppliers] = useState([]);
    const [loading, setLoading] = useState(true);
    usePageLoadbar(loading);
    const [error, setError] = useState(null);
    const [message, setMessage] = useState(null);

    const [showAdd, setShowAdd] = useState(false);
    const [form, setForm] = useState(EMPTY);
    const [saving, setSaving] = useState(false);
    const [formError, setFormError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState([]);
    const [deletingId, setDeletingId] = useState(null);

    // Recent Activity List state.
    const [activity, setActivity] = useState([]);
    const [activityDetail, setActivityDetail] = useState(null);
    const [activityPage, setActivityPage] = useState(1);
    const [activityOptionsOpen, setActivityOptionsOpen] = useState(false);
    const activityOptionsRef = useRef(null);
    const [clearConfirm, setClearConfirm] = useState(false);
    const [clearing, setClearing] = useState(false);
    const [clearError, setClearError] = useState(null);
    const [toast, setToast] = useState(null);
    const ACTIVITY_PER_PAGE = 3;

    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [page, setPage] = useState(1);

    const tableRef = useRef(null);
    const wrapRef = useRef(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [rows, feed] = await Promise.all([
                get('/api/suppliers/active'),
                // Suppliers page owns its slice of the audit feed.
                get('/api/activity/recent', {
                    limit: 12,
                    actions: 'supplier_created,supplier_removed',
                }).catch(() => null),
            ]);

            setSuppliers(Array.isArray(rows) ? rows : []);
            setActivity(Array.isArray(feed) ? feed : []);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    // Options dropdown (kebab) on the Recent Activity card header: closes on
    // outside click and Escape, same behaviour as the other admin pages.
    useEffect(() => {
        if (!activityOptionsOpen) {
            return;
        }

        const onDown = (event) => {
            if (activityOptionsRef.current && !activityOptionsRef.current.contains(event.target)) {
                setActivityOptionsOpen(false);
            }
        };

        const onKey = (event) => {
            if (event.key === 'Escape') {
                setActivityOptionsOpen(false);
            }
        };

        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [activityOptionsOpen]);

    /** Column sort: 1st click asc → 2nd desc → 3rd back to the default order. */
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

    // Reset to the first page whenever the sort changes (mirrors Stock Inventory).
    useEffect(() => {
        setPage(1);
    }, [sortBy, sortDir]);

    const sortedSuppliers = useMemo(() => {
        if (!sortBy) {
            return suppliers;
        }

        const key = SORT_FIELDS[sortBy];
        if (!key) {
            return suppliers;
        }

        const dir = sortDir === 'asc' ? 1 : -1;
        return [...suppliers].sort((a, b) => {
            // Date comparison for the date added
            if (sortBy === 'added') {
                return ((Date.parse(key(a) ?? '') || 0) - (Date.parse(key(b) ?? '') || 0)) * dir;
            }

            // String comparison for name, contact person, phone and email
            const av = String(key(a) ?? '').toLowerCase();
            const bv = String(key(b) ?? '').toLowerCase();
            if (av < bv) return -1 * dir;
            if (av > bv) return 1 * dir;
            return 0;
        });
    }, [suppliers, sortBy, sortDir]);

    const lastPage = Math.max(1, Math.ceil(sortedSuppliers.length / PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const pagedSuppliers = useMemo(
        () => sortedSuppliers.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE),
        [sortedSuppliers, safePage]
    );

    // Recent Activity pagination (3 rows per page, matching Products).
    const activityLastPage = Math.max(1, Math.ceil(activity.length / ACTIVITY_PER_PAGE));
    const activitySafePage = Math.min(activityPage, activityLastPage);
    const pagedActivity = useMemo(
        () => activity.slice((activitySafePage - 1) * ACTIVITY_PER_PAGE, activitySafePage * ACTIVITY_PER_PAGE),
        [activity, activitySafePage, ACTIVITY_PER_PAGE]
    );

    // Keep the activity page in range when the feed shrinks.
    useEffect(() => {
        setActivityPage(1);
    }, [activity.length]);

    // Reserve exactly one full page (6 rows) of height, mirroring Stock
    // Inventory, Backups and Users. Measured from the rendered table so it
    // stays accurate across fonts, zoom levels and button wrapping.
    useLayoutEffect(() => {
        const table = tableRef.current;
        const wrap = wrapRef.current;
        if (!table || !wrap) {
            return;
        }

        const head = table.querySelector('thead');
        const row = table.querySelector('tbody tr');

        // Nothing to measure (empty list / loading): let the wrap size itself.
        if (!head || !row) {
            wrap.style.minHeight = '';
            return;
        }

        wrap.style.minHeight = `${head.getBoundingClientRect().height + row.getBoundingClientRect().height * PER_PAGE}px`;
    }, [pagedSuppliers.length, loading]);

    async function handleSave(event) {
        event.preventDefault();
        setSaving(true);
        setFormError(null);
        setFieldErrors([]);

        try {
            const response = await post('/api/suppliers', {
                ...form,
                contact_person: form.contact_person || null,
                phone: form.phone || null,
                email: form.email || null,
            });

            setMessage(response.message);
            setForm(EMPTY);
            setShowAdd(false);
            await load();
        } catch (err) {
            setFormError(err.message);
            setFieldErrors(validationMessages(err));
        } finally {
            setSaving(false);
        }
    }

    async function handleDeactivate(supplier) {
        if (!window.confirm(`Remove "${supplier.name}" from the active supplier list?`)) {
            return;
        }

        setDeletingId(supplier.id);
        setMessage(null);
        setError(null);

        try {
            const response = await del(`/api/suppliers/${supplier.id}`);
            setMessage(response.message);
            await load();
        } catch (err) {
            setError(err.message);
        } finally {
            setDeletingId(null);
        }
    }

    async function handleClearActivity() {
        setClearing(true);
        setClearError(null);

        try {
            const response = await del('/api/activity/recent?actions=supplier_created,supplier_removed');

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

    const setField = (name) => (event) => setForm((current) => ({ ...current, [name]: event.target.value }));

    return (
        <div className="page">
            <section className="page-hero">
                <header className="page-head">
                    <div>
                        <h2>Suppliers</h2>
                        <p>Manage suppliers and their contact details.</p>
                    </div>
                </header>
            </section>

            {message && <Alert tone="success" onClose={() => setMessage(null)}>{message}</Alert>}
            {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}
            {toast && <Toast tone="success" onClose={() => setToast(null)}>{toast}</Toast>}

            {loading ? (
                /* Loading phase: the hero above stays put (its loadbar is the
                   progress UI) while these sections paint skeletons mirroring
                   the real page — no spinners anywhere. */
                <div className="page-loading-phase" aria-busy="true" aria-live="polite">
                    {/* Add Supplier panel */}
                    <section className="card panel panel-add">
                        <div className="panel-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                            <span className="skeleton" style={{ width: 118, height: 38, borderRadius: 8 }} />
                        </div>
                    </section>

                    {/* Supplier List table + pagination */}
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
                                            <th key={i} className={i === 5 ? 'text-right' : undefined}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4, 5].map((c) => (
                                                <td key={c} className={c === 5 ? 'text-right' : undefined}>
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
                </div>
            ) : (
            <>
            <section className="card panel panel-add">
                <header className="panel-head">
                    <div>
                        <h3>Add Supplier</h3>
                        <p className="muted">Add a new supplier to the directory for use in Stock-In.</p>
                    </div>
                    <button type="button" className="btn btn-primary" onClick={() => setShowAdd(true)}>
                        Add supplier
                    </button>
                </header>
            </section>

            <section className="card padded">
                <div className="filter-head">
                    <div>
                        <h3>Supplier List</h3>
                        <p className="muted">Every active supplier with its contact details and date added.</p>
                    </div>
                </div>
                {!loading && !error && (
                    <div className="table-shell">
                        <div className="table-wrap data-rows-6" ref={wrapRef}>
                            <table className="table" ref={tableRef}>
                                <thead>
                                    <tr>
                                        <th className="sort-header" onClick={() => toggleSort('name')}>
                                            Name{sortBy === 'name' && sortDir === 'asc' ? ' ▲' : sortBy === 'name' ? ' ▼' : ''}
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('contact')}>
                                            Contact person{sortBy === 'contact' && sortDir === 'asc' ? ' ▲' : sortBy === 'contact' ? ' ▼' : ''}
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('phone')}>
                                            Phone{sortBy === 'phone' && sortDir === 'asc' ? ' ▲' : sortBy === 'phone' ? ' ▼' : ''}
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('email')}>
                                            Email{sortBy === 'email' && sortDir === 'asc' ? ' ▲' : sortBy === 'email' ? ' ▼' : ''}
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('added')}>
                                            Added{sortBy === 'added' && sortDir === 'asc' ? ' ▲' : sortBy === 'added' ? ' ▼' : ''}
                                        </th>
                                        <th className="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pagedSuppliers.map((supplier) => (
                                        <tr key={supplier.id}>
                                            <td><strong>{supplier.name}</strong></td>
                                            <td>{supplier.contact_person || '—'}</td>
                                            <td className="mono">{supplier.phone || '—'}</td>
                                            <td>{supplier.email || '—'}</td>
                                            <td className="muted">{formatDate(supplier.created_at)}</td>
                                            <td className="text-right actions-cell">
                                                <button
                                                    type="button"
                                                    className="btn btn-ghost btn-sm"
                                                    disabled={deletingId === supplier.id}
                                                    onClick={() => handleDeactivate(supplier)}
                                                >
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {sortedSuppliers.length === 0 && (
                                <div className="table-empty">
                                    <span className="table-empty-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="M21 21l-4.35-4.35" />
                                            <path d="M11 8v6M8 11h6" />
                                        </svg>
                                    </span>
                                    <p className="table-empty-title">No suppliers yet</p>
                                    <p className="table-empty-sub">Add a supplier to get started.</p>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {!loading && !error && sortedSuppliers.length > 0 && (
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
                        <p className="muted">See the supplier changes you made recently.</p>
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
                                <path d="M3 7.5h11v9H3z" />
                                <path d="M14 10.5h4l3 3v3h-7z" />
                                <circle cx="7" cy="17.5" r="1.5" />
                                <circle cx="17.5" cy="17.5" r="1.5" />
                            </svg>
                        </span>
                        <p className="table-empty-title">No supplier activity yet</p>
                        <p className="table-empty-sub">Add or remove a supplier and it will show up here.</p>
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
                <Modal title="Add supplier" onClose={() => setShowAdd(false)}>
                    <form className="form" onSubmit={handleSave}>
                        <label className="field">
                            <span>Supplier name *</span>
                            <input type="text" required maxLength={255} value={form.name} onChange={setField('name')} autoFocus />
                        </label>
                        <div className="field-row">
                            <label className="field">
                                <span>Contact person</span>
                                <input type="text" maxLength={255} value={form.contact_person} onChange={setField('contact_person')} />
                            </label>
                            <label className="field">
                                <span>Phone</span>
                                <input type="text" maxLength={50} value={form.phone} onChange={setField('phone')} />
                            </label>
                        </div>
                        <label className="field">
                            <span>Email</span>
                            <input type="email" maxLength={255} value={form.email} onChange={setField('email')} />
                        </label>

                        {formError && (
                            <div className="alert alert-error" role="alert">
                                <span>{formError}</span>
                                <FieldError messages={fieldErrors} />
                            </div>
                        )}

                        <div className="modal-actions">
                            <button type="button" className="btn btn-ghost" onClick={() => setShowAdd(false)}>
                                Cancel
                            </button>
                            <button type="submit" className="btn btn-primary" disabled={saving}>
                                {saving ? 'Saving…' : 'Save supplier'}
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
                                <dt>Supplier</dt>
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

            {clearConfirm && (
                <ConfirmDialog
                    tone="danger"
                    title="Clear activity list?"
                    description="This permanently deletes the supplier activity history from the activity log. The suppliers themselves are not affected. This action cannot be undone."
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
