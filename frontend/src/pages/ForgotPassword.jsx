import { useState } from 'react';
import { Link } from 'react-router-dom';
import ThemeToggle from '../components/ThemeToggle.jsx';
import { post, validationMessages } from '../lib/api.js';

export default function ForgotPassword() {
    const [identifier, setIdentifier] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);
    const [result, setResult] = useState(null);

    async function handleSubmit(event) {
        event.preventDefault();
        setError(null);
        setResult(null);
        setSubmitting(true);

        try {
            const response = await post('/api/auth/forgot-password', {
                identifier: identifier.trim(),
            });
            setResult(response);
        } catch (err) {
            setError(validationMessages(err)[0] ?? err.message ?? 'Unable to process request right now.');
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
                        <h2>Recover your access.</h2>
                        <p>
                            Request a time-limited reset token using your assigned username or email address.
                        </p>
                    </div>

                    <ul className="auth-points">
                        <li>Reset tokens expire automatically after 30 minutes</li>
                        <li>Tokens are stored hashed and cleared immediately after use</li>
                        <li>Admins can also reset staff passwords from Users</li>
                    </ul>
                </aside>

                <main className="auth-main">
                    <div className="auth-theme-toggle">
                        <ThemeToggle />
                    </div>
                    <header className="auth-main-head">
                        <h1>Forgot password</h1>
                        <p>Enter your username or email to generate a password reset link.</p>
                    </header>

                    <form onSubmit={handleSubmit} className="form">
                        <label className="field">
                            <span className="field-label">Username or email</span>
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
                                    autoFocus
                                    value={identifier}
                                    onChange={(e) => setIdentifier(e.target.value)}
                                    placeholder="e.g. testadmin or testadmin@gmail.com"
                                />
                            </div>
                        </label>

                        {error && (
                            <p className="form-error" role="alert">
                                {error}
                            </p>
                        )}

                        {result && (
                            <div className="alert alert-success" role="status">
                                <div>
                                    <p style={{ margin: 0 }}>{result.message}</p>
                                    {result.reset_url && (
                                        <p style={{ margin: '8px 0 0' }}>
                                            <Link to={result.reset_url} className="btn btn-primary btn-sm">
                                                Continue to Reset Password
                                            </Link>
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}

                        <button type="submit" className="btn btn-primary btn-block btn-lg" disabled={submitting}>
                            {submitting ? 'Generating link…' : 'Send reset link'}
                        </button>
                    </form>

                    <p className="auth-footnote">
                        Remembered your password? <Link to="/login">Back to Sign in</Link>
                    </p>
                </main>
            </div>
        </div>
    );
}
