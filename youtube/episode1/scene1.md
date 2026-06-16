# What is the Circuit Breaker pattern?

## The problem: Cascading failure

```
[ Your App ] ──→ [ External API ]  (slow / down)
                       │
                       ▼
                  requests pile up
                       │
                       ▼
            threads / memory exhausted
                       │
                       ▼
         [ Your App grinds to a halt ]
```

> Your app fails — not because of your own code,
> but because of a dependency that's failing.

---

## The solution: Circuit Breaker

Like an electrical breaker at home —
when too many failures happen, the circuit **trips**.

- Stop sending requests to the broken service
- **Fail fast** — return an error immediately
- After some time, let one test request through to check recovery

---

## Three states

| State | Behavior |
|-------|----------|
| **Closed** | Normal — requests go through |
| **Open** | Failures exceeded threshold — requests blocked |
| **Half-Open** | Trial period — one request allowed through to test recovery |

---

## References

- Michael Nygard, *Release It!* (2007)

![https://m.media-amazon.com/images/I/81c+o9-DetL._AC_UF1000,1000_QL80_.jpg](https://m.media-amazon.com/images/I/81c+o9-DetL._AC_UF1000,1000_QL80_.jpg)

- Martin Fowler, *Circuit Breaker* — https://martinfowler.com/bliki/CircuitBreaker.html

![](martinfowler.png)