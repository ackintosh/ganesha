# Ganesha

PHP implementation of [Circuit Breaker pattern](http://martinfowler.com/bliki/CircuitBreaker.html)

![ganesha](https://dl.dropboxusercontent.com/u/22083548/ganesha.png)

For now, Ganesha is under development heavily. :muscle:
It's going to be awesome !

## Usage

```php
$ganesha = new Ackintosh\Ganesha();

if (!$ganesha->isAvailable('external_api')) {
    die('external api is not available');
}

try {
    $response = $externalApi->send($request);
    $ganesha->recordSuccess('external_api');
    echo $response->getBody();
} catch (\RuntimeException $e) {
    $ganesha->recordFailure('external_api');
    die($e->getMessage());
}
```

## Great predecessors

Ganesha respects the following libraries.

- [ejsmont-artur/php-circuit-breaker](https://github.com/ejsmont-artur/php-circuit-breaker)
- [itsoneiota/circuit-breaker](https://github.com/itsoneiota/circuit-breaker)

