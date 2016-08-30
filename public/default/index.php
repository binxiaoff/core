<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$loader = require __DIR__.'/../../app/autoload.php';
include __DIR__ . '/../../core/controller.class.php';
include __DIR__ . '/../../core/command.class.php';
include __DIR__ . '/../../config.php';
require_once __DIR__.'/../../app/AppKernel.php';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);

ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

header('X-Server: ' . exec('hostname'));

require __DIR__ . '/prepend.php';

$kernel = new AppKernel('prod', false);
$request  = Request::createFromGlobals();

try {
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
} catch (NotFoundHttpException $exception) {
    $kernel->boot();
    $dispatcher = new \Unilend\core\Dispatcher($kernel, 'default', $config);
}

require __DIR__ . '/append.php';
