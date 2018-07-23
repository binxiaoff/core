<?php

namespace Unilend\Bundle\CommandBundle\Event;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\{
    ConsoleCommandEvent, ConsoleTerminateEvent
};
use Symfony\Component\Stopwatch\Stopwatch;

class ConsoleEventListener
{
    /** @var LoggerInterface */
    private $logger;

    /** @var Stopwatch */
    private $stopwatch;

    /**
     *
     * @param LoggerInterface $logger
     * @param Stopwatch       $stopwatch
     */
    public function __construct(LoggerInterface $logger, Stopwatch $stopwatch)
    {
        $this->logger    = $logger;
        $this->stopwatch = $stopwatch;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onCommandStart(ConsoleCommandEvent $event): void
    {
        $input   = $event->getInput();
        $command = $event->getCommand();

        $this->stopwatch->start($command->getName());

        $this->logger->info('Start command ' . $command->getName(), [
            'arguments' => $input->getArguments(),
            'options'   => $input->getOptions()
        ]);
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onCommandEnd(ConsoleTerminateEvent $event): void
    {
        $input          = $event->getInput();
        $command        = $event->getCommand();
        $stopwatchEvent = $this->stopwatch->stop($command->getName());

        $this->logger->info('End command ' . $command->getName(), [
            'max_memory_usage' => $this->formatMemory($stopwatchEvent->getMemory()),
            'execution_time'   => $this->formatDuration($stopwatchEvent->getDuration()),
            'arguments'        => $input->getArguments(),
            'options'          => $input->getOptions()
        ]);
    }

    /**
     * @param int $bytes
     *
     * @return string
     */
    private function formatMemory($bytes): string
    {
        return round($bytes / 1000 / 1000, 2) . 'MB';
    }

    /**
     * @param int $microseconds
     *
     * @return string
     */
    private function formatDuration($microseconds): string
    {
        return round($microseconds / 1000, 2) . 's';
    }
}
