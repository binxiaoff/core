<?php
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../../app/autoload.php';
include '../../core/controller.class.php';
include '../../core/command.class.php';

header('X-Server: ' . exec('hostname'));

$kernel  = new AppKernel('prod', false);
$request = Request::createFromGlobals();
$kernel->boot();

// use symfony session handler to avoid session issue on PHP7.1 (https://github.com/websupport-sk/pecl-memcache/issues/23)
$kernel->getContainer()->get('session')->start();

$oDispatcher = new \Unilend\core\Dispatcher($kernel, 'admin', $request);
