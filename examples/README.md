# Example

## Setup

```
$ pwd
~/ganesha
$ composer install
$ docker-compose run --rm client composer install
```

## Run

- Starts http and memcached server
- Starts clients (with Ganesha) that repeats http reqeuest to server
```
$ docker-compose up
```

## Monitor your circuit

```
$ brew install watch
$ watch docker-compose run --rm client php monitor.php

Every 2.0s: php monitor.php

[ settings ]
time window : 20s
failure rate : 10s
minumum requests : 10s
interval to half open : 5s

[ failure rate ]
current  : 0 %
previous : 12.21 %

```

