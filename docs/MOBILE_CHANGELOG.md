# Mobile API Changelog

## 2026-03-31

### Added
- Added versioned mobile API base under `/api/v1/mobile`.
- Added token authentication endpoints:
  - `POST /auth/login`
  - `POST /auth/logout`
  - `GET /auth/me`
  - `GET /bootstrap`
- Added module endpoints for `pharmacy`, `hardware`, `commerce`.

### Pharmacy / Hardware / Commerce
- Added catalog endpoints (`products`, `categories`).
- Added customer endpoints (active list and create/quick-create where applicable).
- Added sales endpoints (list/detail/create/update/finalize/cancel according to module).
- Added stock endpoints:
  - `GET /stock`
  - `GET /stock/movements`
- Added purchases endpoints:
  - list/detail
  - write actions (`store`, `confirm`, `receive`, `cancel`) by module support
- Added transfers endpoints:
  - list/detail
  - write actions (`store`, `addItem`, `updateItem`, `removeItem`, `validate`, `cancel`)
- Added inventories endpoints:
  - list/detail
  - write actions (`store`, `start`, `counts`, `validate`, `cancel`)

### Receipts
- Added JSON receipt endpoints for mobile consumption:
  - sales receipt (`/sales/{id}/receipt`)
  - purchases receipt (`/purchases/{id}/receipt`)

### Bootstrap
- Added POS bootstrap endpoint:
  - `GET /bootstrap/{module}` where `{module}` is `pharmacy|hardware|commerce`
- Added module-scoped startup payload:
  - context (shop/depot)
  - lightweight catalog
  - active customers
  - cash registers and open session
  - recent sales

### Incremental Sync
- Added `updated_since` support on `GET /bootstrap/{module}`.
- Added `sync` metadata:
  - `mode`
  - `updated_since`
  - `server_time`
- Added `deleted_ids` payload for deleted entities where soft-delete is supported.

### Pagination
- Standardized pagination on list endpoints with:
  - query params: `limit`, `offset`
  - response: `pagination` (`limit`, `offset`, `count`, `has_more`, `next_offset`)
- Applied pagination convention to:
  - products
  - sales
  - purchases
  - stock
  - stock movements
  - transfers
  - inventories

### Compatibility
- Added `depot_id` request fallback in reused web controllers to support token-based mobile calls without Laravel session context.

