# Supported PHP versions: 8.4, 8.5
ARG PHP_VERSION=8.5
ARG COMPOSER_VERSION=2.8

FROM composer:${COMPOSER_VERSION} AS vendor

FROM php:${PHP_VERSION}-cli-alpine

LABEL maintainer="Liberu Team"
LABEL org.opencontainers.image.title="Liberu CRM"
LABEL org.opencontainers.image.description="Production-ready Dockerfile for Liberu CRM (Laravel Octane)"
LABEL org.opencontainers.image.source=https://github.com/liberu-crm/crm-laravel
LABEL org.opencontainers.image.licenses=MIT

ARG USER_ID=1000
ARG GROUP_ID=1000
ARG TZ=UTC

ENV TERM=xterm-color \
    OCTANE_SERVER=roadrunner \
    TZ=${TZ} \
    LANG=C.UTF-8 \
    USER=octane \
    ROOT=/var/www/html \
    APP_ENV=production \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=48 \
    WITH_HORIZON=false \
    WITH_SCHEDULER=false \
    WITH_REVERB=false

WORKDIR ${ROOT}

SHELL ["/bin/sh", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo ${TZ} > /etc/timezone

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apk update; \
    apk upgrade; \
    apk add --no-cache \
    curl \
    wget \
    vim \
    tzdata \
    ncdu \
    procps \
    unzip \
    ca-certificates \
    bash \
    supervisor \
    libsodium-dev \
    && install-php-extensions \
    apcu \
    bz2 \
    pcntl \
    mbstring \
    bcmath \
    sockets \
    pgsql \
    pdo_pgsql \
    opcache \
    exif \
    pdo_mysql \
    zip \
    intl \
    gd \
    redis \
    igbinary \
    ffi \
    ldap \
    && docker-php-source delete \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

RUN arch="$(apk --print-arch)" \
    && case "$arch" in \
    armhf) _cronic_fname='supercronic-linux-arm' ;; \
    aarch64) _cronic_fname='supercronic-linux-arm64' ;; \
    x86_64) _cronic_fname='supercronic-linux-amd64' ;; \
    x86) _cronic_fname='supercronic-linux-386' ;; \
    *) echo >&2 "error: unsupported architecture: $arch"; exit 1 ;; \
    esac \
    && wget -q "https://github.com/aptible/supercronic/releases/download/v0.2.38/${_cronic_fname}" \
    -O /usr/bin/supercronic \
    && chmod +x /usr/bin/supercronic \
    && mkdir -p /etc/supercronic \
    && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --no-interaction" > /etc/supercronic/laravel

RUN addgroup -g ${GROUP_ID} ${USER} \
    && adduser -D -G ${USER} -u ${USER_ID} -s /bin/sh ${USER}

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

COPY --link --from=vendor /usr/bin/composer /usr/bin/composer

COPY --link .docker/supervisord.conf /etc/supervisor/
COPY --link .docker/octane/RoadRunner/supervisord.roadrunner.conf /etc/supervisor/conf.d/
COPY --link .docker/supervisord.horizon.conf /etc/supervisor/conf.d/
COPY --link .docker/supervisord.reverb.conf /etc/supervisor/conf.d/
COPY --link .docker/supervisord.scheduler.conf /etc/supervisor/conf.d/
COPY --link .docker/supervisord.worker.conf /etc/supervisor/conf.d/
COPY --link .docker/php.ini ${PHP_INI_DIR}/conf.d/99-octane.ini
COPY --link .docker/octane/RoadRunner/.rr.prod.yaml ./.rr.yaml
COPY --link .docker/start-container /usr/local/bin/start-container
COPY --link .docker/utilities.sh /usr/local/bin/utilities.sh

COPY --link composer.* ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --no-progress \
    --prefer-dist

COPY --link . .

RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache \
    && chmod +x /usr/local/bin/start-container \
    && cat .docker/utilities.sh >> ~/.bashrc

RUN composer dump-autoload \
    --optimize \
    --apcu \
    --no-dev

RUN if composer show spiral/roadrunner-cli >/dev/null 2>&1; then \
    ./vendor/bin/rr get-binary --quiet && chmod +x rr; fi

RUN chown -R ${USER_ID}:${GROUP_ID} ${ROOT} \
    && chmod -R a+rw storage

COPY --chown=${USER}:${USER} .env.example ./.env

USER ${USER}

EXPOSE 8000
EXPOSE 6001

ENTRYPOINT ["start-container"]

HEALTHCHECK --start-period=5s --interval=2s --timeout=5s --retries=8 \
    CMD php artisan octane:status || exit 1
