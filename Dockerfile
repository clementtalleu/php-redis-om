FROM dunglas/frankenphp

RUN install-php-extensions \
	intl \
	json \
	redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app
