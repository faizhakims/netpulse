# NetPulse REST API Documentation

**Base URL:** `http://your-domain/api`  
**Authentication:** Laravel Sanctum (Bearer Token)  
**Response Format:** All responses use `Content-Type: application/json`

---

## Standard Response Envelope

All endpoints return a consistent JSON envelope:

```json
{
    "success": true,
    "message": "Success",
    "data": { ... }
}
```

On errors:
```json
{
    "success": false,
    "message": "Error description",
    "errors": { ... }
}
```

---

## Authentication

### POST `/api/auth/login`

Obtain a Sanctum personal access token.

**Request:**
```json
{
    "email": "admin@example.com",
    "password": "secret",
    "device_name": "my-app"
}
```

**Response `200`:**
```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "token": "1|abc123...",
        "token_type": "Bearer",
        "user": {
            "id": 1,
            "name": "Admin",
            "email": "admin@example.com",
            "role": "admin"
        }
    }
}
```

**Error `401`:** Invalid credentials  
**Error `403`:** Account deactivated

---

### POST `/api/auth/logout` 🔒

Revoke the current token.

**Headers:** `Authorization: Bearer {token}`

**Response `200`:**
```json
{
    "success": true,
    "message": "Logged out successfully.",
    "data": null
}
```

---

### GET `/api/auth/me` 🔒

Return the authenticated user profile and permissions.

**Response `200`:**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "id": 1,
        "name": "Admin",
        "email": "admin@example.com",
        "role": "admin",
        "is_active": true,
        "permissions": ["view dashboard", "manage devices", "manage alerts", "..."],
        "last_login_at": "2026-06-12T10:00:00.000000Z"
    }
}
```

---

## Dashboard

### GET `/api/dashboard` 🔒

Requires: `view dashboard`

Returns device summary, health score, latency stats, active incidents, and historical performance charts.

**Response `200`:**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "devices": { "total": 12, "up": 10, "down": 1, "unknown": 1 },
        "health_score": 83,
        "avg_latency_ms": 18.5,
        "latency": { "core_ms": 5.2, "edge_ms": 22.1, "peak_ms": 85.0 },
        "chart": { "core": [5, 6, ...], "edge": [20, 21, ...] },
        "active_incidents": [ ... ],
        "max_severity": "Warning",
        "weekly_history": [ { "label": "Mon", "pct": 100 }, ... ],
        "monthly_history": [ ... ]
    }
}
```

---

## Devices

### GET `/api/devices` 🔒

Requires: `view devices`

**Query Parameters:**

| Parameter  | Type   | Description                            |
|------------|--------|----------------------------------------|
| `search`   | string | Filter by device name or IP address    |
| `status`   | string | `up`, `down`, `unknown`                |
| `sort`     | string | `name` (default), `latency`, `status`, `ip` |
| `page`     | int    | Page number (default: 1)               |
| `per_page` | int    | Items per page (default: 20)           |

**Response `200`:**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "items": [
            {
                "name": "main-router",
                "ip_address": "192.168.1.1",
                "status": "up",
                "raw_status": "up",
                "is_stale": false,
                "latency_ms": 5.2,
                "last_up_at": "2026-06-12T10:00:00",
                "last_down_at": null,
                "checked_at": "2026-06-12T10:05:00.000000Z"
            }
        ],
        "total": 12,
        "per_page": 20,
        "current_page": 1,
        "last_page": 1
    }
}
```

---

### GET `/api/devices/{name}` 🔒

Requires: `view devices`

**Example:** `GET /api/devices/main-router`

**Response `200`:**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "device": { ... },
        "uptime_pct": 99.5,
        "last_reboot": "10-06-2026 at 08.30",
        "latency_avg_ms": 5.1,
        "latency_peak_ms": 12.0,
        "latency_min_ms": 3.0,
        "alert_channels": { "telegram": true, "email": false }
    }
}
```

**Error `404`:** Device not found

---

### POST `/api/devices` 🔒

Requires: `manage devices`

Dispatch a ping action against the monitoring service.

**Request:**
```json
{
    "device": "main-router",
    "action": "ping"
}
```

**Response `200`:** Monitoring service response

