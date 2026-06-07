# Liberu CRM — AI Agent Guide

## Stack

- **PHP** ^8.5, **Laravel** ^13.0, **Laravel Octane** ^2.0
- **Vite** ^8.0 + **Tailwind CSS** ^4.3 + **PostCSS**
- **Filament** ^5.0 (admin panel), **Livewire** ^4.0, **Alpine.js**
- **MySQL** 8.0 / **SQLite** / **PostgreSQL**, **Redis** 7
- **Jetstream** (Livewire stack) + **Socialstream** (social auth)
- **stancl/tenancy** ^3.10 (multi-tenancy)
- **Laravel Reverb** ^1.0 (WebSockets), **Laravel Horizon** ^5.0 (queues)

## Key Directories

| Path | Purpose |
|------|---------|
| `app/Actions/` | Action classes (Fortify, Jetstream, Socialstream) |
| `app/Console/` | Artisan commands |
| `app/Events/` | Event classes |
| `app/Filament/` | Filament admin panel (Admin, App, Pages, Resources, Widgets) |
| `app/Helpers/` | Helper/utility functions |
| `app/Http/` | Controllers, Middleware, Requests, Livewire |
| `app/Jobs/` | Queued jobs |
| `app/Livewire/` | Livewire components |
| `app/Models/` | Eloquent models (~60) |
| `app/Modules/` | Modular architecture (BaseModule, ModuleManager) |
| `app/Notifications/` | Notifications |
| `app/Observers/` | Model observers |
| `app/Policies/` | Authorization policies |
| `app/Providers/` | Service providers (12) |
| `app/Services/` | Service classes (~40: Twilio, Facebook, Google, LinkedIn, MailChimp, etc.) |
| `app/Settings/` | Filament settings plugin |
| `app/Traits/` | Reusable traits |
| `app/View/` | View composers/components |
| `config/` | 26 config files |
| `database/migrations/` | 88 migration files |
| `database/factories/` | Model factories |
| `database/seeders/` | Seeders |
| `resources/views/` | Blade templates |
| `routes/` | api.php, web.php, console.php, channels.php, socialstream.php |
| `tests/Unit/` | Unit tests (24) |
| `tests/Feature/` | Feature tests (49) |
| `tests/Browser/` | Browser tests |
| `docs/` | Documentation |

## Commands

```bash
# Tests
php artisan test                              # Run all tests (SQLite :memory:)
php artisan test --coverage-clover            # Run with coverage
php artisan test --filter=SomeTestClass

# Static analysis
./vendor/bin/phpstan analyse
composer analyse                              # Alias for phpstan

# Code style
./vendor/bin/pint                             # Laravel Pint (PSR-12 + Laravel conventions)

# Refactoring
./vendor/bin/rector process --dry-run         # Dry run
./vendor/bin/rector process                   # Apply Rector rules

# Artisan
php artisan migrate
php artisan db:seed
php artisan make:model ModelName -mf
php artisan make:filament-resource ResourceName
php artisan make:livewire ComponentName

# Dependencies
composer install
composer update
npm install && npm run build
```

## Coding Conventions

