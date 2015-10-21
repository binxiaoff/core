<?php

/**
 * @todo
 * Options and descriptions may be set directly in Cron class
 * Catch other exceptions than UnexpectedValueException
 */

require_once __DIR__ . '/../Autoloader.php';
require_once __DIR__ . '/../config.php';

use Unilend\core\Cron;
use Unilend\core\Bootstrap;
use Unilend\librairies\ULogger;

Autoloader::register();

$oBootstrap = Bootstrap::getInstance($config);
$oCron      = new Cron($oBootstrap);
$oCron->setOptions(
    array('d' => Cron::OPTION_REQUIRED,
          'c' => Cron::OPTION_REQUIRED,
          's' => Cron::OPTION_REQUIRED,
          'f' => Cron::OPTION_OPTIONAL,
          't' => Cron::OPTION_OPTIONAL
    ))
    ->setDescription('d', 'directory name for load class')
    ->setDescription('c', 'classname to use')
    ->setDescription('s', 'name of cron for semaphore')
    ->setDescription('f', 'function name to use if necessary')
    ->setDescription('t', 'cron max execution time, default 5 min')
    ->setParameters();

try {
    $oCron->parseCommand();
    $oCron->executeCron();
} catch (\UnexpectedValueException $oException) {
    $oBootstrap->setLogger('cron', 'cron.log');
    $oBootstrap->getLogger()->addRecord(ULogger::CRITICAL, $oException->getMessage(), array(__FILE__ . ' at ' . __LINE__));
}
