import React, { useState, useRef, useEffect } from 'react';
import { Lightbulb, Send, X, Loader2, Mic, Square, Volume2 } from 'lucide-react';
import axios from 'axios';
import { usePage, router } from '@inertiajs/react';

const STORAGE_KEY = 'hardware_assistant_messages';

const SUGGESTIONS = [
  'Produits en rupture ?',
  'Produits en stock bas ?',
  'Résumé quincaillerie ?',
  'Où est la page stock ?',
  'Détail sur un article (code) ?',
];

const INITIAL_MESSAGE = {
  role: 'assistant',
  content:
    "Je suis l'assistant Quincaillerie. Posez-moi une question sur les produits, le stock ou la navigation dans le module Hardware.",
};

function loadStoredMessages() {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed) || parsed.length === 0) return null;
    const valid = parsed.every((m) => m && typeof m.role === 'string' && (m.content || m.navigation));
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

export default function HardwareAssistant({ bottomOffset = null }) {
  const { auth } = usePage().props;
  const permissions = auth?.permissions ?? [];
  const hasHardware =
    permissions.includes('*') ||
    permissions.includes('module.hardware') ||
    permissions.some((p) => typeof p === 'string' && p.startsWith('hardware.'));
  const hasHardwareVoice =
    permissions.includes('*') ||
    permissions.includes('hardware.assistant.voice') ||
    permissions.includes('hardware.assistant.use');

  const [open, setOpen] = useState(false);
  const [messages, setMessages] = useState(() => loadStoredMessages() || [INITIAL_MESSAGE]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const messagesEndRef = useRef(null);
  const [recording, setRecording] = useState(false);
  const [transcribing, setTranscribing] = useState(false);
  const [speaking, setSpeaking] = useState(false);
  const [voiceStatus, setVoiceStatus] = useState(null);
  const mediaRecorderRef = useRef(null);
  const chunksRef = useRef([]);
  const currentAudioRef = useRef(null);
  const buttonRef = useRef(null);

  useEffect(() => {
    if (!loading && messages.length > 0) {
      saveMessages(messages);
    }
  }, [messages, loading]);

  // Appliquer les styles avec media queries pour le positionnement
  useEffect(() => {
    if (buttonRef.current && bottomOffset) {
      const button = buttonRef.current;
      const mobileQuery = window.matchMedia('(max-width: 639px)');
      const updatePosition = () => {
        if (mobileQuery.matches) {
          button.style.bottom = bottomOffset.mobile;
        } else {
          button.style.bottom = bottomOffset.desktop;
        }
      };
      updatePosition();
      mobileQuery.addEventListener('change', updatePosition);
      return () => mobileQuery.removeEventListener('change', updatePosition);
    }
  }, [bottomOffset]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, open]);

  if (!hasHardware) {
    return null;
  }

  const buildMessageWithHistory = (currentText) => {
    const recent = messages.slice(-6);
    if (recent.length === 0) return currentText;
    const historyLines = recent
      .filter((m) => typeof m.content === 'string' && m.content.trim() !== '')
      .map((m) => `${m.role === 'user' ? 'Utilisateur' : 'Assistant'}: ${m.content.trim()}`);
    if (historyLines.length === 0) return currentText;
    return `Contexte de la conversation (historique récent):\n${historyLines.join(
      '\n',
    )}\n\nQuestion actuelle: ${currentText}`;
  };

  const handleSend = async (textOverride = null) => {
    const text = (textOverride ?? input.trim()).trim();
    if (!text || loading) return;

    if (!textOverride) setInput('');
    setMessages((prev) => [...prev, { role: 'user', content: text }]);
    setLoading(true);

    try {
      const messageForBackend = buildMessageWithHistory(text);
      const { data } = await axios.post(route('hardware.assistant.ask'), { message: messageForBackend });
      if (data.navigation && data.navigation.type === 'navigation' && data.navigation.route) {
        setMessages((prev) => [...prev, { role: 'assistant', content: null, navigation: data.navigation }]);
      } else {
        const answer = data.answer ?? 'Aucune réponse.';
        setMessages((prev) => [...prev, { role: 'assistant', content: answer }]);

        // Mode audio (TTS) si autorisé
        if (hasHardwareVoice && answer && !data.navigation) {
          setSpeaking(true);
          setVoiceStatus("Lecture de la réponse…");
          try {
            const speakRes = await axios.post(route('hardware.api.voice.speak'), {
              text: answer,
              voice: 'female',
              speed: 1.0,
            });
            const audioUrl = speakRes.data?.audio_url;
            if (audioUrl) {
              if (currentAudioRef.current) {
                currentAudioRef.current.pause();
                currentAudioRef.current = null;
              }
              const audio = new Audio(audioUrl);
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
              setSpeaking(false);
              setVoiceStatus(null);
            }
          } catch (err) {
            setMessages((prev) => [
              ...prev,
              { role: 'assistant', content: `Synthèse vocale non disponible : ${err.message || 'Erreur.'}` },
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

  const handleSuggestion = (s) => {
    handleSend(s);
  };

  const handleNavigationClick = (nav) => {
    if (!nav || !nav.route) return;
    router.visit(nav.route, { method: nav.method || 'GET' });
  };

  const startRecording = async () => {
    if (!hasHardwareVoice) {
      return;
    }
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
          const { data } = await axios.post(route('hardware.api.voice.transcribe'), form, {
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

  return (
    <>
      {/* Bouton flottant */}
      <button
        ref={buttonRef}
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        className="fixed right-4 sm:right-6 z-40 inline-flex items-center justify-center rounded-full bg-violet-600 text-white shadow-lg hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500"
        style={{ width: 52, height: 52, ...(!bottomOffset ? { bottom: '16px' } : {}) }}
        title="Assistant Quincaillerie"
        aria-label="Ouvrir l'assistant Quincaillerie"
      >
        <Lightbulb className="h-6 w-6" />
      </button>

      {/* Panneau assistant */}
      {open && (
        <div className="fixed bottom-20 right-4 z-40 w-full max-w-md bg-white dark:bg-gray-900 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col">
          <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div>
              <p className="text-sm font-semibold text-gray-900 dark:text-white">Assistant Quincaillerie</p>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                Questions sur les produits, le stock et la navigation Hardware.
              </p>
            </div>
            <button
              type="button"
              onClick={() => setOpen(false)}
              className="rounded-full p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
            >
              <X className="h-4 w-4" />
            </button>
          </div>

          <div className="flex-1 overflow-y-auto max-h-80 px-4 py-3 space-y-3 text-sm">
            {messages.map((m, idx) => (
              <div key={idx} className={`flex ${m.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                <div
                  className={`rounded-lg px-3 py-2 max-w-[85%] whitespace-pre-wrap ${
                    m.role === 'user'
                      ? 'bg-amber-500 text-white'
                      : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100'
                  }`}
                >
                  {m.navigation ? (
                    <button
                      type="button"
                      onClick={() => handleNavigationClick(m.navigation)}
                      className="underline text-blue-600 dark:text-blue-400"
                    >
                      Aller vers : {m.navigation.label ?? m.navigation.route}
                    </button>
                  ) : (
                    m.content
                  )}
                </div>
              </div>
            ))}
            <div ref={messagesEndRef} />
          </div>

          <div className="px-4 pb-3">
            <div className="flex flex-wrap gap-2 mb-2">
              {SUGGESTIONS.map((s) => (
                <button
                  key={s}
                  type="button"
                  onClick={() => handleSuggestion(s)}
                  className="text-xs px-2 py-1 rounded-full border border-amber-200 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900/40"
                >
                  {s}
                </button>
              ))}
            </div>
            <form
              onSubmit={(e) => {
                e.preventDefault();
                handleSend();
              }}
              className="flex items-center gap-2"
            >
              {hasHardwareVoice && (
                <button
                  type="button"
                  onClick={recording ? stopRecording : startRecording}
                  className={`inline-flex items-center justify-center rounded-md border px-2 py-2 ${
                    recording
                      ? 'border-red-500 text-red-600 hover:bg-red-50'
                      : 'border-gray-300 text-gray-600 hover:bg-gray-50'
                  }`}
                  title={recording ? 'Arrêter l’enregistrement' : 'Parler au micro'}
                >
                  {recording ? <Square className="h-4 w-4" /> : <Mic className="h-4 w-4" />}
                </button>
              )}
              <input
                type="text"
                value={input}
                onChange={(e) => setInput(e.target.value)}
                placeholder="Posez une question sur la quincaillerie…"
                className="flex-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-2 py-1.5 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-amber-500"
              />
              <button
                type="submit"
                disabled={loading || !input.trim()}
                className="inline-flex items-center justify-center rounded-md bg-amber-500 text-white px-3 py-2 text-sm font-medium hover:bg-amber-600 disabled:opacity-60"
              >
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
              </button>
            </form>
            {hasHardwareVoice && (voiceStatus || speaking || transcribing) && (
              <div className="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <Volume2 className="h-3 w-3" />
                <span>{voiceStatus || (speaking ? 'Lecture en cours…' : transcribing ? 'Transcription en cours…' : '')}</span>
              </div>
            )}
          </div>
        </div>
      )}
    </>
  );
}

