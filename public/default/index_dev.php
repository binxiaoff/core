<?php

use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

if (getenv('SYMFONY_ENV') && 'prod' === getenv('SYMFONY_ENV')) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    exit;
}

require __DIR__ . '/../../app/autoload.php';
include __DIR__ . '/../../core/controller.class.php';
include __DIR__ . '/../../core/command.class.php';

setlocale(LC_TIME, 'fr_FR.utf8');

if (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
    $currentCookieParams = session_get_cookie_params();

    session_set_cookie_params(
        $currentCookieParams['lifetime'],
        $currentCookieParams['path'],
        $currentCookieParams['domain'],
        true,
        true
    );
}

Debug::enable();

$kernel   = new AppKernel('dev', true);
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
