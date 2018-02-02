---
title: Unveil Ganesha
template: page
bg: banner
---


```php
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
