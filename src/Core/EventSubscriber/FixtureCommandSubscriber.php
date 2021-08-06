<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FixtureCommandSubscriber implements EventSubscriberInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        // Attention: the connection injected here is the "default" connection. If someone uses "--em" to specify the entity manager for the command fixtures:load,
        // the SET FOREIGN_KEY_CHECKS won't work. In this case, we need to switch to ManagerRegistry to get the right connection.
        // We don't change it at the moment, as we have decided to use only the "default" entity manager (which uses the "default" connection) to do the fixtures.
        $this->connection = $connection;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND   => 'onCommandStart',
            ConsoleEvents::TERMINATE => 'onCommandEnd',
        ];
    }

    /**
     * @throws Exception
     */
    public function onCommandStart(ConsoleCommandEvent $event): void
    {
        if ($this->isFixtureCommand($event->getCommand())) {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
        }
    }

    /**
     * @throws Exception
     */
    public function onCommandEnd(ConsoleTerminateEvent $event): void
    {
        if ($this->isFixtureCommand($event->getCommand())) {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function isFixtureCommand(?Command $command): bool
    {
        return $command && 'doctrine:fixtures:load' === $command->getName();
    }
}
