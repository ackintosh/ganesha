# YouTube Video Script: Implementing `reset()` for the Redis Adapter

**Channel**: [Hand-Rolled Code](channel.md)  
**Project**: [Ganesha](https://github.com/ackintosh/ganesha) — PHP Circuit Breaker library  
**Episode**: 2 — continues from [episode-1.md](episode-1.md)  
**Task**: Implement the `reset()` method in the Redis storage adapter  
**Estimated duration**: 15–20 minutes  

---

## Scene 1 — Introduction (camera on screen, terminal open)

> "Hey everyone, welcome back. Last time I introduced Ganesha — a PHP circuit breaker library I've been maintaining — and walked through the basic API.
>
> Today's task is more focused. I found a TODO comment in the codebase, and I want to take care of it."

---

## Scene 2 — Finding the TODO (open `src/Ganesha/Storage/Adapter/Redis.php`)

> "Let me open the Redis adapter — this is the storage backend that uses Redis to persist circuit breaker state."

*Navigate to [src/Ganesha/Storage/Adapter/Redis.php](src/Ganesha/Storage/Adapter/Redis.php), scroll to the `reset()` method around line 147.*

> "Here it is — line 148. The `reset()` method has a TODO comment: `Implement reset() method`. So right now, if you call `reset()` on Ganesha when using the Redis adapter, nothing actually happens. That's a bug we should fix.

> The `reset()` method is supposed to clear all circuit breaker state — failure counts, statuses, everything. This is useful in tests, or when you want to manually recover a tripped circuit.

> Let me look at how the other adapters implement this to get a sense of what we need to do."

---

## Scene 3 — Exploring other adapters (open `src/Ganesha/Storage/Adapter/Memcached.php`)

*Open [src/Ganesha/Storage/Adapter/Memcached.php](src/Ganesha/Storage/Adapter/Memcached.php) and the APCu adapter.*

> "Looking at the other adapters... okay, I can see the pattern. The reset method needs to delete all keys that Ganesha has created in the storage backend.

> Now let me think about what data Redis is actually storing for each service."

---

## Scene 4 — Understanding the Redis data structure

*Stay on [src/Ganesha/Storage/Adapter/Redis.php](src/Ganesha/Storage/Adapter/Redis.php), walk through the methods.*

> "Let me trace through the code. When a failure is recorded, the `increment()` method adds a timestamped entry to a **sorted set** in Redis — the key is the service name. So for a service called `payment-api`, we'd have a sorted set at a key like `ganesha_payment-api_failure`.

> Then `saveStatus()` saves the circuit status — tripped or calmed down — as a plain string key.

> So to reset, we need to delete both the sorted set and the status key for every service that Ganesha is tracking.

> The tricky part is: how do we know which keys belong to Ganesha? Let me look at the `StorageKeys` class."

*Open [src/Ganesha/Storage/StorageKeys.php](src/Ganesha/Storage/StorageKeys.php).*

> "Okay, so Ganesha uses a configurable prefix for its keys — by default it's `ganesha_`. That means we can use Redis's `KEYS` command or better, `SCAN`, to find all keys matching `ganesha_*` and delete them.

> I'll use `SCAN` instead of `KEYS` because `KEYS` blocks the Redis server while it scans — that can cause latency spikes in production. `SCAN` is non-blocking and iterates in batches. This is an important distinction for production systems."

---

## Scene 5 — Writing the implementation

*Edit [src/Ganesha/Storage/Adapter/Redis.php](src/Ganesha/Storage/Adapter/Redis.php), replace the `reset()` method.*

> "Alright, let me write this. I'll use the `SCAN` command to iterate over all matching keys and then delete them in batches."

```php
public function reset(): void
{
    $cursor = null;
    $prefix = $this->configuration->storageKeys()->prefix();

    do {
        $result = $this->redis->scan($cursor, $prefix . '*');
        if ($result === false) {
            break;
        }
        [$cursor, $keys] = $result;
        if (!empty($keys)) {
            $this->redis->del($keys);
        }
    } while ($cursor !== 0);
}
```

> "Let me walk through this. We start with a `null` cursor, which tells Redis to begin the scan from the start. On each iteration, `SCAN` returns a new cursor and a batch of matching keys. We delete that batch, then loop again until the cursor comes back as zero — that means we've gone through the entire keyspace.

> The `prefix()` method gives us the configured key prefix, so we only delete Ganesha's own keys and nothing else in Redis. That's important — we don't want to accidentally wipe unrelated data."

---

## Scene 6 — Writing the test

*Open the tests directory, find the Redis adapter test file.*

> "Now let's write a test. Good software means tests. Let me find where the Redis adapter tests live."

*Navigate to the relevant test file.*

> "I'll add a test case that:
> 1. Trips the circuit breaker by recording failures
> 2. Calls `reset()`
> 3. Asserts that the circuit is available again and all counts are cleared

> This covers the main use case — a developer who wants to clear state, for example between test runs or after a manual recovery."

```php
public function testReset(): void
{
    // Trip the circuit by recording failures
    for ($i = 0; $i < $this->configuration->failureCountThreshold(); $i++) {
        $this->ganesha->failure('test-service');
    }

    $this->assertFalse($this->ganesha->isAvailable('test-service'));

    // Reset all state
    $this->ganesha->reset();

    // Circuit should be available again
    $this->assertTrue($this->ganesha->isAvailable('test-service'));
}
```

> "Simple and clear. The test tells a story: trip the breaker, reset it, confirm it's back to normal."

---

## Scene 7 — Running the tests

*Open terminal.*

> "Let me run the test suite to make sure everything passes."

```bash
vendor/bin/phpunit
```

> "While this runs — one thing I appreciate about this project is that the tests actually hit a real Redis instance running in Docker. Some projects mock the database layer in tests, but that can hide bugs when the real database behaves differently. Here we're testing against the actual thing, which gives me much more confidence."

*Tests pass.*

> "All green. 

> Let me also run the static analysis tool — Psalm — just to make sure there are no type errors."

```bash
vendor/bin/psalm
```

> "Clean. Psalm is a static analysis tool for PHP that catches type errors before runtime. It's really useful for a library like this where you want to guarantee the API is correct."

---

## Scene 8 — Wrap-up

> "And that's it! We fixed a TODO that's been sitting in the codebase — implemented the `reset()` method for the Redis adapter using `SCAN` for safe, non-blocking key iteration.

> To summarize what we did today:
> - We explored the circuit breaker pattern and how Ganesha implements it
> - We found a TODO in the Redis adapter's `reset()` method
> - We looked at the Redis data structures being used — sorted sets for failure timestamps, plain strings for circuit status
> - We implemented `reset()` using Redis `SCAN` instead of `KEYS` to avoid blocking the server
> - We wrote a test to verify the behavior
> - And all tests pass

> If you found this useful, give it a thumbs up. I'll be posting more videos like this — working through real open-source code. See you next time."

---

## Notes for Recording

- **Editor**: VS Code with the file tree visible on the left sidebar
- **Font size**: Increase to at least 18pt for readability on video
- **Terminal**: Keep it in the same window, use a split pane if possible
- **Pace**: Pause after each code block to give viewers time to read
- **Pronunciation tips**:
  - "circuit breaker" → SIR-kit BRAY-ker
  - "cascading" → kas-KAY-ding
  - "threshold" → THRESH-old
  - "iterate" → IT-er-ate
  - "cursor" → KER-ser
