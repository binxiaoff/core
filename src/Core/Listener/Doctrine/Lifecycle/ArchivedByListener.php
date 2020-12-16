<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\UserRepository;

class ArchivedByListener
{
    /** @var Security */
    private $security;

    /** @var UserRepository $userRepository */
    private $userRepository;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @throws ORMException
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $em     = $args->getEntityManager();
        $uow    = $em->getUnitOfWork();
        $entity = $args->getEntity();
        /** @var User $user */
        $user = $this->security->getUser();

        if ($user instanceof UserInterface && false === $user instanceof User) {
            $user = $this->userRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if (method_exists($entity, 'setArchivedBy')) {
            $entity->setArchivedBy($user->getCurrentStaff());
            $em->persist($entity);
            $uow->propertyChanged($entity, 'archivedBy', null, $user->getCurrentStaff());
            $uow->scheduleExtraUpdate($entity, ['archivedBy' => [null, $user->getCurrentStaff()]]);
        }
    }
}
