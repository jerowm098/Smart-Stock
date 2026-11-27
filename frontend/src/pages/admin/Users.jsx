import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { usePageLoadbar } from '../../components/PageLoadbar.jsx';
import Modal, { MODAL_INFO_ICON } from '../../components/Modal.jsx';
import ConfirmDialog from '../../components/ConfirmDialog.jsx';
import InfoTip from '../../components/InfoTip.jsx';
import Tooltip from '../../components/Tooltip.jsx';
import Alert, { Toast } from '../../components/Feedback.jsx';
import MessageDialog from '../../components/MessageDialog.jsx';
import { del, get, post, put, validationMessages } from '../../lib/api.js';
import { notifyConnectedNav } from '../../lib/navBadges.js';

const EMPTY_CREATE = { name: '', username: '', email: '', role: 'cashier', password: '', password_confirmation: '' };
const PER_PAGE = 6;

// Recent Activity: action → colour tone + short verb, mirroring Products.
const ACTIVITY_TONE = {
    user_created: 'ok',
    user_updated: 'info',
    user_deactivated: 'danger',
    user_reactivated: 'ok',
    user_password_reset: 'warn',
};

const ACTIVITY_VERB = {
    user_created: 'Created account for',
    user_updated: 'Updated account for',
    user_deactivated: 'Deactivated',
    user_reactivated: 'Reactivated',
    user_password_reset: 'Reset password for',
};

const ACTIVITY_ICONS = {
    user_created: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="9" cy="8" r="4" />
            <path d="M2 21a7 7 0 0 1 14 0" />
            <path d="M19 8v6M16 11h6" />
        </svg>
    ),
    user_updated: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
        </svg>
    ),
    user_deactivated: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M8 12h8" />
        </svg>
    ),
    user_reactivated: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M20 6 9 17l-5-5" />
        </svg>
    ),
    user_password_reset: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="7.5" cy="15.5" r="4.5" />
            <path d="M10.7 12.3 21 2" />
            <path d="m17 6 3 3" />
        </svg>
    ),
    default: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8h.01M11 12h1v4h1" />
        </svg>
    ),
};

// One-line row summary, e.g. "Created account for Juan Dela Cruz".
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
        case 'user_created':
            if (meta.username) lines.push(`Username ${meta.username}`);
            if (meta.role) lines.push(`Role ${meta.role}`);
            lines.push('Status Active');
            break;
        case 'user_deactivated':
            lines.push('Status Active → Inactive');
            lines.push('Cannot sign in until reactivated');
            break;
        case 'user_reactivated':
            lines.push('Status Inactive → Active');
            lines.push('Can sign in again');
            break;
        case 'user_password_reset':
            lines.push('Password reset by Admin');
            break;
        default:
            break;
    }

    return lines;
}

