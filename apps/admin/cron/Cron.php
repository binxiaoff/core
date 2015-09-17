<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../Autoloader.php';
require_once __DIR__ . '/../../../config.php';

Autoloader::register();
/**
 * @var string Options mandatory -> d = dirname where found class, c = class name to call, f = function to call
 */
$sOptions = "d:c:f:";

/**
 * @var array Parameters with get all options from line command
 */
$aParameters = getopt($sOptions);

$sClassName = '\\' . $aParameters['d'] . '\\' . $aParameters['c'];
$sFunctionToCall = $aParameters['f'];

$oClassCall = new $sClassName($config);

if (false === empty($sFunctionToCall)) {
    $oClassCall->$sFunctionToCall();
}
