import { useEffect, useState } from 'react';

const QUERY = '(max-width: 767px)';

/**
 * true lorsque la largeur de fenêtre est < 768px (breakpoints Tailwind md).
 */
export function useIsMobile() {
    const [isMobile, setIsMobile] = useState(() => {
        if (typeof window === 'undefined') return false;
        return window.matchMedia(QUERY).matches;
    });

    useEffect(() => {
        const mq = window.matchMedia(QUERY);
        const onChange = () => setIsMobile(mq.matches);
        onChange();
        mq.addEventListener('change', onChange);
        return () => mq.removeEventListener('change', onChange);
    }, []);

    return isMobile;
}
