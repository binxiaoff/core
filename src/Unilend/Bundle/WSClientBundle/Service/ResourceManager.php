<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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
     * @return bool|\ws_external_resource
     */
    public function getResource($resourceLabel)
    {
        /** @var \ws_external_resource $wsResources */
        $wsResources = $this->entityManager->getRepository('ws_external_resource');

        if (true === $wsResources->get($resourceLabel, 'label')) {
            return $wsResources;
        }

        return false;
    }
}