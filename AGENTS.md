# AGENTS.md

## Project Overview

This is a Laravel 12 application running on PHP 8.2. It provides currency quotes and conversions for VES, USDT, USD, EUR, and COP.

The application uses:

- Blade templates and anonymous Blade components for the frontend.
- Tailwind CSS 4 and Vite for frontend assets.
- SQLite by default for local development and Docker Compose persistence.
- A service-oriented backend centered on `App\Services\CurrencyMarketService`.
- Nginx, PHP-FPM, and a Laravel scheduler as separate Docker Compose services.

## Repository Layout

- `app/Http/Controllers/`: HTTP coordination only.
- `app/Services/`: currency resolution, conversion, and market rules.
- `app/Console/Commands/`: quote collection commands.
- `app/Models/`: Eloquent models.
- `resources/views/components/`: reusable Blade components.
- `resources/views/welcome.blade.php`: landing page dashboard.
- `resources/js/`: browser behavior.
- `routes/web.php`: web and API routes.
- `routes/console.php`: Artisan command and scheduler definitions.
- `tests/Feature/ExampleTest.php`: currency, API, dashboard, and conversion regression coverage.
- `Dockerfile`: multi-stage app and Nginx image targets.
- `docker-compose.yml`: `app`, `nginx`, and `scheduler` services.

## Common Commands

Install and initialize locally:

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Run the application locally:

```powershell
php artisan serve
npm run dev
```

Run tests:

```powershell
php artisan test
php artisan test tests/Feature/ExampleTest.php
```

Run formatting:

```powershell
vendor/bin/pint
```

Build and run the Compose stack:

```powershell
docker compose up -d --build
docker compose ps
docker compose logs -f
docker compose down
```

The web application is available at `http://localhost:8000` by default. Set `APP_PORT` to change the host port.

## Scheduler

Scheduled commands are defined in `routes/console.php`. Defining a schedule does not execute it automatically.

Run the scheduler locally:

```powershell
php artisan schedule:work
```

Inspect registered tasks:

```powershell
php artisan schedule:list
```

The Compose `scheduler` service runs `php artisan schedule:work` separately from the web service.

Available quote commands include:

```powershell
php artisan app:queryBinance
php artisan app:queryCOP
php artisan app:queryBCV
```

## Currency Rules

Keep quote orientation explicit:

- A `VES -> ASSET` database quote stores the VES value per asset, such as `1 USD = 1250 VES`.
- Asset to VES uses the stored VES-per-asset rate.
- VES to asset uses the reciprocal rate.
- `COP -> USD` records represent COP per USD, so COP to USD requires inversion.
- COP conversions use the VES/USDT relationship as the VES anchor because USDT and USD are treated as equivalent for this market.
- COP history is displayed separately from the main VES asset chart and is derived daily from `COP/USD / VES/USDT`.

Business rules belong in `CurrencyMarketService`, not Blade templates or controllers. Controllers should load data, call services, and return responses/views.

## Frontend Conventions

- Use Blade components for reusable UI, especially under `resources/views/components/`.
- Keep conversion logic out of views and JavaScript; browser code should call the existing API when interactive conversion is needed.
- Historical chart data is prepared by the service and rendered by Blade.
- Preserve the existing visual language, asset ordering, and responsive layout.
- Build assets with `npm run build` before testing a production-like page or Docker image.

## Change Validation

For currency or dashboard changes, run:

```powershell
php artisan test tests/Feature/ExampleTest.php
```

Maintain at least 90% automated test coverage across the application. When adding or changing behavior, add focused regression tests and verify coverage with:

```powershell
php artisan test --coverage
```

Do not consider a change complete if coverage falls below 90%.

For frontend changes, also run:

```powershell
npm run build
```

For Docker changes, validate:

```powershell
docker compose config
docker compose up -d --build
Invoke-WebRequest http://localhost:8080
docker compose down
```

Do not commit generated local databases, secrets, `node_modules`, or `vendor` changes unless explicitly required. Preserve unrelated user changes in a dirty worktree.
