# Recurlog API v1

Base URL: `/api/v1/`

Auth: `Authorization: Bearer <token>`

## Auth

### POST `/api/v1/auth.php`
Login. No auth required. Rate-limited (5 req / 5 min per IP).

**Body:**
```json
{"email": "admin@demo.com", "password": "demo123"}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "eyJ...",
    "refreshToken": "eyJ...",
    "user": { "id": 1, "name": "Admin User", "email": "admin@demo.com", "role": "admin", "staffId": null }
  }
}
```

### POST `/api/v1/auth.php?action=refresh`
Exchange refresh token for new tokens.

**Body:**
```json
{"refreshToken": "eyJ..."}
```

### GET `/api/v1/auth.php?action=me`
Get current user from token. Requires Bearer auth.

---

## CRUD Endpoints

All endpoints below require `Authorization: Bearer <token>`.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `customers.php` | List (paginated) |
| GET | `customers.php?id=N` | Single |
| POST | `customers.php` | Create |
| PUT | `customers.php?id=N` | Update |
| DELETE | `customers.php?id=N` | Delete |

Same pattern for: `services.php`, `tasks.php`, `orders.php`, `staff.php`, `categories.php`, `localities.php`, `service_types.php`, `notifications.php`.

### Pagination & search

All GET list endpoints accept:

| Param | Default | Description |
|-------|---------|-------------|
| `page` | 1 | Page number |
| `per_page` | 50 | Items per page (max 200) |
| `search` | — | Search term across relevant fields |

**Paginated response:**
```json
{
  "success": true,
  "data": [...],
  "pagination": { "page": 1, "perPage": 50, "total": 142, "totalPages": 3 }
}
```

### Filters

Some endpoints support additional query params:

| Endpoint | Params |
|----------|--------|
| `services.php` | `customer_id`, `category_id`, `is_recurring` |
| `tasks.php` | `status`, `customer_id`, `assigned_to`, `service_id`, `scheduled_date`, `start_date`, `end_date` |
| `orders.php` | `status`, `customer_id`, `priority` |
| `notifications.php` | `is_read` |

### Error responses

```json
{ "success": false, "error": "Customer not found", "code": "NOT_FOUND" }
```

| Code | HTTP | Meaning |
|------|------|---------|
| `UNAUTHORIZED` | 401 | Missing or invalid auth |
| `TOKEN_EXPIRED` | 401 | Token expired or invalid |
| `FORBIDDEN` | 403 | Insufficient role |
| `NOT_FOUND` | 404 | Resource not found |
| `VALIDATION_ERROR` | 400 | Missing/invalid fields |
| `INVALID_INPUT` | 400 | Bad JSON body |
| `RATE_LIMITED` | 429 | Too many requests |
| `DB_ERROR` | 500 | Database connection failure |
| `INTERNAL_ERROR` | 500 | Unhandled server error |

### Token expiry

- Access token: **7 days**
- Refresh token: **30 days**
- Use `?action=refresh` before expiry to get new tokens without re-login

### Data format

Responses use camelCase (e.g., `customerId`, `assignedTo`). Request bodies can use camelCase or snake_case — both work via the `toSnake()` transformer.

Special fields:
- `servicesFor` → array of strings (comma-separated in DB)
- `location` → `{ lat, lng }` object
- `recurrence` → `{ value, unit, repeatFrom }` object
