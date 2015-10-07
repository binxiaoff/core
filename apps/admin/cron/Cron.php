<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../Autoloader.php';
require_once __DIR__ . '/../../../config.php';

Autoloader::register();

/**
 * @object $oBootstrap Instance of Boostrap for log cron calls and set configuration
 */
$oBootstrap = \Unilend\core\Bootstrap::getInstance($config);

/**
 * @object $oCron for manage parameters required and optional
 */
$oCron = $oBootstrap->getCron();

$oCron->setOptions(
    array('d' => Unilend\core\Cron::OPTION_REQUIRED,
          'c' => Unilend\core\Cron::OPTION_REQUIRED,
          'f' => Unilend\core\Cron::OPTION_OPTIONAL,
          's' => \Unilend\core\Cron::OPTION_REQUIRED
    ))
    ->setDescription('d', 'directory name for load class')
    ->setDescription('c', 'classname to use')
    ->setDescription('f', 'function name to use if necessary')
    ->setDescription('s', 'name of cron for semaphore')
    ->setParameters();

try {
    $oCron->parseCommand();
    $oCron->executeCron();
} catch (\UnexpectedValueException $e) {
    echo $e->getMessage();
    $oCron->getLogger()->addRecord(\Unilend\librairies\ULogger::CRITICAL, $e->getMessage(), array(__FILE__ . ' at ' . __LINE__));
}
