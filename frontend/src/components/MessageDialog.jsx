import Modal from './Modal.jsx';

/**
 * Reusable info / error message dialog.
 *
 * A plain, centered message popup (bare modal: icon + title + optional
 * description, then a short list of message lines and one Close button). No
 * colored alert box or badge styling — the message lines are plain text so the
 * dialog reads the same whether it reports an error or informational content.
 *
 * Usage:
 *   <MessageDialog
 *       tone="danger"
 *       title="Couldn't create account"
 *       description="Please fix the following and try again."
 *       messages={validationMessages}
 *       onClose={() => setOpen(false)}
 *   />
 */

const TONE_ICONS = {
    danger: (
        <>
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
            <path d="M12 9v4" />
            <path d="M12 17h.01" />
        </>
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
    success: (
        <>
            <circle cx="12" cy="12" r="9" />
            <path d="m8.5 12 2.5 2.5 4.5-5" />
        </>
    ),
};

const TONE_LABELS = {
    danger: 'Error',
    warning: 'Warning',
    info: 'Information',
    success: 'Success',
};

export default function MessageDialog({
    tone = 'info',
    title,
    description,
    messages = [],
    closeLabel = 'Close',
    onClose,
}) {
    const icon = TONE_ICONS[tone] ?? TONE_ICONS.info;
    // One continuous paragraph: the lead-in sentence plus every message line, so
    // the dialog never splits into a separate block/list of "error-looking" text.
    const body = [description, ...messages].filter(Boolean).join(' ');

    return (
        <Modal bare className="modal-confirm" onClose={onClose} aria-label={title ?? TONE_LABELS[tone]}>
            <div className="message-dialog">
                <div className={`confirm-dialog confirm-dialog-${tone}`}>
                    <span className="confirm-dialog-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            {icon}
                        </svg>
                    </span>
                    <div className="confirm-dialog-body">
                        {title && <p className="confirm-dialog-title">{title}</p>}
                        {body && <p className="muted confirm-dialog-text">{body}</p>}
                    </div>
                </div>

                <div className="modal-actions modal-actions-center">
                    <button type="button" className="btn btn-primary" onClick={onClose}>
                        {closeLabel}
                    </button>
                </div>
            </div>
        </Modal>
    );
}
