<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$loader = require __DIR__.'/../../app/autoload.php';
include __DIR__ . '/../../core/controller.class.php';
include __DIR__ . '/../../core/command.class.php';
include __DIR__ . '/../../config.php';
require_once __DIR__.'/../../app/AppKernel.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
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

//session_start();
//ini_set('session.gc_maxlifetime', 3600); // 1h la session

Debug::enable();

$oKernel = new AppKernel('dev', true);

try {
    Request::enableHttpMethodParameterOverride();
    $request  = Request::createFromGlobals();
    $response = $oKernel->handle($request);
    $response->send();
    $oKernel->terminate($request, $response);

} catch (NotFoundHttpException $exception) {
    $oKernel->boot();
    $errorLogfile = $oKernel->getLogDir() . '/error.' . date('Ymd') . '.log';
    \Unilend\core\ErrorHandler::enable($errorLogfile);
    $oDispatcher = new \Unilend\core\Dispatcher($oKernel, 'default', $config);
}