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
        $logEntry = new StaffLog($staff, $this->security->getUser());
        $this->manager->persist($logEntry);
        $this->manager->flush();
    }
}
