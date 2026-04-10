FROM mcr.microsoft.com/devcontainers/base:trixie

# Set the locale
ENV LANG=en_GB.UTF-8 LC_ALL=en_GB.UTF-8
RUN echo "en_GB.UTF-8 UTF-8" >> /etc/locale.gen && locale-gen en_GB.UTF-8 && update-locale LANG=en_GB.UTF-8 LC_ALL=en_GB.UTF-8

# The bin/provision script does the below steps too, but we may as well do it here so it's cached in the Docker image

# Install OS packages and PHP extensions
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y upgrade --no-install-recommends \
    && apt-get -y install --no-install-recommends \
        apt-utils jq locales sudo zip wget tree lsof procps dirmngr psmisc net-tools unzip curl nano less tzdata zsh \
        ripgrep silversearcher-ag shellcheck \
        composer php8.4-cli php8.4-opcache php8.4-pgsql php8.4-intl php8.4-mbstring php8.4-xml php8.4-zip php8.4-curl \
        postgresql-17 postgresql-client-17

# Allow vscode user to sudo without password
RUN echo "vscode ALL=(ALL) NOPASSWD: ALL" > /etc/sudoers.d/vscode-nopasswd && chmod 0440 /etc/sudoers.d/vscode-nopasswd

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - && apt-get install -y nodejs

# Install Symfony
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash && apt-get install -y symfony-cli

# Clean up
RUN apt-get autoremove -y && apt-get clean -y && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* || true
