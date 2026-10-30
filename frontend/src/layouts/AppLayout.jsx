import { useEffect, useState } from 'react';
import { Link, NavLink, Outlet, useLocation } from 'react-router-dom';
import NotificationBell from '../components/NotificationBell.jsx';
import ThemeToggle from '../components/ThemeToggle.jsx';
import UserMenu from '../components/UserMenu.jsx';
import { useAuth } from '../auth/AuthContext.jsx';

/**
 * Sidebar navigation, grouped by purpose (order per the team's Jira board +
 * Confluence):
 *
 *   - Jira board 34 build order: Inventory Catalogue (SS-17, Sprint 2)
 *     came before Stock-In/Receiving (SS-40, Sprint 3).
 *   - Confluence "Backlog Refinement Summary" prioritised the catalogue
 *     (SS-17/SS-18) ahead of receiving (SS-40) for Sprint 2.
 *   - Confluence System Architecture (2026-09-11) lists: Product
 *     Management -> Inventory -> Stock-In -> Stock-Out (POS) -> Low Stock
 *     -> Supplier -> Reports, with User Management as admin tooling.
 *
 * Each entry is a labelled section; `label` is optional (a section with no
 * label renders as a plain list, e.g. the cashier's single POS link).
 */
const ADMIN_NAV = [
    {
        label: 'Management',
        items: [
            { to: '/stock-in', label: 'Stock Inventory', icon: 'inbox' },
            { to: '/products', label: 'Products', icon: 'box' },
            { to: '/checkout', label: 'POS Checkout', icon: 'cart' },
        ],
    },
    {
        label: 'Records',
        items: [
            { to: '/transactions', label: 'Transactions', icon: 'receipt' },
        ],
    },
    {
        label: 'Planning',
        items: [
            { to: '/order-suggestions', label: 'Order Suggestions', icon: 'clipboard' },
        ],
    },
    {
        label: 'Partners',
        items: [
            { to: '/suppliers', label: 'Suppliers', icon: 'truck' },
        ],
    },
    {
        label: 'Accounts',
        items: [
            { to: '/users', label: 'Users', icon: 'users' },
        ],
    },
    {
        label: 'System',
        items: [
            { to: '/backups', label: 'Backups', icon: 'database' },
        ],
    },
];

const CASHIER_NAV = [{ items: [{ to: '/pos', label: 'Point of Sale', icon: 'cart' }] }];

function Icon({ name }) {
    const paths = {
        box: 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V7.5m0 0L4.5 4.5m7.5 3l7.5-3',
        inbox: 'M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z',
        receipt: 'M9 14.25l3 3m0 0l3-3m-3 3v-8.25m-6.75 9.75h13.5a2.25 2.25 0 002.25-2.25V3.75l-3 1.5-3-1.5-3 1.5-3-1.5-3 1.5v15a2.25 2.25 0 002.25 2.25z',
        clipboard: 'M9 12h6m-6 3.75h6M9 4.5h6M6.75 6.75h10.5a1.5 1.5 0 011.5 1.5v10.5a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5z',
        truck: 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
        users: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        database: 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125',
        cart: 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z',
    };

    return (
        <svg className="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d={paths[name] ?? paths.box} />
        </svg>
    );
}

export default function AppLayout() {
    const { user } = useAuth();
    const location = useLocation();
    const [menuOpen, setMenuOpen] = useState(false);

    const nav = user?.role === 'admin' ? ADMIN_NAV : CASHIER_NAV;
    const navItems = nav.flatMap((section) => section.items);
    const current = navItems.find((item) => location.pathname.startsWith(item.to));
    const title = current?.label ?? 'Smart-Stock';

    // Close the mobile drawer whenever the route changes.
    useEffect(() => {
        setMenuOpen(false);
    }, [location.pathname]);

    return (
        <div className="app-shell">
            {menuOpen && <button type="button" className="sidebar-backdrop" aria-label="Close menu" onClick={() => setMenuOpen(false)} />}

            <aside className={`sidebar${menuOpen ? ' is-open' : ''}`}>
                <div className="brand">
                    <span className="brand-mark">SS</span>
                    <span className="brand-text">
                        <strong>Smart-Stock</strong>
                        <small>Intelligent Inventory</small>
                    </span>
                </div>

                <div className="role-chip">{user?.role === 'admin' ? 'Administrator' : 'Cashier'}</div>

                <nav className="nav">
                    {nav.map((section, index) => (
                        <div className="nav-group" key={section.label ?? index}>
                            {section.label && <p className="nav-group-label">{section.label}</p>}
                            {section.items.map((item) => (
                                <NavLink
                                    key={item.to}
                                    to={item.to}
                                    className={({ isActive }) => `nav-link${isActive ? ' is-active' : ''}`}
                                >
                                    <Icon name={item.icon} />
                                    <span>{item.label}</span>
                                </NavLink>
                            ))}
                        </div>
                    ))}
                </nav>
            </aside>

            <div className="main">
                <header className="topbar">
                    <button type="button" className="menu-btn" onClick={() => setMenuOpen((open) => !open)} aria-label="Toggle menu">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                            <path d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                    {/* Breadcrumb sa topbar (Home / <Page>) — pumapalit sa lumang topbar-title. */}
                    <nav className="topbar-breadcrumb" aria-label="Breadcrumb">
                        <ol className="topbar-breadcrumb-list">
                            <li className="topbar-breadcrumb-item">
                                <Link to="/">Home</Link>
                            </li>
                            <li className="topbar-breadcrumb-item">
                                <span className="topbar-breadcrumb-separator">/</span>
                            </li>
                            <li className="topbar-breadcrumb-item">
                                <span className="topbar-breadcrumb-current">{title}</span>
                            </li>
                        </ol>
                    </nav>
                    <div className="topbar-actions">
                        {user?.role === 'admin' && <NotificationBell />}
                        <ThemeToggle />
                        <UserMenu />
                    </div>
                </header>

                <div className="content-scroll">
                    <main className="content">
                        <Outlet />
                    </main>
                </div>
            </div>
        </div>
    );
}
