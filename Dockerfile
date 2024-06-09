FROM dunglas/frankenphp

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
