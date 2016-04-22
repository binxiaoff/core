<?php
namespace Unilend\Service\Event;


use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Unilend\core\Loader;
use Unilend\librairies\ULogger;

/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 11/04/2016
 * Time: 14:14
 */
class ConsoleEventListener
{
    private $config;
    private $logger;

    public function __construct()
    {
        $this->config = Loader::loadConfig();
        $this->logger = new ULogger('cron', $this->config['log_path'][$this->config['env']], 'cron.' . date('Ymd') . '.log');
    }

    public function onCommandStart(ConsoleCommandEvent $event)
    {
        $input = $event->getInput();
        $command = $event->getCommand();
        
        $this->logger->addRecord(ULogger::INFO, 'Start command ' . $command->getName(), array('arguments' => $input->getArguments(), 'options' => $input->getOptions()));
    }

    public function onCommandEnd(ConsoleTerminateEvent $event)
    {
        $input = $event->getInput();
        $command = $event->getCommand();
        
        $this->logger->addRecord(ULogger::INFO, 'End command ' . $command->getName(), array('arguments' => $input->getArguments(), 'options' => $input->getOptions()));
    }
}