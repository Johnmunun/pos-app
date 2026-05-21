import { useEffect, useMemo, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import {
    Bot,
    ChevronDown,
    ChevronUp,
    ExternalLink,
    Loader2,
    MessageCircle,
    Send,
    Sparkles,
    ThumbsDown,
    ThumbsUp,
    X,
} from 'lucide-react';
import { Link } from '@inertiajs/react';
import useStorefrontLinks from '@/hooks/useStorefrontLinks';
import { formatCurrency } from '@/lib/currency';

const STORAGE_PREFIX = 'storefront_ai_support_';

function storageKey(shopId) {
    return `${STORAGE_PREFIX}${shopId || 'default'}`;
}

function loadMessages(shopId) {
    try {
        const raw = sessionStorage.getItem(storageKey(shopId));
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed) || parsed.length === 0) return null;
        const valid = parsed.every(
            (m) =>
                m &&
                (m.role === 'user' || m.role === 'assistant') &&
                typeof m.content === 'string'
        );
        return valid ? parsed : null;
    } catch {
        return null;
    }
}

function saveMessages(shopId, messages) {
    try {
        sessionStorage.setItem(storageKey(shopId), JSON.stringify(messages));
    } catch {
        // ignore
    }
}

function buildWelcome(shopName, customWelcome) {
    if (customWelcome && String(customWelcome).trim()) {
        return String(customWelcome).trim();
    }
    const name = shopName || 'notre boutique';
    return `Bonjour ! Je suis l'assistant de ${name}. Je peux vous renseigner sur la livraison, les retours, la disponibilité des produits ou le suivi de commande. Comment puis-je vous aider ?`;
}

function defaultSuggestions(productName, semanticEnabled) {
    const base = [
        'Délais et frais de livraison',
        'Politique de retour',
        'Où en est ma commande ?',
    ];
    if (semanticEnabled) {
        base.unshift('Trouvez-moi un produit…');
    }
    if (productName) {
        base.push(`« ${productName} » est-il disponible ?`);
    } else if (!semanticEnabled) {
        base.push('Un produit est-il en stock ?');
    }
    return base;
}

function ProductSuggestions({ products, links }) {
    if (!products?.length) return null;

    return (
        <div className="mt-2 space-y-2 w-full max-w-[88%]">
            {products.map((p) => (
                <Link
                    key={p.id}
                    href={links.product(p.id)}
                    className="flex gap-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 p-2 hover:border-[var(--sf-primary)] transition shadow-sm"
                >
                    <div className="h-14 w-14 shrink-0 rounded-lg bg-slate-100 dark:bg-slate-800 overflow-hidden">
                        {p.image_url ? (
                            <img src={p.image_url} alt="" className="h-full w-full object-cover" />
                        ) : (
                            <div className="h-full w-full flex items-center justify-center text-[10px] text-slate-400">
                                —
                            </div>
                        )}
                    </div>
                    <div className="min-w-0 flex-1 text-left">
                        <p className="text-xs font-semibold text-slate-900 dark:text-white line-clamp-2">{p.name}</p>
                        <p className="text-xs font-bold mt-0.5" style={{ color: 'var(--sf-primary, #7c3aed)' }}>
                            {formatCurrency(p.price, p.currency)}
                        </p>
                        <p className="text-[10px] text-slate-500 mt-0.5">
                            {p.in_stock ? 'En stock' : 'Rupture de stock'}
                        </p>
                    </div>
                </Link>
            ))}
        </div>
    );
}

