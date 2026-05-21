import React, { useState, useRef, useEffect, useMemo } from 'react';
import {
    Bot,
    Send,
    X,
    Loader2,
    ExternalLink,
    Mic,
    Square,
    Settings,
    Volume2,
    Sparkles,
    RotateCcw,
} from 'lucide-react';
import axios from 'axios';
import { usePage, router } from '@inertiajs/react';

const STORAGE_KEY = 'commerce_assistant_messages';

const SUGGESTIONS = [
    'Ventes aujourd\'hui ?',
    'Bénéfice ce mois-ci ?',
    'Produits en rupture ?',
    'Quels produits en stock bas ?',
    'Où sont les rapports ?',
    'Valeur totale du stock ?',
    'Ventes d\'hier ?',
];

const HEADER_GRADIENT = {
    background: 'linear-gradient(135deg, #7c3aed 0%, #6d28d9 50%, #5b21b6 100%)',
};

const DEFAULT_VOICE_SETTINGS = {
    voice_enabled: true,
    voice_type: 'female',
    voice_speed: 1.0,
    auto_play: true,
    language: 'auto',
};

function buildWelcomeMessage(userName) {
    const first = userName ? String(userName).trim().split(/\s+/)[0] : '';
    const greet = first ? `Bonjour ${first}.` : 'Bonjour.';
    return {
        role: 'assistant',
        id: 'welcome',
        content: `${greet} Je suis votre assistant Commerce. Je peux vous renseigner sur les ventes, le stock, la recherche de produits et la navigation dans l'application. Posez votre question ou utilisez le micro.`,
    };
}

function loadStoredMessages() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed) || parsed.length === 0) return null;
        const valid = parsed.every((m) => {
            if (!m || typeof m.role !== 'string') return false;
            if (m.navigation?.type === 'navigation' && m.navigation.route) return true;
            return typeof m.content === 'string';
        });
        return valid ? parsed : null;
    } catch {
        return null;
    }
}

function saveMessages(messages) {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(messages));
    } catch {
        // ignore
    }
}

function NavigationAction({ navigation }) {
    return (
        <button
            type="button"
            onClick={() => router.visit(navigation.route)}
            className="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-700 transition shadow-sm"
        >
            <ExternalLink className="h-4 w-4 shrink-0" />
            {navigation.label || 'Ouvrir la page'}
        </button>
    );
}

