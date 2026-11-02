# Architecture and workflows

**Purpose:** Describe the application structure and shared technical conventions that affect implementation.

**Audience:** Contributors and maintainers.

**Prerequisites:** Familiarity with PHP, Symfony and browser JavaScript is useful but not required.

## High-level architecture

- **Framework:** Symfony 7.4 application using Doctrine ORM and Twig.
- **Backend:** PHP domain logic and HTTP controllers in `src/`.
- **Frontend:** JavaScript and CSS in `assets/`, served through Symfony AssetMapper. Production asset output is built and minified during provisioning.
- **Image rendering:** Local Node.js sidecar using Puppeteer and Chromium to render method pages as PNG images.
- **Data store:** PostgreSQL, including the `fuzzystrmatch` extension used by search.

## Code structure

| Path | Responsibility |
| --- | --- |
| `src/Controller/` | HTTP endpoints and response creation. |
| `src/Entity/` | Doctrine entities and serialization. |
| `src/Repository/` | Doctrine query and repository logic. |
| `src/Command/` | Symfony console commands for import, export and maintenance. |
| `src/Helpers/` | Domain-specific helpers and lookup logic. |
| `src/Doctrine/` | Custom DQL functions. |
| `assets/` | Browser JavaScript, CSS and images. |
| `templates/` | Twig views and response templates. |
| `config/` | Symfony configuration and routes. |
| `public/` | Public entry points and generated production assets. |
| `tests/` | PHPUnit and JavaScript tests. |

## Core workflows

### Setup and data

Follow the [development guide](development-deployment.md) for local setup, tests and data import. Follow the [production guide](production-deployment.md) for deployment and live operations.

### Frontend assets

In development, edit files under `assets/` and refresh the browser; AssetMapper handles mapping and versioning without a manual build. Production compilation is part of provisioning; see [production deployment](production-deployment.md).

### PNG rendering

Requests for `.png` under `/methods/view...` are handled by Symfony and proxied to `bin/image-server.mjs`. The sidecar opens the full method view in Chromium via Puppeteer and takes a screenshot. Requests are processed sequentially, and Chromium restarts after a configurable number of renders (`BROWSER_RESTART_AFTER`, default `200`) to limit memory growth. See the [development guide](development-deployment.md#running-the-application) for startup commands and logs.

### Touch generation

Touch search behavior, controls and ringing rules have a dedicated [touch-generation guide](touch-generator.md).

## Offline and service worker

The service worker supports previously visited document pages when the network is unavailable:

- On installation it caches the application home page and its chromeless variant, then activates immediately and takes control of existing clients.
- It intercepts only `GET` requests for navigations/documents; it does not cache API, image or other subresource requests.
- It looks in the current version's cache first. On a network miss it fetches the page and stores successful responses.
- If fetching fails, it checks older caches before returning an offline message. Once a page is fetched successfully under the new version, older caches are deleted.
- The frontend can prefetch same-origin document URLs in chromeless form, with a timeout. This is used to preload links on mouseover.
- When a new worker takes control, the page may reload once. The frontend suppresses this automatic reload when available connection signals indicate data-saving or a slow connection; if the Network Information API is unavailable, it allows the reload.

Offline support is limited to pages already cached or available from a previous cache; it does not preload the whole database or similar.

## Change guidelines

- Keep changes minimal and localised; preserve existing architecture unless intentionally changing it.
- Add tests close to changed behavior.
- Keep each procedure or behavior in its canonical document; update the [documentation index](README.md) when adding maintained documentation.
- For UI changes, follow the tokens and patterns in [design language](design-language.md).
- Use `symfony console <command>` for Symfony commands.

## FrankenPHP worker safety

HTTP requests may run in FrankenPHP worker mode, where service instances remain alive across requests:

- Do not cache request-derived values in service constructors or mutable service properties.
- Resolve request-dependent values at request/render time, for example through `RequestStack`.
- Treat Doctrine query and result caches as application-level caches; do not clear them per request without evidence of unacceptable pressure.
- Preserve request-end cleanup for stateful subsystems, for example clearing Doctrine's EntityManager on `kernel.terminate`.

## PHP and PostgreSQL naming

- Use camelCase in PHP properties, entity fields and DTO-style keys.
- Use lowercase database identifiers in raw SQL and DBAL.
- Doctrine is the mapping boundary between PHP camelCase and PostgreSQL identifiers.
- In DQL, use entity property names. When DBAL results require camelCase array keys, alias columns explicitly at the fetch boundary, for example `notationexpanded AS "notationExpanded"`.
- Do not rely on unquoted mixed-case SQL identifiers; PostgreSQL folds them to lowercase.

## Related documentation

- [Development and installation](development-deployment.md)
- [Production deployment](production-deployment.md)
- [Touch generation](touch-generator.md)
- [Design language](design-language.md)
- [Data and copyright](data-and-copyright.md)
