import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import ThemeToggle from '../components/ThemeToggle.jsx';
import { post, validationMessages } from '../lib/api.js';

export default function ResetPassword() {
    const [params] = useSearchParams();
    const [token, setToken] = useState(params.get('token') ?? '');
    const [password, setPassword] = useState('');
    const [confirm, setConfirm] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);
    const [doneMessage, setDoneMessage] = useState(null);

    async function handleSubmit(event) {
        event.preventDefault();
        setError(null);

        if (password.length < 6) {
            setError('Password must be at least 6 characters.');
            return;
        }
        if (password !== confirm) {
            setError('Passwords do not match.');
            return;
        }

        setSubmitting(true);
        try {
            const response = await post('/api/auth/reset-password', {
                token: token.trim(),
                password,
                password_confirmation: confirm,
            });
            setDoneMessage(response?.message ?? 'Your password has been reset.');
        } catch (err) {
            setError(validationMessages(err)[0] ?? err.message ?? 'Could not reset your password.');
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="auth-shell">
            <div className="auth-box">
                <aside className="auth-aside">
                    <Link to="/" className="brand brand-inline">
                        <span className="brand-mark">SS</span>
                        <span className="brand-text">
                            <strong>Smart-Stock</strong>
                            <small>Intelligent Inventory</small>
                        </span>
                    </Link>

                    <div className="auth-aside-copy">
                        <h2>Set a new password.</h2>
                        <p>
                            Choose a new password with at least 6 characters to regain access to your account.
                        </p>
                    </div>

                    <ul className="auth-points">
                        <li>Your reset token can only be used once</li>
                        <li>Expired tokens are automatically rejected</li>
                        <li>Sign in immediately once updated</li>
                    </ul>
                </aside>

                <main className="auth-main">
                    <div className="auth-theme-toggle">
                        <ThemeToggle />
                    </div>
                    <header className="auth-main-head">
                        <h1>Reset password</h1>
                        <p>Enter your reset token and choose a new password.</p>
                    </header>

                    {doneMessage ? (
                        <div className="form">
                            <div className="alert alert-success" role="status">
                                <span>{doneMessage}</span>
                            </div>
                            <Link to="/login" className="btn btn-primary btn-block btn-lg">
                                Sign in with new password
                            </Link>
                        </div>
                    ) : (
                        <form onSubmit={handleSubmit} className="form">
                            <label className="field">
                                <span className="field-label">Reset token</span>
                                <input
                                    type="text"
                                    required
                                    className="mono"
                                    value={token}
                                    onChange={(e) => setToken(e.target.value)}
                                    placeholder="Paste your reset token"
                                />
                            </label>

                            <label className="field">
                                <span className="field-label">New password</span>
                                <div className="input-with-action">
                                    <input
                                        type={showPassword ? 'text' : 'password'}
                                        required
                                        minLength={6}
                                        autoComplete="new-password"
                                        placeholder="******"
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                    />
                                    <button
                                        type="button"
                                        className="input-action"
                                        onClick={() => setShowPassword((v) => !v)}
                                    >
                                        {showPassword ? 'Hide' : 'Show'}
                                    </button>
                                </div>
                            </label>

                            <label className="field">
                                <span className="field-label">Confirm new password</span>
                                <div className="input-with-action">
                                    <input
                                        type={showConfirm ? 'text' : 'password'}
                                        required
                                        minLength={6}
                                        autoComplete="new-password"
                                        placeholder="******"
                                        value={confirm}
                                        onChange={(e) => setConfirm(e.target.value)}
                                    />
                                    <button
                                        type="button"
                                        className="input-action"
                                        onClick={() => setShowConfirm((v) => !v)}
                                    >
                                        {showConfirm ? 'Hide' : 'Show'}
                                    </button>
                                </div>
                            </label>

                            {error && (
                                <p className="form-error" role="alert">
                                    {error}
                                </p>
                            )}

                            <button type="submit" className="btn btn-primary btn-block btn-lg" disabled={submitting}>
                                {submitting ? 'Resetting…' : 'Reset password'}
                            </button>
                        </form>
                    )}

                    <p className="auth-footnote">
                        Need a new token? <Link to="/forgot-password">Request reset link</Link> · <Link to="/login">Sign in</Link>
                    </p>
                </main>
            </div>
        </div>
    );
}
