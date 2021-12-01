<?php
const DEFAULT_REDIRECTS = 3;

$current = $_GET['current'] ?? 0;
$redirects = $_GET['redirects'] ?? DEFAULT_REDIRECTS;

if ($current === $redirects) {
    header('Status: 200 OK', false, 200);
    exit;
}

$url = 'http://'.$_SERVER['HTTP_HOST'].'/server/redirect.php?redirects='.$redirects.'&current='.++$current;
header('Status: 301 Moved Permanently', false, 301);
header('Location: '.$url);
exit;
