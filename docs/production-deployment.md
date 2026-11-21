# Production deployment

**Purpose:** Explain the supported production deployment model, provisioning and update operations.

**Audience:** Maintainers and operators responsible for a production instance.

**Prerequisites:** Repository checkout, Debian 13 (Trixie) with internet access, `sudo` access for the provisioning user, and a Cloudflare account with Zero Trust when using the managed tunnel.

## Deployment model

The documented deployment uses a dedicated VM with PostgreSQL, FrankenPHP and a Cloudflare Tunnel. The VM does not need to expose inbound ports directly as external access is via the tunnel. Provisioning installs and manages the local services, configures the tunnel interactively, warms the production cache and builds production frontend assets.

Other environments may work if they provide the required software. Method URL generation uses iconv transliteration from UTF-8 to ASCII; non-Debian environments must provide compatible transliteration support (for example, Alpine's musl environment does not).

## Initial setup

From the repository root, review the script's current help and then select the options that match the deployment:

```sh
./bin/provision --help
./bin/provision --env=prod
```

Provisioning is idempotent where possible. Shared package, locale, dependency and environment setup is described in the [development guide](development-deployment.md#provisioning-behavior). Production-specific actions include:

- Configuring and enabling the local PostgreSQL service and database schema, including `fuzzystrmatch`, unless `--nodatabase` is used.
- Generating the managed Caddyfile and enabling `blueline.service` for FrankenPHP worker mode.
- Enabling `blueline-image.service` for Puppeteer image rendering, with lower CPU, I/O and out-of-memory priority than the main application and database services.
- Running the interactive Cloudflare Tunnel setup unless `--nocloudflared` is used.
- Clearing and warming the production cache, compiling frontend assets, dumping compiled environment variables and pre-generating error pages.

Provisioning writes the production application secret, database URL, environment and server port to `.env.local`. The tunnel origin uses the local HTTPS port; after tunnel setup, `DEFAULT_URI` is set to the public hostname.

| Option | Effect |
| --- | --- |
| `--env=prod` | Set the application environment to production. |
| `--nodatabase` | Skip local PostgreSQL installation and configuration. |
| `--nocloudflared` | Skip Cloudflare Tunnel setup. |
| `--noserver` | Skip FrankenPHP installation and service setup. |
| `--yes` | Answer yes to provisioning prompts non-interactively. |

When using an external database, alternate server or no tunnel, review the script and configure those external resources and environment values separately. `--nodatabase`, `--noserver` and `--nocloudflared` skip local setup; they do not provision replacements.

## Data import

Provisioning does not populate the database. Import or refresh method data with:

```sh
./bin/fetchAndImportData --env=prod
```

The standard update workflow also imports data unless `--nodata` is supplied.

## Ongoing updates

Review the available flags before updating:

```sh
./bin/update --help
./bin/update --env=prod
```

`./bin/update` discards local lockfile changes, pulls code with `--ff-only`, reruns provisioning and refreshes data. Use `--help` for skip flags and deployment options.

Deployment installs Composer/npm dependencies, then applies compatible npm security fixes. Production Composer installs exclude development packages. Audit failures produce a warning without blocking deployment.

## Ports and public URL

Provisioning asks for a local FrankenPHP HTTPS port and stores it in `.env.local` as `FRANKENPHP_PORT`. Without a Cloudflare Tunnel, `DEFAULT_URI` is set to `https://localhost:<FRANKENPHP_PORT>`. With a tunnel, its origin points to that local HTTPS endpoint and `DEFAULT_URI` is updated to the configured public hostname.

## PostgreSQL access

PostgreSQL uses loopback TCP; provisioning creates SCRAM-SHA-256 passwords.

| Identity | Access |
| --- | --- |
| `blueline_web` | Read application tables. |
| `blueline` | Own application objects; update schemas, import data and run maintenance. |
| PostgreSQL administrator | Local provisioning. |

Application logins have no superuser, database/role creation, replication or RLS-bypass privileges.

Runtime `DATABASE_URL` is in `.env.local`. Maintenance uses `.env.maintenance.local`: a Git-ignored, deployment-user-owned `0600` file containing `DATABASE_URL`. Imports load it explicitly, refresh runtime configuration and restart the managed web service.

FrankenPHP (`blueline-web`) and the renderer (`blueline-image`) use separate service accounts. Runtime credentials are readable by the web account; maintenance credentials by the deployment user.

Managed connections use `postgresql://ROLE:PASSWORD@localhost:5432/blueline?serverVersion=17&charset=utf8`. For custom connections, use `--nodatabase` and configure roles separately.

## PHP, Caddy and renderer boundaries

Verify CLI/import and ZTS platform requirements separately:

```sh
symfony php bin/check-php-platform --imports
frankenphp php-cli bin/check-php-platform
```

The renderer validates method paths, restricts requests to its local application origin and verifies HTTPS using its NSS CA store. Keep Chromium's sandbox enabled; investigate host restrictions before considering the risky `BROWSER_DISABLE_SANDBOX=1` override.

One render runs at a time, with ten pending requests by default. Overload returns uncached `503`; disconnects cancel work. The 18-second deadline includes queueing and stays below PHP's 20-second limit. No automatic retries.

For production server setup, `IMAGE_SERVER_CPU_QUOTA` defaults to `80%` of one CPU; `IMAGE_SERVER_MAX_PENDING` defaults to 10 (0–999).

## Related documentation

- [Development and installation](development-deployment.md)
- [Architecture and workflows](architecture-and-workflows.md)
- [API reference](api.md)
- [Cloudflare Tunnel documentation](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/)
