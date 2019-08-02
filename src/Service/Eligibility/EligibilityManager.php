<?php

declare(strict_types=1);

namespace Unilend\Service\Eligibility;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Unilend\Entity\{Companies, Project};
use Unilend\Service\Eligibility\Validator\CompanyValidator;

class EligibilityManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var CompanyValidator */
    private $companyValidator;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CompanyValidator       $companyValidator
     */
    public function __construct(EntityManagerInterface $entityManager, CompanyValidator $companyValidator)
    {
        $this->entityManager    = $entityManager;
        $this->companyValidator = $companyValidator;
    }

    /**
     * @param string $siren
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isSirenEligible($siren): bool
    {
        return 0 === count($this->checkSirenEligibility($siren));
    }

    /**
     * @param Companies $company
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isCompanyEligible(Companies $company): bool
    {
        return 0 === count($this->checkCompanyEligibility($company));
    }

    /**
     * @param Project $project
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isProjectEligible(Project $project): bool
    {
        return 0 === count($this->checkProjectEligibility($project));
    }

    /**
     * @param Project $project
     *
     * @throws Exception
     *
     * @return array
     */
    private function checkProjectEligibility(Project $project): array
    {
        return $this->companyValidator->validate($project->getBorrowerCompany()->getSiren(), $project->getBorrowerCompany(), $project);
    }

    /**
     * @param string $siren
     *
     * @throws Exception
     *
     * @return array
     */
    private function checkSirenEligibility($siren): array
    {
        return $this->companyValidator->validate($siren);
    }

    /**
     * @param Companies $company
     *
     * @throws Exception
     *
     * @return array
     */
    private function checkCompanyEligibility(Companies $company): array
    {
        return $this->companyValidator->validate($company->getSiren(), $company);
    }
}
