<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class ConsoleEventSubscriber implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    private $consoleLogger;

    /** @var Stopwatch */
    private $stopwatch;

    public function __construct(LoggerInterface $consoleLogger, Stopwatch $stopwatch)
    {
        $this->consoleLogger = $consoleLogger;
        $this->stopwatch     = $stopwatch;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND   => 'onCommandStart',
            ConsoleEvents::TERMINATE => 'onCommandEnd',
        ];
    }

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

    private function formatMemory(int $bytes): string
    {
        return \round($bytes / 1000 / 1000, 2) . 'MB';
    }

    /**
     * @param int|float $microseconds
     */
    private function formatDuration($microseconds): string
    {
        return \round($microseconds / 1000, 2) . 's';
    }
}
