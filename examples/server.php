<?php
if ((int)file_get_contents(__DIR__ . '/server.data') === 2) {
    sleep(5);
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}
