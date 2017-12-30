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

```php
$ganesha->isAvailable();
$ganesha->success();
$ganesha->failure();
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
    \YourMonitoringSystem::report();
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

## Examples

Ganesha has two strategies which detect system failure.

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


## Build documents with [Soushi](https://github.com/kentaro/soushi)

https://ackintosh.github.io/ganesha/

```
$ path/to/soushi build docs
```

## Requirements

Ganesha supports PHP 5.6 or higher.