<?php

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
    include_once __DIR__ . '/../../Autoloader.php';
    Autoloader::register();

    require __DIR__ . '/prepend.php';
}

include __DIR__ . '/../../core/dispatcher.class.php';
include __DIR__ . '/../../core/controller.class.php';
include __DIR__ . '/../../core/command.class.php';
include __DIR__ . '/../../core/bdd.class.php';
include __DIR__ . '/../../core/errorhandler.class.php';
include __DIR__ . '/../../route.php';

$tablOk = array('78.225.42.28', '109.0.41.146', '78.225.121.47', '78.225.121.3', '93.26.42.99');

//define in routages.php
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
$dispatcher = new Dispatcher($config, $app);

if ($bCacheFullPage) {
    require __DIR__ . '/append.php';
}
