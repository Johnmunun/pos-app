import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { Lightbulb, Send, X } from 'lucide-react';

export default function AISupportFloatingWidget() {
    const { props } = usePage();
    const cfg = props?.storefrontClient?.aiSupport || {};
    const hasNewKeys =
        Object.prototype.hasOwnProperty.call(cfg, 'shopEnabled') ||
        Object.prototype.hasOwnProperty.call(cfg, 'planEnabled');
    const shopEnabled = cfg?.shopEnabled === true;
    const legacyEnabled = cfg?.enabled === true;
    /** Bouton visible si la boutique a activé l’option (nouveau) ou ancien payload avec les deux conditions déjà fusionnées. */
    const showFab = hasNewKeys ? shopEnabled : legacyEnabled;
    /** Appel API autorisé (boutique + plan). */
    const canAsk = hasNewKeys ? cfg?.canUse === true : legacyEnabled;

    const askPath =
        cfg?.askPath ||
        (typeof window !== 'undefined' && window.location?.pathname?.startsWith('/ecommerce/storefront')
            ? '/ecommerce/storefront/support/ai/ask'
            : '/support/ai/ask');
    const whatsapp = props?.whatsapp || {};
    const whatsappEnabled = !!whatsapp?.enabled && !!whatsapp?.number;

    const [open, setOpen] = useState(false);
    const [message, setMessage] = useState('');
    const [orderNumber, setOrderNumber] = useState('');
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(false);
    const [history, setHistory] = useState([]);

    if (!showFab) {
        return null;
    }

    const send = async () => {
        if (!canAsk) return;
        const msg = String(message || '').trim();
        if (!msg || loading) return;
        setLoading(true);
        setHistory((h) => [...h, { role: 'user', text: msg }]);
        setMessage('');
        try {
            const res = await axios.post(askPath, {
                message: msg,
                order_number: orderNumber || null,
                customer_email: email || null,
            });
            const answer = res.data?.answer || 'Je n’ai pas pu répondre pour le moment.';
            setHistory((h) => [...h, { role: 'assistant', text: answer }]);
        } catch (e) {
            const err = e.response?.data?.message || e.message || 'Erreur du support IA.';
            setHistory((h) => [...h, { role: 'assistant', text: err }]);
        } finally {
            setLoading(false);
        }
    };

    const openHumanSupport = () => {
        if (!whatsappEnabled) return;
        const number = String(whatsapp.number || '').replace(/[^\d]/g, '');
        if (!number) return;
        const lastUserMessage = [...history].reverse().find((m) => m.role === 'user')?.text || '';
        const txt = encodeURIComponent(
            `Bonjour, j’ai besoin d’aide humaine.\n` +
            (lastUserMessage ? `Ma question: ${lastUserMessage}\n` : '') +
            (orderNumber ? `Commande: ${orderNumber}\n` : '') +
            (email ? `Email: ${email}\n` : '')
        );
        window.open(`https://wa.me/${number}?text=${txt}`, '_blank', 'noopener,noreferrer');
    };

    return (
        <div className="fixed bottom-5 left-5 z-[100] pointer-events-auto">
            {!open ? (
                <button
                    type="button"
                    onClick={() => setOpen(true)}
                    className="h-12 w-12 rounded-full bg-violet-600 text-white shadow-lg hover:bg-violet-700 transition"
                    title="Support IA"
                >
                    <Lightbulb className="h-6 w-6 mx-auto" />
                </button>
            ) : (
                <div className="w-[320px] max-w-[90vw] rounded-xl border border-slate-200 bg-white shadow-xl">
                    <div className="flex items-center justify-between px-3 py-2 border-b">
                        <p className="text-sm font-semibold">Support client IA</p>
                        <button type="button" onClick={() => setOpen(false)} className="text-slate-500 hover:text-slate-800">
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                    <div className="px-3 py-2 space-y-2">
                        <input
                            value={orderNumber}
                            onChange={(e) => setOrderNumber(e.target.value)}
                            placeholder="N° commande (optionnel)"
                            className="w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs"
                        />
                        <input
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="Email commande (optionnel)"
                            className="w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs"
                        />
                    </div>
                    <div className="h-44 overflow-y-auto px-3 pb-2 space-y-2">
                        {!canAsk && (
                            <p className="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-2 py-2">
                                Le support client IA n’est pas inclus dans le plan actuel de cette boutique, ou l’abonnement ne
                                correspond pas. Passez au plan Pro / Enterprise (ou vérifiez le forfait du tenant). Vous pouvez
                                aussi utiliser WhatsApp ci-dessous si activé.
                            </p>
                        )}
                        {canAsk && history.length === 0 && (
                            <p className="text-xs text-slate-500">Posez une question: livraison, retours, disponibilité, statut commande.</p>
                        )}
                        {history.map((m, i) => (
                            <div key={i} className={`text-xs rounded-md px-2 py-1.5 ${m.role === 'user' ? 'bg-amber-50' : 'bg-slate-100'}`}>
                                {m.text}
                            </div>
                        ))}
                    </div>
                    <div className="p-2 border-t flex gap-2">
                        <input
                            value={message}
                            onChange={(e) => setMessage(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    send();
                                }
                            }}
                            placeholder="Votre question..."
                            disabled={!canAsk}
                            className="flex-1 rounded-md border border-slate-300 px-2 py-1.5 text-xs disabled:opacity-50"
                        />
                        <button type="button" disabled={loading || !canAsk} onClick={send} className="rounded-md bg-amber-600 text-white px-2 py-1.5 disabled:opacity-50">
                            <Send className="h-4 w-4" />
                        </button>
                    </div>
                    {whatsappEnabled && (
                        <div className="px-2 pb-2">
                            <button
                                type="button"
                                onClick={openHumanSupport}
                                className="w-full rounded-md border border-amber-300 text-amber-700 px-3 py-1.5 text-xs hover:bg-amber-50 transition"
                            >
                                Parler à un agent humain (WhatsApp)
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

