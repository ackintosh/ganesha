# Ganesha

PHP implementation of [Circuit Breaker pattern](http://martinfowler.com/bliki/CircuitBreaker.html)

[![Build Status](https://travis-ci.org/ackintosh/ganesha.svg?branch=master)](https://travis-ci.org/ackintosh/ganesha) [![Coverage Status](https://coveralls.io/repos/github/ackintosh/ganesha/badge.svg?branch=master)](https://coveralls.io/github/ackintosh/ganesha?branch=master)

![ganesha](https://ackintosh.github.io/assets/images/ganesha.png)

https://ackintosh.github.io/ganesha/

For now, Ganesha is under development heavily.  
It's going to be awesome! :muscle:

If you have an idea about enhancement, bugfix, etc..., please let me know it via [Issues](https://github.com/ackintosh/ganesha/issues). :sparkles:

## Are you interested?

[Here](./examples) is an example which is easily executable. All you need is Docker.  
You can experience how Ganesha behaves when a failure occurs.

## Unveil Ganesha

```
composer require ackintosh/ganesha:dev-master
```

## Usage

Ganesha provides following simple interfaces. Each method receives a string (named `$resource` in example) to identify the resource. `$resource` will be the service name of the API, the endpoint name or etc. Please remember that Ganesha detects system failure for each `$resource`.

```php
$ganesha->isAvailable($resource);
$ganesha->success($resource);
$ganesha->failure($resource);
```

```php
$ganesha = Ackintosh\Ganesha\Builder::build([
    'failureRate' => 50,
    'adapter'     => new Ackintosh\Ganesha\Storage\Adapter\Memcached($memcached),
]);


$resource = 'external_api';

if (!$ganesha->isAvailable($resource)) {
    die('external api is not available');
}

try {
    echo ExternalApi::send($request)->getBody();
    $ganesha->success($resource);
} catch (ExternalApi\RequestTimedOutException $e) {
    // If an error occurred, it must be recorded as failure.
    $ganesha->failure($resource);
    die($e->getMessage());
}
```

#### Subscribe to changes in ganesha's state

```php
// $event is `Ganesha::EVENT_XXX`.
$ganesha->subscribe(function ($event, $resource, $message) {
    switch ($event) {
        case Ganesha::EVENT_TRIPPED:
            \YourMonitoringSystem::warn(
                "Ganesha has tripped! It seems that a failure has occurred in {$resource}. {$message}."
            );
            break;
        case Ganesha::EVENT_CALMED_DOWN:
            \YourMonitoringSystem::info(
                "The failure in {$resource} seems to have calmed down. {$message}."
            );
            break;
        case Ganesha::EVENT_STORAGE_ERROR:
            \YourMonitoringSystem::error($message);
            break;
        default:
            break;
    }
});
```

#### Disable

Ganesha will continue to record success/failure statistics, but it will not trip.

```php
Ackintosh\Ganesha::disable();

// Ganesha with threshold `3`.
// Failure count is recorded to storage.
$ganesha->failure($resource);
$ganesha->failure($resource);
$ganesha->failure($resource);

// But Ganesha does not trip.
var_dump($ganesha->isAvailable($resource);
// bool(true)
```

#### Reset


```php
$ganesha = Ackintosh\Ganesha\Builder::build([
	// ...
]);

$ganesha->reset();

```

## Strategies to detect failures

Ganesha has two strategies which detects system failure.

### Rate

```php
$ganesha = Ackintosh\Ganesha\Builder::build([
    'timeWindow'            => 30,
    'failureRate'           => 50,
    'minimumRequests'       => 10,
    'intervalToHalfOpen'    => 5,
    'adapter'               => new Ackintosh\Ganesha\Storage\Adapter\Memcached($memcached),
]);
```

### Count

```php
$ganesha = Ackintosh\Ganesha\Builder::buildWithCountStrategy([
    'failureThreshold'   => 100,
    'intervalToHalfOpen' => 5,
    'adapter'            => new Ackintosh\Ganesha\Storage\Adapter\Memcached($memcached),
]);
```

## Adapters

### Redis

Redis adapter requires [phpredis](https://github.com/phpredis/phpredis). So if you don't have it, run `pecl install redis`.

```php
$redis = new \Redis();
$redis->connect('localhost');
$adapter = new Ackintosh\Ganesha\Storage\Adapter\Redis($redis);

$ganesha = Ackintosh\Ganesha\Builder::build([
    'adapter' => $adapter,
]);
```

### Memcached

Memcached adapter requires [memcached](https://github.com/php-memcached-dev/php-memcached/) (NOT memcache) extension.

```php
$memcached = new \Memcached();
$memcached->addServer('localhost', 11211);
$adapter = new Ackintosh\Ganesha\Storage\Adapter\Memcached($memcached);

$ganesha = Ackintosh\Ganesha\Builder::build([
    'adapter' => $adapter,
]);
```

## Run tests

```
$ docker-compose up # Starts memcached server
$ docker-compose run --rm -w /tmp/ganesha -u ganesha client vendor/bin/phpunit
```

## Build documents with [Soushi](https://github.com/kentaro/soushi)

https://ackintosh.github.io/ganesha/

```
$ path/to/soushi build docs
```

## Requirements

Ganesha supports PHP 5.6 or higher.