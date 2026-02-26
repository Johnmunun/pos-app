# Assistant Intelligent Pharmacie

Chatbot intégré à l’ERP Pharmacie : réponses métier à partir du **contexte préparé par le backend** (pas de requêtes SQL par l’IA).

## Rôle

- Répondre aux questions **ventes** (ex. ventes du jour)
- Donner **statistiques** (stock, alertes, résumé dashboard)
- Expliquer la **navigation** (où trouver une page)
- Donner des **détails produit** (recherche légère par nom/code)
- Répondre de façon **courte et professionnelle** en français

## Règles côté backend

- Aucune requête lourde : agrégats limités, pas de scan complet.
- Contexte = ventes du jour + alertes stock + résumé dashboard + navigation (selon permissions).
- Si la question évoque un produit, une recherche légère (max 5 résultats) est ajoutée au contexte.
- L’IA **n’a accès qu’au JSON de contexte** envoyé par le backend (pas d’accès direct à la base).

## Configuration

### Fichier de config

- `config/pharmacy_assistant.php`
  - `system_prompt` : prompt système (par défaut depuis `PHARMACY_ASSISTANT_SYSTEM_PROMPT`).
  - `enabled` : activer/désactiver l’assistant (`PHARMACY_ASSISTANT_ENABLED`, défaut `true`).
  - `llm_driver` : `openai` ou `fallback` (`PHARMACY_ASSISTANT_LLM_DRIVER`).

### Variables d’environnement (.env)

```env
# Activer/désactiver l’assistant
PHARMACY_ASSISTANT_ENABLED=true

# Optionnel : prompt système personnalisé (sinon valeur par défaut en config)
# PHARMACY_ASSISTANT_SYSTEM_PROMPT="Tu es un assistant..."

# Pour réponses IA avancées : clé API OpenAI (sinon mode fallback)
OPENAI_API_KEY=sk-...
```

Sans `OPENAI_API_KEY`, l’assistant utilise le **mode fallback** : réponses basées sur des motifs (ventes du jour, stock, navigation, produit) à partir du même contexte.

## Utilisation

1. **Côté utilisateur** : bouton flottant (icône message) en bas à droite pour les utilisateurs avec accès au module Pharmacy.
2. **Questions possibles** (exemples) :
   - « Combien de ventes aujourd’hui ? »
   - « Où trouver les rapports ? »
   - « Donne-moi le détail du Paracétamol »
   - « Alertes stock »
   - « Résumé du dashboard »

## Sécurité

- Route protégée : `permission:module.pharmacy`.
- Le contexte ne contient que les données que l’utilisateur a le droit de voir (même shop, permissions prises en compte pour la navigation).

## Ce qui reste pour un assistant complet et très utile

### Contexte & réponses

| Priorité | Amélioration | Détail |
|----------|--------------|--------|
| Haute | **Liste des produits qui expirent bientôt** | Le contexte a `expiring_soon_count` mais pas la liste. Ajouter `products_expiring_soon` (ex. 10–15 entrées : nom, code, date expiration, jours restants) pour répondre à « Quels produits expirent bientôt ? ». |
| Haute | **Détail produit : extraction plus robuste** | Aujourd’hui la recherche produit est déclenchée par des mots-clés fixes (« détail du », « fiche », etc.). Élargir (ex. détecter tout nom de produit après « infos sur », « donne-moi », ou envoyer les 2–3 derniers mots significatifs) pour que « Paracétamol 500mg » ou « Doliprane » soit bien reconnu. |
| Moyenne | **Comparaison ventes** | Répondre à « Plus de ventes aujourd’hui qu’hier ? » en comparant `sales_today` et l’entrée d’hier dans `sales_last_30_days`. Données déjà en contexte ; ajouter une règle dans le prompt + un cas dans le fallback. |
| Moyenne | **Résumé avec devise** | Le résumé dashboard affiche la valeur du stock sans devise. Utiliser systématiquement `context.currency` pour la valeur du stock dans la réponse (prompt + fallback). |
| Basse | **Documenter products_matching dans le prompt** | Le champ `products_matching` (détail produit par nom/code) existe dans le contexte mais n’est pas décrit dans le system_prompt. L’ajouter pour que l’IA sache quand et comment l’utiliser. |

### UX (frontend)

| Priorité | Amélioration | Détail |
|----------|--------------|--------|
| Haute | **Suggestions de questions** | Afficher 4–6 questions cliquables (chips) sous le placeholder ou sous les messages : « Ventes aujourd’hui ? », « Produits en rupture ? », « Où sont les rapports ? », etc. Réduit la friction et montre les capacités. |
| Moyenne | **Historique en session** | Persister les messages dans `sessionStorage` (ou state + clé par onglet) pour ne pas perdre la conversation au rafraîchissement. |
| Basse | **Indicateur de source** | Afficher une mention discrète « Réponse basée sur les données du jour » quand `context_used: true`, et « Donnée non disponible » quand la réponse est générique. |

### Robustesse & perf

| Priorité | Amélioration | Détail |
|----------|--------------|--------|
| Moyenne | **Cache court pour le contexte** | Cache Redis (ou cache Laravel) 1–2 min sur le contexte (ventes du jour, alertes) pour limiter les requêtes en cas de questions répétées. |
| Basse | **Prompt en base (ROOT)** | Écran d’administration pour modifier le system_prompt sans toucher au fichier de config (optionnel). |

### Récap

- **Déjà en place** : ventes (jour, hier, date, total), stock (rupture, stock bas, alertes), navigation, détail produit (avec extraction limitée), résumé dashboard, devise dans les réponses.
- **Pour être « complet et très utile »** : ajouter la liste des produits qui expirent bientôt, améliorer la détection de la question produit, comparer aujourd’hui vs hier, suggestions de questions dans l’UI, et optionnellement historique de conversation + cache contexte.

## Mode vocal (STT + TTS)

- **Transcription (STT)** : `POST /pharmacy/api/voice/transcribe` — envoi d’un fichier audio (WebM/WAV, max 5 Mo). Réponse : `{ "transcript": "...", "language": "fr" }` (Whisper).
- **Synthèse vocale (TTS)** : `POST /pharmacy/api/voice/speak` — envoi `{ "text": "...", "voice": "female", "speed": 1.0 }`. Réponse : `{ "audio_url": "/storage/tts/..." }` ou `sensitive: true` si le texte ne doit pas être lu.
- **Paramètres par pharmacie** : table `pharmacy_assistant_settings` (voice_enabled, voice_type, voice_speed, auto_play, language). GET/PUT `pharmacy/api/voice/settings`.
- **Sécurité** : pas d’audio pour réponses contenant montants élevés, données RH/patients/banque (détection côté backend).
- **Quota** : 30 requêtes vocales / jour / pharmacie (configurable via `PHARMACY_ASSISTANT_VOICE_MAX_PER_DAY`). Fichiers TTS supprimés après 10 min (job `CleanupTtsFileJob`).
- **Prérequis** : `OPENAI_API_KEY` (Whisper + TTS), `php artisan storage:link` pour servir les MP3.

## Évolutions possibles (techniques)

- Stocker le prompt système en base (écran ROOT) et le charger depuis la config.
- Cache Redis pour le contexte (ex. ventes du jour) pour limiter les requêtes.
- Historique des échanges en session ou en base (optionnel).
