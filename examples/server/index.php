<?php
require_once dirname(__DIR__) . '/common.php';

if (trim(file_get_contents(SERVER_STATE_DATA)) === SERVER_STATE_ABNORMAL) {
    sleep(5);
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}
