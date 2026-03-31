# Mobile Reference (Client Only)

## Scope
- Mobile application reproduces **client POS** features only.
- Exclude **ROOT / admin** features (tenant/user management, billing admin, access-manager, impersonation, etc.).

## Goal
- Reuse the existing Laravel DDD backend logic.
- Provide a mobile-friendly **JSON API contract**.
- Keep the same POS theme/tokens across web and mobile (UI may differ).

## Architecture
Backend:
- Laravel 12
- DDD: Domain + Application (UseCases) + Infrastructure (HTTP adapters)
- Mobile API endpoints are implemented in `src/Infrastructure/Mobile/...`

Frontend:
- React Native (recommended) consuming `/api/v1/mobile/*`

## Authentication
Base auth:
- `POST /api/v1/mobile/auth/login`
- Returns: `{ token, token_type: "Bearer" }`
- Use `Authorization: Bearer <token>` for all other calls.

Token handling:
- `POST /api/v1/mobile/auth/logout`
- `GET /api/v1/mobile/auth/me`

Root restriction:
- ROOT users are blocked from mobile login.

## POS Bootstrap
- `GET /api/v1/mobile/bootstrap` (requires token)
- Provides:
  - `tenant.sector`
  - active `depots`
  - lightweight theme placeholder (to be aligned with web branding)

POS startup (optimized):
- `GET /api/v1/mobile/bootstrap/{module}?depot_id=`
- `{module}`: `pharmacy|hardware|commerce`
- Provides in one call:
  - `context` (shop/depot)
  - `catalog.categories`, `catalog.products` (light payload)
  - `customers` (active, limited list)
  - `cash_registers` (with open session if any)
  - `recent_sales` (latest)

Incremental sync:
- `GET /api/v1/mobile/bootstrap/{module}?depot_id=&updated_since=2026-03-31T10:00:00Z`
- If `updated_since` is present:
  - returns only records updated since this datetime for catalog/customers/cash registers/recent sales
  - adds `sync.mode = incremental` and `sync.server_time`
  - adds `deleted_ids` for entities removed since this datetime (implemented for Pharmacy/Hardware catalog where soft-delete exists)

## MVP API Contract (V1)
Pagination convention (list endpoints):
- Query params: `limit` (default endpoint value), `offset` (default `0`)
- Response includes `pagination`:
  - `limit`, `offset`, `count`, `has_more`, `next_offset`
- Applied to: products, sales, purchases, stock, stock movements, transfers, inventories

### Pharmacy
Catalog:
- `GET /api/v1/mobile/pharmacy/products?search=&category_id=&status=&depot_id=`
  - Returns: `products[]`, `categories[]`

- `GET /api/v1/mobile/pharmacy/categories`

Customers:
- `GET /api/v1/mobile/pharmacy/customers/active?depot_id=`
  - Returns: `customers[]`

- `POST /api/v1/mobile/pharmacy/customers`
  - Create customer (full payload defined by existing web validation)

- `POST /api/v1/mobile/pharmacy/customers/quick-create`
  - Quick create (name/phone/email)

Sales (POS):
- `GET /api/v1/mobile/pharmacy/sales?status=&sale_type=&depot_id=&limit=`
- `GET /api/v1/mobile/pharmacy/sales/{id}`
- `GET /api/v1/mobile/pharmacy/sales/{id}/receipt`

- `POST /api/v1/mobile/pharmacy/sales`
  - Create sale draft (lines + totals computed by backend use cases)

- `PUT /api/v1/mobile/pharmacy/sales/{id}`
  - Update draft lines/customer

- `POST /api/v1/mobile/pharmacy/sales/{id}/finalize`
  - Finalize sale, triggers stock updates and finance invoice/debt if needed

- `POST /api/v1/mobile/pharmacy/sales/{id}/cancel`

