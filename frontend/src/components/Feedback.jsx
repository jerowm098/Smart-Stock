import { useEffect, useRef } from 'react';

/** Inline status banner. Renders nothing when there is no message. */
export function Alert({ tone = 'info', children, onClose }) {
    if (!children) {
        return null;
    }

    return (
        <div className={`alert alert-${tone}`} role={tone === 'error' ? 'alert' : 'status'}>
            <span>{children}</span>
            {onClose && (
                <button type="button" className="alert-close" onClick={onClose} aria-label="Dismiss">
                    ✕
                </button>
            )}
        </div>
    );
}

/**
 * Floating toast notification, pinned to the bottom-right. Auto-dismisses after
 * `duration` ms and stays put on screen (never shifts page layout or scroll).
 * A progress bar along the bottom edge shrinks over the same `duration`, so the
 * visual countdown and the dismiss timer always stay in step.
 */
export function Toast({ tone = 'success', children, onClose, duration = 3200 }) {
    // Keep the latest close handler in a ref: a parent re-render should not
    // restart the countdown (inline arrow handlers change identity every time).
    const closeRef = useRef(onClose);
    closeRef.current = onClose;

    useEffect(() => {
        if (!children) {
            return undefined;
        }
        const timer = setTimeout(() => closeRef.current?.(), duration);
        return () => clearTimeout(timer);
    }, [children, duration]);

    if (!children) {
        return null;
    }

    return (
        <div className="toast-stack" aria-live="polite">
            <div
                className={`toast toast-${tone}`}
                role={tone === 'error' ? 'alert' : 'status'}
                style={{ '--toast-duration': `${duration}ms` }}
            >
                <span className="toast-icon" aria-hidden="true">{tone === 'error' ? '!' : '✓'}</span>
                <span className="toast-text">{children}</span>
                <button type="button" className="toast-close" onClick={onClose} aria-label="Dismiss">✕</button>
                <span className="toast-progress" aria-hidden="true" />
            </div>
        </div>
    );
}

export function FieldError({ messages }) {
    if (!messages?.length) {
        return null;
    }

    return (
        <ul className="field-errors">
            {messages.map((message, index) => (
                <li key={index}>{message}</li>
            ))}
        </ul>
    );
}

export default Alert;
