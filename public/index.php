<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Kernel;

// With this mask default directory permissions are 775 and default file permissions are 664.
\umask(0002);

require \dirname(__DIR__) . '/vendor/autoload.php';

\setlocale(LC_TIME, 'fr_FR.utf8');

(new Dotenv())->bootEnv(\dirname(__DIR__) . '/.env');

if ($_SERVER['APP_DEBUG']) {
    \umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(\explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_AWS_ELB);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel   = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
