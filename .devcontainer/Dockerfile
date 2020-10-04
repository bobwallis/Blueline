# Update the VARIANT arg in docker-compose.yml to pick a PHP version: 7, 7.4, 7.3
ARG VARIANT=7.4
FROM mcr.microsoft.com/vscode/devcontainers/php:0-${VARIANT}

# Update args in docker-compose.yaml to set the UID/GID of the "vscode" user.
ARG USER_UID=1000
ARG USER_GID=$USER_UID
RUN if [ "$USER_GID" != "1000" ] || [ "$USER_UID" != "1000" ]; then \
        groupmod --gid $USER_GID vscode \
        && usermod --uid $USER_UID --gid $USER_GID vscode \
        && chmod -R $USER_UID:$USER_GID /home/vscode \
        && chmod -R $USER_UID:root /usr/local/share/nvm; \
    fi

# Install a version of Node.js using nvm for front end dev
ARG INSTALL_NODE="true"
ARG NODE_VERSION="lts/*"
RUN if [ "${INSTALL_NODE}" = "true" ]; then su vscode -c "source /usr/local/share/nvm/nvm.sh && nvm install ${NODE_VERSION} 2>&1"; fi

# Install additional OS packages and PHP extensions
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends wget curl ca-certificates unzip bash git libpq-dev postgresql-client zlib1g-dev libicu-dev g++ automake \
    # Opcache
    && docker-php-ext-install opcache \
    && docker-php-ext-enable opcache \
    # Postgres
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    # Intl
    && docker-php-ext-install intl

# Install global node packages.
RUN su vscode -c "source /usr/local/share/nvm/nvm.sh && npm install -g gulp" 2>&1

# Install Symfony
RUN wget https://get.symfony.com/cli/installer -O - | bash && mv /root/.symfony/bin/symfony /usr/local/bin/symfony

# Cleanup
RUN apt-get clean \
    && pecl clear-cache \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* || true