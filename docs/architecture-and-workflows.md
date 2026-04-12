# Blueline architecture and workflows

This guide is intended for both human contributors and AI coding assistants.

## High-level architecture
- **Framework**: Symfony 7.4 application with Doctrine ORM and Twig.
- **Backend**: PHP domain logic in `src/`.
- **Frontend**: Source assets under `assets/`, managed by Symfony's AssetMapper with automatic versioning. Production builds use SensioLabs Minify Bundle for minification.
- **Data store**: PostgreSQL (with `fuzzystrmatch` extension for search similarity features).

## Code structure
- `src/Controller/`: Web endpoints and request handling.
- `src/Entity/`: Core persisted domain models (`Method`, `Collection`, `MethodSimilarity`, etc.).
- `src/Repository/`: Query logic and persistence access patterns.
- `src/Command/`: CLI flows for import/export/check tasks.
- `src/Helpers/`: Domain helper functions and classification/lookup utilities.
- `templates/`: Twig templates by feature area.
- `config/`: Symfony framework, routing, and package config.
- `tests/`: PHPUnit tests (controller/entity coverage, plus bootstrap).

## Core operational workflows

### 1) Environment setup
Follow `README.md` for full setup. In short:
1. Install PHP/Composer/PostgreSQL prerequisites, plus locale/timezone configuration.
2. `symfony composer install`
3. Ensure PostgreSQL is running locally, then create DB and schema.

### 2) Data import/update
Use:
- `./bin/fetchAndImportData`

This script downloads external method data, runs import commands, recalculates similarities, and bumps `DATABASE_UPDATE`.

### 3) Frontend asset management

**Development**: Edit CSS/JS/image files in `assets/` and refresh your browser. Symfony's AssetMapper handles mapping and versioning automatically on each request—no build step required.

**Production**: Run `php bin/console asset-map:compile --env=prod` to compile assets and generate versioned files in `public/assets/` with minification applied via SensioLabs Minify Bundle. This is executed as part of `./bin/provision` and in the deployment pipeline.

**Asset Versioning**: AssetMapper generates content-hash-based filenames (e.g., `all-abc123.css`, `main-xyz789.js`) automatically, providing cache busting without additional configuration.

### 4) Quality checks
Use:
- `./bin/test`

This runs:
- PHPUnit
- Symfony container lint
- Twig lint
- Doctrine schema validation
- Doctrine migrations up-to-date check

## Change guidelines
- Keep changes minimal and localised.
- Preserve existing architecture and naming unless intentionally changing it.
- Prefer adding tests close to changed behavior.
- If adding a feature that affects setup or runbook steps, update `README.md` and this document.

## Naming policy: PHP vs PostgreSQL
- **PHP code uses camelCase** for properties, array keys, and DTO-style payloads.
- **PostgreSQL identifiers remain lowercase** (unquoted behavior and existing schema conventions).
- **Doctrine is the mapping boundary** between camelCase PHP and lowercase database identifiers.

### Practical rules
- In Doctrine ORM/DQL, use entity property names (camelCase).
- In raw SQL (DBAL), use real database identifiers (lowercase).
- If DBAL results are consumed as associative arrays and camelCase keys are needed in PHP, alias explicitly at the fetch boundary, for example: `notationexpanded AS "notationExpanded"`.
- Do not rely on unquoted mixed-case SQL identifiers; PostgreSQL folds them to lowercase.
