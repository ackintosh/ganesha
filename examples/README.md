# Example

## Install

```
$ cd example
$ composer insetall
```

## Server

```
$ ./vendor/bin/hyper-run -S localhost:8080
```

## Client

```
$ memcached
```
```
$ php client.php
```

## Monitor

```
$ brew install watch
$ watch php monitor.php

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

