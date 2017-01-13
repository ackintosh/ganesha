# Ganesha

PHP implementation of [Circuit Breaker pattern](http://martinfowler.com/bliki/CircuitBreaker.html)

[![Build Status](https://travis-ci.org/ackintosh/ganesha.svg?branch=master)](https://travis-ci.org/ackintosh/ganesha) [![Coverage Status](https://coveralls.io/repos/github/ackintosh/ganesha/badge.svg?branch=master)](https://coveralls.io/github/ackintosh/ganesha?branch=master)

![ganesha](https://dl.dropboxusercontent.com/u/22083548/ganesha.png)

https://ackintosh.github.io/ganesha/

For now, Ganesha is under development heavily. :muscle:
It's going to be awesome !

## Unveil Ganesha

```
composer require ackintosh/ganesha:dev-master
```

## Usage

```php
$ganesha = Ackintosh\Ganesha\Builder::create()
               ->withFailureThreshold(10)
               // Hash adapter can only be used for tests.
               ->withAdapter(new Ackintosh\Ganesha\Storage\Adapter\Hash)
               ->build();

$serviceName = 'external_api';

// We can set the behavior that will be invoked when Ganesha has tripped.
$ganesha->onTrip(function ($unavailableServiceName) {
    Slack::notify("Ganesha has tripped. Something's wrong in {$unavailableServiceName} !");
});

if (!$ganesha->isAvailable($serviceName)) {
    die('external api is not available');
}

try {
    $response = ExternalApi::send($request);
    $ganesha->recordSuccess($serviceName);
    echo $response->getBody();
} catch (ExternalApi\NetworkErrorException $e) {
    // If a network error occurred, it must be recorded as failure.
    $ganesha->recordFailure($serviceName);
    die($e->getMessage());
}
```

#### Setup storage adapter

Ganesha has two ways to setup storage adapter.


```php
// The passed object must be an instance of 'Ackintosh\Ganesha\Storage\AdapterInterface'.
$ganesha = Ackintosh\Ganesha\Builder::create()
               ->withAdapter(new Ackintosh\Ganesha\Storage\Adapter\Hash)
               ->build();

// Also, we can specify function which returns instance of AdapterInterface.
$ganesha = Ackintosh\Ganesha\Builder::create()
               ->withAdapterSetupFunction(function () {
                   $m = new \Memcached();
                   $m->addServer('localhost', 11211);

                   return new Ackintosh\Ganesha\Storage\Adapter\Memcached($m);
               })
               ->build();
```

#### Disable

Ganesha will continue to record success/failure statistics, but it will not trip.

```php
Ackintosh\Ganesha::disable();

// Ganesha with threshold `3`.
// Failure count is recorded to storage.
$ganesha->recordFailure($serviceName);
$ganesha->recordFailure($serviceName);
$ganesha->recordFailure($serviceName);

// But Ganesha does not trip.
var_dump($ganesha->isAvailable($serviceName);
// bool(true)
```

## Examples of Ganesha behavior

(in japanese)


###### 設定

- 失敗数のしきい値
	- 10回 ( `withFailureThreshold(10)` )
- half-open までの時間
	- 5秒 ( `withIntervalToHalfOpen(5)` )
- 失敗数をリセットするまでの時間
	- 60秒 ( `withCountTTL(60)` )

```php
$ganesha = Ackintosh\Ganesha\Builder::create()
               ->withFailureThreshold(10)
               ->withStorageAdapter(new Ackintosh\Ganesha\Storage\Adapter\Hash)
               ->withIntervalToHalfOpen(5)
               ->withCountTTL(60)
               ->build();
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
$ path/to/shoushi build docs
```

## Great predecessors

Ganesha respects the following libraries.

- [ejsmont-artur/php-circuit-breaker](https://github.com/ejsmont-artur/php-circuit-breaker)
- [itsoneiota/circuit-breaker](https://github.com/itsoneiota/circuit-breaker)

## Requirements

Ganesha supports PHP 5.6 or higher.