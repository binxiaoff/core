<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Unilend\Entity\WsExternalResource;
use Doctrine\ORM\EntityManagerInterface;

class ResourceManager
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
     * @param string $resourceLabel
     *
     * @return null|WsExternalResource
     */
    public function getResource($resourceLabel)
    {
        $wsResource = $this->entityManager->getRepository(WsExternalResource::class)
            ->findOneBy(['label' => $resourceLabel]);

        return $wsResource;
    }
}
