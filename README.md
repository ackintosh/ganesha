# Ganesha

PHP implementation of [Circuit Breaker pattern](http://martinfowler.com/bliki/CircuitBreaker.html)

![ganesha](https://dl.dropboxusercontent.com/u/22083548/ganesha.png)

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
               ->withStorageAdapter(new Ackintosh\Ganesha\Storage\Adapter\Hash)
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

## Examples of Ganesha behavior

(in japanese)


###### 設定

- 失敗数のしきい値
	- 10回 ( `withFailureThreshold(10)` )
- リトライ時間
	- 5秒 ( `withRetryTimeout(5)` )
- リセット時間
	- 60秒 ( `withResetTimeout(60)` )

```php
$ganesha = Ackintosh\Ganesha\Builder::create()
               ->withFailureThreshold(10)
               ->withStorageAdapter(new Ackintosh\Ganesha\Storage\Adapter\Hash)
               ->withRetryTimeout(5) // not implemented yet
               ->withResetTimeout(60) // not implemented yet
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


## Great predecessors

Ganesha respects the following libraries.

- [ejsmont-artur/php-circuit-breaker](https://github.com/ejsmont-artur/php-circuit-breaker)
- [itsoneiota/circuit-breaker](https://github.com/itsoneiota/circuit-breaker)