export default function Users() {
    const [users, setUsers] = useState([]);
    const [roles, setRoles] = useState(['admin', 'cashier']);
    const [me, setMe] = useState(null);
    const [loading, setLoading] = useState(true);
    usePageLoadbar(loading);
    const [error, setError] = useState(null);
    const [message, setMessage] = useState(null);
    const [busyId, setBusyId] = useState(null);
    const [deactivateUser, setDeactivateUser] = useState(null);
    const [deactivateError, setDeactivateError] = useState(null);
    const [activateUser, setActivateUser] = useState(null);
    const [activateError, setActivateError] = useState(null);

    // Recent Activity List state.
    const [activity, setActivity] = useState([]);
    const [activityDetail, setActivityDetail] = useState(null);
    const [activityPage, setActivityPage] = useState(1);
    const [activityOptionsOpen, setActivityOptionsOpen] = useState(false);
    const activityOptionsRef = useRef(null);
    const [usersOptionsOpen, setUsersOptionsOpen] = useState(false);
    const usersOptionsRef = useRef(null);
    const [seeding, setSeeding] = useState(false);
    const [deleteAllConfirm, setDeleteAllConfirm] = useState(false);
    const [deletingAll, setDeletingAll] = useState(false);
    const [deleteAllError, setDeleteAllError] = useState(null);
    const [emptyNotice, setEmptyNotice] = useState(null);
    const [clearConfirm, setClearConfirm] = useState(false);
    const [clearing, setClearing] = useState(false);
    const [clearError, setClearError] = useState(null);
    const [toast, setToast] = useState(null);
    const ACTIVITY_PER_PAGE = 3;

    const [addOpen, setAddOpen] = useState(false);
    const [createForm, setCreateForm] = useState(EMPTY_CREATE);
    const [creating, setCreating] = useState(false);
    const [formError, setFormError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState([]);
    const [showCreatePassword, setShowCreatePassword] = useState(false);
    const [showCreateConfirm, setShowCreateConfirm] = useState(false);

    const [editUser, setEditUser] = useState(null);
    const [editForm, setEditForm] = useState(null);
    const [editing, setEditing] = useState(false);
    const [editError, setEditError] = useState(null);

    const [resetUser, setResetUser] = useState(null);
    const [resetForm, setResetForm] = useState({ password: '', password_confirmation: '' });
    const [resetting, setResetting] = useState(false);
    const [resetError, setResetError] = useState(null);
    const [showResetPassword, setShowResetPassword] = useState(false);
    const [showResetConfirm, setShowResetConfirm] = useState(false);

    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [page, setPage] = useState(1);

    const load = useCallback(async (silent = false) => {
        if (!silent) {
            setLoading(true);
        }
        setError(null);

        try {
            const [list, session, feed] = await Promise.all([
                get('/api/users'),
                get('/api/auth/user'),
                // Users page owns its slice of the audit feed.
                get('/api/activity/recent', {
                    limit: 12,
                    actions: 'user_created,user_updated,user_deactivated,user_reactivated,user_password_reset',
                }).catch(() => null),
            ]);
            setUsers(list.users ?? []);
            setRoles(list.roles ?? ['admin', 'cashier']);
            setMe(session.user);
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

    // Options dropdown (kebab) on the Recent Activity card header: closes on
    // outside click and Escape, same behaviour as the other admin pages.
    useEffect(() => {
        if (!activityOptionsOpen && !usersOptionsOpen) {
            return;
        }

        const onDown = (event) => {
            if (activityOptionsRef.current && !activityOptionsRef.current.contains(event.target)) {
                setActivityOptionsOpen(false);
            }
            if (usersOptionsRef.current && !usersOptionsRef.current.contains(event.target)) {
                setUsersOptionsOpen(false);
            }
        };

        const onKey = (event) => {
            if (event.key === 'Escape') {
                setActivityOptionsOpen(false);
                setUsersOptionsOpen(false);
            }
        };

        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [activityOptionsOpen, usersOptionsOpen]);

    async function handleSeedUsers() {
        if (seeding) return;
        setSeeding(true);

        try {
            const response = await post('/api/users/seed');
            setToast(response?.message ?? '7 sample users added.');
            notifyConnectedNav('userAdded', response?.created ?? 7);
            await refresh();
        } catch (err) {
            setError(validationMessages(err)[0] ?? err.message ?? 'Could not add the sample users.');
        } finally {
            setSeeding(false);
        }
    }

    async function handleDeleteAllConfirm() {
        if (deletingAll) return;
        setDeletingAll(true);
        setDeleteAllError(null);

        try {
            const response = await del('/api/users');
            setDeleteAllConfirm(false);
            setToast(response?.message ?? 'All other users deleted.');
            await refresh();
        } catch (err) {
            setDeleteAllError(validationMessages(err)[0] ?? err.message ?? 'Could not delete users.');
        } finally {
            setDeletingAll(false);
        }
    }

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

    const sortedUsers = useMemo(() => {
        // The signed-in account ("Currently Using") is intentionally NOT part
        // of this table — it is maintained from the header account menu
        // instead. Everything here is another account, ordered by recent
        // activity (newest update first) until a column sort is applied.
        const rows = me ? users.filter((user) => user.id !== me.id) : users;

        const sortRows = (list) => {
            if (!sortBy) {
                return [...list].sort(
                    (a, b) => ((Date.parse(b.updated_at_iso ?? '') || 0) - (Date.parse(a.updated_at_iso ?? '') || 0)),
                );
            }

            const dir = sortDir === 'asc' ? 1 : -1;
            return [...list].sort((a, b) => {
                // Date comparison for the last update (ISO timestamp from the API)
                if (sortBy === 'updated') {
                    return ((Date.parse(a.updated_at_iso ?? '') || 0) - (Date.parse(b.updated_at_iso ?? '') || 0)) * dir;
                }

                // Status sorts by the label shown in the cell (Active / Inactive)
                if (sortBy === 'status') {
                    const av = a.is_active ? 'Active' : 'Inactive';
                    const bv = b.is_active ? 'Active' : 'Inactive';
                    if (av < bv) return -1 * dir;
                    if (av > bv) return 1 * dir;
                    return 0;
                }

                // String comparison for account, email and role
                const av = String(a[sortBy] ?? '').toLowerCase();
                const bv = String(b[sortBy] ?? '').toLowerCase();
                if (av < bv) return -1 * dir;
                if (av > bv) return 1 * dir;
                return 0;
            });
        };

        return sortRows(rows);
    }, [users, sortBy, sortDir, me]);

    const lastPage = Math.max(1, Math.ceil(sortedUsers.length / PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const pagedUsers = useMemo(
        () => sortedUsers.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE),
        [sortedUsers, safePage]
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

    async function handleCreate(event) {
        event.preventDefault();
        setCreating(true);
        setFormError(null);
        setFieldErrors([]);

        try {
            const response = await post('/api/users', createForm);
            setMessage(response.message);
            setCreateForm(EMPTY_CREATE);
            setAddOpen(false);
            notifyConnectedNav('userAdded', 1);
            await refresh();
        } catch (err) {
            setFormError(err.message);
            setFieldErrors(validationMessages(err));
        } finally {
            setCreating(false);
        }
    }

    function openEdit(user) {
        setEditUser(user);
        setEditForm({ name: user.name, username: user.username, email: user.email, role: user.role });
        setEditError(null);
    }

    async function handleEdit(event) {
        event.preventDefault();
        if (editing) return;
        setEditing(true);
        setEditError(null);

        try {
            const response = await put(`/api/users/${editUser.id}`, editForm);
            setMessage(response.message);
            setEditUser(null);
            await refresh();
        } catch (err) {
            setEditError(validationMessages(err)[0] ?? err.message);
        } finally {
            setEditing(false);
        }
    }

    // Activate asks through the ConfirmDialog too (tone="info"), mirroring
    // the Products page reactivate flow.
    async function handleActivateConfirm() {
        const target = activateUser;
        if (!target) return;

        setBusyId(target.id);
        setActivateError(null);

        try {
            const response = await post(`/api/users/${target.id}/activate`);

            setActivateUser(null);
            setMessage(response.message);
            await refresh();
        } catch (err) {
            setActivateError(validationMessages(err)[0] ?? err.message ?? 'Could not activate the account.');
        } finally {
            setBusyId(null);
        }
    }

    // Deactivate asks through the reusable ConfirmDialog (never the browser's
    // native confirm), mirroring the Products page flow. Activate stays a
    // direct one-click action.
    async function handleDeactivateConfirm() {
        const target = deactivateUser;
        if (!target) return;

        setBusyId(target.id);
        setDeactivateError(null);

        try {
            const response = await post(`/api/users/${target.id}/deactivate`);

            setDeactivateUser(null);
            setMessage(response.message);
            await refresh();
        } catch (err) {
            setDeactivateError(validationMessages(err)[0] ?? err.message ?? 'Could not deactivate the account.');
        } finally {
            setBusyId(null);
        }
    }

    async function handleReset(event) {
        event.preventDefault();
        setResetting(true);
        setResetError(null);

        try {
            const response = await post(`/api/users/${resetUser.id}/reset-password`, resetForm);
            setMessage(response.message);
            setResetUser(null);
            setResetForm({ password: '', password_confirmation: '' });
        } catch (err) {
            setResetError(validationMessages(err)[0] ?? err.message);
        } finally {
            setResetting(false);
        }
    }

    async function handleClearActivity() {
        setClearing(true);
        setClearError(null);

        try {
            const response = await del(
                '/api/activity/recent?actions=user_created,user_updated,user_deactivated,user_reactivated,user_password_reset',
            );

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

    // Full names accept letters (incl. Ñ/ñ) and spaces only — mirrors the
    // backend rule so digits/symbols can't be typed in the first place.
    const sanitizeName = (value) => value.replace(/[^A-Za-zÑñ ]/g, '');

    const createSet = (name) => (event) => {
        const value = name === 'name' ? sanitizeName(event.target.value) : event.target.value;
        setCreateForm((current) => ({ ...current, [name]: value }));
    };
    const editSet = (name) => (event) => {
        const value = name === 'name' ? sanitizeName(event.target.value) : event.target.value;
        setEditForm((current) => ({ ...current, [name]: value }));
    };
    const resetSet = (name) => (event) => setResetForm((current) => ({ ...current, [name]: event.target.value }));

    // Add-account validation feedback is shown in a nested dialog (not inline in
    // the form). Laravel returns a general message plus per-field messages, so
    // they are merged and de-duplicated before display.
    const createErrorOpen = Boolean(formError) || fieldErrors.length > 0;
    const createErrorMessages = useMemo(() => {
        const list = [...fieldErrors];
        if (formError) {
            list.push(formError);
        }
        return [...new Set(list)];
    }, [formError, fieldErrors]);

    const closeCreateError = () => {
        setFormError(null);
        setFieldErrors([]);
    };

    return (
        <div className="page">
            <section className="page-hero">
                <header className="page-head">
                    <div>
                        <h2>Users</h2>
                        <p>Manage user accounts, roles, and access.</p>
                    </div>
                </header>
            </section>

            {message && <Toast tone="success" onClose={() => setMessage(null)}>{message}</Toast>}
            {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}
            {toast && <Toast tone="success" onClose={() => setToast(null)}>{toast}</Toast>}

            {loading ? (
                /* Loading phase: the hero above stays put (its loadbar is the
                   progress UI) while these sections paint skeletons mirroring
                   the real page — no spinners anywhere. */
                <div className="page-loading-phase" aria-busy="true" aria-live="polite">
                    {/* Add Account panel */}
                    <section className="card panel panel-add">
                        <div className="panel-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                            <span className="skeleton" style={{ width: 118, height: 38, borderRadius: 8 }} />
                        </div>
                    </section>

                    {/* User Accounts table + pagination */}
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
                                            <th key={i} className={i === 6 ? 'text-right' : undefined}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4, 5, 6].map((c) => (
                                                <td key={c} className={c === 6 ? 'text-right' : undefined}>
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
                </div>
            ) : (
            <>
            <section className="card panel panel-add">
                <header className="panel-head">
                    <div>
                        <h3>Add Account</h3>
                        <p className="muted">Create a new user account with a role and initial password.</p>
                    </div>
                    <button type="button" className="btn btn-primary" onClick={() => setAddOpen(true)}>
                        Add account
                    </button>
                </header>
            </section>

            <section className="card padded">
                <div className="filter-head">
                    <div>
                        <h3>User Accounts</h3>
                        <p className="muted">Every other account with its role, status and last update.</p>
                    </div>
                    <div className="activity-options" ref={usersOptionsRef}>
                        <button
                            type="button"
                            className="icon-btn is-outlined"
                            onClick={() => setUsersOptionsOpen((open) => !open)}
                            aria-haspopup="menu"
                            aria-expanded={usersOptionsOpen}
                            aria-label="User account options"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                                <circle cx="12" cy="5" r="1.7" />
                                <circle cx="12" cy="12" r="1.7" />
                                <circle cx="12" cy="19" r="1.7" />
                            </svg>
                        </button>
                        {usersOptionsOpen && (
                            <div className="activity-options-panel" role="menu" aria-label="User account options">
                                <button
                                    type="button"
                                    className="activity-options-item"
                                    role="menuitem"
                                    disabled={seeding || deletingAll}
                                    onClick={() => {
                                        setUsersOptionsOpen(false);
                                        handleSeedUsers();
                                    }}
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <line x1="19" y1="8" x2="19" y2="14" />
                                        <line x1="22" y1="11" x2="16" y2="11" />
                                    </svg>
                                    <span>Add 7 sample users</span>
                                </button>
                                <button
                                    type="button"
                                    className="activity-options-item activity-options-danger"
                                    role="menuitem"
                                    disabled={seeding || deletingAll}
                                    onClick={() => {
                                        setUsersOptionsOpen(false);

                                        if (sortedUsers.length === 0) {
                                            setEmptyNotice({
                                                title: 'No users to delete',
                                                description: 'There are no other user accounts in the table. Add an account or add the 7 sample users first.',
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
                                    <span>Delete all users</span>
                                </button>
                            </div>
                        )}
                    </div>
                </div>
                {!loading && !error && (
                    <div className="table-shell">
                        <div className="table-wrap">
                            <table className="table">
                                <thead>
                                    <tr>
                                        <th className="sort-header" onClick={() => toggleSort('name')}>
                                            Full<br />name
                                            <span className={`sort-indicator${sortBy === 'name' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'name' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('username')}>
                                            Username
                                            <span className={`sort-indicator${sortBy === 'username' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'username' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('email')}>
                                            Email
                                            <span className={`sort-indicator${sortBy === 'email' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'email' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('role')}>
                                            Role
                                            <span className={`sort-indicator${sortBy === 'role' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'role' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('status')}>
                                            Status
                                            <span className={`sort-indicator${sortBy === 'status' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'status' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('updated')}>
                                            Updated
                                            <span className={`sort-indicator${sortBy === 'updated' ? ' is-active' : ''}`} aria-hidden="true">
                                                {sortBy === 'updated' && sortDir === 'desc' ? '▼' : '▲'}
                                            </span>
                                        </th>
                                        <th className="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pagedUsers.map((user) => {
                                        return (
                                            <tr key={user.id} className={user.is_active ? undefined : 'row-inactive'}>
                                                <td>
                                                    <strong>{user.name}</strong>
                                                </td>
                                                <td className="mono muted">{user.username}</td>
                                                <td>{user.email}</td>
                                                <td>
                                                    <span className={`pill ${user.role === 'admin' ? 'pill-warn' : 'pill-neutral'}`}>
                                                        {user.role}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span className={`pill ${user.is_active ? 'pill-ok' : 'pill-danger'}`}>
                                                        {user.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="muted">{user.updated_at}</td>
                                                <td className="text-right actions-cell">
                                                    <Tooltip
                                                        label={user.is_active ? 'Edit' : 'Reactivate first'}
                                                        className="btn btn-ghost btn-sm action-icon-btn"
                                                        disabled={!user.is_active}
                                                        onClick={() => openEdit(user)}
                                                    >
                                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                            <path d="M12 20h9" />
                                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                        </svg>
                                                    </Tooltip>
                                                    <Tooltip
                                                        label={user.is_active ? 'Reset password' : 'Reactivate first'}
                                                        className="btn btn-ghost btn-sm action-icon-btn"
                                                        disabled={!user.is_active}
                                                        onClick={() => setResetUser(user)}
                                                    >
                                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                            <path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z" />
                                                            <circle cx="16.5" cy="7.5" r=".5" fill="currentColor" />
                                                        </svg>
                                                    </Tooltip>
                                                    {user.is_active ? (
                                                        <Tooltip
                                                            label="Deactivate"
                                                            className="btn btn-ghost btn-sm btn-danger-text action-toggle action-icon-btn"
                                                            disabled={busyId === user.id}
                                                            onClick={() => {
                                                                setDeactivateError(null);
                                                                setDeactivateUser(user);
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
                                                            disabled={busyId === user.id}
                                                            onClick={() => {
                                                                setActivateError(null);
                                                                setActivateUser(user);
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
                                        );
                                    })}
                                </tbody>
                            </table>

                            {sortedUsers.length === 0 && (
                                <div className="table-empty table-empty-solid">
                                    <span className="table-empty-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="M21 21l-4.35-4.35" />
                                            <path d="M11 8v6M8 11h6" />
                                        </svg>
                                    </span>
                                    <p className="table-empty-title">No other accounts yet</p>
                                    <p className="table-empty-sub">Create an account or add the 7 sample users to populate this list.</p>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {!loading && !error && sortedUsers.length > 0 && (
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
                        <span className="page-detail">{Math.min(safePage * PER_PAGE, sortedUsers.length)} of {sortedUsers.length}</span>
                    </div>
                )}
            </section>

            <section className="card padded recent-activity">
                <div className="filter-head">
                    <div>
                        <h3>Recent Activity List</h3>
                        <p className="muted">See the account changes you made recently.</p>
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
                                                description: 'The activity list is already empty. Create, edit or deactivate an account and its activity will show up here.',
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
                                <circle cx="9" cy="8" r="4" />
                                <path d="M2 21a7 7 0 0 1 14 0" />
                                <path d="M17 11h5M19.5 8.5v5" />
                            </svg>
                        </span>
                        <p className="table-empty-title">No account activity yet</p>
                        <p className="table-empty-sub">Create, edit or deactivate an account and it will show up here.</p>
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

            {addOpen && (
                <Modal
                    title="Create account"
                    className="modal-user-create"
                    noClose
                    onClose={() => {
                        // Keep the form open while the nested error dialog is up,
                        // otherwise one Escape press would close both dialogs.
                        if (!createErrorOpen) {
                            setAddOpen(false);
                        }
                    }}
                >
                    <form className="form" onSubmit={handleCreate}>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">
                                    Full name
                                    <InfoTip text="Letters and spaces only. Shown in activity logs and across the app." />*
                                </span>
                                <div className="input-prefix-wrap has-icon">
                                    <span className="input-prefix input-prefix-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="12" cy="8" r="4" />
                                            <path d="M4 21a8 8 0 0 1 16 0" />
                                        </svg>
                                    </span>
                                    <input
                                        type="text"
                                        required
                                        maxLength={100}
                                        placeholder="e.g. Juan Dela Cruz"
                                        value={createForm.name}
                                        onChange={createSet('name')}
                                        autoFocus
                                    />
                                </div>
                            </label>
                            <label className="field">
                                <span className="field-label">
                                    Username
                                    <InfoTip text="Used to sign in. Letters and numbers only, at least 3 characters." />*
                                </span>
                                <div className="input-prefix-wrap has-icon">
                                    <span className="input-prefix input-prefix-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="12" cy="8" r="4" />
                                            <path d="M4 21a8 8 0 0 1 16 0" />
                                        </svg>
                                    </span>
                                    <input
                                        type="text"
                                        required
                                        minLength={3}
                                        maxLength={255}
                                        pattern="[A-Za-z0-9]+"
                                        title="Letters and numbers only"
                                        placeholder="e.g. jdelacruz123"
                                        value={createForm.username}
                                        onChange={createSet('username')}
                                    />
                                </div>
                            </label>
                        </div>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">
                                    Email
                                    <InfoTip text="Contact address for the account. Each account needs a unique email." />*
                                </span>
                                <div className="input-prefix-wrap has-icon">
                                    <span className="input-prefix input-prefix-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                            <rect x="2" y="4" width="20" height="16" rx="2" />
                                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                        </svg>
                                    </span>
                                    <input
                                        type="email"
                                        required
                                        maxLength={255}
                                        placeholder="e.g. jdelacruz123@example.com"
                                        value={createForm.email}
                                        onChange={createSet('email')}
                                    />
                                </div>
                            </label>
                            <label className="field">
                                <span className="field-label">
                                    Role
                                    <InfoTip text="Admin manages stock and accounts. Cashier can only sell at the POS." />*
                                </span>
                                <select className="input" value={createForm.role} onChange={createSet('role')}>
                                    {roles.map((role) => (
                                        <option key={role} value={role}>{role}</option>
                                    ))}
                                </select>
                            </label>
                        </div>
                        <label className="field field-password-fixed">
                            <span className="field-label">
                                Password
                                <InfoTip text="At least 6 characters. The user can change it after signing in." />*
                            </span>
                            <div className="input-with-action">
                                <input
                                    type={showCreatePassword ? 'text' : 'password'}
                                    required
                                    minLength={6}
                                    autoComplete="new-password"
                                    placeholder="******"
                                    value={createForm.password}
                                    onChange={createSet('password')}
                                />
                                <button
                                    type="button"
                                    className="input-action"
                                    onClick={() => setShowCreatePassword((visible) => !visible)}
                                >
                                    {showCreatePassword ? 'Hide' : 'Show'}
                                </button>
                            </div>
                            {(() => {
                                const len = createForm.password.length;
                                const met = len >= 6;
                                const state = len === 0 ? 'is-idle' : met ? 'is-met' : 'is-short';
                                const pct = Math.min(100, Math.round((len / 6) * 100));
                                return (
                                    <div className={`password-strength ${state}`} aria-live="polite">
                                        <div className="password-strength-track" aria-hidden="true">
                                            <div className="password-strength-fill" style={{ width: `${pct}%` }} />
                                        </div>
                                        <div className="password-strength-row">
                                            <span className="password-strength-rule">
                                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                    {met ? (
                                                        <>
                                                            <circle cx="12" cy="12" r="9" />
                                                            <path d="m8.5 12 2.3 2.3 4.7-4.7" />
                                                        </>
                                                    ) : (
                                                        <circle cx="12" cy="12" r="9" />
                                                    )}
                                                </svg>
                                                At least 6 characters
                                            </span>
                                        </div>
                                    </div>
                                );
                            })()}
                        </label>
                        <label className="field field-password-fixed">
                            <span className="field-label">
                                Confirm password
                                <InfoTip text="Must exactly match the password above." />*
                            </span>
                            <div className="input-with-action">
                                <input
                                    type={showCreateConfirm ? 'text' : 'password'}
                                    required
                                    minLength={6}
                                    autoComplete="new-password"
                                    placeholder="******"
                                    value={createForm.password_confirmation}
                                    onChange={createSet('password_confirmation')}
                                />
                                <button
                                    type="button"
                                    className="input-action"
                                    onClick={() => setShowCreateConfirm((visible) => !visible)}
                                >
                                    {showCreateConfirm ? 'Hide' : 'Show'}
                                </button>
                            </div>
                            {(() => {
                                const confirm = createForm.password_confirmation;
                                const matched = confirm.length > 0 && confirm === createForm.password;
                                const state = confirm.length === 0 ? 'is-idle' : matched ? 'is-met' : 'is-short';
                                const label = confirm.length === 0
                                    ? 'Passwords must match'
                                    : matched
                                        ? 'Passwords match'
                                        : 'Passwords do not match';
                                return (
                                    <div className={`password-strength ${state}`} aria-live="polite">
                                        <div className="password-strength-row">
                                            <span className="password-strength-rule">
                                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                    {confirm.length === 0 ? (
                                                        <circle cx="12" cy="12" r="9" />
                                                    ) : matched ? (
                                                        <>
                                                            <circle cx="12" cy="12" r="9" />
                                                            <path d="m8.5 12 2.3 2.3 4.7-4.7" />
                                                        </>
                                                    ) : (
                                                        <>
                                                            <circle cx="12" cy="12" r="9" />
                                                            <path d="m9.5 9.5 5 5M14.5 9.5l-5 5" />
                                                        </>
                                                    )}
                                                </svg>
                                                {label}
                                            </span>
                                        </div>
                                    </div>
                                );
                            })()}
                        </label>

                        <div className="modal-actions modal-actions-center">
                            <button type="button" className="btn btn-ghost" onClick={() => setAddOpen(false)}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={creating}>
                                {creating ? 'Creating…' : 'Create account'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {addOpen && createErrorOpen && (
                <MessageDialog
                    tone="danger"
                    title="Couldn't create account"
                    description="Please fix the following and try again."
                    messages={createErrorMessages}
                    onClose={closeCreateError}
                />
            )}

            {editUser && editForm && (
                <Modal
                    title="Edit Account"
                    titleIcon={MODAL_INFO_ICON}
                    className="modal-user-form"
                    loading={editing}
                    onClose={() => {
                        if (!editing) {
                            setEditUser(null);
                        }
                    }}
                >
                    <form className="form" onSubmit={handleEdit}>
                        <label className="field">
                            <span className="field-label">Full name *</span>
                            <div className="input-prefix-wrap has-icon">
                                <span className="input-prefix input-prefix-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <circle cx="12" cy="8" r="4" />
                                        <path d="M4 21a8 8 0 0 1 16 0" />
                                    </svg>
                                </span>
                                <input type="text" required maxLength={100} value={editForm.name} onChange={editSet('name')} />
                            </div>
                        </label>
                        <div className="field-row">
                            <label className="field">
                                <span className="field-label">Username *</span>
                                <div className="input-prefix-wrap has-icon">
                                    <span className="input-prefix input-prefix-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="12" cy="8" r="4" />
                                            <path d="M4 21a8 8 0 0 1 16 0" />
                                        </svg>
                                    </span>
                                    <input type="text" required minLength={3} pattern="[A-Za-z0-9]+" value={editForm.username} onChange={editSet('username')} />
                                </div>
                            </label>
                            <label className="field">
                                <span className="field-label">Role *</span>
                                <select
                                    className="input"
                                    value={editForm.role}
                                    onChange={editSet('role')}
                                    disabled={me?.id === editUser.id}
                                    title={me?.id === editUser.id ? 'You cannot change your own role.' : undefined}
                                >
                                    {roles.map((role) => (
                                        <option key={role} value={role}>{role}</option>
                                    ))}
                                </select>
                            </label>
                        </div>
                        <label className="field">
                            <span className="field-label">Email *</span>
                            <div className="input-prefix-wrap has-icon">
                                <span className="input-prefix input-prefix-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <rect x="2" y="4" width="20" height="16" rx="2" />
                                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                    </svg>
                                </span>
                                <input type="email" required maxLength={255} value={editForm.email} onChange={editSet('email')} />
                            </div>
                        </label>

                        {editError && <div className="alert alert-error" role="alert"><span>{editError}</span></div>}

                        <div className="modal-actions">
                            <button type="button" className="btn btn-ghost" onClick={() => setEditUser(null)} disabled={editing}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={editing}>
                                {editing ? 'Saving…' : 'Save changes'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {resetUser && (
                <Modal title="Reset password" titleIcon={MODAL_INFO_ICON} className="modal-user-form modal-reset" onClose={() => setResetUser(null)}>
                    <form className="form" onSubmit={handleReset}>
                        <div className="field">
                            <span className="field-label">Username</span>
                            <div className="readonly-field">{resetUser.username}</div>
                        </div>
                        <label className="field">
                            <span className="field-label">New password *</span>
                            <div className="input-with-action">
                                <input
                                    type={showResetPassword ? 'text' : 'password'}
                                    required
                                    minLength={6}
                                    autoComplete="new-password"
                                    value={resetForm.password}
                                    onChange={resetSet('password')}
                                    autoFocus
                                />
                                <button
                                    type="button"
                                    className="input-action"
                                    onClick={() => setShowResetPassword((visible) => !visible)}
                                >
                                    {showResetPassword ? 'Hide' : 'Show'}
                                </button>
                            </div>
                        </label>
                        <label className="field">
                            <span className="field-label">Confirm new password *</span>
                            <div className="input-with-action">
                                <input
                                    type={showResetConfirm ? 'text' : 'password'}
                                    required
                                    minLength={6}
                                    autoComplete="new-password"
                                    value={resetForm.password_confirmation}
                                    onChange={resetSet('password_confirmation')}
                                />
                                <button
                                    type="button"
                                    className="input-action"
                                    onClick={() => setShowResetConfirm((visible) => !visible)}
                                >
                                    {showResetConfirm ? 'Hide' : 'Show'}
                                </button>
                            </div>
                        </label>

                        {resetError && <div className="alert alert-error" role="alert"><span>{resetError}</span></div>}

                        <div className="modal-actions">
                            <button type="button" className="btn btn-ghost" onClick={() => setResetUser(null)}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={resetting}>
                                {resetting ? 'Resetting…' : 'Reset password'}
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
                                <dt>Account</dt>
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

            {activateUser && (
                <ConfirmDialog
                    tone="info"
                    title={<>Activate <strong>{activateUser.name}</strong>?</>}
                    description="This reactivates the account so it can sign in again."
                    confirmLabel="Activate"
                    loadingLabel="Activating…"
                    loading={busyId === activateUser?.id}
                    error={activateError}
                    onConfirm={handleActivateConfirm}
                    onCancel={() => {
                        if (busyId !== activateUser?.id) {
                            setActivateUser(null);
                            setActivateError(null);
                        }
                    }}
                />
            )}

            {deactivateUser && (
                <ConfirmDialog
                    tone="danger"
                    title={<>Deactivate <strong>{deactivateUser.name}</strong>?</>}
                    description="This deactivates the account so it can no longer sign in. Its history is preserved and the account can be reactivated at any time."
                    confirmLabel="Deactivate"
                    loadingLabel="Deactivating…"
                    loading={busyId === deactivateUser?.id}
                    error={deactivateError}
                    onConfirm={handleDeactivateConfirm}
                    onCancel={() => {
                        if (busyId !== deactivateUser?.id) {
                            setDeactivateUser(null);
                            setDeactivateError(null);
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
                    title="Delete all users?"
                    description={`This permanently deletes all ${sortedUsers.length} other user account(s) in the table. Your currently signed-in account and any accounts linked to transaction history are kept. This action cannot be undone.`}
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
                    description="This permanently deletes the account activity history from the activity log. The accounts themselves are not affected. This action cannot be undone."
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
