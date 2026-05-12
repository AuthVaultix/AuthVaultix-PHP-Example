<?php
$envFilePath = __DIR__ . '/.env';

if (file_exists($envFilePath)) {
    $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;

        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            putenv(trim($key) . "=" . trim($val));
        }
    }
}

$name = getenv('$name');
$ownerid = getenv('$ownerid');
$secret = getenv('$secret');
$version = getenv('$version');

?>
