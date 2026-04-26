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

## WebSockets

Laravel Reverb handles real-time broadcasting over WebSockets. The server listens on port `8080` by default.

### Channels

| Channel | Type | Description |
|---------|------|-------------|
| `presence-chat-room.{roomId}` | Presence | Broadcasts chat messages to all members of a room |
| `private-direct-message.{userId}` | Private | Broadcasts DMs to the recipient |

### Events

| Event | Channel | Payload |
|-------|---------|---------|
| `message.sent` | `presence-chat-room.{roomId}` | New chat room message |
| `message.sent` | `private-direct-message.{userId}` | New direct message |

### Connecting (Laravel Echo + Pusher JS)

Install the client library:

```bash
npm install laravel-echo pusher-js
```

Configure Echo:

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: false,
    enabledTransports: ['ws'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${yourJwtToken}`,
        },
    },
});
```

### Listening to a Chat Room

```js
echo.join(`chat-room.${roomId}`)
    .here(members => console.log('Online members:', members))
    .joining(member => console.log('Joined:', member))
    .leaving(member => console.log('Left:', member))
    .listen('.message.sent', e => console.log('New message:', e));
```

### Listening to Direct Messages

```js
echo.private(`direct-message.${yourUserId}`)
    .listen('.message.sent', e => console.log('New DM:', e));
```

> **Note:** Both channels require authentication. Include your JWT token in the `Authorization` header of the `/broadcasting/auth` request (see Echo `auth` config above).

## Running Tests

```bash
php artisan test
```

## License

MIT
