<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use \Ackintosh\Ganesha\Builder;

define('SERVICE_NAME', 'example');
define('PATH_TO_LOG', __DIR__ . '/send_request.log');

sendRequest();

function buildGanesha()
{
    return Builder::build(array(
        'adapterSetupFunction'  => function () {
            $m = new \Memcached();
            $m->addServer('localhost', 11211);

            return new \Ackintosh\Ganesha\Storage\Adapter\Memcached($m);
        },
        'behaviorOnTrip' => function ($serviceName) {
            file_put_contents(PATH_TO_LOG, "!!!!! TRIPPED !!!!!\n", FILE_APPEND);
        },
        'timeWindow'            => 30,
        'failureRate'           => 10,
        'minimumRequests'       => 10,
        'intervalToHalfOpen'    => 5,
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
            file_put_contents(PATH_TO_LOG, "<failure>\n", FILE_APPEND);
            $ganesha->failure(SERVICE_NAME);
            return;
        }

        $ganesha->success(SERVICE_NAME);
        file_put_contents(PATH_TO_LOG, "(success)\n", FILE_APPEND);
    } else {
        file_put_contents(PATH_TO_LOG, "[[[ rejected ]]]\n", FILE_APPEND);
    }
}
