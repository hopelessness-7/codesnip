# syntax=docker/dockerfile:1
# docker/8.5/Dockerfile
# Debian bookworm + packages.sury.org PHP (same packages as Ondrej PPA, without Launchpad CDN —
# some networks block ppa.launchpadcontent.net with "connection refused".)
FROM debian:bookworm-slim

LABEL maintainer="Taylor Otwell"

ARG WWWGROUP=1000
ARG NODE_VERSION=24
ARG MYSQL_CLIENT="mariadb-client"
ARG POSTGRES_VERSION=18
# Set to 0 on locked-down networks that require internal DNS only.
ARG PUBLIC_BUILD_DNS=1

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV LANG=C.UTF-8
ENV PLAYWRIGHT_BROWSERS_PATH=0

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom && \
    echo 'Acquire::Retries "5";' >> /etc/apt/apt.conf.d/99custom && \
    echo 'Acquire::ForceIPv4 "true";' >> /etc/apt/apt.conf.d/99custom

RUN set -eux; \
    if [ "${PUBLIC_BUILD_DNS}" = "1" ]; then \
      rm -f /etc/resolv.conf && \
      { echo "nameserver 8.8.8.8"; echo "nameserver 1.1.1.1"; } > /etc/resolv.conf; \
    fi; \
    apt-get update && apt-get upgrade -y \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python3 dnsutils librsvg2-bin fswatch ffmpeg nano  \
    && curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /etc/apt/keyrings/sury-php.gpg \
    && chmod a+r /etc/apt/keyrings/sury-php.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/sury-php.gpg] https://packages.sury.org/php/ bookworm main" > /etc/apt/sources.list.d/php.list \
    && apt-get update \
    && apt-cache show php8.5-cli >/dev/null 2>&1 || (echo "ERROR: PHP 8.5 not available from packages.sury.org. Check HTTPS access to packages.sury.org from Docker."; exit 1) \
    && apt-get install -y \
        libgd3 \
        php8.5-cli \
        php8.5-dev \
        php8.5-fpm \
        php8.5-pgsql \
        php8.5-sqlite3 \
        php8.5-gd \
        php8.5-curl \
        php8.5-mongodb \
        php8.5-imap \
        php8.5-mysql \
        php8.5-mbstring \
        php8.5-xml \
        php8.5-zip \
        php8.5-bcmath \
        php8.5-soap \
        php8.5-intl \
        php8.5-readline \
        php8.5-ldap \
        php8.5-msgpack \
        php8.5-igbinary \
        php8.5-redis \
        php8.5-swoole \
        php8.5-memcached \
        php8.5-pcov \
        php8.5-imagick \
        php8.5-xdebug

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN set -eux; \
    if [ "${PUBLIC_BUILD_DNS}" = "1" ]; then \
      rm -f /etc/resolv.conf && \
      { echo "nameserver 8.8.8.8"; echo "nameserver 1.1.1.1"; } > /etc/resolv.conf; \
    fi; \
    apt-get update \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_VERSION.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && npm install -g pnpm \
    && npm install -g bun \
    && npx playwright install-deps \
    && corepack enable \
    && corepack prepare yarn@stable --activate \
    && curl -sS https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor | tee /etc/apt/keyrings/pgdg.gpg >/dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y $MYSQL_CLIENT \
    && apt-get install -y postgresql-client-$POSTGRES_VERSION \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Создаём конфигурацию Xdebug для CLI и FPM
RUN echo "xdebug.mode=develop,debug" > /etc/php/8.5/mods-available/xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /etc/php/8.5/mods-available/xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /etc/php/8.5/mods-available/xdebug.ini && \
    echo "xdebug.client_port=9003" >> /etc/php/8.5/mods-available/xdebug.ini && \
    echo "xdebug.idekey=PHPSTORM" >> /etc/php/8.5/mods-available/xdebug.ini && \
    echo "xdebug.discover_client_host=false" >> /etc/php/8.5/mods-available/xdebug.ini && \
    echo "xdebug.log_level=7" >> /etc/php/8.5/mods-available/xdebug.ini && \
    echo "xdebug.log=/var/www/html/storage/logs/xdebug.log" >> /etc/php/8.5/mods-available/xdebug.ini

# Включаем Xdebug (он уже установлен, нужно только активировать)docв
RUN phpenmod xdebug

# Настройка PHP-FPM с оптимизациями
RUN sed -i 's/^user = www-data/user = sail/' /etc/php/8.5/fpm/pool.d/www.conf && \
    sed -i 's/^group = www-data/group = sail/' /etc/php/8.5/fpm/pool.d/www.conf && \
    sed -i 's/^listen = .*/listen = 9000/' /etc/php/8.5/fpm/pool.d/www.conf && \
    sed -i 's/^listen\.owner = www-data/listen.owner = sail/' /etc/php/8.5/fpm/pool.d/www.conf && \
    sed -i 's/^listen\.group = www-data/listen.group = sail/' /etc/php/8.5/fpm/pool.d/www.conf && \
    sed -i 's/^pm = .*/pm = dynamic/' /etc/php/8.5/fpm/pool.d/www.conf && \
    sed -i 's/^pm\.max_children = .*/pm.max_children = 50/' /etc/php/8.5/fpm/pool.d/www.conf && \
    sed -i 's/^pm\.start_servers = .*/pm.start_servers = 10/' /etc/php/8.5/fpm/pool.d/www.conf && \
    sed -i 's/^pm\.min_spare_servers = .*/pm.min_spare_servers = 5/' /etc/php/8.5/fpm/pool.d/www.conf && \
    sed -i 's/^pm\.max_spare_servers = .*/pm.max_spare_servers = 30/' /etc/php/8.5/fpm/pool.d/www.conf && \
    sed -i 's/^;pm\.max_requests = .*/pm.max_requests = 500/' /etc/php/8.5/fpm/pool.d/www.conf && \
    sed -i 's/^;request_terminate_timeout = .*/request_terminate_timeout = 30s/' /etc/php/8.5/fpm/pool.d/www.conf && \
    echo "catch_workers_output = yes" >> /etc/php/8.5/fpm/pool.d/www.conf && \
    echo "decorate_workers_output = no" >> /etc/php/8.5/fpm/pool.d/www.conf && \
    echo "php_admin_value[error_log] = /var/www/html/storage/logs/php_errors.log" >> /etc/php/8.5/fpm/pool.d/www.conf && \
    echo "php_admin_flag[log_errors] = on" >> /etc/php/8.5/fpm/pool.d/www.conf

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.5

RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail
RUN git config --global --add safe.directory /var/www/html

# Создаём директории для логов и сессий
RUN mkdir -p /var/www/html/storage/logs && \
    mkdir -p /var/www/html/storage/framework/sessions && \
    mkdir -p /var/www/html/storage/framework/views && \
    mkdir -p /var/www/html/storage/framework/cache && \
    chown -R sail:sail /var/www/html/storage

# Копируем конфигурацию PHP
COPY php.ini /etc/php/8.5/cli/conf.d/99-sail.ini
COPY php.ini /etc/php/8.5/fpm/conf.d/99-sail.ini

# Копируем конфигурацию супервизора для запуска PHP-FPM
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY start-container /usr/local/bin/start-container

RUN chmod +x /usr/local/bin/start-container

EXPOSE 9000

ENTRYPOINT ["start-container"]
