# AGENTS.md

## Project overview

- Stack: Symfony 7.4, PHP 8.4+, Doctrine ORM, Twig, PostgreSQL and AssetMapper.
- App namespace: `Blueline\` under `src/`.
- Match nearby naming and style; keep changes focused.
- See [architecture and workflows](docs/architecture-and-workflows.md) for architecture, worker-safety and naming guidance.

## Setup, build and test commands

- Use `./bin/test` only for full-suite validation; it does not accept path or filter arguments.
- For targeted tests, call PHPUnit directly, e.g. `./bin/phpunit tests/Controller` or `./bin/phpunit --filter testActionName tests/Controller/ExampleTest.php`.
- For PHP changes in `src/` or `tests/`, run `symfony composer lint:php-style`; use `symfony composer fix:php-style` to apply style fixes.
- Use `symfony composer <command>` for Composer operations and `symfony console <command>` for Symfony commands, including production commands. Raw `php` is appropriate for PHP tools such as PHPUnit and PHP-CS-Fixer.
- Include very slow command tests only when needed: `BLUELINE_RUN_SLOW_COMMAND_TESTS=1 ./bin/test`.
- For frontend changes, run `npm run lint` (or the relevant `lint:js`, `lint:css` or `lint:svg` script). `npm run lint` also includes the documentation checks.
- Use `npm run lint:js:fix` only to auto-fix JavaScript; always follow with `npm run lint:js`.
- For JavaScript behavior changes, run `npm run test:js`.
- In normal development, edit `assets/` and refresh the browser; build production assets with `./bin/buildFrontendAssets`.
- Refresh method data with `./bin/fetchAndImportData`; use `./bin/update` for the combined maintenance workflow.

The test environment uses the same database as development. The full suite can write to it, and `./bin/test` may prompt to create a schema or import data when the database is unavailable or empty; run it only where those effects are acceptable.

## Code expectations

- Prefer existing services, entities, repositories and helpers. Avoid unnecessary abstractions and dependencies.
- Keep behavior, naming and formatting consistent with nearby code; state material assumptions and clarify competing interpretations rather than silently choosing.
- For database changes, update Doctrine entities and keep the schema consistent with the repository's validation flow.
- For UI changes, prefer Twig and existing asset patterns; follow [design language](docs/design-language.md).
- Update the relevant documentation when behavior or developer workflows change.
- Follow the repository's PHP/PostgreSQL naming boundary and long-lived FrankenPHP worker-safety rules in the [architecture guide](docs/architecture-and-workflows.md).
- Prefer existing DQL helpers in `src/Doctrine/` over PHP-side query workarounds.

## Comments and documentation

- Add comments only when they explain non-obvious intent, constraints or algorithms; do not narrate straightforward code.
- Keep comments accurate as behavior changes, and use the conventions of the source language.
- Keep each documentation topic in its canonical guide; update links and related docs when moving material.
- Use sentence-case headings, British English and relative links between repository documents.

## Security and scope

- Do not rewrite large sections of legacy code unless explicitly requested.
- Do not change `.env*` defaults or deployment/runtime configuration without clear intent.
- Never commit secrets or credentials.
- Keep every change tied to the request; do not clean up unrelated dead code or formatting.