export default function CommerceAssistant({ bottomOffset = null }) {
    const { auth } = usePage().props;
    const userDisplayName = auth?.user?.name || auth?.user?.email || '';
    const permissions = auth?.permissions ?? [];
    const hasCommerce =
        permissions.includes('*') ||
        permissions.includes('module.commerce') ||
        permissions.includes('commerce.assistant.use') ||
        permissions.some((p) => typeof p === 'string' && p.startsWith('commerce.'));
    const hasCommerceVoice =
        permissions.includes('*') ||
        permissions.includes('commerce.assistant.voice') ||
        permissions.includes('commerce.assistant.use');

    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState(() => {
        const stored = loadStoredMessages();
        return stored ?? [buildWelcomeMessage(userDisplayName)];
    });
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [recording, setRecording] = useState(false);
    const [transcribing, setTranscribing] = useState(false);
    const [speaking, setSpeaking] = useState(false);
    const [voiceStatus, setVoiceStatus] = useState(null);
    const [voiceSettings, setVoiceSettings] = useState(DEFAULT_VOICE_SETTINGS);
    const [showVoiceSettings, setShowVoiceSettings] = useState(false);
    const messagesEndRef = useRef(null);
    const mediaRecorderRef = useRef(null);
    const chunksRef = useRef([]);
    const currentAudioRef = useRef(null);
    const buttonRef = useRef(null);

    const userMessageCount = useMemo(
        () => messages.filter((m) => m.role === 'user').length,
        [messages]
    );

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, loading, transcribing, speaking, open]);

    useEffect(() => {
        if (!loading && messages.length > 0) {
            saveMessages(messages);
        }
    }, [messages, loading]);

    useEffect(() => {
        if (open && hasCommerceVoice) {
            axios
                .get(route('commerce.api.voice.settings'))
                .then(({ data }) => setVoiceSettings((prev) => ({ ...prev, ...data })))
                .catch(() => {});
        }
    }, [open, hasCommerceVoice]);

    useEffect(() => {
        if (!buttonRef.current || !bottomOffset) return;
        const button = buttonRef.current;
        const mobileQuery = window.matchMedia('(max-width: 639px)');
        const updatePosition = () => {
            button.style.bottom = mobileQuery.matches ? bottomOffset.mobile : bottomOffset.desktop;
        };
        updatePosition();
        mobileQuery.addEventListener('change', updatePosition);
        return () => mobileQuery.removeEventListener('change', updatePosition);
    }, [bottomOffset]);

    const buildHistoryPayload = () =>
        messages
            .filter((m) => m.id !== 'welcome')
            .filter((m) => typeof m.content === 'string' && m.content.trim() !== '')
            .slice(-6)
            .map((m) => ({ role: m.role, content: m.content.trim() }));

    const clearChat = () => {
        if (currentAudioRef.current) {
            currentAudioRef.current.pause();
            currentAudioRef.current = null;
        }
        setSpeaking(false);
        setMessages([buildWelcomeMessage(userDisplayName)]);
        setInput('');
        setVoiceStatus(null);
        try {
            sessionStorage.removeItem(STORAGE_KEY);
        } catch {
            // ignore
        }
    };

    const handleSend = async (textOverride = null) => {
        const text = (textOverride ?? input.trim()).trim();
        if (!text || loading) return;

        if (!textOverride) setInput('');
        const history = buildHistoryPayload();
        setMessages((prev) => [...prev, { role: 'user', content: text, id: `u-${Date.now()}` }]);
        setLoading(true);
        setVoiceStatus(null);

        try {
            const { data } = await axios.post(route('commerce.assistant.ask'), {
                message: text,
                history,
            });
            if (data.navigation?.type === 'navigation' && data.navigation.route) {
                setMessages((prev) => [
                    ...prev,
                    { role: 'assistant', content: null, navigation: data.navigation, id: `n-${Date.now()}` },
                ]);
            } else {
                const answer = data.answer ?? 'Aucune réponse disponible.';
                setMessages((prev) => [...prev, { role: 'assistant', content: answer, id: `a-${Date.now()}` }]);
                if (
                    hasCommerceVoice &&
                    voiceSettings.voice_enabled &&
                    answer &&
                    !data.navigation
                ) {
                    setSpeaking(true);
                    setVoiceStatus('Synthèse vocale…');
                    try {
                        const speakRes = await axios.post(route('commerce.api.voice.speak'), {
                            text: answer,
                            voice: voiceSettings.voice_type,
                            speed: voiceSettings.voice_speed,
                        });
                        if (speakRes.data.audio_url && !speakRes.data.sensitive && voiceSettings.auto_play) {
                            if (currentAudioRef.current) {
                                currentAudioRef.current.pause();
                                currentAudioRef.current = null;
                            }
                            const audio = new Audio(speakRes.data.audio_url);
                            currentAudioRef.current = audio;
                            audio.onended = () => {
                                setSpeaking(false);
                                setVoiceStatus(null);
                                currentAudioRef.current = null;
                            };
                            audio.onerror = () => {
                                setSpeaking(false);
                                setVoiceStatus(null);
                            };
                            await audio.play().catch(() => {
                                setSpeaking(false);
                                setVoiceStatus(null);
                            });
                        } else {
                            setSpeaking(false);
                            setVoiceStatus(null);
                        }
                    } catch (err) {
                        const msg = err.response?.data?.message || err.message || 'Erreur de synthèse vocale.';
                        setMessages((prev) => [
                            ...prev,
                            {
                                role: 'assistant',
                                content: `Synthèse vocale indisponible : ${msg}`,
                                id: `ve-${Date.now()}`,
                            },
                        ]);
                        setSpeaking(false);
                        setVoiceStatus(null);
                    }
                }
            }
        } catch (err) {
            const message = err.response?.data?.message || err.message || 'Erreur de connexion.';
            setMessages((prev) => [
                ...prev,
                { role: 'assistant', content: message, id: `err-${Date.now()}` },
            ]);
        } finally {
            setLoading(false);
        }
    };

    const startRecording = async () => {
        if (!hasCommerceVoice) return;
        if (typeof window !== 'undefined' && !window.isSecureContext) {
            setMessages((prev) => [
                ...prev,
                {
                    role: 'assistant',
                    content:
                        "Le micro nécessite une connexion sécurisée (HTTPS). Ouvrez l'application en HTTPS pour utiliser la dictée vocale.",
                    id: `mic-${Date.now()}`,
                },
            ]);
            return;
        }
        if (typeof navigator === 'undefined' || !navigator.mediaDevices?.getUserMedia) {
            setMessages((prev) => [
                ...prev,
                {
                    role: 'assistant',
                    content: 'Micro non supporté sur ce navigateur. Utilisez Chrome, Edge ou Firefox récent.',
                    id: `mic-${Date.now()}`,
                },
            ]);
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const recorder = new MediaRecorder(stream);
            chunksRef.current = [];
            recorder.ondataavailable = (e) => {
                if (e.data.size > 0) chunksRef.current.push(e.data);
            };
            recorder.onstop = async () => {
                stream.getTracks().forEach((t) => t.stop());
                const blob = new Blob(chunksRef.current, { type: 'audio/webm' });
                if (blob.size < 1000) {
                    setTranscribing(false);
                    return;
                }
                setTranscribing(true);
                setVoiceStatus('Transcription…');
                const form = new FormData();
                form.append('audio', blob, 'recording.webm');
                try {
                    const { data } = await axios.post(route('commerce.api.voice.transcribe'), form, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                        timeout: 15000,
                    });
                    const transcript = (data.transcript || '').trim();
                    setVoiceStatus(null);
                    setTranscribing(false);
                    if (transcript) {
                        await handleSend(transcript);
                    } else {
                        setMessages((prev) => [
                            ...prev,
                            { role: 'assistant', content: 'Aucune parole détectée. Réessayez.', id: `tr-${Date.now()}` },
                        ]);
                    }
                } catch (err) {
                    const msg = err.response?.data?.message || err.message || 'Erreur de transcription.';
                    setMessages((prev) => [...prev, { role: 'assistant', content: msg, id: `tr-${Date.now()}` }]);
                    setVoiceStatus(null);
                    setTranscribing(false);
                }
            };
            mediaRecorderRef.current = recorder;
            recorder.start(1000);
            setRecording(true);
        } catch (err) {
            let msg = "Impossible d'accéder au micro. Vérifiez les autorisations du navigateur.";
            if (err?.name === 'NotAllowedError' || err?.name === 'PermissionDeniedError') {
                msg = "Accès au micro refusé. Autorisez le micro pour ce site dans les paramètres du navigateur.";
            }
            setMessages((prev) => [...prev, { role: 'assistant', content: msg, id: `mic-${Date.now()}` }]);
        }
    };

    const stopRecording = () => {
        if (mediaRecorderRef.current && recording) {
            mediaRecorderRef.current.stop();
            mediaRecorderRef.current = null;
            setRecording(false);
        }
    };

    const saveVoiceSettings = () => {
        axios
            .put(route('commerce.api.voice.settings.update'), voiceSettings)
            .then(({ data }) => {
                setVoiceSettings((prev) => ({ ...prev, ...data }));
                setShowVoiceSettings(false);
            })
            .catch(() => {});
    };

    const busy = loading || recording || transcribing || speaking;
    const showSuggestions = userMessageCount === 0 && !busy;

    if (!hasCommerce) return null;

    return (
        <>
            {!open && (
                <button
                    ref={buttonRef}
                    type="button"
                    onClick={() => setOpen(true)}
                    className="fixed right-4 sm:right-6 z-40 flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-full bg-violet-600 text-white shadow-lg shadow-violet-600/30 transition hover:scale-105 hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-400 focus:ring-offset-2"
                    style={!bottomOffset ? { bottom: '96px' } : {}}
                    title="Assistant Commerce"
                    aria-label="Ouvrir l'assistant commerce"
                >
                    <Bot className="h-6 w-6 sm:h-7 sm:w-7" />
                </button>
            )}

            {open && (
                <div
                    className="fixed inset-0 z-50 flex flex-col bg-white dark:bg-slate-950 sm:inset-auto sm:bottom-6 sm:right-6 sm:left-auto sm:top-auto sm:h-[min(560px,90vh)] sm:w-[min(420px,calc(100vw-2rem))] sm:rounded-2xl sm:border sm:border-slate-200/90 dark:sm:border-slate-700 sm:shadow-2xl overflow-hidden"
                    role="dialog"
                    aria-label="Assistant Commerce"
                >
                    <div className="flex items-center justify-between gap-2 px-4 py-3 text-white shrink-0" style={HEADER_GRADIENT}>
                        <div className="flex items-center gap-2.5 min-w-0">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                <Bot className="h-5 w-5" />
                            </span>
                            <div className="min-w-0">
                                <p className="text-sm font-semibold truncate">Assistant Commerce</p>
                                <p className="text-[11px] text-violet-50/90 flex items-center gap-1">
                                    <Sparkles className="h-3 w-3" />
                                    Ventes · stock · navigation · IA
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-0.5 shrink-0">
                            {hasCommerceVoice && (
                                <button
                                    type="button"
                                    onClick={() => setShowVoiceSettings(true)}
                                    className="rounded-lg p-1.5 hover:bg-white/15 transition"
                                    title="Paramètres voix"
                                    aria-label="Paramètres voix"
                                >
                                    <Settings className="h-5 w-5" />
                                </button>
                            )}
                            <button
                                type="button"
                                onClick={() => setOpen(false)}
                                className="rounded-lg p-1.5 hover:bg-white/15 transition"
                                aria-label="Fermer"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div className="flex-1 overflow-y-auto px-3 py-3 space-y-3 min-h-0 bg-slate-50/50 dark:bg-slate-900/30">
                        {messages.map((msg, i) => (
                            <div
                                key={msg.id ?? i}
                                className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
                            >
                                <div className="max-w-[88%]">
                                    <div
                                        className={`rounded-2xl px-3 py-2 text-sm leading-relaxed whitespace-pre-wrap ${
                                            msg.role === 'user'
                                                ? 'rounded-br-md bg-violet-600 text-white'
                                                : 'rounded-bl-md bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 shadow-sm'
                                        }`}
                                    >
                                        {msg.navigation?.type === 'navigation' ? (
                                            <div className="space-y-2">
                                                <p className="text-xs text-slate-600 dark:text-slate-300">
                                                    Page recommandée :
                                                </p>
                                                <NavigationAction navigation={msg.navigation} />
                                            </div>
                                        ) : (
                                            msg.content
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}

                        {(loading || transcribing) && (
                            <div className="flex justify-start">
                                <div className="inline-flex items-center gap-2 rounded-2xl rounded-bl-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm text-slate-500 shadow-sm">
                                    <Loader2 className="h-4 w-4 animate-spin text-violet-600" />
                                    {transcribing ? 'Transcription en cours…' : 'Analyse en cours…'}
                                </div>
                            </div>
                        )}

                        {speaking && (
                            <div className="flex justify-start">
                                <div className="inline-flex items-center gap-2 rounded-2xl rounded-bl-md bg-violet-50 dark:bg-violet-950/40 border border-violet-200 dark:border-violet-800 px-3 py-2 text-sm text-violet-800 dark:text-violet-200">
                                    <Volume2 className="h-4 w-4 animate-pulse" />
                                    Lecture vocale…
                                </div>
                            </div>
                        )}

                        {voiceStatus && !loading && !transcribing && !speaking && (
                            <p className="text-center text-[11px] text-slate-500">{voiceStatus}</p>
                        )}
                        <div ref={messagesEndRef} />
                    </div>

                    {showSuggestions && (
                        <div className="px-3 pb-2 flex flex-wrap gap-1.5 bg-slate-50/50 dark:bg-slate-900/30">
                            {SUGGESTIONS.map((label) => (
                                <button
                                    key={label}
                                    type="button"
                                    onClick={() => handleSend(label)}
                                    className="rounded-full border border-violet-200 dark:border-violet-800 bg-white dark:bg-slate-900 px-2.5 py-1 text-[11px] font-medium text-violet-800 dark:text-violet-200 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition"
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                    )}

                    <div className="border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shrink-0">
                        <div className="p-2 flex gap-2 items-end">
                            {hasCommerceVoice &&
                                (recording ? (
                                    <button
                                        type="button"
                                        onClick={stopRecording}
                                        className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-500 text-white hover:bg-rose-600 transition"
                                        aria-label="Arrêter l'enregistrement"
                                    >
                                        <Square className="h-4 w-4 fill-current" />
                                    </button>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={startRecording}
                                        disabled={busy}
                                        className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:border-violet-400 hover:text-violet-700 disabled:opacity-50 transition"
                                        aria-label="Dictée vocale"
                                    >
                                        <Mic className="h-5 w-5" />
                                    </button>
                                ))}
                            <textarea
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        handleSend();
                                    }
                                }}
                                rows={1}
                                placeholder="Question sur ventes, stock, produits…"
                                className="flex-1 max-h-24 min-h-[40px] resize-none rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:border-violet-500 focus:ring-1 focus:ring-violet-500 disabled:opacity-50"
                                disabled={busy}
                            />
                            <button
                                type="button"
                                onClick={() => handleSend()}
                                disabled={busy || !input.trim()}
                                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-white hover:bg-violet-700 disabled:opacity-50 transition"
                                aria-label="Envoyer"
                            >
                                <Send className="h-4 w-4" />
                            </button>
                        </div>
                        <div className="px-3 pb-2 flex items-center justify-between gap-2">
                            <p className="text-[10px] text-slate-400 leading-snug">
                                Données de votre boutique · vérifiez les chiffres critiques.
                            </p>
                            <button
                                type="button"
                                onClick={clearChat}
                                disabled={busy}
                                className="inline-flex items-center gap-1 text-[10px] text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 shrink-0 disabled:opacity-50"
                            >
                                <RotateCcw className="h-3 w-3" />
                                Nouvelle conversation
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {showVoiceSettings && hasCommerceVoice && (
                <div
                    className="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                    onClick={() => setShowVoiceSettings(false)}
                    role="presentation"
                >
                    <div
                        className="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-sm w-full p-5 space-y-4 border border-slate-200 dark:border-slate-700"
                        onClick={(e) => e.stopPropagation()}
                        role="dialog"
                        aria-label="Paramètres vocaux"
                    >
                        <h3 className="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <Volume2 className="h-5 w-5 text-violet-600" />
                            Paramètres vocaux
                        </h3>
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={voiceSettings.voice_enabled}
                                onChange={(e) =>
                                    setVoiceSettings((s) => ({ ...s, voice_enabled: e.target.checked }))
                                }
                                className="rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                            />
                            <span className="text-sm text-slate-700 dark:text-slate-300">Synthèse vocale (TTS)</span>
                        </label>
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={voiceSettings.auto_play}
                                onChange={(e) => setVoiceSettings((s) => ({ ...s, auto_play: e.target.checked }))}
                                className="rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                            />
                            <span className="text-sm text-slate-700 dark:text-slate-300">Lecture auto des réponses</span>
                        </label>
                        <div>
                            <span className="text-xs font-medium text-slate-600 dark:text-slate-400">Voix</span>
                            <select
                                value={voiceSettings.voice_type}
                                onChange={(e) =>
                                    setVoiceSettings((s) => ({ ...s, voice_type: e.target.value }))
                                }
                                className="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm"
                            >
                                <option value="female">Féminine</option>
                                <option value="male">Masculine</option>
                            </select>
                        </div>
                        <div>
                            <span className="text-xs font-medium text-slate-600 dark:text-slate-400">
                                Vitesse : {voiceSettings.voice_speed}
                            </span>
                            <input
                                type="range"
                                min="0.5"
                                max="2"
                                step="0.1"
                                value={voiceSettings.voice_speed}
                                onChange={(e) =>
                                    setVoiceSettings((s) => ({
                                        ...s,
                                        voice_speed: parseFloat(e.target.value),
                                    }))
                                }
                                className="mt-1 w-full accent-violet-600"
                            />
                        </div>
                        <div>
                            <span className="text-xs font-medium text-slate-600 dark:text-slate-400">Langue</span>
                            <select
                                value={voiceSettings.language}
                                onChange={(e) =>
                                    setVoiceSettings((s) => ({ ...s, language: e.target.value }))
                                }
                                className="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm"
                            >
                                <option value="auto">Auto (FR/EN)</option>
                                <option value="fr">Français</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                        <div className="flex gap-2 pt-1">
                            <button
                                type="button"
                                onClick={saveVoiceSettings}
                                className="flex-1 rounded-xl bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-700"
                            >
                                Enregistrer
                            </button>
                            <button
                                type="button"
                                onClick={() => setShowVoiceSettings(false)}
                                className="rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
                            >
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
