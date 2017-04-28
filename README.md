# Ganesha

PHP implementation of [Circuit Breaker pattern](http://martinfowler.com/bliki/CircuitBreaker.html)

[![Build Status](https://travis-ci.org/ackintosh/ganesha.svg?branch=master)](https://travis-ci.org/ackintosh/ganesha) [![Coverage Status](https://coveralls.io/repos/github/ackintosh/ganesha/badge.svg?branch=master)](https://coveralls.io/github/ackintosh/ganesha?branch=master)

![ganesha](https://ackintosh.github.io/assets/images/ganesha.png)

https://ackintosh.github.io/ganesha/

For now, Ganesha is under development heavily. :muscle:
It's going to be awesome !

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


$serviceName = 'external_api';

if (!$ganesha->isAvailable($serviceName)) {
    die('external api is not available');
}

try {
    $response = ExternalApi::send($request);
    $ganesha->success($serviceName);
    echo $response->getBody();
} catch (ExternalApi\NetworkErrorException $e) {
    // If a network error occurred, it must be recorded as failure.
    $ganesha->failure($serviceName);
    die($e->getMessage());
}
```

#### Behavior on storage error

```php
$ganesha = Ackintosh\Ganesha\Builder::build([
    // with memcached.
    'adapter' =>  new Ackintosh\Ganesha\Storage\Adapter\Memcached($m),
    },
    // we can define the behavior on memcached error.
    'behaviorOnStorageError' => function ($errorMessage) {
        \YourLogger::error('Some errors have occurred on memcached : ' . $errorMessage);
        \YourMonitoringSystem::reportError();
    },
]);
```

#### Behavior on trip

```php
$ganesha = Ackintosh\Ganesha\Builder::build([
    'behaviorOnTrip' => function ($unavailableServiceName) {
        \Slack::notify("Ganesha has tripped. Something's wrong in {$unavailableServiceName} !");
    },
]);
```

#### Disable

Ganesha will continue to record success/failure statistics, but it will not trip.

```php
Ackintosh\Ganesha::disable();

// Ganesha with threshold `3`.
// Failure count is recorded to storage.
$ganesha->failure($serviceName);
$ganesha->failure($serviceName);
$ganesha->failure($serviceName);

// But Ganesha does not trip.
var_dump($ganesha->isAvailable($serviceName);
// bool(true)
```

## Examples

Ganesha has two strategies to detect system failure.

### Rate

```php
$ganesha = Ackintosh\Ganesha\Builder::build([
    'timeWindow'            => 30,
    'failureRate'           => 10,
    'minimumRequests'       => 10,
    'intervalToHalfOpen'    => 5,
    'adapter'               => new Ackintosh\Ganesha\Storage\Adapter\Memcached($memcached),
]);
```

### Count

(ja)


###### 設定

```php
$ganesha = Ackintosh\Ganesha\Builder::buildWithCountStrategy([
    'failureThreshold'   => 10,
    'adapter'            => new Ackintosh\Ganesha\Storage\Adapter\Memcached($memcached),
    'intervalToHalfOpen' => 5,
]);
```

###### 挙動

- 失敗/成功時に失敗数カウントを増減する
- 失敗数カウントが10回を超えると Ganesha が open 状態になる
	- `Ganesha::isAvailable()` が常に `false` を返す
	- open から5秒後、half-open 状態になり、特定のアクセスのみ許可される
		- 特定のアクセス = 5秒経過した後の最初のアクセス
	- そのアクセスが成功すれば、失敗数カウントがしきい値を下回り close 状態になる
		- ( = `Ganesha::isAvailable()` が `true` を返す )
- 60秒間、失敗/成功のどちらもなければカウントがリセットされる

## Build documents

https://ackintosh.github.io/ganesha/

Ganesha using [Soushi](https://github.com/kentaro/soushi) for generating documents.

```
$ path/to/soushi build docs
```

## Great predecessors

Ganesha respects the following libraries.

- [ejsmont-artur/php-circuit-breaker](https://github.com/ejsmont-artur/php-circuit-breaker)
- [itsoneiota/circuit-breaker](https://github.com/itsoneiota/circuit-breaker)

## Requirements

Ganesha supports PHP 5.3 or higher.