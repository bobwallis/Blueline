# [Choice] PHP version (use -bookworm variants on local arm64/Apple Silicon): 8, 8.5, 8.4, 8.3, 8.2, 8-bookworm, 8.5-bookworm, 8.4-bookworm, 8.3-bookworm, 8.2-bookworm, 8-trixie, 8.5-trixie, 8.4-trixie, 8.3-trixie, 8.2-trixie
ARG VARIANT=8.4-trixie
FROM mcr.microsoft.com/devcontainers/php:${VARIANT}

# Update args in docker-compose.yaml to set the UID/GID of the "vscode" user.
ARG USER_UID=1000
ARG USER_GID=$USER_UID
RUN if [ "$USER_GID" != "1000" ] || [ "$USER_UID" != "1000" ]; then \
        groupmod --gid $USER_GID vscode \
        && usermod --uid $USER_UID --gid $USER_GID vscode \
        && chmod -R $USER_UID:$USER_GID /home/vscode; \
    fi

# Node.js version from official tarballs (set to "none" to skip).
# - Exact pin example: NODE_VERSION="24.10.0"
# - Latest patch in major line: NODE_VERSION="24"
ARG NODE_VERSION="24"
RUN if [ "${NODE_VERSION}" != "none" ]; then \
        set -eux; \
        node_version="${NODE_VERSION}"; \
        if echo "${node_version}" | grep -Eq '^[0-9]+$'; then \
            node_version="$(curl -fsSL https://nodejs.org/dist/index.json | grep -o '"version":"v'"${NODE_VERSION}"'\.[0-9]\+\.[0-9]\+"' | head -n1 | sed -E 's/"version":"v([^\"]+)"/\1/')"; \
            if [ -z "${node_version}" ]; then echo "Could not resolve latest Node.js ${NODE_VERSION}.x"; exit 1; fi; \
        fi; \
        arch="$(dpkg --print-architecture)"; \
        case "$arch" in \
            amd64) node_arch='x64' ;; \
            arm64) node_arch='arm64' ;; \
            *) echo "Unsupported architecture: $arch"; exit 1 ;; \
        esac; \
        curl -fsSL "https://nodejs.org/dist/v${node_version}/node-v${node_version}-linux-${node_arch}.tar.gz" -o /tmp/node.tar.gz; \
        tar -xzf /tmp/node.tar.gz -C /usr/local --strip-components=1 --no-same-owner; \
        rm /tmp/node.tar.gz; \
        node --version; \
        npm --version; \
    fi

# Install additional OS packages and PHP extensions
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends wget curl ca-certificates unzip bash git \
        libpq-dev postgresql-client \
        libicu-dev \
        ripgrep \
    # Postgres
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    # Intl
    && docker-php-ext-install intl \
    # Disable Xdebug from the base image to avoid step-debug connection warnings.
    && if command -v phpdismod >/dev/null 2>&1; then phpdismod xdebug || true; fi \
    && find /usr/local/etc/php/conf.d -maxdepth 1 -type f -name '*xdebug*.ini' -delete \
    && apt-get clean \
    && pecl clear-cache \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* || true

# Install global node packages
RUN npm install -g gulp

# Install Symfony
RUN apt-get update && curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash && apt-get install -y symfony-cli
