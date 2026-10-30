import { useState } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import ThemeToggle from '../components/ThemeToggle.jsx';
import { homeFor } from '../lib/api.js';
import { useAuth } from '../auth/AuthContext.jsx';

export default function Login() {
    const { user, login } = useAuth();
    const navigate = useNavigate();

    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [error, setError] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    if (user) {
        return <Navigate to={homeFor(user)} replace />;
    }

    async function handleSubmit(event) {
        event.preventDefault();
        setError(null);
        setSubmitting(true);

        try {
            const signedIn = await login(username.trim(), password);
            navigate(homeFor(signedIn), { replace: true });
        } catch (err) {
            setError(
                err.status === 401
                    ? 'The username or password is incorrect.'
                    : err.message || 'Unable to sign in right now.',
            );
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="auth-shell">
            <aside className="auth-aside">
                <Link to="/" className="brand brand-inline">
                    <span className="brand-mark">SS</span>
                    <span className="brand-text">
                        <strong>Smart-Stock</strong>
                        <small>Intelligent Inventory</small>
                    </span>
                </Link>

                <div className="auth-aside-copy">
                    <h2>Every unit accounted for.</h2>
                    <p>
                        Sign in to receive deliveries, run the counter, or review what the store needs next.
                    </p>
                </div>

                <ul className="auth-points">
                    <li>Admin and cashier see the right surface for their role</li>
                    <li>Live stock levels, no spreadsheet sync</li>
                    <li>Forecast suggestions built from real sales</li>
                </ul>
            </aside>

            <main className="auth-main">
                <div className="auth-card">
                    <div className="auth-card-head">
                        <header>
                            <span className="eyebrow">Welcome back</span>
                            <h1>Sign in</h1>
                            <p>Use the username (or email) assigned by the store admin.</p>
                        </header>
                        <ThemeToggle />
                    </div>

                    <form onSubmit={handleSubmit} className="form" noValidate>
                        <label className="field">
                            <span>Username or email</span>
                            <input
                                type="text"
                                name="username"
                                autoComplete="username"
                                autoFocus
                                value={username}
                                onChange={(event) => setUsername(event.target.value)}
                                placeholder="e.g. testadmin or testadmin@gmail.com"
                                required
                            />
                        </label>

                        <label className="field">
                            <span>Password</span>
                            <div className="input-with-action">
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    name="password"
                                    autoComplete="current-password"
                                    value={password}
                                    onChange={(event) => setPassword(event.target.value)}
                                    placeholder="••••••••"
                                    required
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

                        {error && (
                            <p className="form-error" role="alert">
                                {error}
                            </p>
                        )}

                        <button type="submit" className="btn btn-primary btn-block btn-lg" disabled={submitting}>
                            {submitting ? 'Signing in…' : 'Sign in'}
                        </button>
                    </form>

                    <p className="auth-footnote">
                        Accounts are created by an admin. No self-registration.
                    </p>
                </div>
            </main>
        </div>
    );
}
