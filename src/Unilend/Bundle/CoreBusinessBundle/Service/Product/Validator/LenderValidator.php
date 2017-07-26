<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator;

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType;

class LenderValidator extends ClientValidator
{

    public function validate(Clients $client = null, Projects $project)
    {
        $violations = parent::validate($client, $project);
        $product    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->find($project->getIdProduct());

        if (false === $this->canStillBid($client, $project, $this->contractManager, $this->entityManager)) {
            $violations[] = UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
        }

        $hasEligibleContract = false;
        $violationsContract  = [];
        foreach ($product->getIdContract() as $contract) {
            $contractCheckResult = $this->contractManager->checkClientEligibility($client, $contract);
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
