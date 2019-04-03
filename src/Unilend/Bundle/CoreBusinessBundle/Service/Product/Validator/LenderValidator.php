<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator;

use Unilend\Entity\{Clients, Product, Projects, UnderlyingContractAttributeType};

class LenderValidator extends ClientValidator
{
    /**
     * @param Clients|null $client
     * @param Projects     $project
     *
     * @return array
     */
    public function validate(?Clients $client, Projects $project): array
    {
        $violations = parent::validate($client, $project);
        $product    = $this->entityManager->getRepository(Product::class)->find($project->getIdProduct());

        if (false === $this->canStillBid($client, $project, $this->contractManager, $this->entityManager)) {
            $violations[] = UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
        }

        $hasEligibleContract = false;
        $violationsContract  = [];
        foreach ($product->getProductContract() as $productContract) {
            $contractCheckResult = $this->contractManager->checkClientEligibility($client, $productContract->getIdContract());
            if (0 < count($contractCheckResult)) {
                $violationsContract = array_merge($violationsContract, $contractCheckResult);
            } else {
                $hasEligibleContract = true;
            }
        }

        if (false === $hasEligibleContract) {
            $violations = array_merge($violations, $violationsContract);
        }

        return $violations;
    }
}
