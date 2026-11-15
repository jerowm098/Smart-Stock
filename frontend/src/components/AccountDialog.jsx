import { useState } from 'react';
import { createPortal } from 'react-dom';
import Modal from './Modal.jsx';
import InfoTip from './InfoTip.jsx';
import { put, validationMessages } from '../lib/api.js';
import { useAuth } from '../auth/AuthContext.jsx';

/**
 * Account settings for the signed-in ("Currently Using") account.
 *
 * Opened from the header account menu. The Users page only lists the OTHER
 * accounts, so everything about the current account â€” details and password â€”
 * is maintained here. The role is shown read-only: nobody can change their own
 * role from this dialog (the backend ignores it as well).
 *
 * The dialog is portaled to <body> because the trigger lives inside the topbar,
 * which is its own stacking context (z-index 10 as a flex item). Without the
 * portal the overlay would be trapped under the sidebar (z-index 30) instead of
 * covering the whole screen.
 */
const sanitizeName = (value) => value.replace(/[^A-Za-zÃ‘Ã± ]/g, '');

export default function AccountDialog({ onClose, onSaved }) {
    const { user, refresh } = useAuth();

    const [form, setForm] = useState({
        name: user?.name ?? '',
        username: user?.username ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
    });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    const set = (key) => (event) => {
        const value = key === 'name' ? sanitizeName(event.target.value) : event.target.value;
        setForm((current) => ({ ...current, [key]: value }));
    };

    // Both password fields stay untouched unless the user starts typing; the
    // section is optional so saving details alone never requires a password.
    const passwordTouched = form.password.length > 0 || form.password_confirmation.length > 0;

    async function handleSubmit(event) {
        event.preventDefault();
        setError(null);

        if (passwordTouched) {
            if (form.password.length < 6) {
                setError('The new password must be at least 6 characters.');
                return;
            }

            if (form.password !== form.password_confirmation) {
                setError('The password confirmation does not match.');
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

            // Keep the topbar identity (and the cached user) in sync.
            await refresh();

            onSaved?.(response?.message ?? 'Account updated successfully.');
        } catch (err) {
            setError(validationMessages(err)[0] ?? err.message ?? 'Could not update the account.');
        } finally {
            setSaving(false);
        }
    }

    return createPortal(
        <Modal
            title="Account"
            description="Update the details of the account you are currently using."
            className="modal-account"
            noClose
            onClose={onClose}
        >
            <form className="form" onSubmit={handleSubmit}>
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
                            onChange={set('name')}
                            autoFocus
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
                                onChange={set('username')}
                            />
                        </div>
                    </label>
                    <div className="field">
                        <span className="field-label">Role</span>
                        <div className="readonly-field">{user?.role ?? 'â€”'}</div>
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
                            onChange={set('email')}
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
                                    onChange={set('password')}
                                />
                                <button
                                    type="button"
                                    className="input-action"
                                    onClick={() => setShowPassword((visible) => !visible)}
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
                                    onChange={set('password_confirmation')}
                                />
                                <button
                                    type="button"
                                    className="input-action"
                                    onClick={() => setShowConfirm((visible) => !visible)}
                                >
                                    {showConfirm ? 'Hide' : 'Show'}
                                </button>
                            </div>
                        </label>
                    </div>
                </div>

                {error && <div className="alert alert-error" role="alert"><span>{error}</span></div>}

                <div className="modal-actions modal-actions-center">
                    <button type="button" className="btn btn-ghost" onClick={onClose} disabled={saving}>
                        Cancel
                    </button>
                    <button type="submit" className="btn btn-primary" disabled={saving}>
                        {saving ? 'Savingâ€¦' : 'Save changes'}
                    </button>
                </div>
            </form>
        </Modal>,
        document.body,
    );
}
