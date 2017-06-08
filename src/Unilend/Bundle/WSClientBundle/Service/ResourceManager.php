<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource;
use Doctrine\ORM\EntityManager;

class ResourceManager
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
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
        $wsResource = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WsExternalResource')
            ->findOneBy(['label' => $resourceLabel]);

        return $wsResource;
    }
}