FROM dunglas/frankenphp:php8.4

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    zip

RUN install-php-extensions \
	@composer \
	intl \
	json \
	xdebug \
	redis

WORKDIR /app

COPY composer.* .
RUN composer install --no-cache

COPY . /app
