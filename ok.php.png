<?php

$log = date('Y-m-d H:i:s') . " | IP: " . $_SERVER['REMOTE_ADDR'] . " | UA: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
file_put_contents('test.log', $log, FILE_APPEND);

header('Content-Type: image/png');
echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
?>
