import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext.jsx';
import { titleCaseName } from '../lib/format.js';

/**
 * Topbar account menu — replaces the old sidebar-footer user box.
 *
 * Trigger pill (avatar + name + chevron) on the right side of the topbar,
 * next to the theme toggle. Opens a dropdown with the account identity
 * (name, @username, role) and a Sign out action. Same open/close behaviour
 * as the notification bell: outside click and Escape close it.
 */
export default function UserMenu() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);
    const [busy, setBusy] = useState(false);
    const wrapRef = useRef(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onDown = (event) => {
            if (wrapRef.current && !wrapRef.current.contains(event.target)) {
                setOpen(false);
            }
        };

        const onKey = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    async function handleSignOut() {
        if (busy) {
            return;
        }

        setBusy(true);

        try {
            await logout();
            navigate('/login', { replace: true });
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="user-menu" ref={wrapRef}>
            <button
                type="button"
                className={`user-menu-btn${open ? ' is-open' : ''}`}
                onClick={() => setOpen((value) => !value)}
                aria-haspopup="menu"
                aria-expanded={open}
                aria-label="Account menu"
            >
                <span className="avatar">{(user?.name ?? '?').charAt(0).toUpperCase()}</span>
                <span className="user-menu-who">
                    <strong>{titleCaseName(user?.name)}</strong>
                    <small>{user?.email}</small>
                </span>
            </button>

            {open && (
                <div className="user-menu-panel" role="menu" aria-label="Account">
                    <div className="user-menu-id">
                        <span className="avatar">{(user?.name ?? '?').charAt(0).toUpperCase()}</span>
                        <div className="user-menu-id-text">
                            <strong>{titleCaseName(user?.name)}</strong>
                            <small>@{user?.username}</small>
                        </div>
                        <span className={`pill ${user?.role === 'admin' ? 'pill-warn' : 'pill-neutral'}`}>
                            {user?.role}
                        </span>
                    </div>

                    <div className="user-menu-sep" aria-hidden="true" />

                    <button
                        type="button"
                        className="user-menu-item user-menu-signout"
                        role="menuitem"
                        disabled={busy}
                        onClick={handleSignOut}
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" />
                            <path d="M16 17l5-5-5-5M21 12H9" />
                        </svg>
                        <span>{busy ? 'Signing out…' : 'Sign out'}</span>
                    </button>
                </div>
            )}
        </div>
    );
}
