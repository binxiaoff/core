<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity;

use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Staff;
use Unilend\Entity\StaffLog;

class StaffLogListener
{
    /** @var Security */
    private $security;
    /** @var ObjectManager */
    private $manager;

    /**
     * @param Security      $security
     * @param ObjectManager $manager
     */
    public function __construct(
        Security $security,
        ObjectManager $manager
    ) {
        $this->security = $security;
        $this->manager  = $manager;
    }

    /**
     * @param Staff $staff
     *
     * @throws Exception
     */
    public function logStaff(Staff $staff)
    {
        $logEntry = new StaffLog($staff, $this->security->getUser());
        $this->manager->persist($logEntry);
        $this->manager->flush();
    }
}
