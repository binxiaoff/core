<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Unilend\Entity\CompanyModule;
use Unilend\Entity\CompanyModuleLog;

class ModuleLogListener
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param CompanyModule $moduleStatus
     *
     * @throws Exception
     */
    public function logModule(CompanyModule $moduleStatus)
    {
        $this->entityManager->persist(new CompanyModuleLog($moduleStatus));
        $this->entityManager->flush();
    }
}
