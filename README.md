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

## Great predecessors

Ganesha respects the following libraries.

- [ejsmont-artur/php-circuit-breaker](https://github.com/ejsmont-artur/php-circuit-breaker)
- [itsoneiota/circuit-breaker](https://github.com/itsoneiota/circuit-breaker)