---

### PUT `/api/devices/{name}` 🔒

Requires: `manage devices`

Dispatch a reboot action.

**Request:**
```json
{
    "action": "reboot"
}
```

**Response `200`:** Monitoring service response

---

### DELETE `/api/devices/{name}` 🔒

Requires: `manage devices`

Remove a device from the monitoring service.

**Response `200`:**
```json
{
    "success": true,
    "message": "Device 'main-router' berhasil dihapus",
    "data": null
}
```

---

## Traffic

### GET `/api/traffic` 🔒

Requires: `view traffic`

Returns current bandwidth totals, 24-hour chart, daily log, latency, and top devices.

**Response `200`:**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "bandwidth": {
            "total_in": 1234567890,
            "total_out": 987654321,
            "total_bytes": 2222222211
        },
        "chart": {
            "hours": ["00:00", "01:00", "..."],
            "values": [1024, 2048, "..."]
        },
        "bandwidth_log": [ { "date": "2026-06-12", "total_in": 123, "total_out": 456 } ],
        "latency": { "average_ms": 18.5, "peak_ms": 85.0, "status": "Stable" },
        "packet_loss_pct": null,
        "top_devices": [ ... ]
    }
}
```

---

## Alerts

### GET `/api/alerts` 🔒

Requires: `view alerts`

**Query Parameters:**

| Parameter  | Type   | Description                                       |
|------------|--------|---------------------------------------------------|
| `search`   | string | Filter by title or target device                  |
| `status`   | string | `active` or `inactive`                            |
| `severity` | string | `critical`, `warning`, `info`                     |
| `sort`     | string | `sort_order` (default), `title`, `severity`, `created_at`. Prefix with `-` for descending |
| `page`     | int    | Page number                                       |
| `per_page` | int    | Items per page (default: 20)                      |

**Response `200`:**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "items": [
            {
                "id": 1,
                "title": "High Latency Alert",
                "severity": "critical",
                "metric_type": "latency",
                "condition": "gt",
                "threshold_value": 100,
                "duration": "5m",
                "target_device": null,
                "channels": ["telegram", "email"],
                "is_active": true,
                "trigger_count": 3,
                "last_triggered_at": "2026-06-12T09:00:00.000000Z",
                "condition_label": "If Latency > 100ms for 5m",
                "created_at": "2026-06-01T00:00:00.000000Z",
                "updated_at": "2026-06-12T08:00:00.000000Z"
            }
        ],
        "total": 5,
        "per_page": 20,
        "current_page": 1,
        "last_page": 1
    }
}
```

---

### GET `/api/alerts/{id}` 🔒

Requires: `view alerts`

---

### POST `/api/alerts` 🔒

Requires: `manage alerts`

**Request:**
```json
{
    "title": "Device Down Alert",
    "metric_type": "status",
    "condition": "is_down",
    "duration": "5m",
    "severity": "critical",
    "channels": ["telegram"],
    "is_active": true
}
```

> `threshold_value` is not required for `is_down` / `is_up` conditions.  
> For numeric metrics (`latency`, `bandwidth`, `packet_loss`), `threshold_value` is required.

**Response `201`:** Created alert rule

**Validation Errors `422`:**
```json
{
    "success": false,
    "message": "Metric 'status' hanya mendukung kondisi 'is_down' atau 'is_up'.",
    "errors": { "condition": ["..."] }
}
```

---

### PUT `/api/alerts/{id}` 🔒

Requires: `manage alerts`

Same body as POST. **Response `200`:** Updated alert rule.

---

### DELETE `/api/alerts/{id}` 🔒

Requires: `manage alerts`

**Response `200`:**
```json
{ "success": true, "message": "Alert rule deleted.", "data": null }
```

---

## Incidents

### GET `/api/incidents` 🔒

Requires: `view incidents`

**Query Parameters:**

| Parameter  | Type   | Description                                  |
|------------|--------|----------------------------------------------|
| `status`   | string | `active`, `resolved`, `all` (default)        |
| `search`   | string | Filter by device name or issue text          |
| `sort`     | string | `-started_at` (default), `resolved_at`, `device`, `status` |
| `page`     | int    | Page number                                  |
| `per_page` | int    | Items per page (default: 20)                 |

