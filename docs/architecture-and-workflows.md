# Blueline architecture and workflows

This guide is intended for both human contributors and AI coding assistants.

## High-level architecture
- **Framework**: Symfony 7.4 application with Doctrine ORM and Twig.
- **Backend**: PHP domain logic in `src/`.
- **Frontend**: Source assets under `src/Resources/`, built output in `public/` via Gulp.
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
1. Install PHP/Composer/Node/PostgreSQL prerequisites.
2. `symfony composer install`
3. `npm install && npm audit fix`
4. Create DB and schema.

### 2) Data import/update
Use:
- `./bin/fetchAndImportData`

This script downloads external method data, runs import commands, recalculates similarities, and bumps `DATABASE_UPDATE`.

### 3) Frontend asset build
Use:
- `./bin/buildFrontendAssets`

This script updates cache-busting values, rebuilds CSS/JS bundles, and regenerates images.

### 4) Quality checks
Use:
- `./test`

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
