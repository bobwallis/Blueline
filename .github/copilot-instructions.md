# Copilot instructions for Blueline

## Project baseline
- Stack: Symfony 7.4 (PHP 8.4+), Doctrine ORM, Twig templates, PostgreSQL, AssetMapper-managed frontend assets.
- App namespace is `Blueline\\` under `src/`.
- Match coding style and naming in nearby files.
- Keep changes small and focused; avoid unrelated refactors.

## Important directories
- `src/Controller/`: HTTP controllers.
- `src/Entity/`: Doctrine entities.
- `src/Repository/`: Doctrine query/repository logic.
- `src/Command/`: Symfony console commands used for import/export and maintenance.
- `src/EventListener/`: Request/response lifecycle hooks and worker cleanup.
- `src/Doctrine/`: custom Doctrine DQL functions used by repositories and queries.
- `src/Twig/`: Twig extensions and template-level helpers.
- `src/Helpers/`: domain helpers and lookup logic.
- `assets/`: JS and CSS used by the frontend.
- `templates/`: Twig views.
- `config/`: Symfony config and routes.
- `public/`: built frontend assets and entry points.
- `tests/`: PHPUnit tests.

## Development workflow
- Use `./bin/test` only for full-suite validation.
- Do not pass path/filter arguments to `./bin/test`; it does not support targeted subsets.
- For targeted tests, call PHPUnit directly, e.g. `APP_ENV=test ./bin/phpunit tests/Controller` or `APP_ENV=test ./bin/phpunit --filter <name> <path>`.
- For PHP changes in `src/` or `tests/`, run `symfony composer lint:php-style` (or `symfony composer fix:php-style`) to enforce Symfony coding style via PHP-CS-Fixer.
- Include very slow command tests only when needed with `BLUELINE_RUN_SLOW_COMMAND_TESTS=1 ./bin/test`.
- For frontend iteration, run `npm run lint` (or `lint:js`, `lint:css`, `lint:svg`) when changing files under `assets/`.
- In normal dev loops, edit `assets/` and refresh the browser (no manual compile step).
- Build frontend assets via `./bin/buildFrontendAssets` when validating production asset output.
- Refresh method data via `./bin/fetchAndImportData` when relevant.
- Use `./bin/update` as the maintenance entry point for pull/provision/data refresh flows.

## Coding expectations for generated changes
- Prefer existing services, entities, repositories, and helpers over creating new abstractions.
- For database changes, update Doctrine entities and keep entity/schema in sync with this repo's schema validation flow.
- For UI changes, prefer Twig templates and existing assets pipeline patterns.
- Avoid introducing new dependencies unless clearly justified.
- Update docs when behavior or developer workflow changes.

### FrankenPHP worker safety
- Assume HTTP code can run in worker mode with long-lived service instances.
- Do not cache request-derived values in service constructors or mutable service properties.
- Resolve request-dependent values at request/render time (for example via `RequestStack`).
- Preserve request-end cleanup patterns for stateful subsystems (for example, `kernel.terminate` listeners).

### Naming policy (PHP vs PostgreSQL)
- Use camelCase in PHP code (entity properties, DTO keys, repository-facing fields).
- Use lowercase database identifiers in raw SQL/DBAL.
- Treat Doctrine as the naming boundary between PHP camelCase and SQL lowercase conventions.
- Alias DBAL select columns explicitly when callers require camelCase keys.

### Service and query patterns
- Services under `src/` are auto-wired/auto-configured except excluded paths in `config/services.yaml` (including `src/Helpers/`).
- Prefer existing custom DQL helpers in `src/Doctrine/` (for example, Levenshtein and regex functions) before adding PHP-side query workarounds.

## Testing expectations
- Add or update targeted tests in `tests/` when changing behavior.
- At minimum, run `./bin/test` for validation before submitting.
- During iteration, run only relevant tests via direct PHPUnit commands instead of trying to scope `./bin/test`.
- For style-only PHP changes, run `symfony composer lint:php-style` at minimum before final validation.
- When changing frontend assets, ensure CSS/JS/SVG linting passes (`npm run lint`) before final validation.
- `./bin/test` may prompt to create/populate the test database when missing.

## Safety constraints
- Do not delete or rewrite large sections of legacy code unless explicitly requested.
- Do not modify `.env*` defaults or deployment/runtime config without clear intent in the task.
- Never commit secrets or credentials.

## Scope notes
- Keep this file focused on coding-impact rules; avoid copying full operations runbooks.
- For deeper rationale on architecture/workflow constraints, consult `docs/architecture-and-workflows.md`.
