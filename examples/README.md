# Example

## Setup

```shell
$ pwd
~/ganesha
$ composer install
```

## Run

```shell
# Starts http and memcached server
$ docker-compose up
```

```shell
# Starts clients (with Ganesha) that repeats http reqeuest to server
# It is recommended to run 3 or more clients
$ docker-compose run --rm client sh -c examples/bin/run_client
```

## Monitor your circuit

```shell
$ brew install watch
$ watch docker-compose run --rm client examples/bin/monitor

Every 2.0s: php monitor.php

[ settings ]
time window : 20s
failure rate : 10%
minumum requests : 10
interval to half open : 5s

[ failure rate ]
current  : 0 %
previous : 12.21 %
```

## Change server state

```shell
# Server returns 503
$ examples/bin/change_server_state abnormal

# Restore normal state
$ examples/bin/change_server_state normal
```
