<?php

use Unilend\Kernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Unilend\core\Dispatcher;

require dirname(__DIR__) . '/../config/bootstrap.php';
include dirname(__DIR__) . '/../core/controller.class.php';
include dirname(__DIR__) . '/../core/command.class.php';

header('X-Server: ' . exec('hostname'));

setlocale(LC_TIME, 'fr_FR.utf8');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel  = new Kernel($_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$kernel->boot();

// use symfony session handler to avoid session issue on PHP7.1 (https://github.com/websupport-sk/pecl-memcache/issues/23)
$kernel->getContainer()->get('session')->start();

$oDispatcher = new Dispatcher($kernel, 'admin', $request);
