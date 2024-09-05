# Predis usage

php-redis-om offers two methods of interacting with Redis: either via the native phpredis PHP extension, or via the Predis PHP library. Users can choose their installation method according to their preferences or environment.


[Predis](https://github.com/predis/predis) is a pure PHP library, compatible with environments where the installation of PHP extensions is not possible, such as on some shared hosting sites.

### Installing Predis :

To install Predis, users must add the library to their project via Composer :
```
composer require predis/predis
```

The project will automatically detect the vendor install and use it.