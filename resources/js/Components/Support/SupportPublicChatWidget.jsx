import { useEffect, useRef, useState } from 'react';
import { MessageCircle, X, Send, Circle, User, Image as ImageIcon } from 'lucide-react';
import { playChatNotificationSound } from '@/lib/chatNotify';

function formatTime(ts) {
    if (!ts) return '';
    try {
        const d = new Date(ts);
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch {
        return '';
    }
}

export default function SupportPublicChatWidget() {
    const [open, setOpen] = useState(false);
    const [supportOnline] = useState(true);
    const [conversationId, setConversationId] = useState(null);
    const [messages, setMessages] = useState([]);
    const [draft, setDraft] = useState('');
    const [connecting, setConnecting] = useState(false);
    const [sending, setSending] = useState(false);
    const [unreadCount, setUnreadCount] = useState(0);
    const [attachment, setAttachment] = useState(null);

    const openRef = useRef(open);
    useEffect(() => {
        openRef.current = open;
    }, [open]);

    const [guestName, setGuestName] = useState('');
    const [guestPhone, setGuestPhone] = useState('');
    const [identified, setIdentified] = useState(false);

    const esRef = useRef(null);
    const seenMessageIdsRef = useRef(new Set());
    const listRef = useRef(null);
    const fileRef = useRef(null);

    const scrollToBottom = () => {
        try {
            const el = listRef.current;
            if (!el) return;
            el.scrollTop = el.scrollHeight;
        } catch {
        }
    };

    const ensureConversation = async () => {
        const res = await window.axios.post(route('support.public-chat.conversation'), {
            guest_name: guestName || null,
            guest_phone: guestPhone || null,
        });
        const id = res?.data?.conversation?.id;
        if (!id) throw new Error('conversation missing');
        setConversationId(id);
        return id;
    };

    const loadInitialMessages = async (id) => {
        const res = await window.axios.get(route('support.public-chat.messages', id));
        const list = Array.isArray(res?.data?.messages) ? res.data.messages : [];
        seenMessageIdsRef.current = new Set(list.map((m) => Number(m?.id)).filter((idValue) => Number.isFinite(idValue) && idValue > 0));
        setMessages(list);
        setTimeout(scrollToBottom, 50);
        return list.length ? (list[list.length - 1]?.id || 0) : 0;
    };

    const connectStream = (id, afterId) => {
        try {
            if (esRef.current) {
                esRef.current.close();
                esRef.current = null;
            }
            const url = `${route('support.public-chat.stream', id)}?after_id=${encodeURIComponent(afterId || 0)}`;
            const es = new EventSource(url, { withCredentials: true });
            esRef.current = es;

            es.addEventListener('message', (e) => {
                try {
                    const payload = JSON.parse(e.data);
                    const incomingId = Number(payload?.id || 0);
                    if (!Number.isFinite(incomingId) || incomingId <= 0) return;
                    const incomingFromSupport = payload?.sender_type !== 'guest';
                    setMessages((prev) => {
                        if (seenMessageIdsRef.current.has(incomingId)) return prev;
                        seenMessageIdsRef.current.add(incomingId);
                        return [...prev, payload];
                    });
                    if (incomingFromSupport) {
                        if (!openRef.current) setUnreadCount((n) => n + 1);
                        playChatNotificationSound();
                    }
                    setTimeout(scrollToBottom, 50);
                } catch {
                }
            });
        } catch {
        }
    };

    useEffect(() => {
        if (!identified) return;
        let alive = true;

        (async () => {
            setConnecting(true);
            try {
                const id = await ensureConversation();
                if (!alive) return;
                const afterId = await loadInitialMessages(id);
                if (!alive) return;
                connectStream(id, afterId);
            } catch {
            } finally {
                setConnecting(false);
            }
        })();

        return () => {
            alive = false;
            if (esRef.current) {
                esRef.current.close();
                esRef.current = null;
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [identified]);

    const send = async () => {
        const text = String(draft || '').trim();
        if ((!text && !attachment) || !conversationId) return;
        setSending(true);
        try {
            setDraft('');
            const fd = new FormData();
            if (text) fd.append('message', text);
            if (attachment) fd.append('attachment', attachment);
            await window.axios.post(route('support.public-chat.send', conversationId), fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            setAttachment(null);
            if (fileRef.current) fileRef.current.value = '';
            setUnreadCount(0);
        } catch {
            setDraft(text);
        } finally {
            setSending(false);
        }
    };

    return (
        <>
            {!open ? (
                <div className="fixed bottom-4 left-4 sm:bottom-6 sm:left-6 z-[70]">
                    <button
                        type="button"
                        onClick={() => {
                            setOpen(true);
                            setUnreadCount(0);
                        }}
                        className="relative inline-flex items-center gap-2 h-12 px-4 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-xl shadow-emerald-600/30 transition-transform hover:translate-y-[-2px]"
                        aria-label="Chat support"
                    >
                        <MessageCircle className="h-6 w-6" />
                        <span className="text-sm font-semibold whitespace-nowrap">Besoin d'aide ?</span>
                        {unreadCount > 0 ? (
                            <span className="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[11px] leading-5 text-center font-bold">
                                {unreadCount > 9 ? '9+' : unreadCount}
                            </span>
                        ) : null}
                    </button>
                </div>
            ) : (
                <div className="fixed bottom-4 left-4 sm:bottom-6 sm:left-6 z-[70] w-[92vw] max-w-[420px]">
                    <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl overflow-hidden">
                        <div className="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <div className="w-9 h-9 rounded-full bg-amber-600 text-white flex items-center justify-center font-bold text-sm">
                                    S
                                </div>
                                <div>
                                    <div className="text-sm font-semibold text-gray-900 dark:text-white">
                                        Support
                                        {unreadCount > 0 ? (
                                            <span className="ml-2 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[11px] leading-5 font-bold align-middle">
                                                {unreadCount > 9 ? '9+' : unreadCount}
                                            </span>
                                        ) : null}
                                    </div>
                                    <div className="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                        <Circle className={`h-3 w-3 ${supportOnline ? 'text-emerald-500 fill-emerald-500' : 'text-gray-400 fill-gray-400'}`} />
                                        <span>{supportOnline ? 'En ligne' : 'Hors ligne'}</span>
                                        {connecting ? <span className="ml-1">(connexion...)</span> : null}
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                onClick={() => setOpen(false)}
                                className="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                                aria-label="Fermer"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        {!identified ? (
                            <div className="p-4 bg-white dark:bg-gray-900">
                                <div className="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <User className="h-5 w-5 mt-0.5 text-amber-600" />
                                    <div>
                                        <div className="font-semibold text-gray-900 dark:text-white">Avant de commencer</div>
                                        <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            (Optionnel) Mets ton nom et ton numéro pour qu’on te réponde plus vite.
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-4 space-y-3">
                                    <div>
                                        <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                            Nom
                                        </label>
                                        <input
                                            value={guestName}
                                            onChange={(e) => setGuestName(e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-2 focus:ring-amber-500 focus:border-amber-500"
                                            placeholder="Ex: Sarah"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                            Téléphone / WhatsApp
                                        </label>
                                        <input
                                            value={guestPhone}
                                            onChange={(e) => setGuestPhone(e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-2 focus:ring-amber-500 focus:border-amber-500"
                                            placeholder="Ex: +243..."
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setIdentified(true)}
                                        className="w-full inline-flex items-center justify-center px-4 py-2 rounded-md bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700"
                                    >
                                        Démarrer le chat
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setGuestName('');
                                            setGuestPhone('');
                                            setIdentified(true);
                                        }}
                                        className="w-full inline-flex items-center justify-center px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700"
                                    >
                                        Continuer sans infos
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <>
                                <div ref={listRef} className="h-[360px] sm:h-[420px] overflow-y-auto p-4 space-y-3 bg-white dark:bg-gray-900">
                                    {messages.length === 0 ? (
                                        <div className="text-sm text-gray-500 dark:text-gray-400">
                                            Écris ton message, un agent support te répondra ici.
                                        </div>
                                    ) : null}

                                    {messages.map((m) => {
                                        const mine = m.sender_type === 'guest';
                                        return (
                                            <div key={m.id} className={`flex ${mine ? 'justify-end' : 'justify-start'}`}>
                                                <div className={`max-w-[78%] rounded-2xl px-3 py-2 text-sm shadow-sm ${
                                                    mine
                                                        ? 'bg-amber-600 text-white'
                                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100'
                                                }`}>
                                                    <div className="whitespace-pre-wrap break-words">{m.message}</div>
                                                    {m.attachment_url ? (
                                                        <a href={m.attachment_url} target="_blank" rel="noreferrer">
                                                            <img src={m.attachment_url} alt="Pièce jointe" className="mt-2 rounded-lg max-h-56 w-auto object-cover border border-black/10" />
                                                        </a>
                                                    ) : null}
                                                    <div className={`mt-1 text-[10px] ${mine ? 'text-white/80' : 'text-gray-500 dark:text-gray-400'}`}>
                                                        {formatTime(m.created_at)}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>

                                <div className="p-3 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                                    <div className="flex items-end gap-2">
                                        <textarea
                                            value={draft}
                                            onChange={(e) => setDraft(e.target.value)}
                                            rows={1}
                                            placeholder="Écrire un message..."
                                            className="flex-1 resize-none rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter' && !e.shiftKey) {
                                                    e.preventDefault();
                                                    send();
                                                }
                                            }}
                                        />
                                        <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={(e) => setAttachment(e.target.files?.[0] || null)} />
                                        <button
                                            type="button"
                                            onClick={() => fileRef.current?.click()}
                                            className="inline-flex items-center justify-center h-10 w-10 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200"
                                            aria-label="Ajouter image"
                                        >
                                            <ImageIcon className="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            onClick={send}
                                            disabled={sending || (!draft.trim() && !attachment) || !conversationId}
                                            className="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-amber-600 hover:bg-amber-700 text-white disabled:opacity-50"
                                            aria-label="Envoyer"
                                        >
                                            <Send className="h-4 w-4" />
                                        </button>
                                    </div>
                                    {attachment ? <div className="mt-2 text-xs text-gray-500 dark:text-gray-400 truncate">Image: {attachment.name}</div> : null}
                                    <div className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        Entrée pour envoyer • Shift+Entrée pour une nouvelle ligne
                                    </div>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            )}
        </>
    );
}

