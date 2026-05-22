# YouTube Video Script: Introducing Ganesha — a PHP Circuit Breaker library

**Channel**: [Hand-Rolled Code](channel.md)  
**Project**: [Ganesha](https://github.com/ackintosh/ganesha) — PHP Circuit Breaker library  
**Task**: Introduce the circuit breaker pattern and Ganesha's basic API  
**Estimated duration**: 12–18 minutes  

---

## Scene 1 — Channel intro (camera on screen, terminal open)

> "Hey everyone, welcome to Hand-Rolled Code. This channel is about coding by hand — writing real software with my own hands, no AI autocomplete, no generated boilerplate. Just me and a keyboard. I'm planning to post sessions like this regularly, so if that sounds interesting, feel free to subscribe.
>
> Today I want to introduce a PHP library called **Ganesha** — a circuit breaker implementation I've been maintaining. We're going to look at what the circuit breaker pattern is, why it's useful, and how Ganesha implements it. I'll also walk through the basic API so you can start using it in your own projects."

---

## Scene 2 — What is the circuit breaker pattern?

> "Let me start with the concept, because the code makes a lot more sense once you understand what problem we're solving.
>
> Imagine you have a web application that calls an external payment API. If that API is slow or down, your app starts accumulating requests waiting for a response. Those requests pile up, consuming threads and memory. Eventually your entire application grinds to a halt — not because of your own code, but because of a dependency that's failing. This is called a **cascading failure**.
>
> The **circuit breaker pattern** is a way to prevent this. It works just like the electrical circuit breaker in your home. When something goes wrong — too many failures — the circuit 'trips' and you stop sending requests to the broken service. Instead of waiting for a timeout every time, you fail fast and return an error immediately. After some time, you let a test request through to see if the service has recovered.
>
> There are three states:
> - **Closed** — everything is normal, requests go through
> - **Open** — failures exceeded the threshold, requests are blocked
> - **Half-Open** — a trial period, one request is allowed through to test recovery

*Optionally show the diagram image from the README.*

> "Ganesha implements this pattern in PHP."

---

## Scene 3 — Installing Ganesha

*Open terminal.*

> "Let me install it. Ganesha is on Packagist, so it's just a Composer command."

```bash
composer require ackintosh/ganesha
```

> "That's it. No extra extensions required for the basic setup."

---

## Scene 4 — Building Ganesha with the Count Strategy

*Open a new PHP file, for example `demo.php`.*

> "Ganesha provides two strategies for detecting failures. Let's start with the simpler one: the **Count strategy**. It trips the circuit when the number of failures reaches a threshold.
>
> Here's how you build a Ganesha instance."

```php
<?php
require 'vendor/autoload.php';

$redis = new Redis();
$redis->connect('localhost');

$ganesha = Ackintosh\Ganesha\Builder::withCountStrategy()
    ->adapter(new Ackintosh\Ganesha\Storage\Adapter\Redis($redis))
    ->failureCountThreshold(3)
    ->intervalToHalfOpen(10)
    ->build();
```

> "Let me walk through the options:
>
> - `failureCountThreshold(3)` — the circuit trips after 3 consecutive failures
> - `intervalToHalfOpen(10)` — 10 seconds after tripping, Ganesha allows one trial request through
>
> The adapter is how Ganesha persists its state. Here I'm using Redis. Ganesha supports several adapters — Redis, Memcached, APCu, and MongoDB."

---

## Scene 5 — The basic API: `isAvailable()`, `success()`, `failure()`

> "Ganesha's API is deliberately minimal. There are three methods you need to know."

```php
$service = 'payment-api';

if (!$ganesha->isAvailable($service)) {
    // fail fast — don't even try the request
    throw new RuntimeException('Payment API is not available');
}

try {
    // make the actual request
    $result = callPaymentApi();
    $ganesha->success($service);
} catch (RuntimeException $e) {
    $ganesha->failure($service);
    throw $e;
}
```

> "The `$service` string is just a name — it's how Ganesha tracks state per service. You can have as many services as you like, each with its own circuit state.
>
> - `isAvailable()` — returns `true` if the circuit is closed, `false` if it's open
> - `success()` — tell Ganesha the request succeeded
> - `failure()` — tell Ganesha the request failed
>
> That's the entire integration. You wrap your existing call with an `isAvailable()` check and record the outcome."

---

## Scene 6 — Watching the circuit trip

> "Let me show what actually happens when failures accumulate."

```php
var_dump($ganesha->isAvailable($service)); // bool(true)

$ganesha->failure($service);
$ganesha->failure($service);
$ganesha->failure($service); // 3rd failure — threshold reached

var_dump($ganesha->isAvailable($service)); // bool(false)
```

> "After the third failure, `isAvailable()` returns false. The circuit is open. Any further calls are blocked immediately — no waiting for timeouts, no wasted resources."

---

## Scene 7 — Subscribing to events

> "Ganesha also publishes events when the circuit state changes. This is useful for logging or alerting."

```php
$ganesha->subscribe(function (string $event, string $service, string $message): void {
    error_log(sprintf('[Ganesha] %s: %s', $event, $service));
});
```

> "There are three events:
> - `EVENT_TRIPPED` — the circuit just opened
> - `EVENT_CALMED_DOWN` — the circuit recovered and closed again
> - `EVENT_STORAGE_ERROR` — the storage backend had a problem
>
> Notice that storage errors are handled gracefully — if Redis goes down, Ganesha defaults to returning `true` from `isAvailable()` rather than crashing your application. It fails open, which is usually the right default for a circuit breaker."

---

## Scene 8 — Brief mention of the Rate Strategy

> "I mentioned there's a second strategy — the **Rate strategy**. Instead of counting raw failures, it tracks the failure rate as a percentage over a sliding time window. This is better for high-traffic services where a fixed count doesn't scale well.

```php
$ganesha = Ackintosh\Ganesha\Builder::withRateStrategy()
    ->adapter(new Ackintosh\Ganesha\Storage\Adapter\Redis($redis))
    ->failureRateThreshold(50)   // trip if 50% of requests fail
    ->minimumRequests(10)        // but only after at least 10 requests
    ->timeWindow(30)             // measured over a 30-second window
    ->intervalToHalfOpen(10)
    ->build();
```

> "We won't go deeper into this today, but the API is identical — same three methods, same event system."

---

## Scene 9 — Tease for Episode 2

*Open [src/Ganesha/Storage/Adapter/Redis.php](../src/Ganesha/Storage/Adapter/Redis.php), navigate to the `reset()` method.*

> "Before I wrap up — let me show you something I found in the codebase."

*Scroll to the `reset()` method's TODO comment.*

> "There's a TODO here. The `reset()` method — which is supposed to clear all circuit breaker state — is not implemented for the Redis adapter. If you call `$ganesha->reset()` right now with Redis, nothing happens.
>
> In the next video, I'm going to fix this. We'll look at how Redis is storing Ganesha's data, figure out the right approach to delete it all safely, write the implementation, and test it.
>
> That's it for today. If you found this useful, give it a thumbs up. See you next time."

---

## Notes for Recording

- **Editor**: VS Code with the file tree visible on the left sidebar
- **Font size**: Increase to at least 18pt for readability on video
- **Terminal**: Keep it in the same window, use a split pane if possible
- **Pace**: Pause after each code block to give viewers time to read
- **For the demo**: Start Docker before recording — `docker-compose up` to have Redis running
- **Pronunciation tips**:
  - "circuit breaker" → SIR-kit BRAY-ker
  - "cascading" → kas-KAY-ding
  - "threshold" → THRESH-old
  - "Packagist" → PACK-uh-jist
  - "Composer" → kom-POH-zer
