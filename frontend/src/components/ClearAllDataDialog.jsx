import { useState } from 'react';
import { createPortal } from 'react-dom';
import Modal from './Modal.jsx';
import { post, validationMessages } from '../lib/api.js';
import { useAuth } from '../auth/AuthContext.jsx';

/**
 * Password-confirmed overall data reset modal for Admins.
 *
 * Clears all operational, catalog, audit, backup, and other-user records while
 * preserving the currently signed-in admin account. Portaled to <body> so the
 * overlay escapes the topbar's stacking context (same pattern as AccountDialog).
 */
export default function ClearAllDataDialog({ onClose, onCleared }) {
    const { user } = useAuth();
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [clearing, setClearing] = useState(false);
    const [error, setError] = useState(null);

    async function handleSubmit(event) {
        event.preventDefault();
        setError(null);

        if (!password.trim()) {
            setError('Please enter your current password to confirm.');
            return;
        }

        setClearing(true);

        try {
            const response = await post('/api/system/clear-all', { password });
            onCleared?.(response?.message ?? 'All system data has been cleared.');
        } catch (err) {
            setError(validationMessages(err)[0] ?? err.message ?? 'Could not clear system data.');
        } finally {
            setClearing(false);
        }
    }

    return createPortal(
        <Modal
            bare
            noClose
            ariaLabel="Clear All Data"
            className="modal-clear-all"
            onClose={() => {
                if (!clearing) {
                    onClose?.();
                }
            }}
        >
            <form className="form" onSubmit={handleSubmit}>
                <div className="confirm-dialog confirm-dialog-danger">
                    <span className="confirm-dialog-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z M12 9v4 M12 17h.01" />
                        </svg>
                    </span>
                    <div className="confirm-dialog-body">
                        <p className="confirm-dialog-title">Clear All Data</p>
                        <p className="muted confirm-dialog-text">
                            This permanently clears all data across every page. Your signed-in admin account (<strong>@{user?.username}</strong>) will stay intact. This action cannot be undone.
                        </p>
                    </div>
                </div>

                <label className="field">
                    <span className="field-label">
                        Enter your password to confirm <span className="text-danger">*</span>
                    </span>
                    <div className="input-with-action">
                        <input
                            type={showPassword ? 'text' : 'password'}
                            required
                            autoComplete="current-password"
                            placeholder="******"
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            disabled={clearing}
                            autoFocus
                        />
                        <button
                            type="button"
                            className="input-action"
                            onClick={() => setShowPassword((visible) => !visible)}
                            disabled={clearing}
                        >
                            {showPassword ? 'Hide' : 'Show'}
                        </button>
                    </div>
                </label>

                {error && <div className="alert alert-error" role="alert"><span>{error}</span></div>}

                <div className="modal-actions modal-actions-center">
                    <button type="button" className="btn btn-ghost" onClick={onClose} disabled={clearing}>
                        Cancel
                    </button>
                    <button type="submit" className="btn btn-danger" disabled={clearing || !password}>
                        {clearing ? 'Clearing…' : 'Clear All Data'}
                    </button>
                </div>
            </form>
        </Modal>,
        document.body,
    );
}
