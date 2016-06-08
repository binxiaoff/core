<?php
$loader = require __DIR__.'/../../app/autoload.php';
include '../../core/controller.class.php';
include '../../core/command.class.php';
include '../../config.php';
require_once __DIR__.'/../../app/AppKernel.php';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
ini_set('session.gc_maxlifetime', 3600); // 1h la session

$oKernel     = new AppKernel('preprod', false);
$oKernel->boot();

$errorLogfile = $oKernel->getLogDir() . '/error.'. date('Ymd') .'.log';
\Unilend\core\ErrorHandler::enable($errorLogfile);

$oDispatcher = new \Unilend\core\Dispatcher($oKernel, 'admin', $config);
