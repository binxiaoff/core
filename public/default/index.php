<?php
include_once __DIR__ . '/../../Autoloader.php';

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

include __DIR__ . '/../../config.php';

$bCacheFullPage = ($_SERVER['SERVER_NAME'] === 'www.unilend.fr');

if ($bCacheFullPage) {
    require __DIR__ . '/prepend.php';
}

include __DIR__ . '/../../core/controller.class.php';
include __DIR__ . '/../../core/command.class.php';
include __DIR__ . '/../../core/errorhandler.class.php';
include __DIR__ . '/../../route.php';

$app                    = 'default';
$config['route_projet'] = isset($route_projet) ? $route_projet : '';
$config['route_url']    = isset($route_url) ? $route_url : '';

if (file_exists(__DIR__ . '/../../config.' . $app . '.php')) {
    include __DIR__ . '/../../config.' . $app . '.php';
}

$handler    = new ErrorHandler(
    $config['error_handler'][$config['env']]['file'],
    $config['error_handler'][$config['env']]['allow_display'],
    $config['error_handler'][$config['env']]['allow_log'],
    $config['error_handler'][$config['env']]['report']
);

$oKernel = new \Unilend\core\Kernel('prod', false);
$oKernel->boot();
$oDispatcher = new \Unilend\core\Dispatcher($oKernel, $app, $config);

if ($bCacheFullPage) {
    require __DIR__ . '/append.php';
}
