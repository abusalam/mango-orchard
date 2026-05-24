# Mango Orchard — Setup & Usage Guide

A Laravel 13 application with PostgreSQL, Redis, Tailwind CSS, Pest 4 (with Playwright-driven browser tests), spatie/laravel-permission, Breeze auth, and mews/captcha. The entire dev stack runs in Docker via Laravel Sail.

---

## 1. Prerequisites

Install on your host:

| Tool | Why |
|---|---|
| **Docker** (Desktop or Engine) ≥ 20.10 | Runs every container in the stack |
| **Docker Compose v2** | Bundled with Docker Desktop; `docker compose` subcommand |
| **Git** | Cloning the repo |

You do **not** need PHP, Composer, Node, or PostgreSQL on the host — everything runs inside containers. ([Why we do it this way](#8-rules--gotchas).)

---

## 2. Cloning & first-time setup

Clone the repo and run the **[7-step quick-start in the root README](../README.md#30-second-quick-start)** — `cp .env.example .env`, `sail up -d`, install PHP/JS/Playwright deps, generate the app key, migrate + seed, build the front-end. The app will be reachable at **http://localhost:8080**.

The rest of this guide assumes that's done and adds the bits the quick-start glosses over.

### Convenience alias

> 💡 Add this to your shell profile so you can type `sail …` instead of the long vendor path:
> ```bash
> alias sail='[ -f sail ] && bash sail || bash vendor/laravel/sail/bin/sail'
> ```
> The rest of this guide uses the short `sail` form.

### Ports

| Service | Host port | Container port | What |
|---|---|---|---|
| App (nginx + PHP-FPM) | `8080` | `80` | the web app |
| Postgres | `5433` | `5432` | DB |
| Redis | `6380` | `6379` | cache + sessions |
| Mailpit SMTP | `1025` | `1025` | what the app sends to (`MAIL_HOST=mailpit`) |
| Mailpit dashboard | `8025` | `8025` | **http://localhost:8025** to read caught mail |
| Vite dev server | `5173` | `5173` | front-end HMR (only when `sail npm run dev`) |

Override any of them by editing `.env` (`APP_PORT`, `FORWARD_DB_PORT`, `FORWARD_REDIS_PORT`, `FORWARD_MAILPIT_PORT`, `FORWARD_MAILPIT_DASHBOARD_PORT`, `VITE_PORT`).

---

## 3. Running Docker containers

```bash
sail up -d            # start in the background
sail up               # start in the foreground (logs streamed)
sail stop             # stop containers (volumes preserved)
sail down             # stop AND remove containers (volumes preserved)
sail down -v          # ⚠️ remove containers AND named volumes (deletes DB data)
sail ps               # list running services
sail logs -f          # tail logs from all services
sail logs -f laravel.test
```

Open a shell **inside** the app container:

```bash
sail shell            # bash as the sail user
sail root-shell       # bash as root
```

The compose stack defines four services in [`compose.yaml`](../compose.yaml):

- `laravel.test` — the application (PHP 8.5, nginx, Node)
- `pgsql` — Postgres 18 with the `sail-pgsql` named volume
- `redis` — Redis Alpine with the `sail-redis` named volume
- `mailpit` — SMTP sink + web dashboard for inspecting any email the app sends in dev (see [Checking email in dev](#checking-email-in-dev))

---

## 4. Migrations & seeding

The database schema is in `database/migrations/`. The seeders live in `database/seeders/`.

### Apply migrations

```bash
sail artisan migrate              # run pending migrations
sail artisan migrate:status       # show migration status
sail artisan migrate:rollback     # roll back the last batch
sail artisan migrate:fresh        # drop all tables and re-run every migration
```

### Seed the database

```bash
sail artisan db:seed                              # runs Database\Seeders\DatabaseSeeder
sail artisan db:seed --class=MangoVarietySeeder   # one specific seeder
sail artisan migrate:fresh --seed                 # nuke + migrate + seed in one shot
```

`DatabaseSeeder` currently seeds:
- `RolePermissionSeeder` — the 3 baseline roles (`superuser`, `editor`, `viewer`) and 5 permissions
- `MangoVarietySeeder` — the 12 featured mango varieties on the homepage

There is no seeded admin user. The **first** person to register through `/register` automatically receives the `superuser` role (see [`AssignSuperuserToFirstUser`](../app/Listeners/AssignSuperuserToFirstUser.php)).

To restore the previously-seeded `test@example.com` superuser for local dev, uncomment the block in [`DatabaseSeeder`](../database/seeders/DatabaseSeeder.php) and re-seed.

---

## 5. Database backup & restore (via Docker)

The Postgres data lives in the `sail-pgsql` named volume and is **not** deleted by `sail down`. Use these commands to snapshot the database itself.

> All commands assume the stack is running (`sail up -d`). They use `docker compose exec` rather than copying files in/out of the volume, so they work whether you're on Linux, macOS, or Windows + WSL.

### Backup to a SQL dump

```bash
# Dump the 'laravel' database into ./backups/<timestamp>.sql
mkdir -p backups
docker compose exec -T pgsql \
    pg_dump -U sail -d laravel --clean --if-exists --no-owner \
    > "backups/$(date +%Y%m%d-%H%M%S).sql"
```

For a smaller, faster custom-format dump (recommended for anything non-trivial):

```bash
docker compose exec -T pgsql \
    pg_dump -U sail -d laravel -F c --no-owner \
    > "backups/$(date +%Y%m%d-%H%M%S).dump"
```

### Restore from a SQL dump

```bash
# Plain SQL dump → pipe back through psql
docker compose exec -T pgsql psql -U sail -d laravel < backups/20260524-120000.sql

# Custom-format dump → use pg_restore
docker compose exec -T pgsql \
    pg_restore -U sail -d laravel --clean --if-exists --no-owner \
    < backups/20260524-120000.dump
```

> 💡 The dump uses `--clean --if-exists` so a restore drops and recreates objects rather than failing on conflicts. If you'd rather restore into a **fresh** DB, run `sail artisan migrate:fresh --no-seed` first (or `psql -c 'DROP SCHEMA public CASCADE; CREATE SCHEMA public;'`).

### Snapshot the entire volume (full disaster recovery)

```bash
# Tarball the whole pgsql data directory
docker run --rm \
    -v mango-orchard_sail-pgsql:/data \
    -v "$(pwd)/backups:/backup" \
    alpine tar czf /backup/sail-pgsql-$(date +%Y%m%d).tgz -C / data
```

To **restore from that tarball**, stop the stack, wipe the existing volume, and untar the snapshot back into it:

```bash
# 1. Stop everything that's using the volume.
sail down

# 2. Recreate the named volume empty (only needed if it already has data you want gone).
docker volume rm mango-orchard_sail-pgsql
docker volume create mango-orchard_sail-pgsql

# 3. Untar the snapshot back into the volume.
docker run --rm \
    -v mango-orchard_sail-pgsql:/data \
    -v "$(pwd)/backups:/backup" \
    alpine sh -c 'cd / && tar xzf /backup/sail-pgsql-YYYYMMDD.tgz'

# 4. Bring the stack back up.
sail up -d
```

> ⚠️ Replace `sail-pgsql-YYYYMMDD.tgz` with the actual tarball filename. Step 2 is destructive — only run it when you're certain you want to discard the current DB state.

---

## 6. Running tests

The test suite lives in `tests/` and is organised into three Pest suites declared in [`phpunit.xml`](../phpunit.xml):

| Suite | Location | What it covers |
|---|---|---|
| **Unit** | `tests/Unit/` | Pure PHP helpers, no Laravel boot |
| **Feature** | `tests/Feature/` | HTTP-level integration with the Laravel kernel + DB (`RefreshDatabase`, seeded roles) |
| **Browser** | `tests/Browser/` | End-to-end via Playwright — Pest's browser plugin starts an in-process Laravel server and drives a real browser |

### From the command line

```bash
sail bin pest                              # everything
sail bin pest --testsuite=Feature          # one suite
sail bin pest --testsuite=Browser
sail bin pest tests/Feature/TelemetryTest.php   # one file
sail bin pest --filter='registers a new user'   # filter by name
sail bin pest --bail                       # stop on first failure
sail bin pest --coverage                   # coverage (requires Xdebug/PCOV)
sail bin pest --parallel                   # parallel execution
```

> 💡 Browser tests take ~10–60s extra on the first run because Playwright spins up. The Laravel server is run **in-process** — no separate dev server needed.

### From a GUI / IDE

Both PhpStorm and VS Code can run the suite through the same `phpunit.xml` and produce the standard tree view of passes/failures.

**PhpStorm**

1. **Settings → PHP → CLI Interpreter →** add a *Docker Compose* interpreter pointing at `compose.yaml`, service `laravel.test`.
2. **Settings → PHP → Test Frameworks →** add **PHPUnit by Remote Interpreter**, choose the Docker Compose interpreter, set path to PHPUnit script: `/var/www/html/vendor/bin/pest`, and **Default configuration file**: `/var/www/html/phpunit.xml`.
3. Right-click any test file/folder/method → **Run 'Pest'**. Failures are clickable; assertion diffs render in the run panel.

**VS Code**

Install one of the Pest/PHPUnit extensions (e.g. *Better PHPUnit* or *Pest VSCode*), then configure the test command to:

```
docker compose exec -T laravel.test ./vendor/bin/pest
```

Both IDEs will pick up the `phpunit.xml` testsuite definitions automatically.

### Watching Browser tests headed (visual debugging)

By default the Playwright browser runs headless. To watch a Browser test execute in a real window, add this at the **top** of [`tests/Pest.php`](../tests/Pest.php) (or in the specific spec file):

```php
\Pest\Browser\Playwright\Playwright::headed();
```

You'll also need to forward an X server / use a VNC-enabled image if you're on a remote/headless Docker host. On a local Docker Desktop install on Linux/macOS the browser window pops up directly. Re-run with:

```bash
sail bin pest --testsuite=Browser
```

Remove the `Playwright::headed()` line before committing — CI expects headless.

### Test screenshots

When a Browser test fails it drops a screenshot into [`tests/Browser/Screenshots/`](../tests/Browser/Screenshots) named after the test. Open the PNG to see exactly what the browser was looking at when the assertion failed. The directory is git-ignored.

---

## 7. Common dev tasks

```bash
# Front-end
sail npm run dev          # Vite dev server with HMR (separate terminal)
sail npm run build        # production build

# Laravel artisan
sail artisan tinker       # REPL
sail artisan route:list
sail artisan about        # environment / package overview

# Database introspection
sail artisan db:show
sail artisan tinker --execute='dump(\App\Models\TelemetryEvent::latest()->take(5)->get())'

# Code style (Laravel Pint)
sail bin pint                 # auto-format the codebase
sail bin pint --test          # check without writing
```

### Checking email in dev

The compose stack ships a **Mailpit** container that catches every email the app sends and presents a web UI for browsing them — nothing actually leaves your machine, regardless of the recipient address.

| Where | URL / details |
|---|---|
| **Dashboard** (read caught mail) | **http://localhost:8025** |
| SMTP endpoint the app uses | `mailpit:1025` (container) / `localhost:1025` (host) |
| `.env` wiring | `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025` |

To verify mail is flowing, send a test from tinker:

```bash
sail artisan tinker --execute='Mail::raw("ping", fn ($m) => $m->to("you@example.com")->subject("hi"));'
```

Then refresh http://localhost:8025 — the message should be at the top of the inbox.

Mailpit also exposes a JSON API at `http://localhost:8025/api/v1/messages` (useful for scripted assertions in CI / dev tooling). Mailbox data persists across `sail down` (stored in the `sail-mailpit` named volume); wipe with `sail down -v` or delete from the dashboard.

To opt out of SMTP entirely in dev (Laravel-side, e.g. when running offline), set `MAIL_MAILER=log` in `.env` — messages then go to `storage/logs/laravel.log`.

### Optimising the hero photo

The welcome page's hero loads [`public/images/hero-orchard-photo.webp`](../public/images/hero-orchard-photo.webp). Browser AI-generated or stock orchard photos are usually multi-megabyte PNGs — way too big to ship as the first paint asset. The bundled script resizes + WebP-encodes them inside the Sail container (no host-side tooling needed):

```bash
# 1. Drop a high-res source at public/images/orchard-photo.png
cp ~/Downloads/whatever.png public/images/orchard-photo.png

# 2. Encode it. Output: public/images/hero-orchard-photo.webp (~200 KB at 1600px / q=85)
./scripts/optimize-hero.sh

# OR pass any path:
./scripts/optimize-hero.sh path/to/your-source.png
```

The script uses `ffmpeg` inside the `laravel.test` container, so you don't need ImageMagick / cwebp on the host. The welcome blade prefers WebP > JPG > PNG at the `hero-orchard-photo.*` path, so the next page load picks up the new file with no rebuild.

You can safely delete the source PNG afterwards — only the WebP is referenced.

---

## 8. Rules & gotchas

These are non-negotiable rules learned the hard way on this project. Read them before doing anything unusual.

### Always use Docker for deps and packages

Never run `npm`, `composer`, `php`, or `artisan` directly on the host. Always go through `sail …` (or `docker compose exec laravel.test …`). The host's PHP/Node/Composer versions will not match the container's and silent drift causes hours of debugging.

### Never edit anything under `vendor/`

`vendor/` is owned by Composer and gets blown away on every `composer install`/`update`. If a third-party package needs customising:

1. **Configuration** — most packages publish a config file (see [`config/captcha.php`](../config/captcha.php) for an example).
2. **Wrapper / decorator** — extend or compose the package class in `app/` (see [`App\Captcha\Captcha`](../app/Captcha/Captcha.php) wrapping `Mews\Captcha\Captcha`).
3. **Container rebind** — rebind the package's interface to a subclass in `AppServiceProvider::register()`.
4. **Pin a different version** with `sail composer require pkg:^X` and let Composer reinstall.
5. **Fork the package** as a last resort.

### `WithoutModelEvents` in seeders breaks things

The trait suppresses Eloquent events globally for the seeder run, which kills hooks like `MangoVariety`'s auto-slug saving listener. The bundled `DatabaseSeeder` deliberately does **not** use it. If you write a new seeder that touches models with `booted()` hooks, leave the trait off.

To silence telemetry specifically (e.g. when bulk-importing data), wrap the work in:

```php
\App\Telemetry\Telemetry::withoutRecording(function (): void {
    // ... your imports ...
});
```

### The first registered user becomes the superuser

Implemented by [`AssignSuperuserToFirstUser`](../app/Listeners/AssignSuperuserToFirstUser.php), which assigns the `superuser` role when registration fires the `Registered` event **and** no other user currently holds that role. After someone takes that role, subsequent registrations get no role until a superuser assigns one through `/admin/users`.

If you delete the only superuser, the next person to register reclaims the role.

### Captcha + autosolve

Captcha rendering on `/login` and `/register` is gated by the `captcha_enabled` setting at `/admin/settings`. Defaults to off. The companion `captcha_autosolve` toggle prefills the input with the correct answer (the server still validates normally) — useful for development and end-to-end tests; **do not enable it in production**.

### Telemetry events

Every meaningful user action records into the `telemetry_events` table via [`App\Telemetry\Telemetry`](../app/Telemetry/Telemetry.php). Browse the feed at `/admin/telemetry` as a superuser. Events are written synchronously; for production you'd want to queue them.

### Asset paths for mews/captcha

The published `config/captcha.php` points `fontsDirectory`/`bgsDirectory` at the vendor's bundled assets:

```php
'fontsDirectory' => dirname(__DIR__) . '/vendor/mews/captcha/assets/fonts',
'bgsDirectory'   => dirname(__DIR__) . '/vendor/mews/captcha/assets/backgrounds',
```

If you ever re-publish that config (e.g. `sail artisan vendor:publish --tag=captcha-config --force`), re-apply this change or the captcha image route will throw `DirectoryNotFoundException`.

### `intervention/image` is pinned to `^3.7`

[`composer.json`](../composer.json) pins `intervention/image` to v3 even though `mews/captcha` advertises support for v4 — at v4 the package calls `ImageManager::create()` which doesn't exist (renamed to `createImage()` in v4). Don't bump it back to v4 without verifying the captcha image route still works.

---

## 9. Useful links

| Resource | Path |
|---|---|
| Docker compose definition | [`compose.yaml`](../compose.yaml) |
| Routes | [`routes/web.php`](../routes/web.php), [`routes/auth.php`](../routes/auth.php) |
| Permissions catalogue | [`app/Permissions.php`](../app/Permissions.php) |
| Roles catalogue | [`app/Roles.php`](../app/Roles.php) |
| Telemetry event constants | [`app/Telemetry/Telemetry.php`](../app/Telemetry/Telemetry.php) |
| Settings keys | [`app/Settings/Settings.php`](../app/Settings/Settings.php) |
| Pest bootstrap | [`tests/Pest.php`](../tests/Pest.php) |
| PHPUnit / Pest config | [`phpunit.xml`](../phpunit.xml) |
