# ProjectRack Backend

Laravel + PostgreSQL API and web portal that complements the ProjectRack Flutter app.

## Features

- **REST API** (`/api/v1`) with Sanctum token auth for the mobile app
- **Offline sync** via `POST /sync/push` and `GET /sync/pull` (last-write-wins + tombstones)
- **Project sharing** — supervisors create share links; owners open `/p/{token}` on the web
- **Web portal** — dashboard, project overview, expenses, tasks, inventory, activities, schedule, reports

## Requirements

- PHP 8.2+
- Composer
- PostgreSQL 14+
- Flutter app pointing at `APP_URL`

## Quick start

```bash
# Postgres (Docker Compose from this project)
docker compose up -d

# Or plain Docker:
# docker run -d --name projectrack-pg \
#   -e POSTGRES_USER=projectrack \
#   -e POSTGRES_PASSWORD=projectrack \
#   -e POSTGRES_DB=projectrack \
#   -p 5434:5432 postgres:16-alpine

cd /home/nelson/testdev/backend
cp .env.example .env   # if needed
# Ensure .env has:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5434
# DB_DATABASE=projectrack
# DB_USERNAME=projectrack
# DB_PASSWORD=projectrack

php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve
```

Web UI: http://localhost:8000  
API base: http://localhost:8000/api/v1

## Auth (API)

| Method | Path | Notes |
|--------|------|-------|
| POST | `/auth/register` | body: name, email, password, optional id/role |
| POST | `/auth/login` | returns `{ user, token }` |
| POST | `/auth/logout` | Bearer token |
| GET | `/auth/me` | Bearer token |

## Sync

Push a batch of outbox mutations when online:

```http
POST /api/v1/sync/push
Authorization: Bearer {token}
Content-Type: application/json

{
  "items": [
    {
      "entity": "project",
      "id": "proj_abc",
      "op": "create",
      "client_updated_at": "2026-08-03T10:00:00Z",
      "payload": {
        "name": "Barn Roof",
        "description": "",
        "start_date": "2026-08-01T00:00:00Z",
        "budget": 50000,
        "status": "Active",
        "category": "Construction"
      }
    }
  ]
}
```

Pull changes since last cursor:

```http
GET /api/v1/sync/pull?since=2026-08-01T00:00:00Z
Authorization: Bearer {token}
```

## Sharing

1. Supervisor (authenticated): `POST /api/v1/projects/{id}/shares` → `{ url: "http://.../p/{token}" }`
2. Owner opens the URL in a browser, registers/logs in, clicks **Accept access**
3. Owner can then view the project on the web dashboard

## Roles

| Role | Typical client | Access |
|------|----------------|--------|
| `supervisor` | Mobile app | Create/manage projects, generate share links, sync |
| `owner` | Web portal | Accept shares, read project data |
| `viewer` | Web (optional share role) | Read-only |

## Environment notes

Mobile clients should store the API base URL (e.g. `http://10.0.2.2:8000` for Android emulator → host machine).
