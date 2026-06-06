# 🥭 Aamar Malda

A small Laravel application that doubles as a field guide to the world's mango varieties **and** a worked example of building out a full admin-grade Laravel app — auth, onboarding, role-based permissions, settings, image captcha, and full-coverage telemetry — with Pest 4 + Playwright tests for every feature.

| | |
|---|---|
| **Live** | `http://localhost:8080` (after `sail up -d`) |
| **Stack** | Laravel 13 · PHP 8.5 · PostgreSQL 18 · Redis · Tailwind 3 · Alpine · Pest 4 + Playwright |
| **Auth** | Laravel Breeze (Blade stack) |
| **Permissions** | spatie/laravel-permission |
| **Tests** | 212 (Unit + Feature + Browser) — `sail bin pest` |

---

## Features

- 🌱 **Public mango showcase** — 12 curated varieties with origins, season calendar, and tasting notes.
- 🔐 **Auth + multi-step onboarding** (region → expertise → preferences) gated by middleware until complete.
- 👥 **Roles & permissions** — `superuser`, `curator`, `viewer` baseline (plus `grower`, `impersonator`, `event-manager`); admin UI for managing users and roles.
- 🥭 **Variety CRUD** gated by the `varieties.manage` permission.
- 🧺 **Grower marketplace** — users with the `grower` role (or any superuser) can list mangoes they grow (farm name, location, availability window, price, contact). Public browse at `/listings`, owner-only CRUD at `/my/listings`. Listing creation is gated by the `listings.manage` permission; ownership is enforced by `ListingPolicy`.
- 🤖 **Captcha** on login/registration via `mews/captcha`, toggleable from the admin Settings page, with an **autosolve** dev mode that prefills the real answer.
- 📊 **Telemetry** — every meaningful action (auth, onboarding, CRUD, role assignment, settings change, captcha fail) lands in a queryable activity feed at `/admin/telemetry`.
- 👑 **First-user-is-superuser** — the very first person who registers automatically becomes the superuser.

## Screens at a glance

| Public | Authed |
|---|---|
| `/` — homepage with hero + variety cards | `/dashboard` — Breeze dashboard |
| `/varieties` — full variety listing | `/profile` — account settings |
| `/varieties/{slug}` — variety detail | `/onboarding/{step}` — wizard |
| `/listings` — grower marketplace (browse) | `/my/listings` — your own listings |
| `/listings/{id}` — listing detail | `/my/listings/create` — list a variety |
| `/login`, `/register` — Breeze auth | `/admin/users` — assign roles |
| | `/admin/roles` — manage roles & permissions |
| | `/admin/settings` — captcha + app-wide toggles |
| | `/admin/telemetry` — activity feed |

## 30-second quick start

The entire stack runs in Docker via Laravel Sail. You only need **Docker** and **Git** on the host.

```bash
git clone <repo-url> mango-orchard
cd mango-orchard
cp .env.example .env

./vendor/laravel/sail/bin/sail up -d
./vendor/laravel/sail/bin/sail composer install
./vendor/laravel/sail/bin/sail npm install
./vendor/laravel/sail/bin/sail npx playwright install
./vendor/laravel/sail/bin/sail artisan key:generate
./vendor/laravel/sail/bin/sail artisan migrate --seed
./vendor/laravel/sail/bin/sail npm run build
```

Open **http://localhost:8080**, register an account — the first registration becomes the superuser.

### Where things live

| URL | What |
|---|---|
| **http://localhost:8080** | the app |
| **http://localhost:8025** | **Mailpit dashboard** — every email the app sends is caught here (password resets, verification mails, etc.). SMTP is `localhost:1025` from the host, `mailpit:1025` from inside the container. [Details](docs/README.md#checking-email-in-dev). |
| `localhost:5433` | Postgres (user `sail`, password `password`, db `laravel`) |
| `localhost:6380` | Redis |

For the **full** setup guide (with IDE-driven test runs, headed browser debugging, DB backup/restore, port table, gotchas) see:

➡️ **[`docs/README.md`](docs/README.md)**

## Running the tests

```bash
./vendor/laravel/sail/bin/sail bin pest                       # everything
./vendor/laravel/sail/bin/sail bin pest --testsuite=Feature   # one suite
./vendor/laravel/sail/bin/sail bin pest --testsuite=Browser   # Playwright e2e
```

Suites declared in [`phpunit.xml`](phpunit.xml): **Unit · Feature · Browser**. IDE setup (PhpStorm / VS Code) and headed-mode browser debugging are documented in [`docs/README.md#6-running-tests`](docs/README.md#6-running-tests).

## Project layout

```
app/
├── Captcha/             # Captcha wrapper (delegates to mews/captcha)
├── Http/Controllers/
│   ├── Admin/           # Users, Roles, Settings, Telemetry
│   └── My/              # Owner-only resources (e.g. ListingController)
├── Listeners/           # AssignSuperuserToFirstUser, RecordAuthTelemetry
├── Models/              # MangoVariety, Listing, User, TelemetryEvent
├── Observers/           # Telemetry observers for CRUD
├── Policies/            # ListingPolicy (ownership-based)
├── Settings/            # App-wide settings (cached k/v)
├── Telemetry/           # Telemetry service + event constants
├── Permissions.php      # Permission catalogue
└── Roles.php            # Role catalogue
resources/views/
├── admin/               # users · roles · settings · telemetry
├── auth/                # Breeze
├── onboarding/          # multi-step wizard
├── varieties/           # CRUD views
├── listings/            # public marketplace (index + detail)
├── my/listings/         # owner CRUD for the marketplace
└── welcome.blade.php    # public homepage
tests/
├── Browser/             # Pest + Playwright end-to-end
├── Feature/             # HTTP integration
└── Unit/
docs/
└── README.md            # Full setup & operations guide
```

## Hard rules (the kind that will bite you if ignored)

- 🐳 **Always use Sail for deps and packages.** Never run host `npm`, `composer`, `php`, or `artisan` — version drift will cause baffling failures. ([why](docs/README.md#always-use-docker-for-deps-and-packages))
- 🚫 **Never edit anything under `vendor/`.** Composer owns that directory and will overwrite your changes. Use config, wrappers, container rebinds, or version pins instead. ([allowed alternatives](docs/README.md#never-edit-anything-under-vendor))
- 🌪 **Don't add `WithoutModelEvents` to seeders that touch models with `booted()` hooks** — it silently breaks them. ([details](docs/README.md#withoutmodelevents-in-seeders-breaks-things))
- ⚠️ **Never enable captcha autosolve in production.** It prefills the correct answer in the UI; it's for dev/test only.

## License

The Laravel framework is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