Purchases:
- `GET /api/v1/mobile/pharmacy/purchases?status=&limit=&depot_id=`
- `GET /api/v1/mobile/pharmacy/purchases/{id}?depot_id=`
- `GET /api/v1/mobile/pharmacy/purchases/{id}/receipt?depot_id=`
- `POST /api/v1/mobile/pharmacy/purchases`
- `POST /api/v1/mobile/pharmacy/purchases/{id}/confirm`
- `POST /api/v1/mobile/pharmacy/purchases/{id}/receive`
- `POST /api/v1/mobile/pharmacy/purchases/{id}/cancel`

Transfers:
- `GET /api/v1/mobile/pharmacy/transfers?status=&limit=&depot_id=`
- `GET /api/v1/mobile/pharmacy/transfers/{id}?depot_id=`
- `POST /api/v1/mobile/pharmacy/transfers`
- `POST /api/v1/mobile/pharmacy/transfers/{id}/items`
- `PUT /api/v1/mobile/pharmacy/transfers/{id}/items/{itemId}`
- `DELETE /api/v1/mobile/pharmacy/transfers/{id}/items/{itemId}`
- `POST /api/v1/mobile/pharmacy/transfers/{id}/validate`
- `POST /api/v1/mobile/pharmacy/transfers/{id}/cancel`

Inventories:
- `GET /api/v1/mobile/pharmacy/inventories?status=&limit=&depot_id=`
- `GET /api/v1/mobile/pharmacy/inventories/{id}?depot_id=`
- `POST /api/v1/mobile/pharmacy/inventories`
- `POST /api/v1/mobile/pharmacy/inventories/{id}/start`
- `POST /api/v1/mobile/pharmacy/inventories/{id}/counts`
- `POST /api/v1/mobile/pharmacy/inventories/{id}/counts/{productId}`
- `POST /api/v1/mobile/pharmacy/inventories/{id}/validate`
- `POST /api/v1/mobile/pharmacy/inventories/{id}/cancel`

Stock:
- `GET /api/v1/mobile/pharmacy/stock?search=&category_id=&stock_status=&depot_id=&limit=`
- `GET /api/v1/mobile/pharmacy/stock/movements?product_id=&depot_id=&limit=`

### Hardware
Catalog:
- `GET /api/v1/mobile/hardware/products?search=&category_id=&status=&depot_id=`
- `GET /api/v1/mobile/hardware/categories`

Customers:
- `GET /api/v1/mobile/hardware/customers/active?depot_id=`
- `POST /api/v1/mobile/hardware/customers`

Sales (POS):
- `GET /api/v1/mobile/hardware/sales?status=&sale_type=&depot_id=&limit=`
- `GET /api/v1/mobile/hardware/sales/{id}`
- `GET /api/v1/mobile/hardware/sales/{id}/receipt`
- `POST /api/v1/mobile/hardware/sales`
- `PUT /api/v1/mobile/hardware/sales/{id}`
- `POST /api/v1/mobile/hardware/sales/{id}/finalize`
- `POST /api/v1/mobile/hardware/sales/{id}/cancel`

Purchases:
- `GET /api/v1/mobile/hardware/purchases?status=&limit=&depot_id=`
- `GET /api/v1/mobile/hardware/purchases/{id}?depot_id=`
- `GET /api/v1/mobile/hardware/purchases/{id}/receipt?depot_id=`
- `POST /api/v1/mobile/hardware/purchases`
- `POST /api/v1/mobile/hardware/purchases/{id}/confirm`
- `POST /api/v1/mobile/hardware/purchases/{id}/receive`
- `POST /api/v1/mobile/hardware/purchases/{id}/cancel`

Transfers:
- `GET /api/v1/mobile/hardware/transfers?status=&limit=&depot_id=`
- `GET /api/v1/mobile/hardware/transfers/{id}?depot_id=`
- `POST /api/v1/mobile/hardware/transfers`
- `POST /api/v1/mobile/hardware/transfers/{id}/items`
- `PUT /api/v1/mobile/hardware/transfers/{id}/items/{itemId}`
- `DELETE /api/v1/mobile/hardware/transfers/{id}/items/{itemId}`
- `POST /api/v1/mobile/hardware/transfers/{id}/validate`
- `POST /api/v1/mobile/hardware/transfers/{id}/cancel`

