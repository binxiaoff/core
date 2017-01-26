<?php

if (getenv('SYMFONY_ENV') && 'prod' === getenv('SYMFONY_ENV')) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    exit;
}

use Symfony\Component\HttpFoundation\Request;

$loader = require __DIR__ . '/../../app/autoload.php';
include __DIR__ . '/../../core/controller.class.php';
include __DIR__ . '/../../core/command.class.php';
require_once __DIR__ . '/../../app/AppKernel.php';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
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

session_start();
ini_set('session.gc_maxlifetime', 3600); // 1h la session

$kernel = new AppKernel('preprod', false);

$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);