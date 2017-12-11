<?php

use Symfony\Component\HttpFoundation\Request;

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

$kernel   = new AppKernel('prod', false);
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
