<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$publicPath = __DIR__.'/public';
$requestedFile = realpath($publicPath.$path);

if ($requestedFile !== false && str_starts_with($requestedFile, realpath($publicPath)) && is_file($requestedFile)) {
    return false;
}

// When using Symfony Runtime, the front controller must return a callable.
return require $publicPath.'/index.php';
