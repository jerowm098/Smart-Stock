import { useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

/**
 * Accessible info tooltip.
 *
 * Renders the trigger as a small "i" badge and shows the `text` in a portal
 * attached to <body>. Because the tooltip lives outside the modal, it never
 * stretches the modal's scroll area (no horizontal scrollbar), and it clamps
 * itself to the viewport edges — flipping above/below and shifting sideways so
 * it never gets clipped when the trigger sits near a corner.
 */
export default function InfoTip({ text, children = 'i' }) {
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
    }, [open, text]);

    return (
        <>
            <span
                ref={triggerRef}
                className="field-info"
                tabIndex={0}
                aria-label={typeof text === 'string' ? text : undefined}
                onMouseEnter={() => setOpen(true)}
                onMouseLeave={() => setOpen(false)}
                onFocus={() => setOpen(true)}
                onBlur={() => setOpen(false)}
            >
                {children}
            </span>
            {open &&
                createPortal(
                    <div ref={tipRef} className="info-tip" role="tooltip" style={{ top: coords.top, left: coords.left }}>
                        {text}
                    </div>,
                    document.body,
                )}
        </>
    );
}
