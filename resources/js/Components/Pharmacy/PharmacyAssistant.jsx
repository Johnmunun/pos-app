import React, { useState, useRef, useEffect } from 'react';
import { MessageCircle, Send, X, Loader2, ExternalLink, Mic, Square, Settings, Volume2 } from 'lucide-react';
import axios from 'axios';
import { usePage, router } from '@inertiajs/react';

const STORAGE_KEY = 'pharmacy_assistant_messages';

const SUGGESTIONS = [
  'Ventes aujourd\'hui ?',
  'Produits en rupture ?',
  'Quels produits expirent bientôt ?',
  'Où sont les rapports ?',
  'Valeur totale du stock ?',
  'Ventes d\'hier ?',
];

const INITIAL_MESSAGE = {
  role: 'assistant',
  content: "Je suis l'assistant Pharmacie. Posez-moi une question sur les ventes, le stock, la navigation ou un produit.",
};

const DEFAULT_VOICE_SETTINGS = {
  voice_enabled: true,
  voice_type: 'female',
  voice_speed: 1.0,
  auto_play: true,
  language: 'auto',
};

function loadStoredMessages() {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed) || parsed.length === 0) return null;
    const valid = parsed.every((m) => {
      if (!m || typeof m.role !== 'string') return false;
      if (m.navigation && m.navigation.type === 'navigation' && m.navigation.route) return true;
      return typeof m.content === 'string';
    });
    if (valid) return parsed;
  } catch (_) {
    // ignore
  }
  return null;
}

function saveMessages(messages) {
  try {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(messages));
  } catch (_) {
    // ignore
  }
}

