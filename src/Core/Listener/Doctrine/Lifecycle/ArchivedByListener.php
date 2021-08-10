<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\ORMException;
use KLS\Core\Entity\File;
use KLS\Core\Entity\User;
use KLS\Core\Repository\UserRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class ArchivedByListener
{
    private Security $security;
    private UserRepository $userRepository;

    public function __construct(Security $security, UserRepository $userRepository)
    {
        $this->security       = $security;
        $this->userRepository = $userRepository;
    }

    /**
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

        // Must bypass empty staff for borrower and file
        if ($entity instanceof File && null === $user->getCurrentStaff()) {
            return;
        }

        if (\method_exists($entity, 'setArchivedBy')) {
            $entity->setArchivedBy($user->getCurrentStaff());
            $em->persist($entity);
            $uow->propertyChanged($entity, 'archivedBy', null, $user->getCurrentStaff());
            $uow->scheduleExtraUpdate($entity, ['archivedBy' => [null, $user->getCurrentStaff()]]);
        }
    }
}
