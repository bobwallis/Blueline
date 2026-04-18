# Production Deployment

This guide covers production deployment and operations for Blueline.

## Deployment model

Blueline runs on a dedicated VM with:

- Debian 13 (Trixie)
- PostgreSQL for data storage
- FrankenPHP for serving the application
- Cloudflare Tunnel for secure public access, caching, and public ingress

The VM does not need to expose inbound ports directly.

## Production requirements

- A Debian 13 (Trixie) machine with internet access
- This repository cloned on the machine
- `sudo` access for the provisioning user
- A Cloudflare account with Zero Trust enabled

Any environment that can run the required software should run Blueline, but note that URL generation for method names relies on iconv transliteration (UTF-8 to ASCII). If using a non-Debian base image, ensure iconv transliteration support is available (for example, avoid musl-only environments like Alpine).

## Initial production setup

From the repository root:

1. Run `./bin/provision --help`.
2. Review arguments for your environment.
3. Run `./bin/provision` with any required flags.

If only part of the stack should be managed by Blueline provisioning (for example, if you are using an external database, alternate web server, or no Cloudflare tunnel), use the relevant command-line options.

## Provisioning script behavior

The script `./bin/provision` will:

- Update Debian packages to the latest versions
- Install PHP, Composer, Symfony CLI, PostgreSQL, FrankenPHP, locale, and timezone packages (via apt sources where needed).
- Set locale and timezone to the UK
- Install all of Blueline's PHP and NPM dependencies
- Configure PHP's OPcache according to Symfony's reccomended production settings
- Configure PostgreSQL with suitable settings for the expected database workload, and enable the systemd service
- Generate `APP_SECRET` and write `DATABASE_URL`, `APP_ENV`, `FRANKENPHP_PORT`, and local `ENDPOINT` to `.env.local`
- Create the database, set-up the schema and install the `fuzzystrmatch` extension
- Generate a managed `Caddyfile` for FrankenPHP worker mode
- Install and enable `blueline.service` to run FrankenPHP on configurable HTTPS localhost port
- Install cloudflared and walk through Cloudflare tunnel creation (interactive)
- Clear caches
- Warm production cache
- Build frontend JS, CSS and image assets
- Dump compiled `.env`
- Pre-generate error pages

Provisioning is idempotent where possible and safe to re-run.

## Data import

The provisioning script does not populate the database with data. Do this by running `./bin/update` or `bin/fetchAndImportData`.

## Ongoing production updates

Use:

- `./bin/update`

This updates application code, re-runs provisioning, and refreshes database data.

Before running in production, review:

- `./bin/update --help`

If only part of the stack should be managed by Blueline provisioning (for example, if you are using an external database, alternate web server, or no Cloudflare tunnel), use the relevant command-line options.

## Port and endpoint behavior

- `./bin/provision` asks for a FrankenPHP HTTPS port and stores it in `.env.local` as `FRANKENPHP_PORT`.
- Without a Cloudflare tunnel, `ENDPOINT` is set to `https://localhost:<FRANKENPHP_PORT>`.
- With a Cloudflare tunnel, the tunnel origin points at `https://localhost:<FRANKENPHP_PORT>` and `ENDPOINT` is updated to your public hostname.

## References

- [Development and installation guide](development-deployment.md)
- [Architecture and workflows](architecture-and-workflows.md)
- [Cloudflare Tunnel docs](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/)
