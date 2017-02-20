<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;

class PartnerProductManager extends ProductManager
{
    /**
     * @param bool $includeInactiveProduct
     * @param null $partnerId
     * @return \product[]
     */
    public function getAvailableProducts($includeInactiveProduct = false, $partnerId = null)
    {
        $where = '';
        if (false === empty($partnerId)) {
            $where = 'id_partner = ' . $partnerId;
        }
        /** @var \partner_product $partnerProduct */
        $partnerProduct = $this->entityManager->getRepository('partner_product');

        return $this->filterProductByStatus($partnerProduct->select($where), $includeInactiveProduct);
    }

    /**
     * @param array $productList
     * @param bool  $includeInactiveProduct
     * @return \product[]
     */
    private function filterProductByStatus(array $productList, $includeInactiveProduct = false)
    {
        /** @var \product $product */
        $product           = $this->entityManager->getRepository('product');
        $availableProducts = [];

        foreach ($productList as $oneProduct) {
            $product->get($oneProduct['id_product']);

            if (
                $product->status != \product::STATUS_ARCHIVED
                && ($includeInactiveProduct || $product->status == \product::STATUS_ONLINE)
            ) {
                $availableProduct    = clone $product;
                $availableProducts[] = $availableProduct;
            }
        }

        return $availableProducts;
    }
}