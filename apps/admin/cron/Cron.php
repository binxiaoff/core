<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../Autoloader.php';
require_once __DIR__ . '/../../../config.php';

Autoloader::register();

/**
 * @object $oBootstrap Instance of Boostrap for log cron calls and set configuration
 */
$oBootstrap = \Unilend\core\Bootstrap::getInstance($config);
$oBootstrap->setConfig($config);

/**
 * @object $oCron for manage parameters required and optional
 */
$oCron = $oBootstrap->getCron();

$oCron->setOptions(
    array('d' => Unilend\core\Cron::OPTION_REQUIRED,
        'c' => Unilend\core\Cron::OPTION_REQUIRED,
        'f' => Unilend\core\Cron::OPTION_OPTIONAL
    ))
    ->setDescription('d', 'directory name for load class')
    ->setDescription('c', 'classname to use')
    ->setDescription('f', 'function name to use if necessary')
    ->setParameters();

try {
    $oCron->parseCommand();
    $oCron->executeCron($oBootstrap);
} catch (\UnexpectedValueException $e) {
    echo $e->getMessage();
    $oCron->getLogger->addCritical($e->getMessage(), array(__FILE__ . ' at ' . __LINE__));
}
