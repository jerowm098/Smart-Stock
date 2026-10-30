import Modal from './Modal.jsx';

/**
 * Informational dialog that reports a completed action.
 *
 * Mirrors the ConfirmDialog layout (bare modal, centered tone icon, heading,
 * supporting text) but has a single primary action instead of Cancel/Confirm,
 * since there is nothing to decide — the action already happened.
 *
 * Usage:
 *   <SuccessDialog
 *       title="Backup created successfully"
 *       description="A new database dump has been saved to the server."
 *       onClose={() => setSuccess(null)}
 *   />
 */

export default function SuccessDialog({ title, description, actionLabel = 'Got it', onClose }) {
    return (
        <Modal bare className="modal-confirm" onClose={onClose} aria-label={title}>
            <div className="confirm-dialog confirm-dialog-success">
                <span className="confirm-dialog-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                </span>
                <div className="confirm-dialog-body">
                    {title && <p className="confirm-dialog-title">{title}</p>}
                    {description && <p className="muted confirm-dialog-text">{description}</p>}
                </div>
            </div>

            <div className="modal-actions">
                <button type="button" className="btn btn-primary" onClick={onClose} autoFocus>
                    {actionLabel}
                </button>
            </div>
        </Modal>
    );
}