Inventories:
- `GET /api/v1/mobile/hardware/inventories?status=&limit=&depot_id=`
- `GET /api/v1/mobile/hardware/inventories/{id}?depot_id=`
- `POST /api/v1/mobile/hardware/inventories`
- `POST /api/v1/mobile/hardware/inventories/{id}/start`
- `POST /api/v1/mobile/hardware/inventories/{id}/counts`
- `POST /api/v1/mobile/hardware/inventories/{id}/counts/{productId}`
- `POST /api/v1/mobile/hardware/inventories/{id}/validate`
- `POST /api/v1/mobile/hardware/inventories/{id}/cancel`

Stock:
- `GET /api/v1/mobile/hardware/stock?search=&category_id=&stock_status=&depot_id=&limit=`
- `GET /api/v1/mobile/hardware/stock/movements?product_id=&depot_id=&limit=`

### Commerce
Catalog:
- `GET /api/v1/mobile/commerce/products?search=&category_id=&status=&depot_id=`
- `GET /api/v1/mobile/commerce/categories`

Customers:
- `GET /api/v1/mobile/commerce/customers/active`
- `POST /api/v1/mobile/commerce/customers`
- `POST /api/v1/mobile/commerce/customers/quick-create`

Sales:
- `GET /api/v1/mobile/commerce/sales?status=&limit=&depot_id=`
- `GET /api/v1/mobile/commerce/sales/{id}`
- `GET /api/v1/mobile/commerce/sales/{id}/receipt`
- `POST /api/v1/mobile/commerce/sales`
- `POST /api/v1/mobile/commerce/sales/{id}/finalize`

Purchases:
- `GET /api/v1/mobile/commerce/purchases?status=&limit=&depot_id=`
- `GET /api/v1/mobile/commerce/purchases/{id}?depot_id=`
- `GET /api/v1/mobile/commerce/purchases/{id}/receipt?depot_id=`
- `POST /api/v1/mobile/commerce/purchases`
- `POST /api/v1/mobile/commerce/purchases/{id}/receive`

Transfers:
- `GET /api/v1/mobile/commerce/transfers?status=&limit=&depot_id=`
- `GET /api/v1/mobile/commerce/transfers/{id}?depot_id=`
- `POST /api/v1/mobile/commerce/transfers`
- `POST /api/v1/mobile/commerce/transfers/{id}/items`
- `PUT /api/v1/mobile/commerce/transfers/{id}/items/{itemId}`
- `DELETE /api/v1/mobile/commerce/transfers/{id}/items/{itemId}`
- `POST /api/v1/mobile/commerce/transfers/{id}/validate`
- `POST /api/v1/mobile/commerce/transfers/{id}/cancel`

Inventories:
- `GET /api/v1/mobile/commerce/inventories?status=&limit=&depot_id=`
- `GET /api/v1/mobile/commerce/inventories/{id}?depot_id=`
- `POST /api/v1/mobile/commerce/inventories`
- `POST /api/v1/mobile/commerce/inventories/{id}/start`
- `POST /api/v1/mobile/commerce/inventories/{id}/counts`
- `POST /api/v1/mobile/commerce/inventories/{id}/validate`
- `POST /api/v1/mobile/commerce/inventories/{id}/cancel`

Stock:
- `GET /api/v1/mobile/commerce/stock?search=&category_id=&stock_status=&depot_id=&limit=`
- `GET /api/v1/mobile/commerce/stock/movements?product_id=&limit=`

## How to map Web -> Mobile Features
For each feature in the web UI:
1. Identify the exact action (list/create/update/finalize/export)
2. Find the corresponding DDD UseCase(s) already used by the web controllers
3. Implement a mobile HTTP adapter that returns JSON instead of Inertia/Blade
4. Add a contract in this document (request/response examples)

## Theme consistency (Web + Mobile)
Keep consistent:
- Brand colors and typography tokens
- Icon set
- Spacing rhythm

Implementation idea:
- Reuse the web branding/theme values via `/bootstrap` once we finalize mobile theme tokens.

