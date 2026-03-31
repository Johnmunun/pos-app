# React Native Mobile API Guide

## 1. Objectif

Ce document explique comment consommer l'API mobile Laravel depuis une application React Native (Expo), avec une approche robuste:

- authentification token (Sanctum)
- ordre des appels au demarrage
- gestion d'erreurs claire pour l'utilisateur
- pagination et sync incremental
- structure de code recommandee

Base API:

- `/api/v1/mobile`

Modules supportes:

- `pharmacy`
- `hardware`
- `commerce`

---

## 2. Prerequis backend

Avant de connecter l'app mobile:

- API deployee et accessible en HTTPS (production)
- route API active dans `bootstrap/app.php`
- CORS configure pour le client mobile
- table Sanctum `personal_access_tokens` presente
- compte utilisateur non ROOT

Important:

- les utilisateurs ROOT sont bloques sur login mobile

---

## 3. URLs selon environnement

Configurer une seule source pour la base URL.

Exemple:

- Android emulator: `http://10.0.2.2:8000/api/v1/mobile`
- iOS simulator: `http://127.0.0.1:8000/api/v1/mobile`
- device physique: `http://<IP_LOCALE_PC>:8000/api/v1/mobile`
- production: `https://api.votre-domaine.com/api/v1/mobile`

Ne jamais hardcoder l'URL dans les ecrans.

---

## 4. Installation mobile recommandee

Packages utiles:

- `axios`
- `expo-secure-store`
- `@react-native-community/netinfo`

Commandes:

```bash
npx expo install expo-secure-store @react-native-community/netinfo
npm install axios
```

---

## 5. Structure de code conseillee

```text
src/
  api/
    client.ts
    auth.api.ts
    bootstrap.api.ts
    modules/
      pharmacy.api.ts
      hardware.api.ts
      commerce.api.ts
  auth/
    token.storage.ts
    session.store.ts
  sync/
    sync.store.ts
    sync.service.ts
  errors/
    apiError.ts
```

---

## 6. Client HTTP central

Creer un client Axios unique:

- `baseURL` centralisee
- timeout (ex: 20s)
- `Authorization: Bearer <token>` automatique
- interceptors pour normaliser les erreurs

Exemple:

```ts
import axios from 'axios';
import * as SecureStore from 'expo-secure-store';

export const api = axios.create({
  baseURL: process.env.EXPO_PUBLIC_API_BASE_URL,
  timeout: 20000,
  headers: { Accept: 'application/json' },
});

api.interceptors.request.use(async (config) => {
  const token = await SecureStore.getItemAsync('auth_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
```

---

## 7. Auth flow (login/logout/me)

### Login

Endpoint:

- `POST /auth/login`

Payload:

```json
{
  "email": "user@example.com",
  "password": "secret"
}
```

Succes:

- stocker `token` dans `SecureStore`
- stocker `user` et `tenant` dans un state global

### Logout

Endpoint:

- `POST /auth/logout`

Bon comportement:

- appeler l'API si token present
- supprimer le token local meme si l'API echoue

### Me

Endpoint:

- `GET /auth/me`

Utilisation:

- au lancement app, si token existe, appeler `me` pour valider la session

---

## 8. Ordre des appels au demarrage (recommande)

1. Charger token depuis `SecureStore`
2. Si token absent -> ecran Login
3. Si token present -> `GET /auth/me`
4. Charger module actif (ou demander choix utilisateur)
5. `GET /bootstrap/{module}` (full sync)
6. Afficher Home POS
7. Lancer sync incremental en arriere-plan (timer/app resume)

---

## 9. Bootstrap et sync incremental

Endpoint:

- `GET /bootstrap/{module}?depot_id=&updated_since=`

Sans `updated_since`:

- `sync.mode = full`

Avec `updated_since`:

- `sync.mode = incremental`
- recuperer seulement les modifications
- appliquer `deleted_ids` pour retirer localement les enregistrements supprimes

Strategie:

- conserver `last_sync_at` par module
- apres succes sync, mettre `last_sync_at = sync.server_time`

---

## 10. Pagination standard

Les endpoints liste exposent:

- query: `limit`, `offset`
- response: `pagination.has_more`, `pagination.next_offset`

Boucle type:

1. `offset = 0`
2. fetch liste
3. concat data
4. si `has_more`, reprendre avec `next_offset`

---

## 11. Gestion d'erreurs API (format unifie)

L'API mobile renvoie maintenant des erreurs JSON normalisees.

