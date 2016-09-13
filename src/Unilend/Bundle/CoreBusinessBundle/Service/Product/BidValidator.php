<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class BidValidator
{
    use Checker\LenderChecker;
    use Checker\BidChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;
    /** @var EntityManager */
    private $entityManager;

    public function __construct(ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManager = $entityManager;
    }

    public function isEligible(\bids $bid, \product $product)
    {
        $client = $this->getClient($bid);
        foreach ($this->getAttributeTypeToCheck() as $attributeTypeToCheck) {
            switch ($attributeTypeToCheck) {
                case \product_attribute_type::ELIGIBLE_LENDER_NATIONALITY :
                    $eligibility = $this->isLenderEligibleForNationality($client, $product, $this->productAttributeManager);
                    break;
                case \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE :
                    $eligibility = $this->isLenderEligibleForType($client, $product, $this->productAttributeManager);
                    break;
                case \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO :
                    $eligibility = $this->isBidEligibleForMaxTotalAmount($bid, $product, $this->productAttributeManager);
                    break;
                default :
                    $eligibility = false;
            }

            if (false === $eligibility) {
                return $eligibility;
            }
        }

        return true;
    }

    private function getAttributeTypeToCheck()
    {
        return [
            \product_attribute_type::ELIGIBLE_LENDER_NATIONALITY,
            \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE,
            \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO,
        ];
    }

    private function getClient(\bids $bid)
    {
        /** @var \lenders_accounts $lender */
        $lender = $this->entityManager->getRepository('lenders_accounts');
        if (false === $lender->get($bid->id_lender_account)) {
            throw new \InvalidArgumentException('The lender account id ' . $bid->id_lender_account . ' does not exist');
        }

        /** @var \clients $client */
        $client = $this->entityManager->getRepository('clients');
        if (false === $client->get($lender->id_client_owner)) {
            throw new \InvalidArgumentException('The client id ' . $lender->id_client_owner . ' does not exist');
        }

        return $client;
    }
}
