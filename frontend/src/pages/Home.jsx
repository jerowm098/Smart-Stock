import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import ThemeToggle from '../components/ThemeToggle.jsx';
import { get, homeFor } from '../lib/api.js';
import { useAuth } from '../auth/AuthContext.jsx';

const STEPS = [
    { step: '01', title: 'Receive', body: 'Admin logs incoming stock on the Stock Inventory page.' },
    { step: '02', title: 'Sell', body: 'Cashier checks out from the live catalogue.' },
    { step: '03', title: 'Reorder', body: 'Order Suggestions show what to restock next.' },
];

/* Counters start at zero and count up to the live figures. */
const INITIAL_STATS = [
    { label: 'Stock received', value: 0, suffix: 'units', plus: true },
    { label: 'Suppliers', value: 0, suffix: 'partners', plus: true },
    { label: 'Best seller', value: 0, suffix: 'units sold', plus: true },
];

/* Animated counter — eases a number up from 0 whenever the value changes. */
function useCountUp(target) {
    const [count, setCount] = useState(0);

    useEffect(() => {
        if (typeof target !== 'number' || Number.isNaN(target)) return undefined;

        let frame;
        const start = performance.now();
        const duration = 900;

        const tick = (now) => {
            const progress = Math.min(Math.max((now - start) / duration, 0), 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            setCount(Math.round(target * eased));
            if (progress < 1) frame = requestAnimationFrame(tick);
        };

        frame = requestAnimationFrame(tick);

        return () => cancelAnimationFrame(frame);
    }, [target]);

    return typeof target === 'number' ? count : 0;
}

function HeroStat({ label, value, suffix, plus, title }) {
    const count = useCountUp(value);
    // Always reserve room for 6 characters; a wider final value only grows once.
    const finalWidth = `${value.toLocaleString()}${plus ? '+' : ''}`.length;

    return (
        <div className="hero-stat">
            <span className="hero-stat-label">{label}</span>
            <span className="hero-stat-number" title={title}>
                <span
                    className="hero-stat-count"
                    style={{ minWidth: `${Math.max(6, finalWidth)}ch` }}
                >
                    {count.toLocaleString()}
                    {plus && <span className="hero-stat-plus">+</span>}
                </span>
            </span>
            {suffix && <span className="hero-stat-suffix">{suffix}</span>}
        </div>
    );
}

export default function Home() {
    const { user } = useAuth();
    const primaryTo = user ? homeFor(user) : '/login';
    const primaryLabel = user ? 'Open dashboard' : 'Sign in to Smart-Stock';
    const [summary, setSummary] = useState(null);

    // The landing page scrolls the document viewport itself (no .content-scroll
    // wrapper like the admin area) — flag the root element so CSS can give the
    // viewport the same custom scrollbar the admin dashboard uses.
    useEffect(() => {
        document.documentElement.classList.add('landing-page');
        return () => document.documentElement.classList.remove('landing-page');
    }, []);

    useEffect(() => {
        let active = true;

        const load = () => {
            get('/api/public/stats')
                .then((data) => {
                    if (active) setSummary(data);
                })
                .catch(() => {
                    // Keep whatever counters are already rendered.
                });
        };

        load();

        // Keep the counters live while the visitor stays on the page.
        const timer = setInterval(load, 60_000);

        return () => {
            active = false;
            clearInterval(timer);
        };
    }, []);

    const topProduct = summary?.top_product;

    const heroStats = summary ? [
        {
            label: 'Stock received',
            value: summary.stock_received_units ?? 0,
            suffix: 'units',
            plus: true,
        },
        {
            label: 'Suppliers',
            value: summary.supplier_count ?? 0,
            suffix: 'partners',
            plus: true,
        },
        {
            label: topProduct?.name ? `Best seller · ${topProduct.name}` : 'Best seller',
            value: topProduct?.total_qty ?? 0,
            suffix: 'units sold',
            plus: true,
            title: topProduct?.name,
        },
    ] : INITIAL_STATS;

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
                    <a href="#top">Home</a>
                    <a href="#how">How it works</a>
                </nav>

                <div className="landing-nav-actions">
                    <ThemeToggle />

                    {/* Visible from the first paint — the cached auth user makes
                        the label correct right away, so it no longer waits on
                        the session check (that wait read as a "delayed" button). */}
                    <Link to={primaryTo} className="btn btn-primary">
                        {primaryLabel}
                    </Link>
                </div>
            </header>

            {/* Everything below the pinned header scrolls inside its own
                container, so the custom scrollbar starts at the header's
                bottom edge — same idea as the admin .content-scroll. */}
            <div className="landing-scroll">

            <section id="top" className="hero">
                <div className="hero-copy">
                    <h1>
                        Stock your Hardware in <span className="accent">Smart way.</span>
                    </h1>
                    <p>
                        Smart-Stock brings receiving, checkout and demand forecasting together in one shared
                        inventory for your hardware store, so you always know what's on the shelf and what
                        to restock next.
                    </p>
                    <div className="hero-actions">
                        <Link to={primaryTo} className="btn btn-primary btn-lg">
                            {primaryLabel}
                        </Link>
                    </div>
                    <div className="hero-stats">
                        {heroStats.map((stat) => (
                            <HeroStat key={stat.label} {...stat} />
                        ))}
                    </div>
                </div>
            </section>

            <section id="how" className="section">
                <h2>The full inventory cycle</h2>
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
                <span>© {new Date().getFullYear()} Smart-Stock · Intelligent Inventory System. All rights reserved.</span>
            </footer>

            </div>
        </div>
    );
}
