<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Entity\Project;
use Unilend\Message\Project\ProjectStatusUpdated;

class ProjectListener
{
    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @param MessageBusInterface $messageBus
     */
    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em  = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Project) {
                $changeSet = $uow->getEntityChangeSet($entity);

                if (isset($changeSet['currentStatus'])) {
                    $this->messageBus->dispatch(new ProjectStatusUpdated(
                        $entity,
                        $changeSet['currentStatus'][0],
                        $changeSet['currentStatus'][1]
                    ));
                }
            }
        }

        $uow->computeChangeSets();
    }
}
