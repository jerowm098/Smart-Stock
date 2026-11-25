import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';

const PageLoadbarContext = createContext(null);

// Match the exact CSS animation cycle (750ms) so the bar ALWAYS completes a
// full 0% -> 100% sweep off the right edge instead of vanishing abruptly
// mid-screen when a fast local XAMPP request finishes.
const SWEEP_CYCLE_MS = 750;

/**
 * Single global sliding load bar pinned to the top edge of the browser viewport.
 * Uses `runId` as the React key so every click, navigation, or reload mounts a
 * fresh animation starting cleanly at 0% (translateX(-100%)).
 */
export function PageLoadbarProvider({ children }) {
    const [count, setCount] = useState(0);
    const [runId, setRunId] = useState(0);
    const [visible, setVisible] = useState(false);
    const visibleRef = useRef(false);
    const startedAtRef = useRef(0);
    const hideTimerRef = useRef(null);

    const increment = useCallback(() => {
        clearTimeout(hideTimerRef.current);
        if (!visibleRef.current) {
            startedAtRef.current = Date.now();
            setRunId((id) => id + 1);
            visibleRef.current = true;
            setVisible(true);
        }
        setCount((n) => n + 1);
    }, []);

    const decrement = useCallback(() => {
        setCount((n) => Math.max(0, n - 1));
    }, []);

    useEffect(() => {
        if (count > 0) {
            clearTimeout(hideTimerRef.current);
            visibleRef.current = true;
            setVisible(true);
            return undefined;
        }

        if (!visible) return undefined;

        const elapsed = Date.now() - startedAtRef.current;
        const remainder = SWEEP_CYCLE_MS - (elapsed % SWEEP_CYCLE_MS);
        const remaining = remainder < 50 ? 0 : remainder;
        hideTimerRef.current = setTimeout(() => {
            visibleRef.current = false;
            setVisible(false);
        }, remaining);

        return () => clearTimeout(hideTimerRef.current);
    }, [count, visible]);

    return (
        <PageLoadbarContext.Provider value={{ increment, decrement }}>
            {visible && <div key={runId} className="page-loadbar is-loading" aria-hidden="true" />}
            {children}
        </PageLoadbarContext.Provider>
    );
}

/**
 * Bind a page's `loading` boolean to the single browser-top-edge loadbar.
 * Automatically cleans up on unmount so navigating mid-request never leaves
 * the bar stuck running.
 */
export function usePageLoadbar(loading) {
    const ctx = useContext(PageLoadbarContext);

    useEffect(() => {
        if (!ctx || !loading) return undefined;
        ctx.increment();
        return () => ctx.decrement();
    }, [ctx, loading]);
}
