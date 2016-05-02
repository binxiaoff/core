<?php
namespace Unilend\Service\Event;


use Psr\Log\LoggerInterface;
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

    public function __construct(LoggerInterface $logger)
    {
        $this->config = Loader::loadConfig();
        $this->logger = $logger;
    }

    public function onCommandStart(ConsoleCommandEvent $event)
    {
        $input = $event->getInput();
        $command = $event->getCommand();

        $this->logger->info('Start command ' . $command->getName(), array('arguments' => $input->getArguments(), 'options' => $input->getOptions()));
    }

    public function onCommandEnd(ConsoleTerminateEvent $event)
    {
        $input = $event->getInput();
        $command = $event->getCommand();

        $this->logger->info('End command ' . $command->getName(), array('arguments' => $input->getArguments(), 'options' => $input->getOptions()));
    }
}