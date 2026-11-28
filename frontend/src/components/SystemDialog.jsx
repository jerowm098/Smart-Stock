import { useState } from 'react';
import { createPortal } from 'react-dom';
import Modal from './Modal.jsx';
import InfoTip from './InfoTip.jsx';
import { post, put, validationMessages } from '../lib/api.js';
import { useAuth } from '../auth/AuthContext.jsx';

const sanitizeName = (value) => value.replace(/[^A-Za-zÑñ ]/g, '');

/**
 * Unified System Settings modal (vertical-tab / split-pane settings dialog).
 *
 * Left navigation rail with a vertical separator line switches between:
 *  - Account: edit the currently signed-in user's details & password
 *  - Clear All Data (Admin only): password-confirmed overall system data reset
 *
 * Portaled to <body> so the overlay escapes the topbar's stacking context.
 */
export default function SystemDialog({ onClose, onSaved, onCleared }) {
    const { user, refresh } = useAuth();
    const isAdmin = user?.role === 'admin';

    const [activeTab, setActiveTab] = useState('account');

    // Account tab state
    const [form, setForm] = useState({
        name: user?.name ?? '',
        username: user?.username ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
    });
    const [saving, setSaving] = useState(false);
    const [accountError, setAccountError] = useState(null);
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    // Clear All Data tab state
    const [clearPassword, setClearPassword] = useState('');
    const [showClearPassword, setShowClearPassword] = useState(false);
    const [clearing, setClearing] = useState(false);
    const [clearError, setClearError] = useState(null);

    const busy = saving || clearing;

    const setField = (key) => (event) => {
        const value = key === 'name' ? sanitizeName(event.target.value) : event.target.value;
        setForm((current) => ({ ...current, [key]: value }));
        setAccountError(null);
    };

    const passwordTouched = form.password.length > 0 || form.password_confirmation.length > 0;

    async function handleAccountSubmit(event) {
        event.preventDefault();
        setAccountError(null);

        if (passwordTouched) {
            if (form.password.length < 6) {
                setAccountError('The new password must be at least 6 characters.');
                return;
            }
            if (form.password !== form.password_confirmation) {
                setAccountError('The password confirmation does not match.');
                return;
            }
        }

        setSaving(true);
        try {
            const payload = {
                name: form.name,
                username: form.username,
                email: form.email,
            };
            if (passwordTouched) {
                payload.password = form.password;
                payload.password_confirmation = form.password_confirmation;
            }

            const response = await put('/api/account', payload);
            await refresh();
            onSaved?.(response?.message ?? 'Account updated successfully.');
        } catch (err) {
            setAccountError(validationMessages(err)[0] ?? err.message ?? 'Could not update the account.');
        } finally {
            setSaving(false);
        }
    }

    async function handleClearAllSubmit(event) {
        event.preventDefault();
        setClearError(null);

        if (!clearPassword.trim()) {
            setClearError('Please enter your current password to confirm.');
            return;
        }

        setClearing(true);
        try {
            const response = await post('/api/system/clear-all', { password: clearPassword });
            onCleared?.(response?.message ?? 'All system data has been cleared.');
        } catch (err) {
            setClearError(validationMessages(err)[0] ?? err.message ?? 'Could not clear system data.');
        } finally {
            setClearing(false);
        }
    }

    return createPortal(
        <Modal
            bare
            noClose
            ariaLabel="Settings"
            className="modal-system-settings"
            loading={busy}
            onClose={() => {
                if (!busy) onClose?.();
            }}
        >
            <div className="settings-modal">
                {/* Left vertical tab navigation rail */}
                <aside className="settings-nav" aria-label="Settings sections">
                    <div className="settings-nav-head">
                        <span className="modal-title-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <circle cx="12" cy="12" r="3" />
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" />
                            </svg>
                        </span>
                        <h3>Settings</h3>
                    </div>

                    <nav className="settings-tabs" role="tablist" aria-orientation="vertical">
                        <button
                            type="button"
                            role="tab"
                            aria-selected={activeTab === 'account'}
                            className={`settings-tab${activeTab === 'account' ? ' is-active' : ''}`}
                            disabled={busy}
                            onClick={() => setActiveTab('account')}
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 21a8 8 0 0 1 16 0" />
                            </svg>
                            <span>Account</span>
                        </button>

                        {isAdmin && (
                            <button
                                type="button"
                                role="tab"
                                aria-selected={activeTab === 'clear-all'}
                                className={`settings-tab settings-tab-danger${activeTab === 'clear-all' ? ' is-active' : ''}`}
                                disabled={busy}
                                onClick={() => setActiveTab('clear-all')}
                            >
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                    <path d="M3 6h18" />
                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                    <path d="M10 11v6M14 11v6" />
                                </svg>
                                <span>Clear All Data</span>
                            </button>
                        )}
                    </nav>
                </aside>

                {/* Right active-tab content pane */}
                <div className="settings-content">
                    {activeTab === 'account' && (
                        <div className="settings-pane" role="tabpanel" aria-label="Account">
                            <header className="settings-pane-head">
                                <h4>Account</h4>
                                <p className="muted">Update the details of the account you are currently using.</p>
                            </header>

                            <form className="form" onSubmit={handleAccountSubmit}>
                                <label className="field">
                                    <span className="field-label">Full name *</span>
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
                                            value={form.name}
                                            onChange={setField('name')}
                                            disabled={saving}
                                        />
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
                                            <input
                                                type="text"
                                                required
                                                minLength={3}
                                                maxLength={255}
                                                pattern="[A-Za-z0-9]+"
                                                title="Letters and numbers only"
                                                value={form.username}
                                                onChange={setField('username')}
                                                disabled={saving}
                                            />
                                        </div>
                                    </label>
                                    <div className="field">
                                        <span className="field-label">Role</span>
                                        <div className="readonly-field">{user?.role ?? '—'}</div>
                                    </div>
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
                                        <input
                                            type="email"
                                            required
                                            maxLength={255}
                                            placeholder="e.g. jdelacruz123@example.com"
                                            value={form.email}
                                            onChange={setField('email')}
                                            disabled={saving}
                                        />
                                    </div>
                                </label>

                                <div className="account-section">
                                    <div className="account-section-title">
                                        <span>Change password</span>
                                        <small className="muted">Optional. Leave both fields blank to keep the current password.</small>
                                    </div>

                                    <div className="field-row">
                                        <label className="field">
                                            <span className="field-label">
                                                New password
                                                <InfoTip text="Minimum 6 characters." />
                                            </span>
                                            <div className="input-with-action">
                                                <input
                                                    type={showPassword ? 'text' : 'password'}
                                                    minLength={6}
                                                    autoComplete="new-password"
                                                    placeholder="******"
                                                    value={form.password}
                                                    onChange={setField('password')}
                                                    disabled={saving}
                                                />
                                                <button
                                                    type="button"
                                                    className="input-action"
                                                    onClick={() => setShowPassword((visible) => !visible)}
                                                    disabled={saving}
                                                >
                                                    {showPassword ? 'Hide' : 'Show'}
                                                </button>
                                            </div>
                                        </label>
                                        <label className="field">
                                            <span className="field-label">
                                                Confirm new password
                                                <InfoTip text="Re-enter the new password to confirm it." />
                                            </span>
                                            <div className="input-with-action">
                                                <input
                                                    type={showConfirm ? 'text' : 'password'}
                                                    minLength={6}
                                                    autoComplete="new-password"
                                                    placeholder="******"
                                                    value={form.password_confirmation}
                                                    onChange={setField('password_confirmation')}
                                                    disabled={saving}
                                                />
                                                <button
                                                    type="button"
                                                    className="input-action"
                                                    onClick={() => setShowConfirm((visible) => !visible)}
                                                    disabled={saving}
                                                >
                                                    {showConfirm ? 'Hide' : 'Show'}
                                                </button>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                {accountError && <div className="alert alert-error" role="alert"><span>{accountError}</span></div>}

                                <div className="modal-actions modal-actions-end">
                                    <button type="button" className="btn btn-ghost" onClick={onClose} disabled={saving}>
                                        Cancel
                                    </button>
                                    <button type="submit" className="btn btn-primary" disabled={saving}>
                                        {saving ? 'Saving…' : 'Save changes'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}

                    {isAdmin && activeTab === 'clear-all' && (
                        <div className="settings-pane" role="tabpanel" aria-label="Clear All Data">
                            <header className="settings-pane-head">
                                <h4>Clear All Data</h4>
                                <p className="muted">Permanently reset all operational and catalog records across the system.</p>
                            </header>

                            <form className="form" onSubmit={handleClearAllSubmit}>
                                <div className="settings-danger-box">
                                    <span className="settings-danger-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z M12 9v4 M12 17h.01" />
                                        </svg>
                                    </span>
                                    <p>
                                        This permanently clears all data across every page. Your signed-in admin account (<strong>@{user?.username}</strong>) will stay intact. This action cannot be undone.
                                    </p>
                                </div>

                                <label className="field">
                                    <span className="field-label">
                                        Enter your password to confirm <span className="text-danger">*</span>
                                    </span>
                                    <div className="input-with-action">
                                        <input
                                            type={showClearPassword ? 'text' : 'password'}
                                            required
                                            autoComplete="current-password"
                                            placeholder="******"
                                            value={clearPassword}
                                            onChange={(event) => {
                                                setClearPassword(event.target.value);
                                                setClearError(null);
                                            }}
                                            disabled={clearing}
                                        />
                                        <button
                                            type="button"
                                            className="input-action"
                                            onClick={() => setShowClearPassword((visible) => !visible)}
                                            disabled={clearing}
                                        >
                                            {showClearPassword ? 'Hide' : 'Show'}
                                        </button>
                                    </div>
                                </label>

                                {clearError && <div className="alert alert-error" role="alert"><span>{clearError}</span></div>}

                                <div className="modal-actions modal-actions-end">
                                    <button type="button" className="btn btn-ghost" onClick={onClose} disabled={clearing}>
                                        Cancel
                                    </button>
                                    <button type="submit" className="btn btn-danger" disabled={clearing || !clearPassword}>
                                        {clearing ? 'Clearing…' : 'Clear All Data'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}
                </div>
            </div>
        </Modal>,
        document.body,
    );
}
