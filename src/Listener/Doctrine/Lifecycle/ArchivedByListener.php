<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\Clients;
use Unilend\Repository\ClientsRepository;

class ArchivedByListener
{
    /** @var Security */
    private $security;

    /** @var ClientsRepository $clientsRepository */
    private $clientsRepository;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        /** @var Clients $user */
        $user = $this->security->getUser();

        if ($user instanceof UserInterface && false === $user instanceof Clients) {
            $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if (method_exists($entity, 'setArchivedBy')) {
            $entity->setArchivedBy($user->getCurrentStaff());
        }
    }
}
