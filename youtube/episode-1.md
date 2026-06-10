# YouTube Video Script: Introducing Ganesha — a PHP Circuit Breaker library

**Channel**: [Hand-Rolled Code](channel.md)  
**Project**: [Ganesha](https://github.com/ackintosh/ganesha) — PHP Circuit Breaker library  
**Task**: Introduce the circuit breaker pattern and Ganesha's basic API  
**Estimated duration**: 12–18 minutes  

---

## Scene 1 — Channel intro (camera on screen, terminal open)

*show the channel image*

> "Hey everyone, welcome to Hand-Rolled Code. This channel is about coding by hand — no vibe coding, no AI doing the thinking for me. Just me working through problems the old-fashioned way. I think there's real value in writing every line yourself — it forces you to actually understand what you're doing. All right. I'm planning to post sessions like this regularly, so if that sounds interesting, feel free to subscribe.

*Show the Ganesha logo*

> Today I want to introduce a PHP library called **Ganesha** — a circuit breaker implementation I've been maintaining. We're going to look at what the circuit breaker is, why it's useful, and how Ganesha implements it. And I'll walk through the basic API so you can start using it in your own projects. Cool, let's get into it."

---

## Scene 2 — What is the circuit breaker pattern?

*Show episode-1-scene-02.png*

> "All right, let me start with the concept, because the code makes a lot more sense once you understand what problem we're solving.
>
> Imagine you have a web application that calls an external payment API. If that API is slow or down, your app starts accumulating requests waiting for a response. Those requests pile up, consuming threads and memory. Eventually your entire application grinds to a halt — not because of your own code, but because of a dependency that's failing. This is called a **cascading failure**.
>
> Okay. So the **circuit breaker** is a way to prevent this. It works just like the electrical circuit breaker in your home. When something goes wrong — too many failures — the circuit 'trips' and you stop sending requests to the broken service. Instead of waiting for a timeout every time, you fail fast and return an error immediately. After some time, you let a test request through to see if the service has recovered. Cool.
>
> There are three states:
> - **Closed** — everything is normal, requests go through
> - **Open** — failures exceeded the threshold, requests are blocked
> - **Half-Open** — a trial period, one request is allowed through to test recovery

*Show Release It*

> "This pattern was popularized by Michael Nygard in his 2007 book *Release It!*.

*Show Fowler's article*

> and Martin Fowler wrote a well-known article about it on his website. If you want to go deeper on the concept, Fowler's article is a great starting point — I'll link it in the description.
>
> All right. Ganesha implements this pattern in PHP."

---

## Scene 3 — Building Ganesha with the Count Strategy

*Open a new PHP file, for example `demo.php`.*

> "Okay. Ganesha provides two strategies for detecting failures. Let's start with the simpler one: the **Count strategy**. It trips the circuit when the number of failures reaches a threshold.
>
> All right, here's how you build a Ganesha instance."

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

> "All right, let me walk through the options:
>
> - `failureCountThreshold(3)` — the circuit trips after 3 consecutive failures
> - `intervalToHalfOpen(10)` — 10 seconds after tripping, Ganesha allows one trial request through
>
> Cool. The adapter is how Ganesha persists its state. Here I'm using Redis. Ganesha supports multiple storage adapters — Redis and Memcached are the ones I'd recommend."

---

## Scene 4 — The basic API: `isAvailable()`, `success()`, `failure()`

> "All right. Ganesha's API is deliberately minimal. There are three methods you need to know."

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

> "Okay. The `$service` string is just a name — it's how Ganesha tracks state per service. You can have as many services as you like, each with its own circuit state.
>
> - `isAvailable()` — returns `true` if the circuit is closed, `false` if it's open
> - `success()` — tell Ganesha the request succeeded
> - `failure()` — tell Ganesha the request failed
>
> All right, so that's the entire integration. You wrap your existing call with an `isAvailable()` check and record the outcome. Cool."

---

## Scene 5 — Watching the circuit trip

> "All right, let me show what actually happens when failures accumulate."

```php
var_dump($ganesha->isAvailable($service)); // bool(true)

$ganesha->failure($service);
$ganesha->failure($service);
$ganesha->failure($service); // 3rd failure — threshold reached

var_dump($ganesha->isAvailable($service)); // bool(false)
```

> "After the third failure, `isAvailable()` returns false. The circuit is open. Cool. Any further calls are blocked immediately — no waiting for timeouts, no wasted resources."

---

## Scene 6 — Subscribing to events

> "All right. Ganesha also publishes events when the circuit state changes. This is useful for logging or alerting."

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
> Cool. And notice that storage errors are handled gracefully — if Redis goes down, Ganesha defaults to returning `true` from `isAvailable()` rather than crashing your application. It fails open, which is usually the right default for a circuit breaker."

---

## Scene 7 — Brief mention of the Rate Strategy

> "Okay, I mentioned there's a second strategy — the **Rate strategy**. Instead of counting raw failures, it tracks the failure rate as a percentage over a sliding time window. This is better for high-traffic services where a fixed count doesn't scale well.

```php
$ganesha = Ackintosh\Ganesha\Builder::withRateStrategy()
    ->adapter(new Ackintosh\Ganesha\Storage\Adapter\Redis($redis))
    ->failureRateThreshold(50)   // trip if 50% of requests fail
    ->minimumRequests(10)        // but only after at least 10 requests
    ->timeWindow(30)             // measured over a 30-second window
    ->intervalToHalfOpen(10)
    ->build();
```

> "All right. We won't go deeper into this today, but the API is identical — same three methods, same event system. Cool."

---

## Scene 8 — Tease for Episode 2

*Open [src/Ganesha/Storage/Adapter/Redis.php](../src/Ganesha/Storage/Adapter/Redis.php), navigate to the `reset()` method.*

> "All right, before I wrap up — let me show you something I found in the codebase."

*Scroll to the `reset()` method's TODO comment.*

> "There's a TODO here. The `reset()` method — which is supposed to clear all circuit breaker state — is not implemented for the Redis adapter. If you call `$ganesha->reset()` right now with Redis, nothing happens. All right.
>
> So in the next video, I'm going to fix this. We'll look at how Redis is storing Ganesha's data, figure out the right approach to delete it all safely, write the implementation, and test it.
>
> Cool. That's it for today. If you found this useful, give it a thumbs up. See you next time."

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
