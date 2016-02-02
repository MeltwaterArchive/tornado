# Cache

There is a "global" cache service available in the application under `@cache` name.

## Configuration

This service is configured dynamically by a parameter `%cache.service_name%` which MUST 
contain a name of an existing service (without the `@` sign) to which the `@cache` service
will be an alias.

The [cache.yml](../src/config/cache.yml) file defines all existing cache services that can
be used as values of `%cache.service_name%`. If you want to implement more cache systems
you should register their services there.

## Cache Interface

All caches MUST implement `Doctrine\Common\Cache\Cache` interface. Therefore, in any class
that depends on a cache, you can typehint to this interface.

## Cache Directory

The parameter `%cache_dir%` defines location of the cache directory. This directory CAN be
used by other caches, not only the application cache. One example would be the compiled
service container which would put its compiled files there.

This parameter is also used for configuration of the `@cache.file`.

## Clearing cache

You can clear the cache by executing the `cache:clear` command:

    $ php src/app/console cache:clear

This command will call on `@cache` service to clear its cache but also it will force clear
the contents of the cache directory defined by `%cache_dir%` parameter.
