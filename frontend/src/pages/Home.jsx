import { Link } from 'react-router-dom';
import ThemeToggle from '../components/ThemeToggle.jsx';
import { homeFor } from '../lib/api.js';
import { useAuth } from '../auth/AuthContext.jsx';

const FEATURES = [
    {
        title: 'One shared catalogue',
        body: 'Admin and cashier work on the same stock rows, so a sale on the counter is reflected everywhere instantly.',
    },
    {
        title: 'Forecast-driven restocking',
        body: 'Thirty days of sales history turns into order suggestions before items run dry.',
    },
    {
        title: 'Audit-ready history',
        body: 'Every sale, stock-in and adjustment keeps the trail needed for reconciliation and reporting.',
    },
];

const STEPS = [
    { step: '01', title: 'Receive', body: 'Admin logs incoming stock on the Stock Inventory page.' },
    { step: '02', title: 'Sell', body: 'Cashier checks out from the live catalogue.' },
    { step: '03', title: 'Reorder', body: 'Order Suggestions show what to restock next.' },
];

export default function Home() {
    const { user, loading } = useAuth();
    const primaryTo = user ? homeFor(user) : '/login';
    const primaryLabel = user ? 'Open dashboard' : 'Sign in to Smart-Stock';

    return (
        <div className="landing">
            <header className="landing-nav">
                <Link to="/" className="brand brand-inline">
                    <span className="brand-mark">SS</span>
                    <span className="brand-text">
                        <strong>Smart-Stock</strong>
                        <small>Intelligent Inventory</small>
                    </span>
                </Link>

                <nav className="landing-links">
                    <a href="#features">Features</a>
                    <a href="#how">How it works</a>
                </nav>

                <div className="landing-nav-actions">
                    <ThemeToggle />

                    {/* Always reserved (never unmounted) so the nav doesn't reflow
                        when the auth check resolves on reload — just hidden while loading. */}
                    <Link
                        to={primaryTo}
                        className="btn btn-primary"
                        aria-hidden={loading}
                        style={loading ? { visibility: 'hidden' } : undefined}
                    >
                        {primaryLabel}
                    </Link>
                </div>
            </header>

            <section className="hero">
                <div className="hero-glow" aria-hidden="true" />
                <div className="hero-copy">
                    <span className="eyebrow">Hardware store inventory · Sales · Forecasting</span>
                    <h1>
                        Stock that stays <span className="accent">ahead of demand.</span>
                    </h1>
                    <p>
                        Smart-Stock connects receiving, the checkout counter and demand forecasting into one
                        shared inventory — so the shelf is never a surprise.
                    </p>
                    <div className="hero-actions">
                        <Link to={primaryTo} className="btn btn-primary btn-lg">
                            {primaryLabel}
                        </Link>
                        <a href="#features" className="btn btn-ghost btn-lg">
                            See what it does
                        </a>
                    </div>
                    <dl className="hero-stats">
                        <div>
                            <dt>Shared catalogue</dt>
                            <dd>1 source of truth</dd>
                        </div>
                        <div>
                            <dt>Forecast window</dt>
                            <dd>30 days</dd>
                        </div>
                        <div>
                            <dt>Audit trail</dt>
                            <dd>Every movement</dd>
                        </div>
                    </dl>
                </div>

                <div className="hero-panel" aria-hidden="true">
                    <div className="panel-head">
                        <span className="dot dot-red" />
                        <span className="dot dot-amber" />
                        <span className="dot dot-green" />
                        <span className="panel-title">Order suggestions</span>
                    </div>
                    <div className="panel-row">
                        <span>Cordless Drill 18V</span>
                        <span className="pill pill-warn">Reorder 12</span>
                    </div>
                    <div className="panel-row">
                        <span>Hammer Claw 16oz</span>
                        <span className="pill pill-danger">Out of stock</span>
                    </div>
                    <div className="panel-row">
                        <span>Screwdriver Set 8-in-1</span>
                        <span className="pill pill-ok">Healthy</span>
                    </div>
                    <div className="panel-foot">
                        <span>Next delivery</span>
                        <strong>+36 units</strong>
                    </div>
                </div>
            </section>

            <section id="features" className="section">
                <h2>Built for the counter and the stockroom</h2>
                <div className="feature-grid">
                    {FEATURES.map((feature) => (
                        <article key={feature.title} className="card feature-card">
                            <h3>{feature.title}</h3>
                            <p>{feature.body}</p>
                        </article>
                    ))}
                </div>
            </section>

            <section id="how" className="section">
                <h2>One loop, three steps</h2>
                <div className="step-grid">
                    {STEPS.map((item) => (
                        <article key={item.step} className="card step-card">
                            <span className="step-number">{item.step}</span>
                            <h3>{item.title}</h3>
                            <p>{item.body}</p>
                        </article>
                    ))}
                </div>
            </section>

            <footer className="landing-footer">
                <span>Smart-Stock · Intelligent Inventory System</span>
                <Link to={primaryTo}>{primaryLabel}</Link>
            </footer>
        </div>
    );
}