**Response `200`:**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "items": [
            {
                "id": 5,
                "device": "edge-switch-1",
                "ip_address": "192.168.1.10",
                "issue": "Device Down — connection lost",
                "status": "Critical",
                "is_active": true,
                "duration": "14m 30s",
                "started_at": "2026-06-12T09:45:00.000000Z",
                "resolved_at": null
            }
        ],
        "total": 2,
        "per_page": 20,
        "current_page": 1,
        "last_page": 1
    }
}
```

---

### GET `/api/incidents/{id}` 🔒

Requires: `view incidents`

---

### PUT `/api/incidents/{id}` 🔒

Requires: `manage incidents`

Manually resolve an active incident.

**Request:**
```json
{
    "action": "resolve"
}
```

**Response `200`:** Resolved incident  
**Error `409`:** Incident already resolved

---

## Users

### GET `/api/users` 🔒

Requires: `manage users`

**Query Parameters:**

| Parameter  | Type   | Description                                   |
|------------|--------|-----------------------------------------------|
| `search`   | string | Filter by name or email                       |
| `role`     | string | `admin`, `operator`, `viewer`                 |
| `status`   | string | `active` or `inactive`                        |
| `sort`     | string | `name` (default), `email`, `created_at`, `last_login_at` |
| `page`     | int    | Page number                                   |
| `per_page` | int    | Items per page (default: 20)                  |

**Response `200`:**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "items": [
            {
                "id": 1,
                "name": "Admin User",
                "email": "admin@example.com",
                "role": "admin",
                "is_active": true,
                "last_login_at": "2026-06-12T08:00:00.000000Z",
                "created_at": "2026-06-01T00:00:00.000000Z",
                "updated_at": "2026-06-12T08:00:00.000000Z"
            }
        ],
        "total": 3,
        "per_page": 20,
        "current_page": 1,
        "last_page": 1
    }
}
```

---

### GET `/api/users/{id}` 🔒

Requires: `manage users`

---

## Permission Reference

| Permission         | Role(s) with access        |
|--------------------|----------------------------|
| `view dashboard`   | admin, operator, viewer    |
| `view devices`     | admin, operator, viewer    |
| `manage devices`   | admin, operator            |
| `view traffic`     | admin, operator, viewer    |
| `view alerts`      | admin, operator, viewer    |
| `manage alerts`    | admin                      |
| `view incidents`   | admin, operator, viewer    |
| `manage incidents` | admin, operator            |
| `manage users`     | admin                      |

---

## Error Codes

| Code | Meaning                              |
|------|--------------------------------------|
| 400  | Bad request / validation error       |
| 401  | Unauthenticated (missing/bad token)  |
| 403  | Forbidden (insufficient permissions) |
| 404  | Resource not found                   |
| 409  | Conflict (e.g., already resolved)    |
| 422  | Unprocessable entity (validation)    |
| 429  | Too many requests (rate limited)     |
| 500  | Internal server error                |
| 502  | Monitoring service unavailable       |
| 503  | Monitoring API not configured        |

---

## Quick Start Example

```bash
# 1. Login and get token
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@netpulse.local","password":"secret","device_name":"curl-test"}'

# 2. Use the token
TOKEN="1|abc123..."

# 3. Get dashboard summary
curl http://localhost/api/dashboard \
  -H "Authorization: Bearer $TOKEN"

# 4. List all devices (filter UP only)
curl "http://localhost/api/devices?status=up" \
  -H "Authorization: Bearer $TOKEN"

# 5. Get active incidents
curl "http://localhost/api/incidents?status=active" \
  -H "Authorization: Bearer $TOKEN"

# 6. Create an alert rule
curl -X POST http://localhost/api/alerts \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "High Latency",
    "metric_type": "latency",
    "condition": "gt",
    "threshold_value": 100,
    "duration": "5m",
    "severity": "warning",
    "channels": ["telegram"]
  }'

# 7. Logout
curl -X POST http://localhost/api/auth/logout \
  -H "Authorization: Bearer $TOKEN"
```
