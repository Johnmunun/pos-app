import React, { useCallback, useEffect, useRef } from 'react';

const INTERACTIVE =
    'a, button, input, select, textarea, label, [role="button"], [data-no-grab-scroll]';

/**
 * Zone scrollable horizontalement : molette verticale translate en horizontal
 * quand le contenu déborde, et clic maintenu + glisser pour faire défiler.
 * Les clics sur liens / boutons / champs ne déclenchent pas le drag.
 */
export default function GrabScroll({ children, className = '' }) {
    const ref = useRef(null);

    useEffect(() => {
        const el = ref.current;
        if (!el) return;

        const onWheel = (e) => {
            if (el.scrollWidth <= el.clientWidth) return;
            if (Math.abs(e.deltaX) >= Math.abs(e.deltaY)) return;
            el.scrollLeft += e.deltaY;
            e.preventDefault();
        };

        el.addEventListener('wheel', onWheel, { passive: false });
        return () => el.removeEventListener('wheel', onWheel);
    }, []);

    const onMouseDown = useCallback((e) => {
        if (e.button !== 0) return;
        if (e.target.closest(INTERACTIVE)) return;

        const el = ref.current;
        if (!el || el.scrollWidth <= el.clientWidth) return;

        const startX = e.clientX;
        const startScroll = el.scrollLeft;

        const onMove = (ev) => {
            el.scrollLeft = startScroll - (ev.clientX - startX);
        };
        const onUp = () => {
            window.removeEventListener('mousemove', onMove);
            window.removeEventListener('mouseup', onUp);
            el.classList.remove('cursor-grabbing', 'select-none');
        };

        el.classList.add('cursor-grabbing', 'select-none');
        window.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onUp);
    }, []);

    return (
        <div
            ref={ref}
            onMouseDown={onMouseDown}
            title="Défilement horizontal : clic maintenu + glisser, ou molette sur la zone"
            className={`min-w-0 cursor-grab overflow-x-auto ${className}`.trim()}
        >
            {children}
        </div>
    );
}
