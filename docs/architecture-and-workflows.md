# Architecture and workflows

## High-level architecture
- **Framework**: Symfony 7.4 application with Doctrine ORM and Twig.
- **Backend**: PHP domain logic in `src/`.
- **Frontend**: Source assets under `assets/`, managed by Symfony's AssetMapper with automatic versioning. Production builds use SensioLabs Minify Bundle for minification.
- **Image rendering**: Local Node.js sidecar using Puppeteer and Chromium for `.png` images.
- **Data store**: PostgreSQL (with `fuzzystrmatch` extension for search similarity features).

## Code structure
- `src/Controller/`: HTTP controllers.
- `src/Entity/`: Doctrine entities.
- `src/Repository/`: Doctrine query/repository logic.
- `src/Command/`: Symfony console commands used for import/export and maintenance.
- `src/Helpers/`: domain-specific helpers and lookup logic.
- `assets/`: JS and CSS used by the frontend.
- `templates/`: Twig views.
- `config/`: Symfony config and routes.
- `public/`: built frontend assets and entry points.
- `tests/`: PHPUnit tests.

## Core operational workflows

### Environment setup
Follow [development-deployment.md](development-deployment.md) or [production-deploymeny.md](production-deployment.md) for setup.

### Data import/update
Use:
- `./bin/fetchAndImportData`

This script downloads external method data, runs import commands, recalculates similarities, and bumps `DATABASE_UPDATE`.

### Frontend asset management

**Development**: Edit CSS/JS/image files in `assets/` and refresh your browser. Symfony's AssetMapper handles mapping and versioning automatically on each request—no build step required.

**Production**: Run `php bin/console asset-map:compile --env=prod` to compile assets and generate versioned files in `public/assets/` with minification applied via SensioLabs Minify Bundle. This is executed as part of `./bin/provision` and in the deployment pipeline.

**Asset Versioning**: AssetMapper generates content-hash-based filenames (e.g., `all-abc123.css`, `main-xyz789.js`) automatically, providing cache busting without additional configuration.

### PNG image generation

`.png` requests under `/methods/view...` are handled by Symfony but proxied to a local Node.js image server running `bin/image-server.mjs`.

- The image server opens the full method view page in Chromium via Puppeteer and takes a screenshot of it.
- Requests are processed sequentially. Concurrent image requests queue behind the active render.
- The browser instance is kept warm between requests and restarted after a configurable number of renders (`BROWSER_RESTART_AFTER`, default `200`) to limit memory growth over time.

### Quality checks
Use:
- `./bin/test`

For PHP style checks/fixes in `src/` and `tests/`:
- `symfony composer lint:php-style`
- `symfony composer fix:php-style`

This runs:
- A check that the test database exists and  has data
- Doctrine schema validation
- Symfony container lint
- Twig template lint
- PHP code lint
- PHP-CS-Fixer PSR-12 style check for `src/` and `tests/` (non-blocking)
- Frontend asset complication
- PHPUnit

Or run targeted tests directly with PHPUnit:
- `APP_ENV=test ./bin/phpunit tests/Controller`
- `APP_ENV=test ./bin/phpunit --filter testActionName tests/Controller/DefaultControllerTest.php`

## Change guidelines
- Keep changes minimal and localised.
- Preserve existing architecture and naming unless intentionally changing it.
- Prefer adding tests close to changed behavior.
- If adding a feature that affects setup or runbook steps, update `README.md` and other files in `./docs`.

### FrankenPHP worker safety
- `src/Command/` commands are run using `symfony console ...` commands, but HTTP requests may be run using FrankenPHP's worker mode, and so should be architected to work well in that scenario.
- Treat service instances as long-lived when running in worker mode.
- Do not cache request-derived values in service constructors or mutable service properties.
- Resolve request-dependent values at request/render time (for example via `RequestStack` in the method that needs the current request).
- Treat Doctrine query/result caches as app-level caches; do not clear them per request unless metrics show they are causing unacceptable pressure.
- Add request-end cleanup for stateful subsystems (for example clearing Doctrine's EntityManager on `kernel.terminate`) to avoid cross-request state reuse.

### Naming policy: PHP vs PostgreSQL
- **PHP code uses camelCase** for properties, array keys, and DTO-style payloads.
- **PostgreSQL identifiers remain lowercase** (unquoted behavior and existing schema conventions).
- **Doctrine is the mapping boundary** between camelCase PHP and lowercase database identifiers.

#### Practical rules
- In Doctrine ORM/DQL, use entity property names (camelCase).
- In raw SQL (DBAL), use real database identifiers (lowercase).
- If DBAL results are consumed as associative arrays and camelCase keys are needed in PHP, alias explicitly at the fetch boundary, for example: `notationexpanded AS "notationExpanded"`.
- Do not rely on unquoted mixed-case SQL identifiers; PostgreSQL folds them to lowercase.
