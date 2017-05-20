<?php
require_once __DIR__ . '/send_request.php';
echo "[ settings ]\n";
echo "time window : " . TIME_WINDOW . "s\n";
echo "failure rate : " . FAILURE_RATE . "%\n";
echo "minumum requests : " . MINIMUM_REQUESTS . "\n";
echo "interval to half open : " . INTERVAL_TO_HALF_OPEN . "s\n";
echo "\n";

echo "[ failure rate ]\n";

$ganesha = buildGanesha();
$prop = new \ReflectionProperty($ganesha, 'strategy');
$prop->setAccessible(true);
$strategy = $prop->getValue($ganesha);

// current
$prop = new \ReflectionProperty($strategy, 'storage');
$prop->setAccessible(true);
$storage = $prop->getValue($strategy);

$failure = $storage->getFailureCount(SERVICE_NAME);
$success = $storage->getSuccessCount(SERVICE_NAME);
$rejection = $storage->getRejectionCount(SERVICE_NAME);

$total = $failure + $success + $rejection;
$rate = $total ? ($failure / $total) * 100 : 0;
echo sprintf("current : %.2F %%\n", $rate);

// previous
$method = new \ReflectionMethod($strategy, 'keyForPreviousTimeWindow');
$method->setAccessible(true);
$key = $method->invokeArgs($strategy, [SERVICE_NAME, TIME_WINDOW]);

$failure = $storage->getFailureCountByCustomKey($key);
$success = $storage->getSuccessCountByCustomKey($key);
$rejection = $storage->getRejectionCountByCustomKey($key);

$total = $failure + $success + $rejection;
$rate = $total ? ($failure / $total) * 100 : 0;
echo sprintf("previous : %.2F %%\n", $rate);
