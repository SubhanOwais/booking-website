# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Common commands

### One-time setup (fresh clone)
- Install PHP deps + create `.env` + generate app key + run migrations + install JS deps + build assets:
  - `composer run setup`

### Run the app locally
- Full dev loop (Laravel server + queue listener + logs via Pail + Vite dev server):
  - `composer run dev`

- If you only want the Vite dev server (front-end HMR):
  - `npm run dev`

- If you only want the Laravel HTTP server:
  - `php artisan serve`

### Build production assets
- `npm run build`

### Tests
- Run the full test suite (clears config first):
  - `composer run test`

- Run tests directly:
  - `php artisan test`

- Run a single test file:
  - `php artisan test tests/Feature/ProfileTest.php`

- Run a single test (by name/pattern):
  - `php artisan test --filter=ProfileTest`

### PHP formatting
- Laravel Pint (repo has Pint installed, no custom `pint.json`):
  - `php vendor/bin/pint`

## High-level architecture

### Backend (Laravel 12)
- HTTP entrypoint is `public/index.php`, which bootstraps the app via `bootstrap/app.php`.
- Routes are configured in `bootstrap/app.php` via `->withRouting(...)`:
  - Web routes: `routes/web.php`
  - Auth routes: `routes/auth.php` (included from `routes/web.php`)
  - Console commands: `routes/console.php`

### Inertia (server-driven SPA)
- The server returns Inertia responses from routes/controllers:
  - Example: `routes/web.php` uses `Inertia::render('Welcome', [...])` and `Inertia::render('Dashboard')`.
- Inertia middleware is wired into the web stack in `bootstrap/app.php`:
  - `App\Http\Middleware\HandleInertiaRequests`
- The Inertia “root view” is `resources/views/app.blade.php` (set by `HandleInertiaRequests::$rootView`).
  - The root view renders `@inertia` and includes assets via `@vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])`.

### Frontend (Vue 3 + Vite)
- Vite entrypoint: `resources/js/app.js`
  - Creates the Inertia app (`createInertiaApp`) and resolves page components from `resources/js/Pages/**/*.vue` using `laravel-vite-plugin` helpers.
- Page components live under `resources/js/Pages/`.
  - The string passed to `Inertia::render('...')` corresponds to a `.vue` file under `resources/js/Pages/` (e.g. `Inertia::render('Profile/Edit')` → `resources/js/Pages/Profile/Edit.vue`).
- Shared UI:
  - Layouts: `resources/js/Layouts/` (e.g. `AuthenticatedLayout.vue`)
  - Reusable components: `resources/js/Components/`
- Import aliasing:
  - `jsconfig.json` maps `@/*` to `resources/js/*`.

### Routing helpers in the frontend (Ziggy)
- Ziggy is enabled:
  - Blade includes `@routes` in `resources/views/app.blade.php`.
  - Vue app registers `ZiggyVue` in `resources/js/app.js`.
- Result: Vue components can call `route('route.name')` (see `resources/js/Layouts/AuthenticatedLayout.vue`).

### Styling
- Tailwind CSS is used.
  - Entry CSS: `resources/css/app.css`.
  - Tailwind content scanning includes Blade views and Vue files (see `tailwind.config.js`).

### Data layer
- Eloquent models live in `app/Models/` (default `User` model is present).
- Migrations/seeders/factories live under `database/`.
  - A local SQLite DB file exists at `database/database.sqlite`.

### Tests
- PHPUnit config: `phpunit.xml`.
  - Default test suites: `tests/Unit/` and `tests/Feature/`.
  - Testing environment defaults to in-memory SQLite (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` in `phpunit.xml`).
