<?php

if(getenv('SYMFONY_ENV') && 'prod' === getenv('SYMFONY_ENV')) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    exit;
}

use Symfony\Component\HttpFoundation\Request;

$loader = require __DIR__.'/../../app/autoload.php';
include __DIR__ . '/../../core/controller.class.php';
include __DIR__ . '/../../core/command.class.php';
include __DIR__ . '/../../config.php';
require_once __DIR__.'/../../app/AppKernel.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

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

$kernel = new AppKernel('demo', false);

$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
