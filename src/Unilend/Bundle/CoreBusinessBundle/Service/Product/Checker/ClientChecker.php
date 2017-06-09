<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;

trait ClientChecker
{
    /**
     * @param Clients|null            $client
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isEligibleForClientId(Clients $client = null, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $attributes = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_LENDER_ID);

        if (empty($attributes)) {
            return true; // No limitation found
        }

        return $client !== null && in_array($client->getIdClient(), $attributes);
    }

}
