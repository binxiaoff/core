<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Unilend\Entity\Module;
use Unilend\Entity\ModuleLog;

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
     * @param Module $moduleStatus
     *
     * @throws Exception
     */
    public function logModule(Module $moduleStatus)
    {
        $this->entityManager->persist(new ModuleLog($moduleStatus));
        $this->entityManager->flush();
    }
}
