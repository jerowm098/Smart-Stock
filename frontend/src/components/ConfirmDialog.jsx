import Modal from './Modal.jsx';

/**
 * Reusable confirmation dialog for destructive or significant actions.
 *
 * Renders a bare modal (no title header / underline) with a centered tone icon,
 * a heading, supporting text, and Cancel / Confirm actions. Themed via the
 * `tone` prop so every page shares the same look.
 *
 * Usage:
 *   <ConfirmDialog
 *       tone="danger"
 *       title={<>Deactivate <strong>{product.name}</strong>?</>}
 *       description="Its history is preserved."
 *       confirmLabel="Deactivate"
 *       loading={saving}
 *       error={err}
 *       onConfirm={handleConfirm}
 *       onCancel={() => setTarget(null)}
 *   />
 */

const TONE_ICONS = {
    danger: (
        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z M12 9v4 M12 17h.01" />
    ),
    warning: (
        <>
            <path d="M12 8v5" />
            <path d="M12 16h.01" />
            <circle cx="12" cy="12" r="9" />
        </>
    ),
    info: (
        <>
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v5" />
            <path d="M12 8h.01" />
        </>
    ),
};

const TONE_LABELS = {
    danger: 'Confirm destructive action',
    warning: 'Confirm action',
    info: 'Confirm action',
};

export default function ConfirmDialog({
    tone = 'danger',
    title,
    description,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    loadingLabel,
    loading = false,
    error = null,
    onConfirm,
    onCancel,
}) {
    const icon = TONE_ICONS[tone] ?? TONE_ICONS.danger;

    return (
        <Modal bare className="modal-confirm" onClose={onCancel} loading={loading} aria-label={TONE_LABELS[tone]}>
            <form
                className="form"
                onSubmit={(event) => {
                    event.preventDefault();
                    onConfirm?.();
                }}
            >
                <div className={`confirm-dialog confirm-dialog-${tone}`}>
                    <span className="confirm-dialog-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            {icon}
                        </svg>
                    </span>
                    <div className="confirm-dialog-body">
                        {title && <p className="confirm-dialog-title">{title}</p>}
                        {description && <p className="muted confirm-dialog-text">{description}</p>}
                    </div>
                </div>

                {error && <div className="alert alert-error" role="alert"><span>{error}</span></div>}

                <div className="modal-actions">
                    <button type="button" className="btn btn-ghost" onClick={onCancel} disabled={loading}>
                        {cancelLabel}
                    </button>
                    <button type="submit" className={`btn btn-${tone}`} disabled={loading}>
                        {loading ? (loadingLabel ?? 'Working…') : confirmLabel}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
