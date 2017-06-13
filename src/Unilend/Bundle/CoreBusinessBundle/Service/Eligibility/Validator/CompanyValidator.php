<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator;

use Doctrine\ORM\EntityManager;

class CompanyValidator
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public function validate()
    {
        $violations = [];

        return $violations;
    }
}
