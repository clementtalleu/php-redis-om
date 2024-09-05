# phpredis usage

php-redis-om offers two methods of interacting with Redis: either via the native phpredis PHP extension, or via the Predis PHP library. Users can choose their installation method according to their preferences or environment.


The phpredis extension is a native PHP extension, offering optimal performance as it is directly integrated into the language. It is recommended for production environments.

### Installing phpredis :

To install phpredis, you must install the [extension](https://github.com/phpredis/phpredis?tab=readme-ov-file#installingconfiguring), and enable it in your php.ini

```
extension=redis.so
```

The project will automatically detect the extension if it is installed and use it without further configuration.