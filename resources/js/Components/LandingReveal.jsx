import { useEffect, useRef, useState } from 'react';
import clsx from 'clsx';

/**
 * Révélation légère au scroll (IntersectionObserver, une seule fois).
 * Performant : observer déconnecté après apparition.
 */
export default function LandingReveal({ children, className, delay = 0, as: Tag = 'div' }) {
    const ref = useRef(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const el = ref.current;
        if (!el) return undefined;

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry?.isIntersecting) {
                    setVisible(true);
                    observer.disconnect();
                }
            },
            { rootMargin: '0px 0px -6% 0px', threshold: 0.06 }
        );

        observer.observe(el);
        return () => observer.disconnect();
    }, []);

    return (
        <Tag
            ref={ref}
            className={clsx(
                'motion-safe:transition-all motion-safe:duration-700 motion-safe:ease-[cubic-bezier(0.22,1,0.36,1)]',
                visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-7',
                className
            )}
            style={delay ? { transitionDelay: `${delay}ms` } : undefined}
        >
            {children}
        </Tag>
    );
}
