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

### Touch generation on method pages

- The **Touches** tab searches locally in browser web workers, showing distinct true touches ordered by length.
- [Compile.js](../assets/js/touch_generator/Compile.js) prepares the method and calls; [Search.js](../assets/js/touch_generator/Search.js) explores call sequences, checks every row for truth, and removes rotation-equivalent results.
- Touches start at the method's first change and end at the first return to rounds. Multipart discovery repeats shorter call patterns within the selected length limits.
- [TouchSearchPool.js](../assets/js/ui/MethodView/TouchSearchPool.js) shares work across short-base and full-depth searches, favouring multipart discovery while preserving early finishes.
- Workers resume saved search snapshots. Pruning tables skip impossible branches without replacing exact truth checks.
- Settings control lengths, minimum parts, calling positions, singles, result limits and search time. Pausing retains work; broader settings can restart exploration while retaining proved results.
- **Allowed calling positions** rejects disallowed calls during exploration, not just in the displayed results. The optional **Tenors together** switch intersects this filter with call-specific permissions derived from the plain lead's directed working-place cycle. At every lead head it preserves the two heaviest working bells' directed separation as at rounds: continuing plain until the heavier bell is home must bring the other bell home too. They need not be neighbours in the cycle (Cambridge Minor and Major are supported); Bristol's separation happens to be one lead. This is not an adjacency check on intermediate rows, nor only a check at Home positions actually reached in the touch. Multipart touches and display rotations are rechecked. The switch is off by default, hidden without defined calling positions, and disabled with an explanation unless the working places form one cycle and calls end at the lead head. Its note lists the method's permissions independently of other selected settings.
- Calling-position names for singles refer to the corresponding bob at the same decision, not simply the single's destination. With standard 14 bobs and 1234 singles (for example, Bristol or Cambridge Major), single Before finishes in 2nds while bob Before finishes in 3rds; single In finishes in 3rds while bob In finishes in 2nds. The optional single-position name Thirds (T) is not used: the label remains In (I). As the [CCCBR explanation](https://callingitround.cccbr.org.uk/docs/calling/3_demystifying_in_out_make/) notes, names vary by method and stage; no method-specific Out/Make override is applied.
- [Touches.js](../assets/js/ui/MethodView/Touches.js) handles the UI; [Presentation.js](../assets/js/touch_generator/Presentation.js) builds lead/course layouts, compact sequences and Siril exports.
- Source-file comments explain the algorithms, scheduling, worker loading and presentation rules in more detail. Regression tests live in [tests/JavaScript/TouchGenerator/](../tests/JavaScript/TouchGenerator/).

### PNG image generation

`.png` requests under `/methods/view...` are handled by Symfony but proxied to a local Node.js image server running `bin/image-server.mjs`.

- The image server opens the full method view page in Chromium via Puppeteer and takes a screenshot of it.
- Requests are processed sequentially. Concurrent image requests queue behind the active render.
- The browser instance is kept warm between requests and restarted after a configurable number of renders (`BROWSER_RESTART_AFTER`, default `200`) to limit memory growth over time.

### Quality checks
Use `./bin/test`, or other things as described in [development-deployment.md](development-deployment.md).

## Change guidelines
- Keep changes minimal and localised.
- Preserve existing architecture and naming unless intentionally changing it.
- Prefer adding tests close to changed behavior.
- If adding a feature that affects setup or runbook steps, update `README.md` and other files in `./docs`.
- For UI/styling changes, see `docs/design-language.md` for the colour, typography, spacing, and layout tokens/patterns to reuse.

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
