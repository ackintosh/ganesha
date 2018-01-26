# Ganesha

Ganesha is PHP implementation of [Circuit Breaker pattern](http://martinfowler.com/bliki/CircuitBreaker.html) which has multi strategies to detect failures and supports various storages to record statistics.

[![Build Status](https://travis-ci.org/ackintosh/ganesha.svg?branch=master)](https://travis-ci.org/ackintosh/ganesha) [![Coverage Status](https://coveralls.io/repos/github/ackintosh/ganesha/badge.svg?branch=master)](https://coveralls.io/github/ackintosh/ganesha?branch=master)

![ganesha](https://ackintosh.github.io/assets/images/ganesha.png)

https://ackintosh.github.io/ganesha/

For now, Ganesha is under development heavily and growing up day by day.  
It's going to be awesome! :muscle:

If you have an idea about enhancement, bugfix, etc..., please let me know it via [Issues](https://github.com/ackintosh/ganesha/issues). :sparkles:

## Are you interested?

[Here](./examples) is an example which is easily executable. All you need is Docker.  
You can experience how Ganesha behaves when a failure occurs.

## Unveil Ganesha

```bash
# Install Composer
$ curl -sS https://getcomposer.org/installer | php

# Run the Composer command to install the latest version of Ganesha
$ php composer.phar require ackintosh/ganesha
```

## Usage

Ganesha provides following simple interface. Each method receives a string (named `$resource` in example) to identify the resource. `$resource` will be the service name of the API, the endpoint name or etc. Please remember that Ganesha detects system failure for each `$resource`.

```php
$ganesha->isAvailable($resource);
$ganesha->success($resource);
$ganesha->failure($resource);
```

```php
$ganesha = Ackintosh\Ganesha\Builder::build([
    'failureRateThreshold' => 50,
    'adapter'              => new Ackintosh\Ganesha\Storage\Adapter\Redis($redis),
]);

$resource = 'external_api';

if (!$ganesha->isAvailable($resource)) {
    die('external api is not available');
}

try {
    echo \Api::send($request)->getBody();
    $ganesha->success($resource);
} catch (\Api\RequestTimedOutException $e) {
    // If an error occurred, it must be recorded as failure.
    $ganesha->failure($resource);
    die($e->getMessage());
}
```

#### Subscribe to changes in ganesha's state

```php
$ganesha->subscribe(function ($event, $resource, $message) {
    switch ($event) {
        case Ganesha::EVENT_TRIPPED:
            \YourMonitoringSystem::warn(
                "Ganesha has tripped! It seems that a failure has occurred in {$resource}. {$message}."
            );
            break;
        case Ganesha::EVENT_CALMED_DOWN:
            \YourMonitoringSystem::info(
                "The failure in {$resource} seems to have calmed down :). {$message}."
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
// Ganesha with Count strategy(threshold `3`).
// $ganesha = ...

// Disable
Ackintosh\Ganesha::disable();

// Although the failure is recorded to storage,
$ganesha->failure($resource);
$ganesha->failure($resource);
$ganesha->failure($resource);

// Ganesha does not trip and Ganesha::isAvailable() returns true.
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

### Rate (default)

```php
$ganesha = Ackintosh\Ganesha\Builder::build([
    'timeWindow'            => 30,
    'failureRateThreshold'  => 50,
    'minimumRequests'       => 10,
    'intervalToHalfOpen'    => 5,
    'adapter'               => new Ackintosh\Ganesha\Storage\Adapter\Memcached($memcached),
]);
```

### Count

If you want use Count strategy, use `Builder::buildWithCountStrategy()`.

```php
$ganesha = Ackintosh\Ganesha\Builder::buildWithCountStrategy([
    'failureCountThreshold' => 100,
    'intervalToHalfOpen'    => 5,
    'adapter'               => new Ackintosh\Ganesha\Storage\Adapter\Memcached($memcached),
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

## Ganesha :heart: Guzzle

[Guzzle Middleware](http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html) powered by Ganesha will [comming soon](https://github.com/ackintosh/ganesha/issues/10).

## Run tests

We can run unit tests on Docker container, so it is not necessary to install dependencies in local machine.

```bash
# Start redis, memcached server
$ docker-compose up 

# Run tests in container
$ docker-compose run --rm -w /tmp/ganesha -u ganesha client vendor/bin/phpunit
```

## Build documents with [Soushi](https://github.com/kentaro/soushi)

https://ackintosh.github.io/ganesha/

```bash
$ path/to/soushi build docs
```

## Requirements

Ganesha supports PHP 5.6 or higher.
