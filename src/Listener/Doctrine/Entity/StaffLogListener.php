<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Staff, StaffLog};

class StaffLogListener
{
    /** @var Security */
    private $security;
    /** @var EntityManagerInterface */
    private $manager;

    /**
     * @param Security               $security
     * @param EntityManagerInterface $manager
     */
    public function __construct(Security $security, EntityManagerInterface $manager)
    {
        $this->security = $security;
        $this->manager  = $manager;
    }

    /**
     * @param Staff $staff
     *
     * @throws Exception
     */
    public function logStaff(Staff $staff): void
    {
        if ($staff->isArchived()) {
            return;
        }

        $user = $this->security->getUser();

        if (null === $user) {
            return; // TODO : Voir ce qu'il faut faire dans ce cas
        }

        $addedBy  = $user->getCurrentStaff();
        $logEntry = new StaffLog($staff, $addedBy);
        $this->manager->persist($logEntry);
        $this->manager->flush();
    }
}
