# Production deployment

**Purpose:** Explain the supported production deployment model, provisioning and update operations.

**Audience:** Maintainers and operators responsible for a production instance.

**Prerequisites:** Repository checkout, Debian 13 (Trixie) with internet access, `sudo` access for the provisioning user, and a Cloudflare account with Zero Trust when using the managed tunnel.

## Deployment model

Both deployment profiles use local PostgreSQL and a PNG renderer:

- **Cloudflared:** Cloudflare provides public HTTPS; cloudflared connects to loopback FrankenPHP using Caddy's private CA.
- **Direct:** Caddy (on which FrankenPHP is based) manages public HTTPS certificates, with a separate loopback HTTPS listener for the renderer.

Other environments may work if they provide the required software. Method URL generation uses iconv transliteration from UTF-8 to ASCII; non-Debian environments must provide compatible transliteration support (for example, Alpine's musl environment does not).

## Initial setup

From the repository root, review the script's current help and then select the options that match the deployment:

```sh
./bin/provision --help
./bin/provision --env=prod --deployment=cloudflared
```

Provisioning is idempotent where possible. Shared package, locale, dependency and environment setup is described in the [development guide](development-deployment.md#provisioning-behavior). Production-specific actions include:

- Configuring and enabling the local PostgreSQL service and database schema, including `fuzzystrmatch`, unless `--nodatabase` is used.
- Generating the managed Caddyfile and enabling `blueline.service` for FrankenPHP worker mode.
- Running FrankenPHP and the renderer as separate unprivileged service accounts.
- Enabling `blueline-image.service` with a CPU quota and a bounded queue.
- Setting up or updating the Cloudflare Tunnel for the `cloudflared` profile.
- Clearing and warming the production cache, compiling frontend assets, dumping compiled environment variables and pre-generating error pages.

Before non-interactive provisioning, set the public hostname (`PUBLIC_HOSTNAME` or `CLOUDFLARE_PUBLIC_HOSTNAME`) in `.env.local`.

Provisioning writes runtime settings to `.env.local` and maintenance credentials to `.env.maintenance.local`.

| Option | Effect |
| --- | --- |
| `--env=prod` | Set the application environment to production. |
| `--deployment=cloudflared\|direct` | Select ingress profile. |
| `--nodatabase` | Skip local PostgreSQL installation and configuration. |
| `--noserver` | Skip FrankenPHP installation and service setup. |
| `--yes` | Use provisioning defaults non-interactively. |

The selected profile is saved as `DEPLOYMENT_MODE` in `.env.local`. Cloudflared setup requires Cloudflare credentials; non-interactive runs require an existing `~/.cloudflared/cert.pem`.

With `--nodatabase` or `--noserver`, configure the external database or server separately, including runtime and maintenance database access.

Use a checkout such as `/srv/blueline`: web-readable and service-user read-only, except for cache/log/minification directories. Persistent service state lives in `/var/lib/blueline-web` and `/var/lib/blueline-image`.

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

`DEFAULT_URI` is the public HTTPS URL including any `PATH_PREFIX`.

### Cloudflared profile

`FRANKENPHP_PORT` defaults to loopback port `8443`. Cloudflared verifies `localhost` using `caPool: /etc/blueline/origin-ca.crt`.

Caddy's state is kept in `/var/lib/blueline-web`. Caddy does not install its CA into system-wide trust stores. Provisioning updates cloudflared's CA file and the renderer's trust store, then restarts the services. Rerun provisioning if Caddy's CA changes.

Provisioning updates the installed cloudflared unit's configuration: the managed home-directory file or `/etc/cloudflared/config.yml`. Other configurations require operator review.

Under this profile, all inbound connections can be dropped by the firewall for security. Cloudflared makes an outbound connection, which is then used for the tunnel.

### Direct profile

Set `PUBLIC_HOSTNAME` and point DNS to the public endpoint. Forward public TCP `443` to `FRANKENPHP_PORT` (default `8443`) and TCP `80` to `FRANKENPHP_HTTP_PORT` (default `8080`) for redirects and HTTP-01 validation. TLS-ALPN-01 validation uses public `443`.

The renderer uses a separate loopback HTTPS listener on `FRANKENPHP_RENDER_PORT` (default `9443`).

Configure firewall/NAT rules separately; keep PostgreSQL and image/renderer listeners private.

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

FrankenPHP logs errors to stderr, disables displayed errors and file uploads, and permits remote reads for oEmbed.

Verify CLI/import and ZTS platform requirements separately:

```sh
symfony php bin/check-php-platform --imports
frankenphp php-cli bin/check-php-platform
```

Caddy serves `public`, with PHP restricted to the front controller. `STATIC_FILES_ROOT` serves public files outside `PATH_PREFIX`; checkout paths are limited to `public`, and symlinks must stay within the static root. Symfony trusts Caddy's prefix header; Caddy strips forwarded scheme/host/port headers. Its administration API is disabled.

The renderer validates method paths, restricts requests to its local application origin and verifies HTTPS using its NSS CA store. Keep Chromium's sandbox enabled; investigate host restrictions before considering the risky `BROWSER_DISABLE_SANDBOX=1` override.

One render runs at a time, with ten pending requests by default. Overload returns uncached `503`; disconnects cancel work. The 18-second deadline includes queueing and stays below PHP's 20-second limit. No automatic retries.

For production server setup, `IMAGE_SERVER_CPU_QUOTA` defaults to `80%` of one CPU; `IMAGE_SERVER_MAX_PENDING` defaults to 10 (0–999).

## Staging verification and rollout

1. Back up deployment configuration, PostgreSQL and Caddy state.
2. On staging, check routing, public URLs, TLS/ACME, schema updates, imports, renderer sandboxing and service restarts.
3. Check runtime database reads, rejected writes, service-user permissions and effective worker settings.
4. Load-test PNG rendering and select a CPU quota from measured usage.

Roll back application/configuration and compatible database grants together; retain Caddy state for CA trust.

## Related documentation

- [Development and installation](development-deployment.md)
- [Architecture and workflows](architecture-and-workflows.md)
- [API reference](api.md)
- [Cloudflare Tunnel documentation](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/)
