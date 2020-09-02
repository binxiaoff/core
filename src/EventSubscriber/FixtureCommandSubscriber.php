<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber;

use Doctrine\DBAL\{Connection, DBALException};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\{ConsoleCommandEvent, ConsoleTerminateEvent};
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FixtureCommandSubscriber implements EventSubscriberInterface
{
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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
     *
     * @throws DBALException
     */
    public function onCommandStart(ConsoleCommandEvent $event): void
    {
        if ($this->isFixtureCommand($event->getCommand())) {
            $this->connection->exec('SET FOREIGN_KEY_CHECKS=0;');
        }
    }

    /**
     * @param ConsoleTerminateEvent $event
     *
     * @throws DBALException
     */
    public function onCommandEnd(ConsoleTerminateEvent $event): void
    {
        if ($this->isFixtureCommand($event->getCommand())) {
            $this->connection->exec('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * @param Command|null $command
     *
     * @return bool
     */
    private function isFixtureCommand(?Command $command): bool
    {
        return $command && 'doctrine:fixtures:load' === $command->getName();
    }
}
