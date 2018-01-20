<?php
use \Ackintosh\Ganesha;
use \Ackintosh\Ganesha\Builder;

require_once dirname(__DIR__) . '/vendor/autoload.php';

define('RESOURCE', 'example');
define('TIME_WINDOW', 20);
define('FAILURE_RATE', 10);
define('MINIMUM_REQUESTS', 10);
define('INTERVAL_TO_HALF_OPEN', 5);
define('SERVER_STATE_DATA', __DIR__ . '/server/state.dat');
define('SERVER_STATE_NORMAL', 'normal');
define('SERVER_STATE_ABNORMAL', 'abnormal');

function buildGanesha($storage)
{
    switch ($storage) {
        case 'redis':
            $redis = new \Redis();
            $redis->connect(getenv('GANESHA_EXAMPLE_REDIS') ?: 'localhost');
            $adapter = new Ackintosh\Ganesha\Storage\Adapter\Redis($redis);
            break;
        case 'memcached':
            $m = new \Memcached();
            $m->addServer(getenv('GANESHA_EXAMPLE_MEMCACHED') ?: 'localhost' , 11211);
            $adapter = new \Ackintosh\Ganesha\Storage\Adapter\Memcached($m);
            break;
        default:
            throw new \InvalidArgumentException();
            break;
    }

    $ganesha =  Builder::build([
        'adapter'               => $adapter,
        'timeWindow'            => TIME_WINDOW,
        'failureRateThreshold'  => FAILURE_RATE,
        'minimumRequests'       => MINIMUM_REQUESTS,
        'intervalToHalfOpen'    => INTERVAL_TO_HALF_OPEN,
    ]);

    $messageOnTripped = <<<__EOS__
!!!!!!!!!!!!!!!!!!!!!!!
!!!!!!! TRIPPED !!!!!!!
!!!!!!!!!!!!!!!!!!!!!!!

__EOS__;
    $messageOnCalmedDown = <<<__EOS__
=======================
===== CALMED DOWN =====
=======================

__EOS__;

    $ganesha->subscribe(function ($event, $resource, $message) use ($messageOnTripped, $messageOnCalmedDown) {
        switch ($event) {
            case Ganesha::EVENT_TRIPPED:
                echo $messageOnTripped;
                break;
            case Ganesha::EVENT_CALMED_DOWN:
                echo $messageOnCalmedDown;
                break;
            default:
                break;
        }
    });

    return $ganesha;
}
