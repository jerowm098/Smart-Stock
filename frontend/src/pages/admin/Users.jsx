import { useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import Modal from '../../components/Modal.jsx';
import Alert, { FieldError } from '../../components/Feedback.jsx';
import { get, post, put, validationMessages } from '../../lib/api.js';

const EMPTY_CREATE = { name: '', username: '', email: '', role: 'cashier', password: '', password_confirmation: '' };
const PER_PAGE = 6;

export default function Users() {
    const [users, setUsers] = useState([]);
    const [roles, setRoles] = useState(['admin', 'cashier']);
    const [me, setMe] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [message, setMessage] = useState(null);
    const [busyId, setBusyId] = useState(null);

    const [addOpen, setAddOpen] = useState(false);
    const [createForm, setCreateForm] = useState(EMPTY_CREATE);
    const [creating, setCreating] = useState(false);
    const [formError, setFormError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState([]);

    const [editUser, setEditUser] = useState(null);
    const [editForm, setEditForm] = useState(null);
    const [editError, setEditError] = useState(null);

    const [resetUser, setResetUser] = useState(null);
    const [resetForm, setResetForm] = useState({ password: '', password_confirmation: '' });
    const [resetting, setResetting] = useState(false);
    const [resetError, setResetError] = useState(null);

    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [page, setPage] = useState(1);

    const tableRef = useRef(null);
    const wrapRef = useRef(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [list, session] = await Promise.all([get('/api/users'), get('/api/auth/user')]);
            setUsers(list.users ?? []);
            setRoles(list.roles ?? ['admin', 'cashier']);
            setMe(session.user);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

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
        if (!sortBy) {
            return users;
        }

        const dir = sortDir === 'asc' ? 1 : -1;
        return [...users].sort((a, b) => {
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
    }, [users, sortBy, sortDir]);

    const lastPage = Math.max(1, Math.ceil(sortedUsers.length / PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const pagedUsers = useMemo(
        () => sortedUsers.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE),
        [sortedUsers, safePage]
    );

    // Reserve exactly one full page (6 rows) of height, mirroring Stock
    // Inventory and Backups. Measured from the rendered table so it stays
    // accurate across fonts, zoom levels and action-button wrapping.
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
    }, [pagedUsers.length, loading]);

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
            await load();
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
        setEditError(null);

        try {
            const response = await put(`/api/users/${editUser.id}`, editForm);
            setMessage(response.message);
            setEditUser(null);
            await load();
        } catch (err) {
            setEditError(validationMessages(err)[0] ?? err.message);
        }
    }

    async function handleStatus(user, action) {
        setBusyId(user.id);
        setMessage(null);
        setError(null);

        try {
            const response = await post(`/api/users/${user.id}/${action}`);
            setMessage(response.message);
            await load();
        } catch (err) {
            setError(err.message);
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

    const createSet = (name) => (event) => setCreateForm((current) => ({ ...current, [name]: event.target.value }));
    const editSet = (name) => (event) => setEditForm((current) => ({ ...current, [name]: event.target.value }));
    const resetSet = (name) => (event) => setResetForm((current) => ({ ...current, [name]: event.target.value }));

    return (
        <div className="page">
            <section className={`page-hero${loading ? ' is-loading' : ''}`}>
                <div className="page-loadbar" aria-hidden="true" />
                <header className="page-head">
                    <div>
                        <h2>Users</h2>
                        <p>Manage user accounts, roles, and access.</p>
                    </div>
                </header>
            </section>

            {message && <Alert tone="success" onClose={() => setMessage(null)}>{message}</Alert>}
            {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}

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
                        <p className="muted">Every account with its role, status and last update.</p>
                    </div>
                </div>
                {!loading && !error && (
                    <div className="table-shell">
                        <div className="table-wrap data-rows-6" ref={wrapRef}>
                            <table className="table" ref={tableRef}>
                                <thead>
                                    <tr>
                                        <th className="sort-header" onClick={() => toggleSort('name')}>
                                            Account{sortBy === 'name' && sortDir === 'asc' ? ' ▲' : sortBy === 'name' ? ' ▼' : ''}
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('username')}>
                                            Username{sortBy === 'username' && sortDir === 'asc' ? ' ▲' : sortBy === 'username' ? ' ▼' : ''}
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('email')}>
                                            Email{sortBy === 'email' && sortDir === 'asc' ? ' ▲' : sortBy === 'email' ? ' ▼' : ''}
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('role')}>
                                            Role{sortBy === 'role' && sortDir === 'asc' ? ' ▲' : sortBy === 'role' ? ' ▼' : ''}
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('status')}>
                                            Status{sortBy === 'status' && sortDir === 'asc' ? ' ▲' : sortBy === 'status' ? ' ▼' : ''}
                                        </th>
                                        <th className="sort-header" onClick={() => toggleSort('updated')}>
                                            Updated{sortBy === 'updated' && sortDir === 'asc' ? ' ▲' : sortBy === 'updated' ? ' ▼' : ''}
                                        </th>
                                        <th className="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pagedUsers.map((user) => {
                                        const isSelf = me?.id === user.id;

                                        return (
                                            <tr key={user.id} className={user.is_active ? undefined : 'row-inactive'}>
                                                <td>
                                                    <strong>{user.name}</strong>
                                                </td>
                                                <td className="mono muted">@{user.username}</td>
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
                                                    <button type="button" className="btn btn-ghost btn-sm" onClick={() => openEdit(user)}>
                                                        Edit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        className="btn btn-ghost btn-sm"
                                                        onClick={() => setResetUser(user)}
                                                        disabled={!user.is_active}
                                                        title={user.is_active ? 'Reset password' : 'Reactivate first'}
                                                    >
                                                        Reset password
                                                    </button>
                                                    {user.is_active ? (
                                                        <button
                                                            type="button"
                                                            className="btn btn-ghost btn-sm btn-danger-text action-toggle"
                                                            disabled={isSelf || busyId === user.id}
                                                            title={isSelf ? 'You cannot deactivate your own account.' : 'Deactivate'}
                                                            onClick={() => handleStatus(user, 'deactivate')}
                                                        >
                                                            Deactivate
                                                        </button>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            className="btn btn-ghost btn-sm btn-success-text action-toggle"
                                                            disabled={busyId === user.id}
                                                            onClick={() => handleStatus(user, 'activate')}
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
                    </div>
                )}
            </section>
            </>
            )}

            {addOpen && (
                <Modal title="Create account" onClose={() => setAddOpen(false)}>
                    <form className="form" onSubmit={handleCreate}>
                        <label className="field">
                            <span>Full name *</span>
                            <input type="text" required maxLength={100} value={createForm.name} onChange={createSet('name')} autoFocus />
                        </label>
                        <div className="field-row">
                            <label className="field">
                                <span>Username *</span>
                                <input type="text" required minLength={3} maxLength={255} pattern="[A-Za-z0-9]+" title="Letters and numbers only" value={createForm.username} onChange={createSet('username')} />
                            </label>
                            <label className="field">
                                <span>Role *</span>
                                <select className="input" value={createForm.role} onChange={createSet('role')}>
                                    {roles.map((role) => (
                                        <option key={role} value={role}>{role}</option>
                                    ))}
                                </select>
                            </label>
                        </div>
                        <label className="field">
                            <span>Email *</span>
                            <input type="email" required maxLength={255} value={createForm.email} onChange={createSet('email')} />
                        </label>
                        <div className="field-row">
                            <label className="field">
                                <span>Password *</span>
                                <input type="password" required minLength={6} autoComplete="new-password" value={createForm.password} onChange={createSet('password')} />
                            </label>
                            <label className="field">
                                <span>Confirm password *</span>
                                <input type="password" required minLength={6} autoComplete="new-password" value={createForm.password_confirmation} onChange={createSet('password_confirmation')} />
                            </label>
                        </div>

                        {formError && (
                            <div className="alert alert-error" role="alert">
                                <span>{formError}</span>
                                <FieldError messages={fieldErrors} />
                            </div>
                        )}

                        <div className="modal-actions">
                            <button type="button" className="btn btn-ghost" onClick={() => setAddOpen(false)}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={creating}>
                                {creating ? 'Creating…' : 'Create account'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {editUser && editForm && (
                <Modal title={`Edit @${editUser.username}`} onClose={() => setEditUser(null)}>
                    <form className="form" onSubmit={handleEdit}>
                        <label className="field">
                            <span>Full name *</span>
                            <input type="text" required maxLength={100} value={editForm.name} onChange={editSet('name')} />
                        </label>
                        <div className="field-row">
                            <label className="field">
                                <span>Username *</span>
                                <input type="text" required minLength={3} pattern="[A-Za-z0-9]+" value={editForm.username} onChange={editSet('username')} />
                            </label>
                            <label className="field">
                                <span>Role *</span>
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
                            <span>Email *</span>
                            <input type="email" required maxLength={255} value={editForm.email} onChange={editSet('email')} />
                        </label>

                        {editError && <div className="alert alert-error" role="alert"><span>{editError}</span></div>}

                        <div className="modal-actions">
                            <button type="button" className="btn btn-ghost" onClick={() => setEditUser(null)}>Cancel</button>
                            <button type="submit" className="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </Modal>
            )}

            {resetUser && (
                <Modal title={`Reset password — @${resetUser.username}`} onClose={() => setResetUser(null)}>
                    <form className="form" onSubmit={handleReset}>
                        <label className="field">
                            <span>New password *</span>
                            <input type="password" required minLength={6} autoComplete="new-password" value={resetForm.password} onChange={resetSet('password')} autoFocus />
                        </label>
                        <label className="field">
                            <span>Confirm new password *</span>
                            <input type="password" required minLength={6} autoComplete="new-password" value={resetForm.password_confirmation} onChange={resetSet('password_confirmation')} />
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
        </div>
    );
}
