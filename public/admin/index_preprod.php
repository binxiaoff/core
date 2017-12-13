<?php
use Symfony\Component\HttpFoundation\Request;

if (getenv('SYMFONY_ENV') && 'prod' === getenv('SYMFONY_ENV')) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    exit;
}

require __DIR__ . '/../../app/autoload.php';
include '../../core/controller.class.php';
include '../../core/command.class.php';

$kernel  = new AppKernel('preprod', false);
$request = Request::createFromGlobals();
$kernel->boot();

// use symfony session handler to avoid session issue on PHP7.1 (https://github.com/websupport-sk/pecl-memcache/issues/23)
$kernel->getContainer()->get('session')->start();

$oDispatcher = new \Unilend\core\Dispatcher($kernel, 'admin', $request);

