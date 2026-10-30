import { useEffect } from 'react';
import InfoTip from './InfoTip.jsx';

export default function Modal({ title, description, titleTip, ariaLabel, onClose, children, footer, wide = false, bare = false, noClose = false, className = '', loading = false }) {
    useEffect(() => {
        const onKey = (event) => {
            if (event.key === 'Escape') {
                onClose?.();
            }
        };

        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [onClose]);

    return (
        <div
            className="modal-overlay"
            onMouseDown={(event) => {
                if (event.target === event.currentTarget) {
                    onClose?.();
                }
            }}
        >
            <div className={`modal${wide ? ' modal-wide' : ''}${bare ? ' modal-bare' : ''}${className ? ` ${className}` : ''}`} role="dialog" aria-modal="true" aria-label={ariaLabel ?? title}>
                {bare ? (
                    !noClose && (
                        <button type="button" className="icon-btn modal-bare-close" onClick={onClose} aria-label="Close">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                                <path d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    )
                ) : (
                    <header className="modal-head">
                        <div className="modal-head-text">
                            <h3>
                                {title}
                                {titleTip && <InfoTip text={titleTip} />}
                            </h3>
                            {description && <p className="modal-desc">{description}</p>}
                        </div>
                        {!noClose && (
                            <button type="button" className="icon-btn" onClick={onClose} aria-label="Close">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                                    <path d="M6 6l12 12M18 6L6 18" />
                                </svg>
                            </button>
                        )}
                    </header>
                )}
                <div className="modal-body">{children}</div>
                {footer && <footer className="modal-foot">{footer}</footer>}
                <div className={`modal-loadbar${loading ? ' is-active' : ''}`} aria-hidden="true" />
            </div>
        </div>
    );
}
