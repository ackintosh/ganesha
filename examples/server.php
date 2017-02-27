<?php

$data = file_get_contents(__DIR__ . '/server.data');

if ((int)$data === 2) {
    sleep(3);
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}
