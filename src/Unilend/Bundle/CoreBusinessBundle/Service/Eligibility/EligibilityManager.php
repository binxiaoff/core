<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Eligibility;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator\CompanyDirectorValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator\CompanyValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator\ProjectValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;

class EligibilityManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var ProjectManager */
    private $projectManager;
    /** @var CompanyValidator */
    private $companyValidator;
    /** @var CompanyDirectorValidator */
    private $companyDirectorValidator;
    /** @var ProjectValidator */
    private $projectValidator;

    /**
     * @param EntityManager            $entityManager
     * @param ProjectManager           $projectManager
     * @param CompanyValidator         $companyValidator
     * @param CompanyDirectorValidator $companyDirectorValidator
     * @param ProjectValidator         $projectValidator
     */
    public function __construct(
        EntityManager $entityManager,
        ProjectManager $projectManager,
        CompanyValidator $companyValidator,
        CompanyDirectorValidator $companyDirectorValidator,
        ProjectValidator $projectValidator
    )
    {
        $this->entityManager            = $entityManager;
        $this->projectManager           = $projectManager;
        $this->companyValidator         = $companyValidator;
        $this->companyDirectorValidator = $companyDirectorValidator;
        $this->projectValidator         = $projectValidator;
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
        return $this->projectValidator->validate($project);
    }
}
