<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Eligibility;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator\CompanyValidator;

class EligibilityManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var CompanyValidator */
    private $companyValidator;

    /**
     * @param EntityManager    $entityManager
     * @param CompanyValidator $companyValidator
     */
    public function __construct(
        EntityManager $entityManager,
        CompanyValidator $companyValidator
    )
    {
        $this->entityManager    = $entityManager;
        $this->companyValidator = $companyValidator;
    }

    /**
     * @param string $siren
     *
     * @return bool
     */
    public function isSirenEligible($siren)
    {
        return 0 === count($this->checkSirenEligibility($siren));
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkSirenEligibility($siren)
    {
        return $this->companyValidator->validate($siren);
    }

    /**
     * @param Companies $company
     *
     * @return bool
     */
    public function isCompanyEligible(Companies $company)
    {
        return 0 === count($this->checkCompanyEligibility($company));
    }

    /**
     * @param Companies $company
     *
     * @return array
     */
    public function checkCompanyEligibility(Companies $company)
    {
        return $this->companyValidator->validate($company->getSiren(), $company);
    }

    /**
     * @param Projects $project
     *
     * @return bool
     */
    public function isProjectEligible(Projects $project)
    {
        return 0 === count($this->checkProjectEligibility($project));
    }

    /**
     * @param Projects $project
     *
     * @return array
     */
    public function checkProjectEligibility(Projects $project)
    {
        return $this->companyValidator->validate($project->getIdCompany()->getSiren(), $project->getIdCompany(), $project);
    }
}