export default function PharmacyAssistant() {
  const { auth } = usePage().props;
  const permissions = auth?.permissions ?? [];
  const hasPharmacy = permissions.includes('*') || permissions.includes('module.pharmacy')
    || permissions.some((p) => typeof p === 'string' && p.startsWith('pharmacy.'));

  const [open, setOpen] = useState(false);
  const [messages, setMessages] = useState(() => loadStoredMessages() || [INITIAL_MESSAGE]);
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

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  useEffect(() => {
    if (!loading && messages.length > 0) {
      saveMessages(messages);
    }
  }, [messages, loading]);

  useEffect(() => {
    if (open && hasPharmacy) {
      axios.get(route('pharmacy.api.voice.settings')).then(({ data }) => {
        setVoiceSettings((prev) => ({ ...prev, ...data }));
      }).catch(() => {});
    }
  }, [open, hasPharmacy]);

  const handleSend = async (textOverride = null) => {
    const text = (textOverride ?? input.trim()).trim();
    if (!text || loading) return;

    if (!textOverride) setInput('');
    setMessages((prev) => [...prev, { role: 'user', content: text }]);
    setLoading(true);
    setVoiceStatus(null);

    try {
      const { data } = await axios.post(route('pharmacy.assistant.ask'), { message: text });
      if (data.navigation && data.navigation.type === 'navigation' && data.navigation.route) {
        setMessages((prev) => [...prev, { role: 'assistant', content: null, navigation: data.navigation }]);
      } else {
        const answer = data.answer ?? 'Aucune réponse.';
        setMessages((prev) => [...prev, { role: 'assistant', content: answer }]);
        if (voiceSettings.voice_enabled && answer && !data.navigation) {
          setSpeaking(true);
          setVoiceStatus('Assistant parle…');
          try {
            const speakRes = await axios.post(route('pharmacy.api.voice.speak'), {
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
              audio.play().catch(() => {
                setSpeaking(false);
                setVoiceStatus(null);
              });
            } else {
              // Si pas d'audio retourné, afficher le message d'erreur/sensibilité si disponible
              const msg = speakRes.data?.message;
              if (msg) {
                setMessages((prev) => [
                  ...prev,
                  {
                    role: 'assistant',
                    content: `Synthèse vocale non disponible : ${msg}`,
                  },
                ]);
              }
              setSpeaking(false);
              setVoiceStatus(null);
            }
          } catch (err) {
            const msg = err.response?.data?.message || err.message || 'Erreur de synthèse vocale.';
            setMessages((prev) => [
              ...prev,
              { role: 'assistant', content: `Synthèse vocale non disponible : ${msg}` },
            ]);
            setSpeaking(false);
            setVoiceStatus(null);
          }
        }
      }
    } catch (err) {
      const message = err.response?.data?.message || err.message || 'Erreur de connexion.';
      setMessages((prev) => [...prev, { role: 'assistant', content: `Erreur : ${message}` }]);
    } finally {
      setLoading(false);
    }
  };

  const startRecording = async () => {
    // Vérifier le contexte sécurisé (HTTPS) et le support navigateur
    if (typeof window !== 'undefined' && !window.isSecureContext) {
      setMessages((prev) => [
        ...prev,
        {
          role: 'assistant',
          content:
            "Le navigateur ne permet pas d'accéder au micro depuis une page non sécurisée (HTTP). Ouvrez l'application en HTTPS (ou via un tunnel sécurisé) pour activer le micro.",
        },
      ]);
      return;
    }
    if (typeof navigator === 'undefined' || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      setMessages((prev) => [
        ...prev,
        {
          role: 'assistant',
          content:
            "La capture audio n'est pas supportée dans ce navigateur. Utilisez un navigateur moderne (Chrome, Edge, Firefox, Safari récent).",
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
        setVoiceStatus('Transcription en cours…');
        const form = new FormData();
        form.append('audio', blob, 'recording.webm');
        try {
          const { data } = await axios.post(route('pharmacy.api.voice.transcribe'), form, {
            headers: { 'Content-Type': 'multipart/form-data' },
            timeout: 15000,
          });
          const transcript = (data.transcript || '').trim();
          setVoiceStatus(null);
          setTranscribing(false);
          if (transcript) {
            await handleSend(transcript);
          } else {
            setMessages((prev) => [...prev, { role: 'assistant', content: 'Aucune parole détectée. Réessayez.' }]);
          }
        } catch (err) {
          const msg = err.response?.data?.message || err.message || 'Erreur de transcription.';
          setMessages((prev) => [...prev, { role: 'assistant', content: `Vocal : ${msg}` }]);
          setVoiceStatus(null);
          setTranscribing(false);
        }
      };
      mediaRecorderRef.current = recorder;
      recorder.start(1000);
      setRecording(true);
    } catch (err) {
      let msg =
        "Impossible d'accéder au micro. Vérifiez les autorisations dans votre navigateur puis réessayez.";
      if (err?.name === 'NotAllowedError' || err?.name === 'PermissionDeniedError') {
        msg =
          "L'accès au micro a été refusé. Autorisez le micro dans les paramètres du navigateur puis réessayez.";
      }
      setMessages((prev) => [...prev, { role: 'assistant', content: msg }]);
    }
  };

  const stopRecording = () => {
    if (mediaRecorderRef.current && recording) {
      mediaRecorderRef.current.stop();
      mediaRecorderRef.current = null;
      setRecording(false);
    }
  };

  const handleSuggestionClick = (suggestion) => {
    setInput(suggestion);
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  const saveVoiceSettings = () => {
    axios.put(route('pharmacy.api.voice.settings.update'), voiceSettings).then(({ data }) => {
      setVoiceSettings((prev) => ({ ...prev, ...data }));
      setShowVoiceSettings(false);
    }).catch(() => {});
  };

  const busy = loading || recording || transcribing || speaking;

  if (!hasPharmacy) return null;

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="fixed right-4 bottom-24 sm:bottom-6 sm:right-6 z-40 flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-full bg-amber-500 text-white shadow-lg transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400"
        title="Assistant Pharmacie"
        aria-label="Ouvrir l'assistant"
      >
        <MessageCircle className="h-6 w-6 sm:h-7 sm:w-7" />
      </button>

      {open && (
        <div className="fixed inset-0 z-50 flex flex-col bg-white dark:bg-gray-900 sm:inset-auto sm:bottom-6 sm:right-6 sm:left-auto sm:top-auto sm:h-[420px] sm:w-[380px] sm:rounded-xl sm:shadow-xl">
          <div className="flex items-center justify-between border-b border-gray-200 bg-amber-500 px-4 py-3 dark:border-gray-700 dark:bg-amber-600">
            <span className="font-semibold text-white">Assistant Pharmacie</span>
            <div className="flex items-center gap-1">
              <button
                type="button"
                onClick={() => setShowVoiceSettings(true)}
                className="rounded p-1.5 text-white hover:bg-amber-600 dark:hover:bg-amber-700"
                title="Paramètres voix"
                aria-label="Paramètres voix"
              >
                <Settings className="h-5 w-5" />
              </button>
              <button
                type="button"
                onClick={() => setOpen(false)}
                className="rounded p-1 text-white hover:bg-amber-600 dark:hover:bg-amber-700"
                aria-label="Fermer"
              >
                <X className="h-5 w-5" />
              </button>
            </div>
          </div>

          <div className="flex-1 overflow-y-auto p-4 space-y-3">
            {messages.map((msg, i) => (
              <div
                key={i}
                className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
              >
                <div
                  className={`max-w-[85%] rounded-lg px-3 py-2 text-sm ${
                    msg.role === 'user'
                      ? 'bg-amber-500 text-white'
                      : 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-gray-100'
                  }`}
                >
                  {msg.navigation && msg.navigation.type === 'navigation' ? (
                    <button
                      type="button"
                      onClick={() => router.visit(msg.navigation.route)}
                      className="inline-flex items-center gap-2 rounded-md bg-amber-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-1 dark:focus:ring-offset-gray-800"
                    >
                      <ExternalLink className="h-4 w-4 shrink-0" />
                      {msg.navigation.label || 'Aller'}
                    </button>
                  ) : (
                    msg.content
                  )}
                </div>
              </div>
            ))}
            {(loading || transcribing) && (
              <div className="flex justify-start">
                <div className="rounded-lg bg-gray-100 px-3 py-2 dark:bg-gray-700 flex items-center gap-2">
                  <Loader2 className="h-5 w-5 shrink-0 animate-spin text-amber-500" />
                  <span className="text-xs text-gray-600 dark:text-gray-400">
                    {transcribing ? 'Transcription en cours…' : 'Réflexion…'}
                  </span>
                </div>
              </div>
            )}
            {speaking && (
              <div className="flex justify-start">
                <div className="rounded-lg bg-amber-50 dark:bg-amber-900/20 px-3 py-2 flex items-center gap-2 border border-amber-200 dark:border-amber-800">
                  <Volume2 className="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400 animate-pulse" />
                  <span className="text-xs text-amber-800 dark:text-amber-200">Assistant parle…</span>
                </div>
              </div>
            )}
            {voiceStatus && !loading && !transcribing && (
              <div className="text-center text-xs text-gray-500 dark:text-gray-400">{voiceStatus}</div>
            )}
            <div ref={messagesEndRef} />
          </div>

          <div className="border-t border-gray-200 p-3 dark:border-gray-700 space-y-2">
            <div className="flex flex-wrap gap-1.5">
              {SUGGESTIONS.map((label) => (
                <button
                  key={label}
                  type="button"
                  onClick={() => handleSuggestionClick(label)}
                  disabled={busy}
                  className="rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200 px-2.5 py-1 text-xs font-medium hover:bg-amber-100 dark:hover:bg-amber-900/30 border border-amber-200 dark:border-amber-800 disabled:opacity-50"
                >
                  {label}
                </button>
              ))}
            </div>
            <div className="flex gap-2 items-center">
              {recording ? (
                <button
                  type="button"
                  onClick={stopRecording}
                  className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600"
                  aria-label="Arrêter l'enregistrement"
                >
                  <Square className="h-5 w-5 fill-current" />
                </button>
              ) : (
                <button
                  type="button"
                  onClick={startRecording}
                  disabled={loading || transcribing}
                  className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50"
                  aria-label="Parler (micro)"
                >
                  <Mic className="h-5 w-5" />
                </button>
              )}
              <input
                type="text"
                value={input}
                onChange={(e) => setInput(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder="Posez une question..."
                className="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                disabled={busy}
              />
              <button
                type="button"
                onClick={() => handleSend()}
                disabled={busy || !input.trim()}
                className="rounded-lg bg-amber-500 px-3 py-2 text-white hover:bg-amber-600 disabled:opacity-50 shrink-0"
                aria-label="Envoyer"
              >
                <Send className="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>
      )}

      {showVoiceSettings && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4" onClick={() => setShowVoiceSettings(false)}>
          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-4 space-y-4" onClick={(e) => e.stopPropagation()}>
            <h3 className="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <Volume2 className="h-5 w-5 text-amber-500" />
              Paramètres vocaux
            </h3>
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={voiceSettings.voice_enabled}
                onChange={(e) => setVoiceSettings((s) => ({ ...s, voice_enabled: e.target.checked }))}
                className="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
              />
              <span className="text-sm text-gray-700 dark:text-gray-300">Activer la voix (TTS)</span>
            </label>
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={voiceSettings.auto_play}
                onChange={(e) => setVoiceSettings((s) => ({ ...s, auto_play: e.target.checked }))}
                className="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
              />
              <span className="text-sm text-gray-700 dark:text-gray-300">Lecture automatique des réponses</span>
            </label>
            <div>
              <span className="text-sm text-gray-600 dark:text-gray-400">Voix</span>
              <select
                value={voiceSettings.voice_type}
                onChange={(e) => setVoiceSettings((s) => ({ ...s, voice_type: e.target.value }))}
                className="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
              >
                <option value="female">Féminine</option>
                <option value="male">Masculine</option>
              </select>
            </div>
            <div>
              <span className="text-sm text-gray-600 dark:text-gray-400">Vitesse : {voiceSettings.voice_speed}</span>
              <input
                type="range"
                min="0.5"
                max="2"
                step="0.1"
                value={voiceSettings.voice_speed}
                onChange={(e) => setVoiceSettings((s) => ({ ...s, voice_speed: parseFloat(e.target.value) }))}
                className="mt-1 w-full"
              />
            </div>
            <div>
              <span className="text-sm text-gray-600 dark:text-gray-400">Langue (détection)</span>
              <select
                value={voiceSettings.language}
                onChange={(e) => setVoiceSettings((s) => ({ ...s, language: e.target.value }))}
                className="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
              >
                <option value="auto">Auto (FR/EN)</option>
                <option value="fr">Français</option>
                <option value="en">English</option>
              </select>
            </div>
            <div className="flex gap-2 pt-2">
              <button
                type="button"
                onClick={saveVoiceSettings}
                className="flex-1 rounded-lg bg-amber-500 px-3 py-2 text-sm font-medium text-white hover:bg-amber-600"
              >
                Enregistrer
              </button>
              <button
                type="button"
                onClick={() => setShowVoiceSettings(false)}
                className="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
              >
                Annuler
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
