<?php
namespace Unilend\Bundle\CommandBundle\Event;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 11/04/2016
 * Time: 14:14
 */
class ConsoleEventListener
{
    /** @var LoggerInterface $logger */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onCommandStart(ConsoleCommandEvent $event)
    {
        $input   = $event->getInput();
        $command = $event->getCommand();

        $this->logger->info('Start command ' . $command->getName(), array('arguments' => $input->getArguments(), 'options' => $input->getOptions()));
    }

    public function onCommandEnd(ConsoleTerminateEvent $event)
    {
        $input   = $event->getInput();
        $command = $event->getCommand();

        $this->logger->info('End command ' . $command->getName(), array('arguments' => $input->getArguments(), 'options' => $input->getOptions()));

//        $statusCode = $event->getExitCode();
//
//        if ($statusCode === 0) {
//            return;
//        }
//
//        if ($statusCode > 255) {
//            $statusCode = 255;
//            $event->setExitCode($statusCode);
//        }
//
//        $this->logger->warning(sprintf(
//            'Command `%s` exited with status code %d',
//            $command->getName(),
//            $statusCode
//        ));
    }

    public function onCommandException(ConsoleExceptionEvent $event)
    {
        $input   = $event->getInput();
        $command = $event->getCommand();

        $this->logger->error(
            'Uncaught exception in command ' . $command->getName() . ': ' . $event->getException()->getMessage(),
            array('arguments' => $input->getArguments(), 'options' => $input->getOptions())
        );
    }
}
