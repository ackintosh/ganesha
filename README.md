# Ganesha

PHP implementation of [Circuit Breaker pattern](http://martinfowler.com/bliki/CircuitBreaker.html)

For now, Ganesha is under development heavily. :muscle:
Don't miss it !

### Usage

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