function FeedbackButtons({ logId, feedbackPath, onDone }) {
    const [sent, setSent] = useState(false);
    const [loading, setLoading] = useState(false);

    if (!logId || sent) {
        return sent ? (
            <p className="text-[10px] text-slate-400 mt-1">Merci pour votre retour.</p>
        ) : null;
    }

    const send = async (value) => {
        setLoading(true);
        try {
            await axios.post(feedbackPath, { log_id: logId, feedback: value });
            setSent(true);
            onDone?.();
        } catch {
            // silent
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="flex items-center gap-2 mt-1.5">
            <span className="text-[10px] text-slate-400">Utile ?</span>
            <button
                type="button"
                disabled={loading}
                onClick={() => send('helpful')}
                className="p-1 rounded-md text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 disabled:opacity-50"
                aria-label="Réponse utile"
            >
                <ThumbsUp className="h-3.5 w-3.5" />
            </button>
            <button
                type="button"
                disabled={loading}
                onClick={() => send('not_helpful')}
                className="p-1 rounded-md text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 disabled:opacity-50"
                aria-label="Réponse pas utile"
            >
                <ThumbsDown className="h-3.5 w-3.5" />
            </button>
        </div>
    );
}

export default function AISupportFloatingWidget() {
    const { props } = usePage();
    const shop = props?.shop;
    const product = props?.product;
    const cfg = props?.storefrontClient?.aiSupport || {};
    const links = useStorefrontLinks();
    const shopId = shop?.id;

    const hasNewKeys =
        Object.prototype.hasOwnProperty.call(cfg, 'shopEnabled') ||
        Object.prototype.hasOwnProperty.call(cfg, 'planEnabled');
    const shopEnabled = cfg?.shopEnabled === true;
    const legacyEnabled = cfg?.enabled === true;
    const showFab = hasNewKeys ? shopEnabled : legacyEnabled;
    const canAsk = hasNewKeys ? cfg?.canUse === true : legacyEnabled;
    const semanticEnabled = cfg?.semanticSearchEnabled === true;

    const askPath =
        cfg?.askPath ||
        (typeof window !== 'undefined' && window.location?.pathname?.startsWith('/ecommerce/storefront')
            ? '/ecommerce/storefront/support/ai/ask'
            : '/support/ai/ask');
    const feedbackPath =
        cfg?.feedbackPath ||
        (typeof window !== 'undefined' && window.location?.pathname?.startsWith('/ecommerce/storefront')
            ? '/ecommerce/storefront/support/ai/feedback'
            : '/support/ai/feedback');

    const whatsapp = props?.whatsapp || {};
    const whatsappEnabled = !!whatsapp?.enabled && !!whatsapp?.number;

    const shopName = shop?.name || 'Boutique';
    const welcomeText = buildWelcome(shopName, cfg?.welcomeMessage);
    const suggestions = useMemo(
        () => defaultSuggestions(product?.name, semanticEnabled),
        [product?.name, semanticEnabled]
    );

    const initialAssistant = useMemo(
        () => ({ role: 'assistant', content: welcomeText, id: 'welcome' }),
        [welcomeText]
    );

    const [open, setOpen] = useState(false);
    const [message, setMessage] = useState('');
    const [orderNumber, setOrderNumber] = useState('');
    const [email, setEmail] = useState('');
    const [showOrderFields, setShowOrderFields] = useState(false);
    const [loading, setLoading] = useState(false);
    const [messages, setMessages] = useState(() => {
        const stored = loadMessages(shopId);
        if (stored) return stored;
        return [initialAssistant];
    });

    const messagesEndRef = useRef(null);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, loading, open]);

    useEffect(() => {
        if (!loading && messages.length > 0) {
            saveMessages(shopId, messages);
        }
    }, [messages, loading, shopId]);

    useEffect(() => {
        const stored = loadMessages(shopId);
        if (!stored) {
            setMessages([initialAssistant]);
        }
    }, [shopId, initialAssistant]);

    if (!showFab) {
        return null;
    }

    const buildHistoryPayload = () =>
        messages
            .filter((m) => m.id !== 'welcome' && !m.products?.length)
            .slice(-6)
            .map((m) => ({ role: m.role, content: m.content }));

    const pageContext = product?.name
        ? { page_type: 'product', product_name: String(product.name), product_id: product.id ?? null }
        : { page_type: 'general' };

    const send = async (textOverride) => {
        if (!canAsk) return;
        const msg = String(textOverride ?? message ?? '').trim();
        if (!msg || loading) return;

        setLoading(true);
        setMessages((prev) => [...prev, { role: 'user', content: msg }]);
        setMessage('');

        try {
            const res = await axios.post(askPath, {
                message: msg,
                order_number: orderNumber || null,
                customer_email: email || null,
                history: buildHistoryPayload(),
                page_context: pageContext,
            });
            const answer = res.data?.answer || 'Je n’ai pas pu répondre pour le moment.';
            setMessages((prev) => [
                ...prev,
                {
                    role: 'assistant',
                    content: answer,
                    logId: res.data?.log_id || null,
                    products: res.data?.products || [],
                    topic: res.data?.topic || null,
                    showCatalogLink: !!res.data?.show_catalog_link,
                },
            ]);
        } catch (e) {
            const err = e.response?.data?.message || e.message || 'Erreur du support IA.';
            setMessages((prev) => [...prev, { role: 'assistant', content: err }]);
        } finally {
            setLoading(false);
        }
    };

    const openHumanSupport = () => {
        if (!whatsappEnabled) return;
        const number = String(whatsapp.number || '').replace(/[^\d]/g, '');
        if (!number) return;
        const transcript = messages
            .filter((m) => m.id !== 'welcome')
            .slice(-8)
            .map((m) => `${m.role === 'user' ? 'Client' : 'Assistant'}: ${m.content}`)
            .join('\n');
        const lastUser = [...messages].reverse().find((m) => m.role === 'user')?.content || '';
        const txt = encodeURIComponent(
            `Bonjour, j’ai besoin d’aide humaine (${shopName}).\n` +
                (lastUser ? `Dernière question: ${lastUser}\n` : '') +
                (orderNumber ? `Commande: ${orderNumber}\n` : '') +
                (email ? `Email: ${email}\n` : '') +
                (transcript ? `\n--- Conversation ---\n${transcript}` : '')
        );
        window.open(`https://wa.me/${number}?text=${txt}`, '_blank', 'noopener,noreferrer');
    };

    const clearChat = () => {
        setMessages([initialAssistant]);
        try {
            sessionStorage.removeItem(storageKey(shopId));
        } catch {
            // ignore
        }
    };

    const headerStyle = {
        background: 'linear-gradient(135deg, var(--sf-primary, #7c3aed) 0%, var(--sf-secondary, #5b21b6) 100%)',
    };

    const showSuggestions =
        canAsk && messages.filter((m) => m.role === 'user').length === 0 && !loading;

    return (
        <>
            {!open && (
                <button
                    type="button"
                    onClick={() => setOpen(true)}
                    className="fixed bottom-5 left-5 z-[100] flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-full text-white shadow-lg shadow-[var(--sf-primary)]/30 transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[var(--sf-primary)] focus:ring-offset-2"
                    style={{ backgroundColor: 'var(--sf-primary, #7c3aed)' }}
                    title={`Assistant ${shopName}`}
                    aria-label="Ouvrir l'assistant boutique"
                >
                    <MessageCircle className="h-6 w-6 sm:h-7 sm:w-7" />
                </button>
            )}

            {open && (
                <div
                    className="fixed inset-0 z-[110] flex flex-col bg-white dark:bg-slate-950 sm:inset-auto sm:bottom-5 sm:left-5 sm:top-auto sm:h-[min(560px,90vh)] sm:w-[min(400px,calc(100vw-2.5rem))] sm:rounded-2xl sm:border sm:border-slate-200/90 dark:sm:border-slate-700 sm:shadow-2xl overflow-hidden"
                    role="dialog"
                    aria-label={`Assistant ${shopName}`}
                >
                    <div className="flex items-center justify-between gap-2 px-4 py-3 text-white shrink-0" style={headerStyle}>
                        <div className="flex items-center gap-2.5 min-w-0">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                <Bot className="h-5 w-5" />
                            </span>
                            <div className="min-w-0">
                                <p className="text-sm font-semibold truncate">Assistant {shopName}</p>
                                <p className="text-[11px] text-white/85 flex items-center gap-1">
                                    <Sparkles className="h-3 w-3" />
                                    Réponses instantanées · IA
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            className="rounded-lg p-1.5 hover:bg-white/15 transition"
                            aria-label="Fermer"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    {!canAsk && (
                        <div className="mx-3 mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                            Le support IA n’est pas actif sur ce forfait. Utilisez WhatsApp ci-dessous si disponible.
                        </div>
                    )}

                    <div className="flex-1 overflow-y-auto px-3 py-3 space-y-3 min-h-0">
                        {messages.map((msg, i) => (
                            <div key={msg.id ?? i}>
                                <div
                                    className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
                                >
                                    <div className="max-w-[88%]">
                                        <div
                                            className={`rounded-2xl px-3 py-2 text-sm leading-relaxed whitespace-pre-wrap ${
                                                msg.role === 'user'
                                                    ? 'rounded-br-md text-white'
                                                    : 'rounded-bl-md bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'
                                            }`}
                                            style={
                                                msg.role === 'user'
                                                    ? { backgroundColor: 'var(--sf-primary, #7c3aed)' }
                                                    : undefined
                                            }
                                        >
                                            {msg.content}
                                        </div>
                                        {msg.role === 'assistant' && msg.logId && (
                                            <FeedbackButtons
                                                logId={msg.logId}
                                                feedbackPath={feedbackPath}
                                            />
                                        )}
                                    </div>
                                </div>
                                {msg.role === 'assistant' && msg.products?.length > 0 && (
                                    <div className="flex justify-start flex-col gap-2">
                                        <ProductSuggestions products={msg.products} links={links} />
                                    </div>
                                )}
                                {msg.role === 'assistant' && msg.showCatalogLink && (
                                    <div className="flex justify-start">
                                        <Link
                                            href={links.catalog()}
                                            className="inline-flex items-center gap-1.5 rounded-full border border-[var(--sf-primary)]/40 bg-white dark:bg-slate-900 px-3 py-1.5 text-xs font-semibold text-[var(--sf-primary)] hover:bg-[var(--sf-primary)]/5 transition"
                                        >
                                            <ExternalLink className="h-3.5 w-3.5" />
                                            Voir tout le catalogue
                                        </Link>
                                    </div>
                                )}
                            </div>
                        ))}
                        {loading && (
                            <div className="flex justify-start">
                                <div className="inline-flex items-center gap-2 rounded-2xl rounded-bl-md bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm text-slate-500">
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    Rédaction en cours…
                                </div>
                            </div>
                        )}
                        <div ref={messagesEndRef} />
                    </div>

                    {showSuggestions && (
                        <div className="px-3 pb-2 flex flex-wrap gap-1.5">
                            {suggestions.map((s) => (
                                <button
                                    key={s}
                                    type="button"
                                    onClick={() => send(s)}
                                    className="rounded-full border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-2.5 py-1 text-[11px] text-slate-600 dark:text-slate-300 hover:border-[var(--sf-primary)] hover:text-[var(--sf-primary)] transition"
                                >
                                    {s}
                                </button>
                            ))}
                        </div>
                    )}

                    <div className="border-t border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/80 shrink-0">
                        <button
                            type="button"
                            onClick={() => setShowOrderFields((v) => !v)}
                            className="w-full flex items-center justify-between px-3 py-2 text-[11px] font-medium text-slate-600 dark:text-slate-400 hover:text-[var(--sf-primary)]"
                        >
                            Suivi de commande (optionnel)
                            {showOrderFields ? <ChevronUp className="h-3.5 w-3.5" /> : <ChevronDown className="h-3.5 w-3.5" />}
                        </button>
                        {showOrderFields && (
                            <div className="px-3 pb-2 grid grid-cols-1 gap-2">
                                <input
                                    value={orderNumber}
                                    onChange={(e) => setOrderNumber(e.target.value)}
                                    placeholder="N° de commande"
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-xs"
                                />
                                <input
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="Email utilisé à la commande"
                                    type="email"
                                    className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-xs"
                                />
                            </div>
                        )}

                        <div className="p-2 flex gap-2">
                            <input
                                value={message}
                                onChange={(e) => setMessage(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        send();
                                    }
                                }}
                                placeholder="Posez votre question…"
                                disabled={!canAsk || loading}
                                className="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm disabled:opacity-50"
                            />
                            <button
                                type="button"
                                disabled={loading || !canAsk || !message.trim()}
                                onClick={() => send()}
                                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white disabled:opacity-50 transition hover:opacity-90"
                                style={{ backgroundColor: 'var(--sf-primary, #7c3aed)' }}
                                aria-label="Envoyer"
                            >
                                <Send className="h-4 w-4" />
                            </button>
                        </div>

                        <div className="px-3 pb-2 flex flex-col gap-1.5">
                            {whatsappEnabled && (
                                <button
                                    type="button"
                                    onClick={openHumanSupport}
                                    className="w-full rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-200 px-3 py-2 text-xs font-medium hover:bg-emerald-100 dark:hover:bg-emerald-950/50 transition"
                                >
                                    Parler à un conseiller (WhatsApp)
                                </button>
                            )}
                            <div className="flex items-center justify-between gap-2">
                                <p className="text-[10px] text-slate-400 leading-snug">
                                    Réponses automatiques, sans engagement contractuel.
                                </p>
                                <button
                                    type="button"
                                    onClick={clearChat}
                                    className="text-[10px] text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 shrink-0"
                                >
                                    Nouvelle conversation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
