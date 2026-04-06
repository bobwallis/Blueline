# Copilot instructions for Blueline

## Project baseline
- Stack: Symfony 7.4 (PHP 8.2+), Doctrine ORM, Twig templates, PostgreSQL, Gulp-built frontend assets.
- App namespace is `Blueline\\` under `src/`.
- Use existing coding style and naming conventions in neighboring files.
- Keep changes small and focused; do not refactor unrelated code.

## Important directories
- `src/Controller/`: HTTP controllers.
- `src/Entity/`: Doctrine entities.
- `src/Repository/`: Doctrine query/repository logic.
- `src/Command/`: Symfony console commands used for import/export and maintenance.
- `src/Helpers/`: domain-specific helpers and lookup logic.
- `templates/`: Twig views.
- `config/`: Symfony config and routes.
- `migrations/`: Doctrine migrations.
- `public/`: built frontend assets and entry points.
- `tests/`: PHPUnit tests.

## Development workflow
- Run tests and lints via `./bin/test` for full-suite validation only.
- Do not pass path/filter arguments to `./bin/test`; it does not support targeted subsets.
- For targeted testing, call PHPUnit directly, e.g. `APP_ENV=test ./bin/phpunit tests/Controller` or `APP_ENV=test ./bin/phpunit --filter <name> <path>`.
- Build frontend assets via `./bin/buildFrontendAssets` (or `gulp watch` while iterating).
- Refresh method data via `./bin/fetchAndImportData` when relevant.

## Coding expectations for generated changes
- Prefer existing services, entities, repositories, and helpers over creating new abstractions.
- For database changes, use Doctrine migrations and keep entity/migration/schema in sync.
- For UI changes, prefer Twig templates and existing assets pipeline patterns.
- Avoid introducing new dependencies unless clearly justified.
- Update docs when behavior or developer workflow changes.

## Testing expectations
- Add or update targeted tests in `tests/` when changing behavior.
- At minimum, run `./bin/test` for validation before submitting.
- During iteration, run only relevant tests via direct PHPUnit commands instead of trying to scope `./bin/test`.

## Safety constraints
- Do not delete or rewrite large sections of legacy code unless explicitly requested.
- Do not modify `.env*` defaults or deployment/runtime config without clear intent in the task.
- Never commit secrets or credentials.
