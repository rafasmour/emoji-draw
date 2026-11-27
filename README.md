# Emoji Draw

A real-time multiplayer guessing game: one player draws a term on a canvas using only emojis, everyone else tries to guess it.
Live at **https://emoji-draw.rafasmour.com**.

## How it plays

1. Open the site and you're signed in as a guest automatically (or register an account).
2. Create a room, pick one from the public room list, or join one by its ID.
3. The room owner starts the game once there are at least two players.
4. Each round, one player is the artist and gets a secret term. They place emojis on the canvas while the others type guesses.
5. Every 10 seconds another letter of the term is revealed as a hint.
6. The round ends when time runs out or everyone has guessed. A new artist is chosen, and after the last round the scores are shown.

Scoring: a correct guess is worth 50 to 500 points depending on how much time is left, plus 100 for the first correct guess of the round. The artist earns half of what each guesser scores.

## Features

- Guests get a generated `Guest_XXXXXX` account on first visit; registered users also get profile, password, 2FA and appearance settings (Fortify)
- Emoji canvas with an emoji picker and size control; strokes are validated server-side as emoji
- Live room chat, player list, scores, countdown and hints over WebSockets
- Room owner can start/stop the game, kick players (kicked players can't rejoin) and destroy the room; ownership passes to a random player if the owner leaves
- Rooms have settings for player cap, public/private, rounds (1–10) and time limit (30/60/120s)
- About 100 seeded terms (short phrases like "night owl"), tagged with difficulty and category
- Browser tabs send heartbeats; players whose connection lapses are removed, and empty rooms are deleted

Note: terms are currently picked at random from the whole pool. The room's difficulty and category settings are stored but not yet used for term selection, and there is no settings UI in the lobby yet.

## Tech stack

Laravel 12 (PHP 8.4), Laravel Reverb + Laravel Echo, Inertia 2 + React 19 + TypeScript, Tailwind 4 + shadcn/ui (Radix), MongoDB 8 via `mongodb/laravel-mongodb`, Fortify, Wayfinder, Vite. Docker Compose behind Traefik.

## Architecture

- **web**: nginx in front of **php-fpm** (the Laravel app)
- **reverb**: WebSocket server. Traefik routes `/app` on the same host to it, so the browser connects to `wss://emoji-draw.<domain>/app`. Laravel publishes to it over the internal network.
- **queue**: queue worker that runs round timers and hint reveals as delayed jobs
- **scheduler**: runs `rooms:prune-disconnected` every minute to remove players whose heartbeat has expired
- **mongodb**: stores users, rooms, terms, sessions, cache and the job queue

## Local development

Dev runs behind a Traefik instance on the external Docker network `webgateway`, with a `websecure` entrypoint. The app is served at `https://emoji-draw.${DOMAIN}` and the Vite dev server is proxied at `/vite` on the same host. mongo-express is at `https://emoji-db.${DOMAIN}`.

```bash
cp .env.example .env
# set DOMAIN, UID/GID, and DB_URI (a MongoDB URI using the DB_* credentials)
# for live events set BROADCAST_CONNECTION=reverb and point VITE_REVERB_* at emoji-draw.${DOMAIN}

docker network create webgateway   # if it doesn't exist yet
docker compose -f docker-compose.dev.yml up -d --build

docker compose -f docker-compose.dev.yml exec workspace composer install
docker compose -f docker-compose.dev.yml exec workspace npm install
docker compose -f docker-compose.dev.yml exec workspace php artisan key:generate
docker compose -f docker-compose.dev.yml exec workspace php artisan migrate
docker compose -f docker-compose.dev.yml exec workspace php artisan db:seed   # terms + 3 test users
```

The dev stack also runs `reverb`, `queue`, `scheduler` and `vite` containers, all with the project mounted at `/var/www`. Xdebug is enabled by default (see the `XDEBUG_*` variables).

## Production

`docker-compose.prod.yml` builds production images (assets are compiled inside the image) and runs `web`, `php-fpm`, `reverb`, `queue`, `scheduler` and `mongodb`. Only `web` and `reverb` join the `webgateway` network; TLS comes from Traefik's `myresolver` cert resolver. php-fpm runs migrations on startup. See `.env.production.example` for the environment.

Deployment is handled by `.github/workflows/deploy-vps.yml`. It runs after the `tests` workflow passes on `main` (or manually). Over SSH it:

1. writes `.env` on the server from repository secrets and variables
2. clones or pulls the repo
3. runs `docker compose -f docker-compose.prod.yml up -d --build --wait`
4. seeds terms with `TermSeeder` if the terms collection is empty (first deploy)

Required repository secrets: `VPS_IP`, `USER`, `DEPLOY_SSH_KEY`, `DEPLOY_SSH_PASSPHRASE`, `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`.
Required repository variable: `DOMAIN` (the app is served at `emoji-draw.${DOMAIN}`).

## Tests

PHPUnit feature and unit tests live in `tests/`. CI (`.github/workflows/tests.yml`) runs them on pushes and PRs to `main` and `develop`:

```bash
docker compose -f docker-compose.dev.yml exec workspace composer test   # or ./vendor/bin/phpunit
```

A separate `linter` workflow runs Pint, Prettier and ESLint (`npm run format`, `npm run lint`).
