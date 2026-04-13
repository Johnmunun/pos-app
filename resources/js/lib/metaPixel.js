/**
 * Meta (Facebook) Pixel — partagé vitrine e-commerce et app Inertia principale.
 */
export function initFacebookPixel(pixelId) {
    if (window.fbq) {
        window.fbq('track', 'PageView');
        return;
    }
    const n = window.fbq || function fbqFallback() {
        n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
    };
    window.fbq = n;
    if (!window._fbq) window._fbq = n;
    n.push = n;
    n.loaded = true;
    n.version = '2.0';
    n.queue = [];
    const t = document.createElement('script');
    t.async = true;
    t.src = 'https://connect.facebook.net/en_US/fbevents.js';
    const s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(t, s);
    window.fbq('init', pixelId);
    window.fbq('track', 'PageView');
}

export function trackFacebookPageView() {
    try {
        if (window.fbq) window.fbq('track', 'PageView');
    } catch {
        /* ignore third-party */
    }
}
