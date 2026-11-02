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

`./bin/update` pulls code, reruns provisioning and refreshes method data by default. It supports `--nopull`, `--nodata`, `--nodatabase`, `--nocloudflared`, `--noserver` and `--env=dev|prod`. The default pull step restores `package-lock.json`, `composer.lock` and `symfony.lock` before `git pull`; preserve any local changes to these files before running the updater.

## Ports and public URL

Provisioning asks for a local FrankenPHP HTTPS port and stores it in `.env.local` as `FRANKENPHP_PORT`. Without a Cloudflare Tunnel, `DEFAULT_URI` is set to `https://localhost:<FRANKENPHP_PORT>`. With a tunnel, its origin points to that local HTTPS endpoint and `DEFAULT_URI` is updated to the configured public hostname.

## Related documentation

- [Development and installation](development-deployment.md)
- [Architecture and workflows](architecture-and-workflows.md)
- [API reference](api.md)
- [Cloudflare Tunnel documentation](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/)
