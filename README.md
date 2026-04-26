# simple-chat-service

A Laravel 11 REST API for a real-time chat service with JWT authentication, chat rooms, direct messaging, and WebSocket support via Laravel Reverb.

## Features

- **User Auth** — Register, login (JWT), logout, token refresh, forgot/reset password (via SMTP)
- **Chat Rooms** — Create public or private rooms, join (password-protected for private), leave (room auto-deletes when empty), send messages
- **WebSocket Broadcasting** — Real-time chat messages via Laravel Reverb (presence channels for rooms, private channels for DMs)
- **Direct Messages** — Send DMs between users, view conversation history
- **User Profile** — Update name and location
- **Swagger UI** — Auto-generated API docs at `/api/documentation`
- **Flexible storage** — SQLite by default; supports PostgreSQL, MySQL, or any other Laravel-supported driver
- **Docker** — Dockerfile + docker-compose.yml with Nginx, PHP-FPM, Reverb, and Mailpit

## Quick Start (Docker)

```bash
cp .env.example .env
docker-compose build
docker-compose up -d
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
docker-compose exec app php artisan migrate --force
```

For live file watching and automatic sync/rebuild while developing in Docker:

```bash
docker compose watch
```

- API: `http://localhost:8000/api/v1`
- Swagger UI: `http://localhost:8000/api/documentation`
- Mailpit UI: `http://localhost:8025`
- WebSocket: `ws://localhost:8080`

## Quick Start (Local)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
touch database/database.sqlite
php artisan migrate
php artisan serve
# In another terminal:
php artisan reverb:start
```

## API Endpoints

### Auth (`/api/v1/auth`)
| Method | Path | Description |
|--------|------|-------------|
| POST | `/register` | Register a new user |
| POST | `/login` | Log in, receive JWT token |
| POST | `/logout` | Invalidate JWT (auth required) |
| POST | `/refresh` | Refresh JWT token (auth required) |
| GET  | `/me` | Get current user (auth required) |
| POST | `/forgot-password` | Send password reset email |
| POST | `/reset-password` | Reset password via token |

### Users (`/api/v1/users`)
| Method | Path | Description |
|--------|------|-------------|
| PUT | `/profile` | Update name/location (auth required) |

### Chat Rooms (`/api/v1/chat-rooms`)
| Method | Path | Description |
|--------|------|-------------|
| GET  | `/` | List all public rooms |
| POST | `/` | Create a new room |
| GET  | `/{id}` | Get room details |
| POST | `/{id}/join` | Join a room (password required for private) |
| POST | `/{id}/leave` | Leave a room (room deleted when empty) |
| GET  | `/{id}/messages` | Get room messages (members only) |
| POST | `/{id}/messages` | Send a message (members only) |

### Direct Messages (`/api/v1/direct-messages`)
| Method | Path | Description |
|--------|------|-------------|
| GET  | `/{userId}` | View conversation with a user |
| POST | `/{userId}` | Send a DM to a user |

## Environment Variables

See `.env.example` for all supported variables.

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_CONNECTION` | `sqlite` | Database driver (`sqlite`, `mysql`, `pgsql`) |
| `JWT_SECRET` | — | JWT signing secret (set via `php artisan jwt:secret`) |
| `JWT_TTL` | `60` | Token lifetime in minutes |
| `MAIL_MAILER` | `smtp` | Mailer driver |
| `BROADCAST_CONNECTION` | `reverb` | Broadcasting driver |

## Running Tests

```bash
php artisan test
```

## License

MIT
