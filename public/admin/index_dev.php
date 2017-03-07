<?php
use Symfony\Component\HttpFoundation\Request;

if (getenv('SYMFONY_ENV') && 'prod' === getenv('SYMFONY_ENV')) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    exit;
}

$loader = require __DIR__ . '/../../app/autoload.php';
include '../../core/controller.class.php';
include '../../core/command.class.php';
require_once __DIR__ . '/../../app/AppKernel.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
ini_set('session.gc_maxlifetime', 3600); // 1h la session

$kernel = new AppKernel('dev', true);
$request  = Request::createFromGlobals();
$kernel->boot();

$oDispatcher = new \Unilend\core\Dispatcher($kernel, 'admin', $request);
