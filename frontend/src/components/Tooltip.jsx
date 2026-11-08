import { useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

/**
 * Hover/focus tooltip for a button.
 *
 * `children` is the button content (usually an icon) and `label` is both the
 * accessible name and the text shown on hover. The bubble renders in a portal
 * on <body> so a table's overflow/scrolling wrapper can never clip it, and it
 * clamps itself inside the viewport like InfoTip does.
 */
export default function Tooltip({ label, children, className = '', onClick, disabled = false }) {
    const triggerRef = useRef(null);
    const tipRef = useRef(null);
    const [open, setOpen] = useState(false);
    const [coords, setCoords] = useState({ top: -9999, left: -9999 });

    useLayoutEffect(() => {
        if (!open) {
            return;
        }

        const trigger = triggerRef.current;
        const tip = tipRef.current;
        if (!trigger || !tip) {
            return;
        }

        const t = trigger.getBoundingClientRect();
        const tipRect = tip.getBoundingClientRect();
        const margin = 8;

        // Horizontal: centre on the trigger, then clamp within the viewport.
        let left = t.left + t.width / 2 - tipRect.width / 2;
        left = Math.max(margin, Math.min(left, window.innerWidth - tipRect.width - margin));

        // Vertical: prefer above; flip below when there isn't room.
        let top = t.top - tipRect.height - 8;
        if (top < margin) {
            top = t.bottom + 8;
        }

        setCoords({ top, left });
    }, [open, label]);

    return (
        <>
            <button
                ref={triggerRef}
                type="button"
                className={className}
                onClick={onClick}
                disabled={disabled}
                aria-label={label}
                onMouseEnter={() => setOpen(true)}
                onMouseLeave={() => setOpen(false)}
                onFocus={() => setOpen(true)}
                onBlur={() => setOpen(false)}
            >
                {children}
            </button>
            {open && !disabled &&
                createPortal(
                    <div ref={tipRef} className="info-tip" role="tooltip" style={{ top: coords.top, left: coords.left }}>
                        {label}
                    </div>,
                    document.body,
                )}
        </>
    );
}
