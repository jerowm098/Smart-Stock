import { createContext, useContext, useEffect, useMemo, useState } from 'react';

/**
 * Global theme state (dark / light).
 *
 * - Dark ang default ("current theme").
 * - Ang pinili ng user ay naka-save sa localStorage, kaya nababalik ito
 *   sa susunod na visit.
 * - Ang aktwal na paglipat ng kulay ay ginagawa ng CSS: ang provider ay
 *   nagse-set lang ng data-theme sa <html>, at ang [data-theme='light']
 *   token overrides sa app.css ang bahala sa lahat ng kulay.
 *
 * Anti-flash: ang index.html ay may inline script na nagse-set na agad ng
 * data-theme bago pa ma-load ang React.
 */

const STORAGE_KEY = 'smart-stock-theme';

const ThemeContext = createContext(null);

function readSavedTheme() {
    try {
        const saved = window.localStorage.getItem(STORAGE_KEY);

        if (saved === 'light' || saved === 'dark') {
            return saved;
        }
    } catch {
        // Private mode / storage disabled — fall through to the default.
    }

    return 'dark';
}

export function ThemeProvider({ children }) {
    const [theme, setTheme] = useState(readSavedTheme);

    useEffect(() => {
        document.documentElement.dataset.theme = theme;

        try {
            window.localStorage.setItem(STORAGE_KEY, theme);
        } catch {
            // Ignore write failures; the toggle still works for this session.
        }
    }, [theme]);

    const value = useMemo(
        () => ({
            theme,
            setTheme,
            toggleTheme: () => setTheme((current) => (current === 'dark' ? 'light' : 'dark')),
        }),
        [theme],
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
    const context = useContext(ThemeContext);

    if (!context) {
        throw new Error('useTheme must be used inside <ThemeProvider>.');
    }

    return context;
}
