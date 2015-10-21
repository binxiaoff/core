<?php

if (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
    $currentCookieParams = session_get_cookie_params();

    session_set_cookie_params(
        $currentCookieParams["lifetime"],
        $currentCookieParams["path"],
        $currentCookieParams["domain"],
        true,
        true
    );
}

session_start();
ini_set('session.gc_maxlifetime', 3600); // 1h la session

include('../../config.php');
if ($_SERVER['SERVER_NAME'] == "www.unilend.fr") {
    include('../../Autoloader.php');
    Autoloader::register();

    require('prepend.php');
}

include('../../core/dispatcher.class.php');
include('../../core/controller.class.php');
include('../../core/command.class.php');
include('../../core/bdd.class.php');
include('../../core/errorhandler.class.php');
include('../../route.php');

$tablOk = array('78.225.42.28', '109.0.41.146', '78.225.121.47', '78.225.121.3', '93.26.42.99');

$app                    = 'default';
//define in routages.php
$config['route_projet'] = isset($route_projet) ? $route_projet : '';
$config['route_url']    = isset($route_url) ? $route_url : '';

if (file_exists('../../config.' . $app . '.php'))
    include('../../config.' . $app . '.php');

$handler    = new ErrorHandler($config['error_handler'][$config['env']]['file'], $config['error_handler'][$config['env']]['allow_display'], $config['error_handler'][$config['env']]['allow_log'], $config['error_handler'][$config['env']]['report']);
$dispatcher = new Dispatcher($config, $app);
if ($_SERVER['SERVER_NAME'] == "www.unilend.fr") {
    require('append.php');
}