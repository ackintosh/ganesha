<?php
require_once __DIR__ . '/send_request.php';

if (trim(file_get_contents(__DIR__ . '/server.data')) === SERVER_STATE_ABNORMAL) {
    sleep(5);
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}
