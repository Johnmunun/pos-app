# Mobile API Handoff (Expo Ready)

## 1) Can You Push and Close?

Yes.

- You can push this backend state now.
- You can close this project after push.
- Later, reopen and continue from docs without losing context.

Important: assistant chat memory is not guaranteed across sessions, so this file is the source of truth.

---

## 2) Current Backend Status

Mobile API is implemented under:

- Base: `/api/v1/mobile`
- Auth: Sanctum bearer token

Implemented modules:

- `pharmacy`
- `hardware`
- `commerce`

Implemented feature groups:

- auth
- bootstrap
- catalog (products/categories)
- customers
- sales
- stock + stock movements
- purchases
- transfers
- inventories
- receipt JSON endpoints
- incremental sync (`updated_since`)
- unified pagination (`limit`, `offset`, `pagination`)

---

## 3) Core Endpoints

### Auth

- `POST /api/v1/mobile/auth/login`
- `POST /api/v1/mobile/auth/logout`
- `GET /api/v1/mobile/auth/me`
- `GET /api/v1/mobile/bootstrap`
- `GET /api/v1/mobile/bootstrap/{module}?depot_id=&updated_since=`

`{module}` in: `pharmacy|hardware|commerce`

---

## 4) Module Endpoints (Pattern)

For each module prefix:

- `/api/v1/mobile/pharmacy/*`
- `/api/v1/mobile/hardware/*`
- `/api/v1/mobile/commerce/*`

Groups exposed:

- `products`, `categories`
- `customers/active`, `customers`, `customers/quick-create` (where applicable)
- `sales`, `sales/{id}`, `sales/{id}/receipt`, write actions
- `stock`, `stock/movements`
- `purchases`, `purchases/{id}`, `purchases/{id}/receipt`, write actions
- `transfers`, `transfers/{id}`, item actions, validate/cancel
- `inventories`, `inventories/{id}`, start/counts/validate/cancel

For exact list and payload details:

- `docs/MOBILE_REFERENCE.md`
- `docs/MOBILE_CHANGELOG.md`
- `docs/MOBILE_REACT_NATIVE_GUIDE.md` (guide complet d'utilisation cote React Native)

---

## 5) Pagination Convention

On list endpoints:

- Query: `limit`, `offset`
- Response:
  - `pagination.limit`
  - `pagination.offset`
  - `pagination.count`
  - `pagination.has_more`
  - `pagination.next_offset`

---

## 6) Incremental Sync Convention

Use:

- `GET /api/v1/mobile/bootstrap/{module}?updated_since=2026-03-31T10:00:00Z`

Response includes:

- `sync.mode` (`full` or `incremental`)
- `sync.updated_since`
- `sync.server_time`
- `deleted_ids` (implemented for soft-delete supported catalog entities)

---

## 7) Expo Project Recommendation

Create a separate project, e.g.:

- `pos-mobile` (outside web repo)

Suggested startup flow in app:

1. Login (`/auth/login`)
2. Store token securely
3. Call `/bootstrap/{module}` (full)
4. Render home POS
5. Use paginated module endpoints
6. Background incremental sync via `updated_since`

---

## 8) VPS / Production Checklist

- HTTPS enabled
- `APP_URL` correct
- Sanctum token auth working
- CORS allows mobile client
- API routes deployed
- Logs enabled for API errors
- Test with real tenant/shop/depot data


## 9) Before Building Screens

Validate with Postman/Insomnia:

- login/logout/me
- bootstrap module
- one complete sales flow
- one stock flow
- one purchases flow
- transfers + inventories flow
- receipt endpoints

---

## 10) Git Workflow Suggested

1. Commit backend API changes
2. Push to remote
3. Deploy VPS
4. Create Expo project
5. Start with Auth + Bootstrap + POS Home
