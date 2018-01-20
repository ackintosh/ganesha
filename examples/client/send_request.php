<?php
declare(ticks = 1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use \Ackintosh\Ganesha;
use \Ackintosh\Ganesha\Builder;

define('RESOURCE', 'example');
define('TIME_WINDOW', 20);
define('FAILURE_RATE', 10);
define('MINIMUM_REQUESTS', 10);
define('INTERVAL_TO_HALF_OPEN', 5);
define('SERVER_STATE_DATA', __DIR__ . '/../server/state.dat');
define('SERVER_STATE_NORMAL', 'normal');
define('SERVER_STATE_ABNORMAL', 'abnormal');

function buildGanesha($storage)
{
    $tripped = <<<__EOS__
!!!!!!!!!!!!!!!!!!!!!!!
!!!!!!! TRIPPED !!!!!!!
!!!!!!!!!!!!!!!!!!!!!!!

__EOS__;
    $calmedDown = <<<__EOS__
=======================
===== CALMED DOWN =====
=======================

__EOS__;

    switch ($storage) {
        case 'redis':
            $redis = new \Redis();
            $redis->connect(getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost');
            $adapter = new Ackintosh\Ganesha\Storage\Adapter\Redis($redis);
            break;
        case 'memcached':
            $m = new \Memcached();
            $m->addServer(getenv('GANESHA_EXAMPLE_MEMCACHED') ? getenv('GANESHA_EXAMPLE_MEMCACHED') : 'localhost' , 11211);
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

    $ganesha->subscribe(function ($event, $resource, $message) use ($tripped, $calmedDown) {
        switch ($event) {
            case Ganesha::EVENT_TRIPPED:
                echo $tripped;
                break;
            case Ganesha::EVENT_CALMED_DOWN:
                echo $calmedDown;
                break;
            default:
                break;
        }
    });

    return $ganesha;
}

function sendRequest($storage)
{
    $ganesha = buildGanesha($storage);
    $client = new GuzzleHttp\Client();
    if ($ganesha->isAvailable(RESOURCE)) {
        try {
            $client->request('GET', 'http://server/server/index.php');
        } catch (\Exception $e) {
            echo  date('H:i:s') . " <failure>\n";
            $ganesha->failure(RESOURCE);
            return;
        }

        $ganesha->success(RESOURCE);
        echo date('H:i:s') . " (success)\n";
    } else {
        echo date('H:i:s') . " [[[ reject ]]]\n";
    }
}
