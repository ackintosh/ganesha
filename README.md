<h1 align="center">Ganesha</h1>

Ganesha is PHP implementation of [Circuit Breaker pattern](http://martinfowler.com/bliki/CircuitBreaker.html) which has multi strategies to avoid cascading failures and supports various storages to record statistics.

<div align="center">

![ganesha](https://ackintosh.github.io/assets/images/ganesha.png)

[![Latest Stable Version](https://img.shields.io/packagist/v/ackintosh/ganesha.svg?style=flat-square)](https://packagist.org/packages/ackintosh/ganesha) [![Tests](https://github.com/ackintosh/ganesha/workflows/Tests/badge.svg)](https://github.com/ackintosh/ganesha/actions) [![Coverage Status](https://coveralls.io/repos/github/ackintosh/ganesha/badge.svg?branch=master)](https://coveralls.io/github/ackintosh/ganesha?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ackintosh/ganesha/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ackintosh/ganesha/?branch=master) [![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-8892BF.svg?style=flat-square)](https://php.net/)

</div>

<div align="center">

**If Ganesha is saving your service from system failures, please consider [supporting](https://github.com/sponsors/ackintosh) to this project's author, [Akihito Nakano](#author), to show your :heart: and support. Thank you!**

[Sponsor @ackintosh on GitHub Sponsors](https://github.com/sponsors/ackintosh)

</div>

---

This is one of the [Circuit Breaker](https://martinfowler.com/bliki/CircuitBreaker.html) implementation in PHP which has been actively developed and production ready - well-tested and well-documented. :muscle:  You can integrate Ganesha to your existing code base easily as Ganesha provides just simple interfaces and [Guzzle Middleware](https://github.com/ackintosh/ganesha#ganesha-heart-guzzle) behaves transparency.

If you have an idea about enhancement, bugfix..., please let me know via [Issues](https://github.com/ackintosh/ganesha/issues). :sparkles:

## Table of contents

- [Ganesha](#ganesha)
- [Table of contents](#table-of-contents)
- [Are you interested?](#are-you-interested)
- [Unveil Ganesha](#unveil-ganesha)
- [Usage](#usage)
- [Strategies](#strategies)
- [Adapters](#adapters)
- [Customizing storage keys](#customizing-storage-keys)
- [Ganesha :heart: Guzzle](#ganesha-heart-guzzle)
- [Ganesha :heart: OpenAPI Generator](#ganesha-heart-openapi-generator)
- [Ganesha :heart: Symfony HttpClient](#ganesha-heart-symfony-httpclient)
- [Companies using Ganesha :rocket:](#companies-using-ganesha-rocket)
- [The articles/videos Ganesha loves :sparkles: :elephant: :sparkles:](#the-articlesvideos-ganesha-loves-sparkles-elephant-sparkles)
- [Run tests](#run-tests)
- [Requirements](#requirements)
- [Build promotion site with Soushi](#build-promotion-site-with-soushi)
- [Author](#author)

## [Are you interested?](#table-of-contents)

[Here](./examples) is an example which shows you how Ganesha behaves when a failure occurs.  
It is easily executable. All you need is Docker.

## [Unveil Ganesha](#table-of-contents)

```bash
# Install Composer
$ curl -sS https://getcomposer.org/installer | php

# Run the Composer command to install the latest version of Ganesha
$ php composer.phar require ackintosh/ganesha
```

## [Usage](#table-of-contents)

Ganesha provides following simple interfaces. Each method receives a string (named `$service` in example) to identify the service. `$service` will be the service name of the API, the endpoint name, etc. Please remember that Ganesha detects system failure for each `$service`.

```php
$ganesha->isAvailable($service);
$ganesha->success($service);
$ganesha->failure($service);
```

```php
// For further details about builder options, please see the `Strategy` section.
$ganesha = Ackintosh\Ganesha\Builder::withRateStrategy()
    ->adapter(new Ackintosh\Ganesha\Storage\Adapter\Redis($redis))
    ->failureRateThreshold(50)
    ->intervalToHalfOpen(10)
    ->minimumRequests(10)
    ->timeWindow(30)
    ->build();

$service = 'external_api';

if (!$ganesha->isAvailable($service)) {
    die('external api is not available');
}

try {
    echo \Api::send($request)->getBody();
    $ganesha->success($service);
} catch (\Api\RequestTimedOutException $e) {
    // If an error occurred, it must be recorded as failure.
    $ganesha->failure($service);
    die($e->getMessage());
}
```

### Three states of circuit breaker

<img src="https://user-images.githubusercontent.com/1885716/53690408-4a7f3d00-3dad-11e9-852c-0e082b7b9636.png" width="500">

([martinfowler.com : CircuitBreaker](https://martinfowler.com/bliki/CircuitBreaker.html))

Ganesha follows the states and transitions described in the article faithfully. `$ganesha->isAvailable()` returns `true` if the circuit states on `Closed`, otherwise it returns `false`.

### Subscribe to events in ganesha

- When the circuit state transitions to `Open` the event `Ganesha::EVENT_TRIPPED` is triggered
- When the state back to `Closed` the event `Ganesha::EVENT_CALMED_DOWN` is triggered

```php
$ganesha->subscribe(function ($event, $service, $message) {
    switch ($event) {
        case Ganesha::EVENT_TRIPPED:
            \YourMonitoringSystem::warn(
                "Ganesha has tripped! It seems that a failure has occurred in {$service}. {$message}."
            );
            break;
        case Ganesha::EVENT_CALMED_DOWN:
            \YourMonitoringSystem::info(
                "The failure in {$service} seems to have calmed down :). {$message}."
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

### Disable

If disabled, Ganesha keeps to record success/failure statistics, but Ganesha doesn't trip even if the failure count reached to a threshold.

```php
// Ganesha with Count strategy(threshold `3`).
// $ganesha = Ackintosh\Ganesha\Builder::withCountStrategy() ...

// Disable
Ackintosh\Ganesha::disable();

// Although the failure is recorded to storage,
$ganesha->failure($service);
$ganesha->failure($service);
$ganesha->failure($service);

// Ganesha does not trip and Ganesha::isAvailable() returns true.
var_dump($ganesha->isAvailable($service));
// bool(true)
```

### Reset

Resets the statistics saved in a storage.

```php
$ganesha = Ackintosh\Ganesha\Builder::withRateStrategy()
    // ...
    ->build();

$ganesha->reset();

```

## [Strategies](#table-of-contents)

Ganesha has two strategies which avoids cascading failures.

### Rate

```php
$ganesha = Ackintosh\Ganesha\Builder::withRateStrategy()
    // The interval in time (seconds) that evaluate the thresholds.
    ->timeWindow(30)
    // The failure rate threshold in percentage that changes CircuitBreaker's state to `OPEN`.
    ->failureRateThreshold(50)
    // The minimum number of requests to detect failures.
    // Even if `failureRateThreshold` exceeds the threshold,
    // CircuitBreaker remains in `CLOSED` if `minimumRequests` is below this threshold.
    ->minimumRequests(10)
    // The interval (seconds) to change CircuitBreaker's state from `OPEN` to `HALF_OPEN`.
    ->intervalToHalfOpen(5)
    // The storage adapter instance to store various statistics to detect failures.
    ->adapter(new Ackintosh\Ganesha\Storage\Adapter\Memcached($memcached))
    ->build();
```

Note about "time window": The Storage Adapter implements either [SlidingTimeWindow](https://github.com/ackintosh/ganesha/blob/master/src/Ganesha/Storage/Adapter/SlidingTimeWindowInterface.php) or [TumblingTimeWindow](https://github.com/ackintosh/ganesha/blob/master/src/Ganesha/Storage/Adapter/TumblingTimeWindowInterface.php). The difference of the implementation comes from constraints of the storage functionalities.

#### [SlidingTimeWindow]

- [SlidingTimeWindow](https://github.com/ackintosh/ganesha/blob/master/src/Ganesha/Storage/Adapter/SlidingTimeWindowInterface.php) implements a time period that stretches back in time from the present. For instance, a SlidingTimeWindow of 30 seconds includes any events that have occurred in the past 30 seconds.
- [Redis adapter](https://github.com/ackintosh/ganesha#redis) and [MongoDB adapter](https://github.com/ackintosh/ganesha#mongodb) implements SlidingTimeWindow.

The details to help us understand visually is shown below:  
(quoted from [Introduction to Stream Analytics windowing functions - Microsoft Azure](https://github.com/MicrosoftDocs/azure-docs/blob/master/articles/stream-analytics/stream-analytics-window-functions.md#sliding-window))

<img height="350" title="slidingtimewindow" src="https://s3-ap-northeast-1.amazonaws.com/ackintosh.github.io/timewindow/sliding-window.png">

#### [TumblingTimeWindow]

- [TumblingTimeWindow](https://github.com/ackintosh/ganesha/blob/master/src/Ganesha/Storage/Adapter/TumblingTimeWindowInterface.php) implements time segments, which are divided by a value of `timeWindow`.
- [APCu adapter](https://github.com/ackintosh/ganesha#apcu) and
  [Memcached adapter](https://github.com/ackintosh/ganesha#memcached) implement TumblingTimeWindow.

The details to help us understand visually is shown below:  
(quoted from [Introduction to Stream Analytics windowing functions - Microsoft Azure](https://github.com/MicrosoftDocs/azure-docs/blob/master/articles/stream-analytics/stream-analytics-window-functions.md#tumbling-window))

<img height="350" title="tumblingtimewindow" src="https://s3-ap-northeast-1.amazonaws.com/ackintosh.github.io/timewindow/tumbling-window.png">

### Count

If you prefer the Count strategy use `Builder::buildWithCountStrategy()` to build an instance.

```php
$ganesha = Ackintosh\Ganesha\Builder::withCountStrategy()
    // The failure count threshold that changes CircuitBreaker's state to `OPEN`.
    // The count will be increased if `$ganesha->failure()` is called,
    // or will be decreased if `$ganesha->success()` is called.
    ->failureCountThreshold(100)
    // The interval (seconds) to change CircuitBreaker's state from `OPEN` to `HALF_OPEN`.
    ->intervalToHalfOpen(5)
    // The storage adapter instance to store various statistics to detect failures.
    ->adapter(new Ackintosh\Ganesha\Storage\Adapter\Memcached($memcached))
    ->build();
```

## [Adapters](#table-of-contents)

### APCu

The APCu adapter requires the [APCu](https://www.php.net/manual/en/book.apcu.php) extension.

```php
$adapter = new Ackintosh\Ganesha\Storage\Adapter\Apcu();

$ganesha = Ackintosh\Ganesha\Builder::withRateStrategy()
    ->adapter($adapter)
    // ... (omitted) ...
    ->build();
```

Note: APCu is internal to each server/instance, not pooled like most Memcache and Redis setups. Each
worker's circuit breaker will activate or reset individually, and failure thresholds should be
set lower to compensate.

### Redis

Redis adapter requires [phpredis](https://github.com/phpredis/phpredis) or [Predis](https://github.com/nrk/predis) client instance. The example below is using [phpredis](https://github.com/phpredis/phpredis).

```php
$redis = new \Redis();
$redis->connect('localhost');
$adapter = new Ackintosh\Ganesha\Storage\Adapter\Redis($redis);

$ganesha = Ackintosh\Ganesha\Builder::withRateStrategy()
    ->adapter($adapter)
    // ... (omitted) ...
    ->build();
```

### Memcached

Memcached adapter requires [memcached](https://github.com/php-memcached-dev/php-memcached/) (NOT memcache) extension.

```php
$memcached = new \Memcached();
$memcached->addServer('localhost', 11211);
$adapter = new Ackintosh\Ganesha\Storage\Adapter\Memcached($memcached);

$ganesha = Ackintosh\Ganesha\Builder::withRateStrategy()
    ->adapter($adapter)
    // ... (omitted) ...
    ->build();
```

### MongoDB

MongoDB adapter requires [mongodb](https://github.com/mongodb/mongo-php-library) extension.

```php
$manager = new \MongoDB\Driver\Manager('mongodb://localhost:27017/');
$adapter = new Ackintosh\Ganesha\Storage\Adapter\MongoDB($manager, 'dbName', 'collectionName');

$ganesha = Ackintosh\Ganesha\Builder::withRateStrategy()
    ->adapter($adapter)
    // ... (omitted) ...
    ->build();
```

## [Customizing storage keys](#table-of-contents)

If you want to customize the keys to be used when storing circuit breaker information, set an instance which implements [StorageKeysInterface](https://github.com/ackintosh/ganesha/blob/master/src/Ganesha/Storage/StorageKeysInterface.php).

```php
class YourStorageKeys implements StorageKeysInterface
{
    public function prefix()
    {
        return 'your_prefix_';
    }

    // ... (omitted) ...
}

$ganesha = Ackintosh\Ganesha\Builder::withRateStrategy()
    // The keys which will stored by Ganesha to the storage you specified via `adapter`
    // will be prefixed with `your_prefix_`.
    ->storageKeys(new YourStorageKeys())
    // ... (omitted) ...
    ->build();
```

## [Ganesha :heart: Guzzle](#table-of-contents)

If you are using [Guzzle](https://github.com/guzzle/guzzle) (v6 or higher), [Guzzle Middleware](http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html) powered by Ganesha makes it easy to integrate Circuit Breaker to your existing code base.

```php
use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\GuzzleMiddleware;
use Ackintosh\Ganesha\Exception\RejectedException;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

$ganesha = Builder::withRateStrategy()
    ->timeWindow(30)
    ->failureRateThreshold(50)
    ->minimumRequests(10)
    ->intervalToHalfOpen(5)
    ->adapter($adapter)
    ->build();

$middleware = new GuzzleMiddleware($ganesha);

$handlers = HandlerStack::create();
$handlers->push($middleware);

$client = new Client(['handler' => $handlers]);

try {
    $client->get('http://api.example.com/awesome_resource');
} catch (RejectedException $e) {
    // If the circuit breaker is open, RejectedException will be thrown.
}
```

### How does Guzzle Middleware determine the `$service`?

As documented in [Usage](https://github.com/ackintosh/ganesha#usage), Ganesha detects failures for each `$service`. Below, We will show you how Guzzle Middleware determine `$service` and how we specify `$service` explicitly.

By default, the host name is used as `$service`.


```php
// In the example above, `api.example.com` is used as `$service`.
$client->get('http://api.example.com/awesome_resource');
```

You can also specify `$service` via a option passed to client, or request header. If both are specified, the option value takes precedence.

```php
// via constructor argument
$client = new Client([
    'handler' => $handlers,
    // 'ganesha.service_name' is defined as ServiceNameExtractor::OPTION_KEY
    'ganesha.service_name' => 'specified_service_name',
]);

// via request method argument
$client->get(
    'http://api.example.com/awesome_resource',
    [
        'ganesha.service_name' => 'specified_service_name',
    ]
);

// via request header
$request = new Request(
    'GET',
    'http://api.example.com/awesome_resource',
    [
        // 'X-Ganesha-Service-Name' is defined as ServiceNameExtractor::HEADER_NAME
        'X-Ganesha-Service-Name' => 'specified_service_name'
    ]
);
$client->send($request);
```

Alternatively, you can apply your own rules by implementing a class that implements the `ServiceNameExtractorInterface`.

```php
use Ackintosh\Ganesha\GuzzleMiddleware\ServiceNameExtractorInterface;
use Psr\Http\Message\RequestInterface;

class SampleExtractor implements ServiceNameExtractorInterface
{
    /**
     * @override
     */
    public function extract(RequestInterface $request, array $requestOptions)
    {
        // We treat the combination of host name and HTTP method name as $service.
        return $request->getUri()->getHost() . '_' . $request->getMethod();
    }
}

// ---

$ganesha = Builder::withRateStrategy()
    // ...
    ->build();
$middleware = new GuzzleMiddleware(
    $ganesha,
    // Pass the extractor as an argument of GuzzleMiddleware constructor.
    new SampleExtractor()
);
```

### How does Guzzle Middleware determine the failure?

By default, if the next handler promise is fulfilled ganesha will consider it a success, and a failure if it is rejected.

You can implement your own rules on fulfilled response by passing an implementation of `FailureDetectorInterface` to the middleware.

```php
use Ackintosh\Ganesha\GuzzleMiddleware\FailureDetectorInterface;
use Psr\Http\Message\ResponseInterface;

class HttpStatusFailureDetector implements FailureDetectorInterface
{
    public function isFailureResponse(ResponseInterface $response) : bool
    {
        return in_array($response->getStatusCode(), [503, 504], true);
    }
}

// ---
$ganesha = Builder::withRateStrategy()
    // ...
    ->build();
$middleware = new GuzzleMiddleware(
    $ganesha,
    // Pass the failure detector to the GuzzleMiddleware constructor.
    failureDetector: new HttpStatusFailureDetector()
);
```

## [Ganesha :heart: OpenAPI Generator](#table-of-contents)

PHP client generated by [OpenAPI Generator](https://github.com/OpenAPITools/openapi-generator) is using Guzzle as HTTP client and as we mentioned as [Ganesha :heart: Guzzle](https://github.com/ackintosh/ganesha#ganesha-heart-guzzle), Guzzle Middleware powered by Ganesha is ready. So it is easily possible to integrate Ganesha and the PHP client generated by OpenAPI Generator in a smart way as below.

```php
// For details on how to build middleware please see https://github.com/ackintosh/ganesha#ganesha-heart-guzzle
$middleware = new GuzzleMiddleware($ganesha);

// Set the middleware to HTTP client.
$handlers = HandlerStack::create();
$handlers->push($middleware);
$client = new Client(['handler' => $handlers]);

// Just pass the HTTP client to the constructor of API class.
$api = new PetApi($client);

try {
    // Ganesha is working in the shadows! The result of api call is monitored by Ganesha.
    $api->getPetById(123);
} catch (RejectedException $e) {
    awesomeErrorHandling($e);
}
```

## [Ganesha :heart: Symfony HttpClient](#table-of-contents)

If you are using [Symfony HttpClient](https://github.com/symfony/http-client), GaneshaHttpClient makes it easy to integrate Circuit Breaker to your existing code base.

```php
use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\GaneshaHttpClient;
use Ackintosh\Ganesha\Exception\RejectedException;

$ganesha = Builder::withRateStrategy()
    ->timeWindow(30)
    ->failureRateThreshold(50)
    ->minimumRequests(10)
    ->intervalToHalfOpen(5)
    ->adapter($adapter)
    ->build();

$client = HttpClient::create();
$ganeshaClient = new GaneshaHttpClient($client, $ganesha);

try {
    $ganeshaClient->request('GET', 'http://api.example.com/awesome_resource');
} catch (RejectedException $e) {
    // If the circuit breaker is open, RejectedException will be thrown.
}
```

### How does GaneshaHttpClient determine the `$service`?

As documented in [Usage](https://github.com/ackintosh/ganesha#usage), Ganesha detects failures for each `$service`. Below, We will show you how GaneshaHttpClient determine `$service` and how we specify `$service` explicitly.

By default, the host name is used as `$service`.


```php
// In the example above, `api.example.com` is used as `$service`.
$ganeshaClient->request('GET', 'http://api.example.com/awesome_resource');
```

You can also specify `$service` via a option passed to client, or request header. If both are specified, the option value takes precedence.

```php
// via constructor argument
$ganeshaClient = new GaneshaHttpClient($client, $ganesha, [
    // 'ganesha.service_name' is defined as ServiceNameExtractor::OPTION_KEY
    'ganesha.service_name' => 'specified_service_name',
]);

// via request method argument
$ganeshaClient->request(
    'GET',
    'http://api.example.com/awesome_resource',
    [
        'ganesha.service_name' => 'specified_service_name',
    ]
);

// via request header
$ganeshaClient->request('GET', '', ['headers' => [
     // 'X-Ganesha-Service-Name' is defined as ServiceNameExtractor::HEADER_NAME
     'X-Ganesha-Service-Name' => 'specified_service_name'
]]);
```

Alternatively, you can apply your own rules by implementing a class that implements the `ServiceNameExtractorInterface`.

```php
use Ackintosh\Ganesha\HttpClient\HostTrait;
use Ackintosh\Ganesha\HttpClient\ServiceNameExtractorInterface;

final class SampleExtractor implements ServiceNameExtractorInterface
{
    use HostTrait;

    /**
     * @override
     */
    public function extract(string $method, string $url, array $requestOptions): string
    {
        // We treat the combination of host name and HTTP method name as $service.
        return self::extractHostFromUrl($url) . '_' . $method;
    }
}

// ---

$ganesha = Builder::withRateStrategy()
    // ...
    ->build();
$ganeshaClient = new GaneshaHttpClient(
    $client,
    $ganesha,
    // Pass the extractor as an argument of GaneshaHttpClient constructor.
    new SampleExtractor()
);
```

### How does GaneshaHttpClient determine the failure?

As documented in [Usage](https://github.com/ackintosh/ganesha#usage), Ganesha detects failures for each `$service`.
Below, We will show you how GaneshaHttpClient specify failure explicitly.

By default, Ganesha considers a request is successful as soon as the server responded, whatever the HTTP status code.

Alternatively, you can use the `RestFailureDetector` implementation of `FailureDetectorInterface` to specify a list of HTTP Status Code to be considered as failure via an option passed to client.  
This implementation will consider failure when these HTTP status codes are returned by the server:
- 500 (Internal Server Error)
- 502 (Bad Gateway or Proxy Error)
- 503 (Service Unavailable)
- 504 (Gateway Time-out)
- 505 (HTTP Version not supported)

```php
// via constructor argument
$ganeshaClient = new GaneshaHttpClient(
    $client, $ganesha, null,
    new RestFailureDetector([503])
);

// via request method argument
$ganeshaClient->request(
    'GET',
    'http://api.example.com/awesome_resource',
    [
        // 'ganesha.failure_status_codes' is defined as RestFailureDetector::OPTION_KEY
        'ganesha.failure_status_codes' => [503],
    ]
);
```

Alternatively, you can apply your own rules by implementing a class that implements the `FailureDetectorInterface`.

```php
use Ackintosh\Ganesha\HttpClient\FailureDetectorInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SampleFailureDetector implements FailureDetectorInterface
{
    /**
     * @override
     */
    public function isFailureResponse(ResponseInterface $response, array $requestOptions): bool
    {
        try {
            $jsonData = $response->toArray();
        } catch (ExceptionInterface $e) {
            return true;
        }

        // Server is not RestFull and always returns HTTP 200 Status Code, but set an error flag in the JSON payload.
        return true === ($jsonData['error'] ?? false);
    }

    /**
     * @override
     */
    public function getOptionKeys(): array
    {
       // No option is defined for this implementation
       return [];
    }
}

// ---

$ganesha = Builder::withRateStrategy()
    // ...
    ->build();
$ganeshaClient = new GaneshaHttpClient(
    $client,
    $ganesha,
    null,
    // Pass the failure detector as an argument of GaneshaHttpClient constructor.
    new SampleFailureDetector()
);
```

## [Companies using Ganesha :rocket:](#table-of-contents)

Here are some companies using Ganesha in production! We are proud of them. :elephant:

To add your company to the list, please visit [README.md](https://github.com/ackintosh/ganesha/blob/master/README.md) and click on the icon to edit the page or let me know via [issues](https://github.com/ackintosh/ganesha/issues)/[twitter](https://twitter.com/NAKANO_Akihito).

(alphabetical order)

- [APISHIP LLC](https://apiship.ru)
- [Bedrock Streaming](https://www.bedrockstreaming.com/)
- [Dapda](https://dapda.com/)
- [Wikia, Inc.](https://www.fandom.com)

## [The articles/videos Ganesha loves :sparkles: :elephant: :sparkles:](#table-of-contents)

Here are some articles/videos introduce Ganesha! All of them are really shining like a jewel for us. :sparkles:

### Articles

- 2022/09/02 [Using a circuit breaker to spare the API we are calling | Bedrock Tech Blog](https://tech.bedrockstreaming.com/2022/09/02/backend-circuit-breaker.html)
- 2021/06/25 [Чек-лист: как оставаться отказоустойчивым, переходя на микросервисы на PHP (и как правильно падать) / Блог компании Skyeng / Хабр](https://habr.com/ru/company/skyeng/blog/560842/)
- 2020/12/21 [장애 확산을 막기 위한 서킷브레이커 패턴을 PHP에서 구현해보자](https://saramin.github.io/2020-12-21-php-circuit-breaker-ganesha/)
- 2020/04/22 [PHP Annotated – April 2020 | PhpStorm Blog](https://blog.jetbrains.com/phpstorm/2020/04/php-annotated-april-2020/)
- 2020/03/23 [Circuit Breaker - SarvenDev](https://sarvendev.com/en/2020/03/circuit-breaker/)
- 2020/03/23 [PHP-Дайджест № 177 (23 марта – 6 апреля 2020) / Хабр](https://habr.com/ru/post/495838/)
- 2019/08/01 [PHP Weekly. Archive. August 1, 2019. News, Articles and more all about PHP](http://www.phpweekly.com/archive/2019-08-01.html)
- 2019/07/15 [PHP Annotated – July 2019 | PhpStorm Blog](https://blog.jetbrains.com/phpstorm/2019/07/php-annotated-july-2019/)
- 2019/04/25 [PHP Weekly. Archive. April 25, 2019. News, Articles and more all about PHP](http://www.phpweekly.com/archive/2019-04-25.html)
- 2019/03/18 [A Semana PHP - Edição Nº229 | Revue](https://www.getrevue.co/profile/asemanaphp/issues/a-semana-php-edicao-n-229-165581)
- 2018/06/08 [Безопасное взаимодействие в распределенных системах / Блог компании Badoo / Хабр](https://habr.com/ru/company/badoo/blog/413555/)
- 2018/01/22 [PHP DIGEST #12: NEWS & TOOLS (JANUARY 1 - JANUARY 14, 2018)](https://www.zfort.com/blog/php-digest-january-14-2018)

### Videos

- [«Безопасное взаимодействие в распределенных системах» — Алексей Солодкий, Badoo - YouTube](https://youtu.be/1k_0ax9DNGI?t=906)

## [Run tests](#table-of-contents)

We can run unit tests on a Docker container, so it is not necessary to install the dependencies in your machine.

```bash
# Start data stores (Redis, Memcached, etc)
$ docker-compose up

# Run `composer install`
$ docker-compose run --rm -w /tmp/ganesha -u ganesha client composer install

# Run tests in container
$ docker-compose run --rm -w /tmp/ganesha -u ganesha client vendor/bin/phpunit
```

## [Requirements](#table-of-contents)

- An extension or client library which is used by [the storage adapter](https://github.com/ackintosh/ganesha#adapters) you've choice will be required. Please check the [Adapters](https://github.com/ackintosh/ganesha#adapters) section for details.

### Version Guidance

| Version | PHP Version |
|---------|-------------|
| 3.x     | >=8.0       |
| 2.x     | >=7.3       |
| 1.x     | >=7.1       |
| 0.x     | >=5.6       |

## [Author](#table-of-contents)

**Ganesha** &copy; ackintosh, Released under the [MIT](./LICENSE) License.  
Authored and maintained by ackintosh

> GitHub [@ackintosh](https://github.com/ackintosh) / Twitter [@NAKANO_Akihito](https://twitter.com/NAKANO_Akihito) / [Blog (ja)](https://ackintosh.github.io/)
