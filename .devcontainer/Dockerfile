# [Choice] PHP version (use -bookworm variants on local arm64/Apple Silicon): 8, 8.5, 8.4, 8.3, 8.2, 8-bookworm, 8.5-bookworm, 8.4-bookworm, 8.3-bookworm, 8.2-bookworm, 8-trixie, 8.5-trixie, 8.4-trixie, 8.3-trixie, 8.2-trixie
ARG VARIANT=8.4-trixie
FROM mcr.microsoft.com/devcontainers/php:${VARIANT}

# Update args in docker-compose.yaml to set the UID/GID of the "vscode" user.
ARG USER_UID=1000
ARG USER_GID=$USER_UID
RUN if [ "$USER_GID" != "1000" ] || [ "$USER_UID" != "1000" ]; then \
        groupmod --gid $USER_GID vscode \
        && usermod --uid $USER_UID --gid $USER_GID vscode \
        && chmod -R $USER_UID:$USER_GID /home/vscode \
        && chmod -R $USER_UID:root /usr/local/share/nvm; \
    fi

# Node.js version: none, lts/*, 16, 14, 12, 10
ARG NODE_VERSION="lts/*"
RUN if [ "${NODE_VERSION}" != "none" ]; then su vscode -c "umask 0002 && . /usr/local/share/nvm/nvm.sh && nvm install ${NODE_VERSION} 2>&1"; fi

# Install additional OS packages and PHP extensions
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends wget curl ca-certificates unzip bash git \
        libpq-dev postgresql-client \
        libicu-dev gifsicle \
    # Postgres
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    # Intl
    && docker-php-ext-install intl \
    # Xdebug
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && pecl clear-cache \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* || true

# Install global node packages
RUN su vscode -c "source /usr/local/share/nvm/nvm.sh && npm install -g gulp" 2>&1

# Install Symfony
RUN apt-get update && curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash && apt-get install -y symfony-cli
