<?php
declare(ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use \Ackintosh\Ganesha;
use \Ackintosh\Ganesha\Builder;

define('RESOURCE', 'example');
define('TIME_WINDOW', 20);
define('FAILURE_RATE', 10);
define('MINIMUM_REQUESTS', 10);
define('INTERVAL_TO_HALF_OPEN', 5);
define('PATH_TO_LOG', __DIR__ . '/send_request.log');

function buildGanesha()
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

    $m = new \Memcached();
    $m->addServer(getenv('GANESHA_EXAMPLE_MEMCACHED') ? getenv('GANESHA_EXAMPLE_MEMCACHED') : 'localhost' , 11211);
    $adapter = new \Ackintosh\Ganesha\Storage\Adapter\Memcached($m);
    $ganesha =  Builder::build([
        'adapter'               => $adapter,
        'timeWindow'            => TIME_WINDOW,
        'failureRate'           => FAILURE_RATE,
        'minimumRequests'       => MINIMUM_REQUESTS,
        'intervalToHalfOpen'    => INTERVAL_TO_HALF_OPEN,
    ]);

    $ganesha->subscribe(function ($event, $resource, $message) use ($tripped, $calmedDown) {
        switch ($event) {
            case Ganesha::EVENT_TRIPPED:
                file_put_contents(PATH_TO_LOG, $tripped, FILE_APPEND);
                break;
            case Ganesha::EVENT_CALMED_DOWN:
                file_put_contents(PATH_TO_LOG, $calmedDown, FILE_APPEND);
                break;
            default:
                break;
        }
    });

    return $ganesha;
}

function sendRequest()
{
    $ganesha = buildGanesha();
    $client = new GuzzleHttp\Client();
    if ($ganesha->isAvailable(RESOURCE)) {
        try {
            $client->request('GET', 'http://server/server.php');
        } catch (\Exception $e) {
            file_put_contents(PATH_TO_LOG, date('H:i:s') . " <failure>\n", FILE_APPEND);
            $ganesha->failure(RESOURCE);
            return;
        }

        $ganesha->success(RESOURCE);
        file_put_contents(PATH_TO_LOG, date('H:i:s') . " (success)\n", FILE_APPEND);
    } else {
        file_put_contents(PATH_TO_LOG, date('H:i:s') . " [[[ reject ]]]\n", FILE_APPEND);
    }
}
