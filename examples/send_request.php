<?php
declare(ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use \Ackintosh\Ganesha\Builder;

define('SERVICE_NAME', 'example');
define('TIME_WINDOW', 20);
define('FAILURE_RATE', 10);
define('MINIMUM_REQUESTS', 10);
define('INTERVAL_TO_HALF_OPEN', 5);
define('PATH_TO_LOG', __DIR__ . '/send_request.log');

if (strpos($argv[0], basename(__FILE__))) {
    sendRequest();
}

function buildGanesha()
{
    $messageOnTrip = <<<__EOS__
!!!!!!!!!!!!!!!!!!!!!!!
!!!!!!! TRIPPED !!!!!!!
!!!!!!!!!!!!!!!!!!!!!!!

__EOS__;
    $messageOnCalmedDown = <<<__EOS__
=======================
===== CALMED DOWN =====
=======================

__EOS__;

    return Builder::build(array(
        'adapterSetupFunction'  => function () {
            $m = new \Memcached();
            $m->addServer('localhost', 11211);

            return new \Ackintosh\Ganesha\Storage\Adapter\Memcached($m);
        },
        'behaviorOnTrip' => function ($serviceName) use ($messageOnTrip) {
            file_put_contents(PATH_TO_LOG, $messageOnTrip, FILE_APPEND);
        },
        'behaviorOnCalmedDown' => function ($serviceName) use ($messageOnCalmedDown) {
            file_put_contents(PATH_TO_LOG, $messageOnCalmedDown, FILE_APPEND);
        },
        'timeWindow'            => TIME_WINDOW,
        'failureRate'           => FAILURE_RATE,
        'minimumRequests'       => MINIMUM_REQUESTS,
        'intervalToHalfOpen'    => INTERVAL_TO_HALF_OPEN,
    ));
}

function sendRequest()
{
    $ganesha = buildGanesha();
    $client = new GuzzleHttp\Client();
    if ($ganesha->isAvailable(SERVICE_NAME)) {
        try {
            $client->request('GET', 'http://localhost:8080/server.php');
        } catch (\Exception $e) {
            file_put_contents(PATH_TO_LOG, date('H:i:s') . " <failure>\n", FILE_APPEND);
            $ganesha->failure(SERVICE_NAME);
            return;
        }

        $ganesha->success(SERVICE_NAME);
        file_put_contents(PATH_TO_LOG, date('H:i:s') . " (success)\n", FILE_APPEND);
    } else {
        file_put_contents(PATH_TO_LOG, date('H:i:s') . " [[[ reject ]]]\n", FILE_APPEND);
    }
}
