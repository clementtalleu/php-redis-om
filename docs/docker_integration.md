### Docker

The package provide a Docker Compose configuration to run a Redis
server with the required modules (RedisJSON and Redisearch) for testing purposes.

You could retrieve an example in compose.yaml file and a Dockerfile for the PHP container configuration.

```yaml
  redis:
    image: redis/redis-stack-server:7.2.0-v6
    ports:
      - 6379:6379
    healthcheck:
      test: [ "CMD", "redis-cli", "--raw", "incr", "ping" ]
    volumes:
      - redis_data:/data
```

To run the Redis server locally, you can use the following command:

```console
docker compose up -d
```

### Running tests

```console
docker compose exec php vendor/bin/phpunit tests
```
