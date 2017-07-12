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

        if (false === $this->canStillBid($client, $project, $this->contractManager, $this->entityManager)) {
            $violations[] = UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
        }

        return $violations;
    }
}