### PHP
- **Laravel Pint** defaults (PSR-12 + Laravel conventions). No custom pint config.
- **PHPStan** level 2, analyzes `app/`. Baseline at `phpstan-baseline.neon`.
- **Rector** enabled with CODE_QUALITY, DEAD_CODE, TYPE_DECLARATION sets (PHP 8.4).
- PSR-4 autoloading: `App\` → `app/`, `Database\Factories\` → `database/factories/`, `Tests\` → `tests/`.
- Use type hints, return types, and strict types where possible.
- Avoid `dd()`, `dump()`, `var_dump()` in committed code.
- Name migrations with Laravel's default timestamp format.

### Models
- Located in `app/Models/`.
- Use `HasFactory`, `HasRoles` (Spatie), `HasSlug`, etc. as traits.
- Define `$fillable`, `$casts`, relationships, and query scopes.

### Controllers
- Keep thin — delegate business logic to service classes (`app/Services/`).
- Use Form Requests for validation (`app/Http/Requests/`).

### Services
- One class per external integration (Twilio, Facebook, Google, LinkedIn, etc.).
- Inject dependencies via constructor.

### Livewire
- Components in `app/Livewire/`.
- Views in `resources/views/livewire/`.
- Follow standard Livewire lifecycle (`mount`, `render`, `updated`, actions).

### Filament
- Resources in `app/Filament/App/Resources/` (tenant-scoped) or `app/Filament/Admin/Resources/`.
- Pages, Widgets follow same pattern.

### Modules
- Custom modular system in `app/Modules/`.
- Each module extends `BaseModule` and is registered via `ModuleServiceProvider`.

### Tests
- **PHPUnit** ^13.0 with **Mockery** ^1.6 + **FakerPHP** ^1.23.
- Database: SQLite `:memory:` for tests.
- Cache driver: `array`, Session: `array`, Queue: `sync`.
- Follow Laravel testing conventions: `Unit` for isolated logic, `Feature` for HTTP/workflow tests.
- Use model factories for test data.

### Database
- Default driver: MySQL (configurable via `DB_CONNECTION`).
- Multi-tenancy via `stancl/tenancy` v3.10.
- 88 migrations, timestamped naming.

### Frontend
- Vite build with `laravel-vite-plugin`.
- Tailwind CSS v4 with `@tailwindcss/forms`, `@tailwindcss/typography` plugins.
- PostCSS with `postcss-nesting`.
- Preline UI components.
- JS entry: `resources/js/app.js`.

## CI / Quality Gates

- **GitHub Actions**: tests, install, Docker build, security scan (PHPCPD, PHP Insights, security checker).
- **Codecov** coverage upload.
- **Dependabot**: daily updates for Composer, npm, Actions.
- **Docker**: multi-stage build (PHP 8.5 Alpine + Octane/RoadRunner) with `docker-compose.yml`.

## Testing Locally

```bash
# Copy env and configure
cp .env.example .env
# Default .env uses SQLite, which is fine for local dev

# Install
composer install
npm install

# Run tests
php artisan test
```

## Agent Guidelines

1. **Never commit** unless explicitly asked.
2. **Run `php artisan test`** after making changes to verify nothing breaks.
3. **Run `./vendor/bin/pint`** to auto-fix code style.
4. **Run `./vendor/bin/phpstan analyse`** and fix new errors (baseline is OK for pre-existing).
5. Prefer **editing existing files** over creating new ones.
6. Match the **existing code style** — look at neighboring files for patterns.
7. Use **dependency injection** over facades where possible.
8. Keep **service classes** thin — one responsibility per class.
9. Write or update **tests** when adding/changing behavior.
10. Follow **Laravel naming conventions**: camelCase methods, snake_case database columns, PascalCase classes.

# Project Rules: Strict Anti-Slop Policy

## 1. Output Constraints
* **Absolute Completeness:** Never write `// TODO`, `// ... implement later`, or leave logic stubbed. Write full, working implementations. If you cannot complete a function, explain why technically in exactly two bullet points rather than leaving empty blocks.
* **No Obvious Comments:** Do not document self-documenting code. Delete lines like `// increments i` or `// initialize list`. Only write comments for highly complex, unintuitive algorithms.
* **Zero Conversational Slop:** Do not include conversational preambles or postambles (e.g., "Sure, let's fix that error for you..."). Output **only** the code edits or highly dense, bulleted technical steps.
* **No Speculative Dependencies:** Only use modern, stable packages that already exist in this local workspace. Do not guess or hallucinate API structures or download untrusted extensions.

## 2. Refactoring & Tool Protocol
* **Surgical Edits:** When using your `edit` or `write` tools, modify *only* the specific blocks that require changes. Do not replace large sections of unchanged file logic with boilerplate.
* **LSP Compliance:** All written code must clear local language server (LSP) diagnostics. Check for type safety and resolve errors locally before concluding the task.
* **Error Hygiene:** Wrap I/O operations, API calls, and unsafe state adjustments in defensive error handling blocks. Do not assume clean input profiles.
