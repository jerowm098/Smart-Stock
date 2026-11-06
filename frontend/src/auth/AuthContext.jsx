import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api } from '../lib/api.js';

const AuthContext = createContext(null);

/**
 * Last known signed-in user (shape: id / name / username / email / role — no
 * tokens). Read synchronously on first render so the landing page paints the
 * correct CTA immediately instead of waiting for the /api/auth/user round trip.
 * Display-only: every protected route still re-validates against the backend.
 */
const STORAGE_KEY = 'smartstock.auth.user';

function readStoredUser() {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

export function AuthProvider({ children }) {
    const [user, setUser] = useState(readStoredUser);
    const [loading, setLoading] = useState(true);

    const refresh = useCallback(async () => {
        try {
            const data = await api('/api/auth/user');
            setUser(data.user);
        } catch {
            setUser(null);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        refresh();
    }, [refresh]);

    /* Mirror the resolved user into storage so the next load starts informed. */
    useEffect(() => {
        try {
            if (user) {
                window.localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
            } else {
                window.localStorage.removeItem(STORAGE_KEY);
            }
        } catch {
            /* Private mode / storage disabled — the cache is only an optimisation. */
        }
    }, [user]);

    const login = useCallback(async (username, password) => {
        const data = await api('/api/auth/login', {
            method: 'POST',
            body: { username, password },
        });

        setUser(data.user);

        return data.user;
    }, []);

    const logout = useCallback(async () => {
        try {
            await api('/api/auth/logout', { method: 'POST' });
        } finally {
            setUser(null);
        }
    }, []);

    const value = useMemo(
        () => ({ user, loading, login, logout, refresh }),
        [user, loading, login, logout, refresh],
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth must be used inside <AuthProvider>.');
    }

    return context;
}
