# Update the VARIANT arg in docker-compose.yml to pick a PHP version: 8, 7, 7.4, 7.3
ARG VARIANT=8
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

# Update expired keys
RUN for K in $(apt-key list | grep expired | cut -d'/' -f2 | cut -d' ' -f1); do sudo apt-key adv --recv-keys --keyserver keys.gnupg.net $K; done

# Install a version of Node.js using nvm for front end dev
ARG INSTALL_NODE="true"
ARG NODE_VERSION="lts/*"
RUN if [ "${INSTALL_NODE}" = "true" ]; then su vscode -c "source /usr/local/share/nvm/nvm.sh && nvm install ${NODE_VERSION} 2>&1"; fi

# Install additional OS packages and PHP extensions
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends wget curl ca-certificates unzip bash git \
        # Postgres
        libpq-dev postgresql-client \
        # Gifsicle
        zlib1g-dev libicu-dev g++ automake \
        # Pupeteer
        dumb-init libnss3 libatk1.0 libatk-bridge2.0 libx11-xcb1 libxcb-dri3-0 libxcomposite1 libxdamage1 libcups2 libdrm2 libxrandr2 libgbm1 libasound2 libpangocairo-1.0 libgtk-3-0 libxshmfence1 \
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
RUN wget https://get.symfony.com/cli/installer -O - | bash && ln -s /root/.symfony/bin/symfony /usr/local/bin/symfony

# Cleanup
RUN apt-get clean \
    && pecl clear-cache \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* || true

ENTRYPOINT ["dumb-init", "--"]
