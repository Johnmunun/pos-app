import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
    Send, Circle, Image as ImageIcon, Pin, Search, PhoneCall, Video, MoreHorizontal, Link2, FileText, CheckCircle2, Loader2,
} from 'lucide-react';
import { playChatNotificationSound } from '@/lib/chatNotify';
import { useToast } from '@/Components/ui/use-toast';

function formatTime(ts) {
    if (!ts) return '';
    try {
        const d = new Date(ts);
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch {
        return '';
    }
}

export default function AdminChat({ conversations = [] }) {
    const { auth } = usePage().props;
    const currentUserId = auth?.user?.id || null;
    const { toast } = useToast();

    const [conversationList, setConversationList] = useState(conversations);
    const [selected, setSelected] = useState(conversations[0] || null);
    const [messages, setMessages] = useState([]);
    const [draft, setDraft] = useState('');
    const [supportOnline] = useState(true);
    const [unreadByConversationId, setUnreadByConversationId] = useState({});
    const [attachment, setAttachment] = useState(null);
    const [sending, setSending] = useState(false);
    const [pinningMessageId, setPinningMessageId] = useState(null);
    const [search, setSearch] = useState('');
    const [showMoreMenu, setShowMoreMenu] = useState(false);
    const [agents, setAgents] = useState([]);
    const [showAssignMenu, setShowAssignMenu] = useState(false);
    const esRef = useRef(null);
    const listRef = useRef(null);
    const selectedIdRef = useRef(conversations[0]?.id || null);
    const fileRef = useRef(null);
    const moreMenuRef = useRef(null);
    const assignMenuRef = useRef(null);

    const convId = selected?.id || null;

    const pinnedMessage = useMemo(
        () => messages.find((m) => Boolean(m.pinned_at)) || null,
        [messages],
    );
    const mediaMessages = useMemo(
        () => messages.filter((m) => Boolean(m.attachment_url)),
        [messages],
    );
    const filteredConversations = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return conversationList;
        return conversationList.filter((c) => {
            const name = String(c?.user?.name || '').toLowerCase();
            const phone = String(c?.user?.phone || '').toLowerCase();
            const id = String(c?.id || '');
            return name.includes(q) || phone.includes(q) || id.includes(q);
        });
    }, [conversationList, search]);
    const members = useMemo(() => {
        const list = [{ id: selected?.user?.id || 'client', name: selected?.user?.name || 'Client' }];
        if (selected?.assigned_to?.name) {
            list.push({ id: selected.assigned_to.id || 'agent', name: selected.assigned_to.name });
        }
        return list;
    }, [selected]);
    const links = useMemo(() => {
        const re = /https?:\/\/[^\s]+/gi;
        const items = [];
        messages.forEach((m) => {
            const found = String(m.message || '').match(re);
            if (found?.length) items.push(...found);
        });
        return Array.from(new Set(items)).slice(0, 8);
    }, [messages]);
    const files = useMemo(
        () => mediaMessages.map((m) => ({ id: m.id, url: m.attachment_url })).slice(-8).reverse(),
        [mediaMessages],
    );

    useEffect(() => {
        setConversationList(conversations);
    }, [conversations]);

    const scrollToBottom = () => {
        const el = listRef.current;
        if (!el) return;
        el.scrollTop = el.scrollHeight;
    };

    const loadInitial = async (id) => {
        const res = await window.axios.get(route('support.chat.messages', id));
        const list = Array.isArray(res?.data?.messages) ? res.data.messages : [];
        setMessages(list);
        setTimeout(scrollToBottom, 50);
        return list.length ? (list[list.length - 1]?.id || 0) : 0;
    };

    const connectStream = (id, afterId) => {
        if (esRef.current) {
            esRef.current.close();
            esRef.current = null;
        }
        const url = `${route('support.chat.stream', id)}?after_id=${encodeURIComponent(afterId || 0)}`;
        const es = new EventSource(url, { withCredentials: true });
        esRef.current = es;
        es.addEventListener('message', (e) => {
            try {
                const payload = JSON.parse(e.data);
                setMessages((prev) => {
                    if (prev.length && prev[prev.length - 1]?.id === payload.id) return prev;
                    return [...prev, payload];
                });
                const incomingFromClient = payload?.sender_type === 'user' && payload?.sender_user_id !== currentUserId;
                if (incomingFromClient) {
                    playChatNotificationSound();
                }
                setTimeout(scrollToBottom, 50);
            } catch {
            }
        });
    };

    useEffect(() => {
        if (!convId) return;
        (async () => {
            const afterId = await loadInitial(convId);
            connectStream(convId, afterId);
        })();
        return () => {
            if (esRef.current) {
                esRef.current.close();
                esRef.current = null;
            }
        };
    }, [convId]);

    useEffect(() => {
        selectedIdRef.current = convId;
        if (convId) {
            setUnreadByConversationId((prev) => ({ ...prev, [convId]: 0 }));
        }
    }, [convId]);

    useEffect(() => {
        const onFcm = (event) => {
            const data = event?.detail?.data || event?.detail || {};
            if (String(data?.type || '') !== 'support.chat.message') return;
            const cid = Number(data?.conversation_id || 0);
            if (!cid || selectedIdRef.current === cid) return;
            setUnreadByConversationId((prev) => ({ ...prev, [cid]: (prev[cid] || 0) + 1 }));
            playChatNotificationSound();
        };
        window.addEventListener('fcm-notification', onFcm);
        return () => window.removeEventListener('fcm-notification', onFcm);
    }, []);

    useEffect(() => {
        const onClickOutside = (e) => {
            if (!moreMenuRef.current) return;
            if (!moreMenuRef.current.contains(e.target)) {
                setShowMoreMenu(false);
            }
            if (assignMenuRef.current && !assignMenuRef.current.contains(e.target)) {
                setShowAssignMenu(false);
            }
        };
        document.addEventListener('mousedown', onClickOutside);
        return () => document.removeEventListener('mousedown', onClickOutside);
    }, []);

    useEffect(() => {
        (async () => {
            try {
                const res = await window.axios.get(route('support.chat.agents'));
                setAgents(Array.isArray(res?.data?.agents) ? res.data.agents : []);
            } catch {
            }
        })();
    }, []);

    const send = async () => {
        const text = String(draft || '').trim();
        if ((!text && !attachment) || !convId) return;
        setSending(true);
        setDraft('');
        try {
            const fd = new FormData();
            if (text) fd.append('message', text);
            if (attachment) fd.append('attachment', attachment);
            await window.axios.post(route('support.chat.send', convId), fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            setConversationList((prev) => prev.map((c) => (
                c.id === convId
                    ? { ...c, last_message_at: new Date().toISOString(), last_message_preview: text || 'Image' }
                    : c
            )));
            setAttachment(null);
            if (fileRef.current) fileRef.current.value = '';
        } catch {
            setDraft(text);
        } finally {
            setSending(false);
        }
    };

    const quickCall = () => {
        const phone = String(selected?.user?.phone || '').trim();
        if (!phone) return;
        const normalized = phone.replace(/[^\d+]/g, '');
        const wa = normalized.replace(/^\+/, '');
        window.open(`https://wa.me/${wa}`, '_blank');
    };

    const toggleConversationStatus = async () => {
        if (!convId || !selected) return;
        const next = selected.status === 'closed' ? 'open' : 'closed';
        try {
            await window.axios.post(route('support.chat.status', convId), { status: next });
            setSelected((prev) => (prev ? { ...prev, status: next } : prev));
            setConversationList((prev) => prev.map((c) => (c.id === convId ? { ...c, status: next } : c)));
        } catch {
        }
    };

    const initials = (name) => {
        const parts = String(name || 'C').trim().split(/\s+/).slice(0, 2);
        return parts.map((p) => p.charAt(0).toUpperCase()).join('');
    };

    const copyPhone = async () => {
        const phone = String(selected?.user?.phone || '').trim();
        if (!phone) return;
        try {
            await navigator.clipboard.writeText(phone);
            toast({ title: 'Numero copie' });
        } catch {
            toast({ title: 'Erreur', description: 'Impossible de copier le numero', variant: 'destructive' });
        }
        setShowMoreMenu(false);
    };

    const goToHistoryStart = () => {
        const el = listRef.current;
        if (el) el.scrollTop = 0;
        setShowMoreMenu(false);
    };

    const exportConversationPdf = () => {
        if (!selected) return;
        const lines = messages.map((m) => {
            const who = m.sender_type === 'guest'
                ? 'VISITEUR'
                : (m.sender_user_id === currentUserId ? 'AGENT' : 'CLIENT');
            const base = `[${m.created_at || ''}] ${who}: ${(m.message || '').replace(/</g, '&lt;')}`.trim();
            return m.attachment_url ? `${base}<br/>PIECE_JOINTE: ${m.attachment_url}` : base;
        });
        const html = `
            <html>
            <head><title>Conversation ${selected.id}</title></head>
            <body style="font-family: Arial, sans-serif; padding: 16px;">
                <h2>Conversation #${selected.id}</h2>
                <p><strong>Client:</strong> ${selected.user?.name || 'Client'}</p>
                <p><strong>Telephone:</strong> ${selected.user?.phone || 'N/A'}</p>
                <p><strong>Statut:</strong> ${selected.status || 'open'}</p>
                <hr />
                ${lines.map((l) => `<p style="font-size:12px;line-height:1.4">${l}</p>`).join('')}
            </body>
            </html>
        `;
        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);

        const clean = () => {
            try {
                document.body.removeChild(iframe);
            } catch {
            }
        };

        const doc = iframe.contentWindow?.document;
        if (!doc || !iframe.contentWindow) {
            clean();
            toast({ title: 'Erreur', description: "Impossible de lancer l'export PDF", variant: 'destructive' });
            setShowMoreMenu(false);
            return;
        }

        doc.open();
        doc.write(html);
        doc.close();

        const printWindow = iframe.contentWindow;
        // Give browser a short time to layout before print.
        window.setTimeout(() => {
            try {
                printWindow.focus();
                printWindow.print();
                toast({ title: 'Export PDF lance' });
            } catch {
                toast({ title: 'Erreur', description: "Impossible de lancer l'export PDF", variant: 'destructive' });
            } finally {
                // Cleanup after print dialog opens.
                window.setTimeout(clean, 1500);
            }
        }, 250);

        setShowMoreMenu(false);
    };

    const openUserProfile = () => {
        if (!selected?.user?.id) return;
        window.location.href = route('admin.users.show', selected.user.id);
        setShowMoreMenu(false);
    };

    const assignToAgent = async (agentId) => {
        if (!convId) return;
        try {
            const res = await window.axios.post(route('support.chat.assign', convId), {
                assigned_to_user_id: agentId || null,
            });
            const assigned = res?.data?.conversation?.assigned_to || null;
            setSelected((prev) => (prev ? { ...prev, assigned_to: assigned } : prev));
            setConversationList((prev) => prev.map((c) => (c.id === convId ? { ...c, assigned_to: assigned } : c)));
            toast({
                title: 'Assignation mise a jour',
                description: assigned ? `Assigne a ${assigned.name}` : 'Conversation desassignee',
            });
        } catch {
            toast({ title: 'Erreur', description: "Impossible d'assigner cette conversation", variant: 'destructive' });
        } finally {
            setShowAssignMenu(false);
            setShowMoreMenu(false);
        }
    };

    const togglePin = async (m) => {
        if (!convId || !m?.id) return;
        setPinningMessageId(m.id);
        try {
            const pin = !Boolean(m.pinned_at);
            await window.axios.post(route('support.chat.pin', { conversation: convId, message: m.id }), { pin });
            setMessages((prev) => prev.map((it) => {
                if (it.id === m.id) return { ...it, pinned_at: pin ? new Date().toISOString() : null };
                if (pin && it.id !== m.id) return { ...it, pinned_at: null };
                return it;
            }));
        } catch {
        } finally {
            setPinningMessageId(null);
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                            Support Chat
                        </h2>
                        <div className="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mt-1">
                            <Circle className={`h-3 w-3 ${supportOnline ? 'text-emerald-500 fill-emerald-500' : 'text-gray-400 fill-gray-400'}`} />
                            <span>Support {supportOnline ? 'en ligne' : 'hors ligne'}</span>
                        </div>
                    </div>
                </div>
            }
            fullWidth
        >
            <Head title="Support Chat (Admin)" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid gap-4 xl:grid-cols-[320px,1fr,320px]">
                        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <p className="text-sm font-semibold text-gray-900 dark:text-white">Conversations</p>
                                <div className="mt-2 relative">
                                    <Search className="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                    <input
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Recherche..."
                                        className="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 pl-9 pr-3 py-2 text-sm text-gray-900 dark:text-gray-100"
                                    />
                                </div>
                            </div>
                            <div className="max-h-[70vh] overflow-y-auto">
                                {filteredConversations.map((c) => (
                                    <button
                                        key={c.id}
                                        type="button"
                                        onClick={() => {
                                            setSelected(c);
                                            setUnreadByConversationId((prev) => ({ ...prev, [c.id]: 0 }));
                                        }}
                                        className={`w-full text-left px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/30 ${
                                            convId === c.id ? 'bg-amber-50 dark:bg-amber-900/10' : ''
                                        }`}
                                    >
                                        <div className="flex items-center justify-between gap-2">
                                            <div className="font-semibold text-sm text-gray-900 dark:text-white truncate">
                                                <span className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-[10px] mr-2 align-middle">
                                                    {initials(c.user?.name || 'Client')}
                                                </span>
                                                {c.user?.name || 'Client'}
                                                {unreadByConversationId[c.id] > 0 ? (
                                                    <span className="ml-2 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[11px] leading-5 font-bold align-middle">
                                                        {unreadByConversationId[c.id] > 9 ? '9+' : unreadByConversationId[c.id]}
                                                    </span>
                                                ) : null}
                                            </div>
                                            <span className="text-[11px] text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                {formatTime(c.last_message_at)}
                                            </span>
                                        </div>
                                        {c.user?.phone ? (
                                            <div className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {c.user.phone}
                                            </div>
                                        ) : null}
                                        <div className="text-xs text-gray-500 dark:text-gray-400">
                                            Statut: {c.status}
                                        </div>
                                        {c.last_message_preview ? (
                                            <div className="text-xs text-gray-500 dark:text-gray-400 truncate mt-1">
                                                {c.last_message_preview}
                                            </div>
                                        ) : null}
                                    </button>
                                ))}
                                {filteredConversations.length === 0 ? (
                                    <div className="p-4 text-sm text-gray-500 dark:text-gray-400">
                                        Aucune conversation trouvée.
                                    </div>
                                ) : null}
                            </div>
                        </div>

                        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col min-h-[72vh]">
                            <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                {selected ? (
                                    <div className="flex items-center justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-semibold text-gray-900 dark:text-white">
                                                {selected.user?.name || 'Client'}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                Conversation #{selected.id} {selected.user?.phone ? `• ${selected.user.phone}` : ''}
                                            </p>
                                        </div>
                                        <div className="text-xs text-gray-500 dark:text-gray-400">
                                            <div className="flex items-center gap-2">
                                                <button type="button" onClick={quickCall} className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="WhatsApp"><PhoneCall className="h-4 w-4" /></button>
                                                <button type="button" disabled className="p-1 rounded text-gray-400 cursor-not-allowed" title="Appel video bientot disponible"><Video className="h-4 w-4" /></button>
                                                <div className="relative" ref={moreMenuRef}>
                                                    <button type="button" onClick={() => setShowMoreMenu((v) => !v)} className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Plus d'actions"><MoreHorizontal className="h-4 w-4" /></button>
                                                    {showMoreMenu ? (
                                                        <div className="absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg z-20 py-1">
                                                            <button type="button" onClick={copyPhone} className="w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                                Copier numero
                                                            </button>
                                                            <button type="button" onClick={goToHistoryStart} className="w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                                Voir historique
                                                            </button>
                                                            <button type="button" onClick={openUserProfile} disabled={!selected?.user?.id} className="w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50">
                                                                Voir profil client
                                                            </button>
                                                            <button type="button" onClick={exportConversationPdf} className="w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                                Exporter PDF
                                                            </button>
                                                            <div className="relative" ref={assignMenuRef}>
                                                                <button type="button" onClick={() => setShowAssignMenu((v) => !v)} className="w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                                    Assigner a un agent
                                                                </button>
                                                                {showAssignMenu ? (
                                                                    <div className="absolute left-full top-0 ml-1 w-52 max-h-60 overflow-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-xl z-30 py-1">
                                                                        <button type="button" onClick={() => assignToAgent(null)} className="w-full text-left px-3 py-2 text-xs hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">
                                                                            Non assigne
                                                                        </button>
                                                                        {agents.map((a) => (
                                                                            <button key={a.id} type="button" onClick={() => assignToAgent(a.id)} className="w-full text-left px-3 py-2 text-xs hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">
                                                                                {a.name}
                                                                            </button>
                                                                        ))}
                                                                    </div>
                                                                ) : null}
                                                            </div>
                                                        </div>
                                                    ) : null}
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={toggleConversationStatus}
                                                    className={`inline-flex items-center gap-1 px-2 py-1 rounded text-[11px] ${
                                                        selected.status === 'closed'
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'
                                                    }`}
                                                >
                                                    <CheckCircle2 className="h-3.5 w-3.5" />
                                                    {selected.status === 'closed' ? 'Rouvrir' : 'Résoudre'}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-sm font-semibold text-gray-900 dark:text-white">Sélectionner une conversation</p>
                                )}
                            </div>

                            <div ref={listRef} className="flex-1 min-h-[520px] max-h-[70vh] overflow-y-auto p-4 space-y-3">
                                {messages.map((m) => (
                                    <div key={m.id} className={`flex ${m.sender_user_id === currentUserId ? 'justify-end' : 'justify-start'}`}>
                                        <div className={`max-w-[78%] rounded-2xl px-3 py-2 text-sm ${
                                            m.sender_user_id === currentUserId
                                                ? 'bg-amber-600 text-white'
                                                : 'bg-gray-100 dark:bg-gray-900/40 text-gray-900 dark:text-gray-100'
                                        }`}>
                                            <div className="mb-1 flex justify-between gap-2">
                                                <span className={`text-[10px] ${m.sender_user_id === currentUserId ? 'text-white/80' : 'text-gray-500 dark:text-gray-400'}`}>
                                                    {m.sender_type === 'guest'
                                                        ? 'Visiteur'
                                                        : (m.sender_user_id === currentUserId
                                                            ? 'Vous'
                                                            : (m.sender_type === 'support'
                                                                ? (m.sender_name || 'Support')
                                                                : (m.sender_name || 'Client')))}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => togglePin(m)}
                                                    disabled={pinningMessageId === m.id}
                                                    className={`inline-flex items-center gap-1 text-[10px] ${
                                                        m.sender_user_id === currentUserId
                                                            ? (m.pinned_at ? 'text-white' : 'text-white/80')
                                                            : (m.pinned_at ? 'text-amber-600' : 'text-gray-500')
                                                    }`}
                                                >
                                                    <Pin className="h-3 w-3" />
                                                    {m.pinned_at ? 'Épinglé' : 'Épingler'}
                                                </button>
                                            </div>
                                            <div className="whitespace-pre-wrap break-words">{m.message}</div>
                                            {m.attachment_url ? (
                                                <a href={m.attachment_url} target="_blank" rel="noreferrer">
                                                    <img src={m.attachment_url} alt="Pièce jointe" className="mt-2 rounded-lg max-h-56 w-auto object-cover border border-black/10" />
                                                </a>
                                            ) : null}
                                            <div className={`mt-1 text-[10px] ${m.sender_user_id === currentUserId ? 'text-white/80' : 'text-gray-500 dark:text-gray-400'}`}>
                                                {formatTime(m.created_at)}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                {messages.length === 0 ? (
                                    <div className="text-sm text-gray-500 dark:text-gray-400">
                                        Aucun message.
                                    </div>
                                ) : null}
                            </div>

                            <div className="p-3 border-t border-gray-200 dark:border-gray-700">
                                <div className="flex items-end gap-2">
                                    <textarea
                                        value={draft}
                                        onChange={(e) => setDraft(e.target.value)}
                                        rows={1}
                                        placeholder="Répondre..."
                                        className="flex-1 resize-none rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter' && !e.shiftKey) {
                                                e.preventDefault();
                                                send();
                                            }
                                        }}
                                        disabled={!convId}
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
                                        disabled={sending || (!draft.trim() && !attachment) || !convId}
                                        className="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-amber-600 hover:bg-amber-700 text-white disabled:opacity-50"
                                        aria-label="Envoyer"
                                    >
                                        {sending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                                    </button>
                                </div>
                                {attachment ? <div className="mt-2 text-xs text-gray-500 dark:text-gray-400 truncate">Image: {attachment.name}</div> : null}
                            </div>
                        </div>

                        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden h-fit">
                            <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <p className="text-sm font-semibold text-gray-900 dark:text-white">Informations</p>
                            </div>
                            <div className="p-4 space-y-5">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Contact</p>
                                    <p className="mt-2 text-sm font-medium text-gray-900 dark:text-white">{selected?.user?.name || 'Client'}</p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">{selected?.user?.phone || 'Sans numéro'}</p>
                                </div>

                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Message épinglé</p>
                                    {pinnedMessage ? (
                                        <div className="mt-2 rounded-xl border border-amber-200 bg-amber-50/70 dark:bg-amber-900/20 dark:border-amber-800 p-3">
                                            <p className="text-xs text-gray-700 dark:text-gray-300 line-clamp-3">
                                                {pinnedMessage.message || 'Image épinglée'}
                                            </p>
                                            {pinnedMessage.attachment_url ? (
                                                <a href={pinnedMessage.attachment_url} target="_blank" rel="noreferrer">
                                                    <img src={pinnedMessage.attachment_url} alt="Pinned" className="mt-2 rounded-lg max-h-32 w-auto object-cover" />
                                                </a>
                                            ) : null}
                                        </div>
                                    ) : (
                                        <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">Aucun message épinglé.</p>
                                    )}
                                </div>

                                <div>
                                    <div className="flex items-center justify-between">
                                        <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Médias</p>
                                        <span className="text-xs text-gray-500 dark:text-gray-400">{mediaMessages.length}</span>
                                    </div>
                                    {mediaMessages.length > 0 ? (
                                        <div className="mt-2 grid grid-cols-3 gap-2">
                                            {mediaMessages.slice(-9).reverse().map((m) => (
                                                <a key={m.id} href={m.attachment_url} target="_blank" rel="noreferrer" className="block">
                                                    <img src={m.attachment_url} alt="Media" className="h-20 w-full rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                                                </a>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">Aucune image pour le moment.</p>
                                    )}
                                </div>

                                <div>
                                    <div className="flex items-center justify-between">
                                        <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Fichiers</p>
                                        <span className="text-xs text-gray-500 dark:text-gray-400">{files.length}</span>
                                    </div>
                                    {files.length > 0 ? (
                                        <div className="mt-2 space-y-2">
                                            {files.map((f) => (
                                                <a key={f.id} href={f.url} target="_blank" rel="noreferrer" className="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200 hover:underline">
                                                    <FileText className="h-3.5 w-3.5" />
                                                    Fichier #{f.id}
                                                </a>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">Aucun fichier.</p>
                                    )}
                                </div>

                                <div>
                                    <div className="flex items-center justify-between">
                                        <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Liens</p>
                                        <span className="text-xs text-gray-500 dark:text-gray-400">{links.length}</span>
                                    </div>
                                    {links.length > 0 ? (
                                        <div className="mt-2 space-y-2">
                                            {links.map((l) => (
                                                <a key={l} href={l} target="_blank" rel="noreferrer" className="flex items-start gap-2 text-xs text-blue-600 dark:text-blue-400 hover:underline break-all">
                                                    <Link2 className="h-3.5 w-3.5 mt-0.5" />
                                                    {l}
                                                </a>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">Aucun lien détecté.</p>
                                    )}
                                </div>

                                <div>
                                    <div className="flex items-center justify-between">
                                        <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Membres</p>
                                        <span className="text-xs text-gray-500 dark:text-gray-400">{members.length}</span>
                                    </div>
                                    <div className="mt-2 space-y-2">
                                        {members.map((m) => (
                                            <div key={m.id} className="text-xs text-gray-700 dark:text-gray-200">
                                                {m.name}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

