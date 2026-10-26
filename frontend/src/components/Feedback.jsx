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

export function Loading({ label = 'Loading…' }) {
    return (
        <p className="muted padded loading-line">
            <span className="spinner spinner-sm" aria-hidden="true" /> {label}
        </p>
    );
}

export default Alert;
