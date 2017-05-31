<?php
declare(ticks = 1);
require_once __DIR__ . '/vendor/autoload.php';

use \Ackintosh\Snidel;

define('PATH_TO_LOG', __DIR__ . '/client.log');

// clean up
$m = new \Memcached();
$m->addServer('localhost', 11211);
$m->flush();
file_put_contents(PATH_TO_LOG, '');

if (
    ($awsKey = getenv('SNIDEL_AWS_KEY'))
    && ($awsSecret = getenv('SNIDEL_AWS_SECRET'))
    && ($awsRegion = getenv('SNIDEL_AWS_REGION'))
) {
    $config = [
        'aws-key' => $awsKey,
        'aws-secret' => $awsSecret,
        'aws-region' => $awsRegion,
        'concurrency' => 3,
        'taskQueue'     => [
            'className'         => '\Ackintosh\Snidel\Queue\Sqs\Task',
        ],
        'resultQueue'   => [
            'className'         => '\Ackintosh\Snidel\Queue\Sqs\Result',
        ],
    ];
} else {
    $config = [
        'concurrency' => 3,
    ];
}
$snidel = new Snidel($config);
$snidel->fork(function () {
    request();
});
$snidel->fork(function () {
    request();
});
$snidel->fork(function () {
    request();
});

$snidel->wait();

function request()
{
    while (1) {
        usleep(300000);
        exec('php ' . __DIR__ . '/send_request.php >> '. __DIR__ .'/client.log &');
    }
}
