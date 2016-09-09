<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

class BidValidator extends Validator
{
    public function isEligible(\bids $bid, \product $product)
    {
        foreach ($this->getAttributeTypeToCheck() as $attributeTypeToCheck) {
            switch ($attributeTypeToCheck) {
                case \product_attribute_type::ELIGIBLE_LENDER_NATIONALITY :
                    $eligibility = $this->isEligibleForNationality($bid, $product);
                    break;
                case \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE :
                    $eligibility = $this->isEligibleForLenderType($bid, $product);
                    break;
                case \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO :
                    $eligibility = $this->isEligibleForMaxTotalAmount($bid, $product);
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

    /**
     * @param \bids    $bid
     * @param \product $product
     *
     * @return bool
     */
    private function isEligibleForNationality(\bids $bid, \product $product)
    {
        $eligibleNationality = $this->getProductAttributesByType($product, \product_attribute_type::ELIGIBLE_LENDER_NATIONALITY);

        if (empty($eligibleNationality)) {
            return true;
        }
        $client = $this->getClient($bid);

        return $client->id_nationalite == 0 || in_array($client->id_nationalite, $eligibleNationality);
    }

    /**
     * @param \bids    $bid
     * @param \product $product
     *
     * @return bool
     */
    private function isEligibleForLenderType(\bids $bid, \product $product)
    {
        $attrVars = $this->getContractAttributesByType($product, \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE);

        if (empty($attrVars)) {
            return true; // No limitation found!
        }

        $client       = $this->getClient($bid);
        $eligibleType = [];
        foreach ($attrVars as $contractAttr) {
            if (empty($contractAttr)) {
                return true; // No limitation found for one of the underlying contract!
            } else {
                $eligibleType = array_merge($eligibleType, $contractAttr);
            }
        }

        return in_array($client->type, $eligibleType);
    }

    private function isEligibleForMaxTotalAmount(\bids $bid, \product $product)
    {
        $totalAmount = $bid->getBidsEncours($bid->id_project, $bid->id_lender_account)['solde'];

        $attrVars    = $this->getContractAttributesByType($product, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);

        if (empty($attrVars)) {
            return true; // No limitation found!
        }

        $maxAmountEligible = 0;
        foreach ($attrVars as $contractAttr) {
            if (empty($contractAttr)) {
                return true; // No limitation found for one of the underlying contract!
            } else {
                $maxAmountEligible = bccomp($contractAttr[0], $maxAmountEligible, 2) === 1 ? $contractAttr[0] : $maxAmountEligible;
            }
        }

        return bccomp($maxAmountEligible, $totalAmount, 2) >= 0;
    }
}
