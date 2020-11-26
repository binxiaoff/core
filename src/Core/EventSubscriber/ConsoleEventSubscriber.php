<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\{ConsoleCommandEvent, ConsoleTerminateEvent};
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class ConsoleEventSubscriber implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    private $consoleLogger;

    /** @var Stopwatch */
    private $stopwatch;

    /**
     * @param LoggerInterface $consoleLogger
     * @param Stopwatch       $stopwatch
     */
    public function __construct(LoggerInterface $consoleLogger, Stopwatch $stopwatch)
    {
        $this->consoleLogger = $consoleLogger;
        $this->stopwatch     = $stopwatch;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND   => 'onCommandStart',
            ConsoleEvents::TERMINATE => 'onCommandEnd',
        ];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onCommandStart(ConsoleCommandEvent $event): void
    {
        $input   = $event->getInput();
        $command = $event->getCommand();

        $this->stopwatch->start($command->getName());

        $this->consoleLogger->info('Start command ' . $command->getName(), [
            'arguments' => $input->getArguments(),
            'options'   => $input->getOptions(),
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

        $this->consoleLogger->info('End command ' . $command->getName(), [
            'max_memory_usage' => $this->formatMemory($stopwatchEvent->getMemory()),
            'execution_time'   => $this->formatDuration($stopwatchEvent->getDuration()),
            'arguments'        => $input->getArguments(),
            'options'          => $input->getOptions(),
        ]);
    }

    /**
     * @param int $bytes
     *
     * @return string
     */
    private function formatMemory(int $bytes): string
    {
        return round($bytes / 1000 / 1000, 2) . 'MB';
    }

    /**
     * @param int|float $microseconds
     *
     * @return string
     */
    private function formatDuration($microseconds): string
    {
        return round($microseconds / 1000, 2) . 's';
    }
}