Champs attendus:

- `message`
- `code` (quand disponible)
- `errors` (validation)

Codes frequents:

- `AUTH_INVALID_CREDENTIALS`
- `AUTH_SERVICE_UNAVAILABLE`
- `AUTH_TOKEN_STORE_MISSING`
- `AUTH_TOKEN_CREATE_FAILED`
- `VALIDATION_ERROR`
- `UNAUTHENTICATED`
- `FORBIDDEN`
- `NOT_FOUND`
- `METHOD_NOT_ALLOWED`
- `INTERNAL_SERVER_ERROR`

Mapping UI recommande:

- `401/UNAUTHENTICATED` -> "Session expiree, reconnectez-vous."
- `403/FORBIDDEN` -> "Vous n'avez pas la permission."
- `422/VALIDATION_ERROR` -> afficher le message API (champ par champ si utile)
- `503/*UNAVAILABLE*` -> "Serveur indisponible, reessayez dans un instant."
- `500/INTERNAL_SERVER_ERROR` -> "Une erreur technique est survenue."

---

## 12. Helper d'erreur pret a utiliser

```ts
import axios from 'axios';

export function normalizeApiError(error: unknown): string {
  if (!axios.isAxiosError(error)) return 'Erreur inattendue.';
  const status = error.response?.status;
  const data = error.response?.data as any;
  const code = data?.code as string | undefined;
  const message = data?.message as string | undefined;

  if (message) return message;

  if (status === 401 || code === 'UNAUTHENTICATED') return 'Session expiree, reconnectez-vous.';
  if (status === 403 || code === 'FORBIDDEN') return "Vous n'avez pas la permission.";
  if (status === 422 || code === 'VALIDATION_ERROR') return 'Donnees invalides.';
  if (status === 503) return 'Serveur indisponible, reessayez plus tard.';
  if (status === 500) return 'Erreur serveur, veuillez reessayer.';
  if (!error.response) return 'Impossible de joindre le serveur. Verifiez votre connexion.';

  return 'Operation echouee.';
}
```

---

## 13. Exemples de services API

### `auth.api.ts`

```ts
import { api } from './client';

export async function login(email: string, password: string) {
  const { data } = await api.post('/auth/login', { email, password });
  return data as {
    token: string;
    token_type: 'Bearer';
    user: any;
    tenant: any;
  };
}

export async function me() {
  const { data } = await api.get('/auth/me');
  return data;
}

export async function logout() {
  await api.post('/auth/logout');
}
```

### `bootstrap.api.ts`

```ts
import { api } from './client';

type ModuleName = 'pharmacy' | 'hardware' | 'commerce';

export async function getBootstrap(module: ModuleName, depotId?: number, updatedSince?: string) {
  const params: Record<string, string | number> = {};
  if (depotId) params.depot_id = depotId;
  if (updatedSince) params.updated_since = updatedSince;
  const { data } = await api.get(`/bootstrap/${module}`, { params });
  return data;
}
```

---

## 14. Bonnes pratiques offline

- garder un cache local (catalogue, clients, stock resume)
- marquer les actions offline (queue)
- rejouer les actions quand reseau revient
- detecter conflits via `updated_at`/refresh detail

Minimum conseille:

- lecture offline du bootstrap precedent
- blocage des actions critiques si pas reseau (ou queue explicite)

---

## 15. Checklist de test mobile

Auth:

- login OK
- login invalide -> message clair
- logout OK
- token expire -> redirection login

Bootstrap:

- full sync OK
- incremental sync OK
- `deleted_ids` applique

Modules:

- list endpoints pagines
- detail endpoints
- creation/modification principales
- erreurs permission claires

Robustesse:

- mode avion
- timeout serveur
- serveur down (503/500)

---

## 16. Troubleshooting rapide

`500 server unavailable` au login:

- verifier DB backend accessible
- verifier table `personal_access_tokens`
- verifier logs Laravel (`storage/logs/laravel.log`)

`401` apres login:

- verifier header `Authorization: Bearer <token>`
- verifier token bien sauvegarde et recharge

`403` sur endpoints module:

- verifier permissions de l'utilisateur
- verifier module/tenant/depot associes

`404` endpoint:

- verifier prefixe exact: `/api/v1/mobile/...`

---

## 17. References internes

- `docs/MOBILE_HANDOFF.md`
- `docs/MOBILE_REFERENCE.md`
- `docs/MOBILE_CHANGELOG.md`

