import { useCallback, useEffect, useState } from 'react';
import Modal from '../../components/Modal.jsx';
import Alert, { FieldError, Loading } from '../../components/Feedback.jsx';
import { get, post, put, validationMessages } from '../../lib/api.js';

const EMPTY_CREATE = { name: '', username: '', email: '', role: 'cashier', password: '', password_confirmation: '' };

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
            <header className="page-head">
                <div>
                    <h2>Users</h2>
                    <p>Create accounts, assign roles, deactivate instead of delete, and reset passwords.</p>
                </div>
                <button type="button" className="btn btn-primary" onClick={() => setAddOpen(true)}>
                    Add account
                </button>
            </header>

            {message && <Alert tone="success" onClose={() => setMessage(null)}>{message}</Alert>}
            {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}

            <section className="card table-card">
                {loading && <Loading label="Loading accounts…" />}

                {!loading && !error && (
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Updated</th>
                                    <th className="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.map((user) => {
                                    const isSelf = me?.id === user.id;

                                    return (
                                        <tr key={user.id}>
                                            <td>
                                                <strong>{user.name}</strong>
                                                <small className="muted"> @{user.username}</small>
                                            </td>
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
                                                        className="btn btn-ghost btn-sm btn-danger-text"
                                                        disabled={isSelf || busyId === user.id}
                                                        title={isSelf ? 'You cannot deactivate your own account.' : 'Deactivate'}
                                                        onClick={() => handleStatus(user, 'deactivate')}
                                                    >
                                                        Deactivate
                                                    </button>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        className="btn btn-ghost btn-sm"
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
                )}
            </section>

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
