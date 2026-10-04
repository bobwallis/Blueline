# AGENTS.md

## Project overview
- Stack: Symfony 7.4 (PHP 8.4+), Doctrine ORM, Twig templates, PostgreSQL, AssetMapper-managed frontend assets.
- App namespace is `Blueline\\` under `src/`.
- Match coding style and naming in nearby files.
- Keep changes small and focused; avoid unrelated refactors.
- See [docs/architecture-and-workflows.md](docs/architecture-and-workflows.md) for the full code structure and operational runbooks.

## Setup, build, and test commands
- Use `./bin/test` only for full-suite validation.
- Do not pass path/filter arguments to `./bin/test`; it does not support targeted subsets.
- For targeted tests, call PHPUnit directly, e.g. `./bin/phpunit tests/Controller` or `./bin/phpunit --filter <name> <path>`.
- For PHP changes in `src/` or `tests/`, run `symfony composer lint:php-style` (or `symfony composer fix:php-style`) to enforce Symfony coding style via PHP-CS-Fixer.
- Use `symfony composer <command>` for Composer operations. Use `symfony console <command>` (including production commands); do not invoke as `php bin/console` or `./bin/console`. Raw `php` remains appropriate for PHP-specific tools such as linting and PHPUnit.
- Include very slow command tests only when needed with `BLUELINE_RUN_SLOW_COMMAND_TESTS=1 ./bin/test`.
- For frontend iteration, run `npm run lint` (or `lint:js`, `lint:css`, `lint:svg`) when changing files under `assets/`.
- To auto-fix JS style violations, run `npm run lint:js:fix` (applies ESLint `--fix`). Always run `npm run lint:js` afterwards to confirm no unfixable errors remain.
- In normal dev loops, edit `assets/` and refresh the browser (no manual compile step).
- Build frontend assets via `./bin/buildFrontendAssets` when validating production asset output.
- Refresh method data via `./bin/fetchAndImportData` when relevant.
- Use `./bin/update` as the maintenance entry point for pull/provision/data refresh flows.

## Code style and coding expectations
- Prefer existing services, entities, repositories, and helpers over creating new abstractions.
- State material assumptions when they affect the implementation; if the request is ambiguous, surface the competing interpretations instead of choosing silently.
- Prefer the simplest implementation that satisfies the request; avoid speculative flexibility, abstractions, or handling for scenarios the task does not require.
- Do not introduce helper methods, local helper functions, or one-off variables for logic that is only used once; inline it unless reuse or readability clearly justifies extraction.
- If a simpler or lower-risk approach exists, call it out before adding complexity.
- For database changes, update Doctrine entities and keep entity/schema in sync with this repo's schema validation flow.
- For UI changes, prefer Twig templates and existing assets pipeline patterns. See [docs/design-language.md](docs/design-language.md) for the colour, typography, spacing, and layout conventions to follow.
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

## Testing instructions
- Add or update targeted tests in `tests/` when changing behavior.
- At minimum, run `./bin/test` for validation before submitting.
- During iteration, run only relevant tests via direct PHPUnit commands instead of trying to scope `./bin/test`.
- For style-only PHP changes, run `symfony composer lint:php-style` at minimum before final validation.
- When changing frontend assets, ensure CSS/JS/SVG linting passes (`npm run lint`) before final validation.
- `./bin/test` may prompt to create/populate the test database when missing.

## Security considerations
- Do not delete or rewrite large sections of legacy code unless explicitly requested.
- Do not modify `.env*` defaults or deployment/runtime config without clear intent in the task.
- Never commit secrets or credentials.

## Output and edit behavior
- Prefer balanced edits: solve the task end-to-end while avoiding broad, unrelated refactors.
- Keep changes surgical: every changed line should trace back to the request or to cleanup made necessary by the change itself.
- Do not refactor adjacent code, comments, or formatting unless the task requires it.
- Remove imports, variables, or helpers made unused by your own change, but do not clean up pre-existing dead code unless asked.
- Keep diffs easy to review: preserve existing naming, formatting, and file structure unless the task requires changes.
- When changing multiple layers (for example, command + tests), complete both in the same change set when practical.
- For multi-step work, define short, verification-oriented success criteria and use them to drive the implementation.
- When fixing bugs, prefer a reproducing test or another concrete check before or alongside the code change.
- Explain validation clearly in final responses, including what was run and what was not run.

## Scope notes
- Keep this file focused on coding-impact rules; avoid copying full operations runbooks.
- For deeper rationale on architecture/workflow constraints, consult `docs/architecture-and-workflows.md`.
