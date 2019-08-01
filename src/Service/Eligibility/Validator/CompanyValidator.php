<?php

declare(strict_types=1);

namespace Unilend\Service\Eligibility\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Entity\{Companies, Project, ProjectEligibilityAssessment, ProjectEligibilityRule, ProjectEligibilityRuleSet, ProjectRejectionReason};

class CompanyValidator
{
    public const CHECK_RULE_METHODS = [
        'TC-RISK-001' => 'checkSiren',
    ];

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @param string         $siren
     * @param Companies|null $company
     * @param Project|null   $project
     *
     * @throws \Exception
     *
     * @return array
     */
    public function validate($siren, ?Companies $company = null, ?Project $project = null)
    {
        $sirenCheck = $this->checkRule('TC-RISK-001', $siren, $project);
        if (false === empty($sirenCheck)) {
            return $sirenCheck;
        }

        return [];
    }

    /**
     * @param string       $ruleName
     * @param string       $siren
     * @param Project|null $project
     *
     * @throws \Exception
     *
     * @return array
     */
    public function checkRule($ruleName, $siren, Project $project = null)
    {
        $method = new \ReflectionMethod($this, self::CHECK_RULE_METHODS[$ruleName]);
        $method->setAccessible(true);
        $result = $method->invoke($this, $siren);

        if ($project instanceof Project) {
            $rule    = $this->entityManager->getRepository(ProjectEligibilityRule::class)->findOneBy(['label' => $ruleName]);
            $ruleSet = $this->entityManager->getRepository(ProjectEligibilityRuleSet::class)->findOneBy(['status' => ProjectEligibilityRuleSet::STATUS_ACTIVE]);
            $this->logCheck($project, $rule, $ruleSet, $result);
        }

        return $result;
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkSiren($siren)
    {
        try {
            if (Companies::INVALID_SIREN_EMPTY === $siren) {
                return [ProjectRejectionReason::UNKNOWN_SIREN];
            }
        } catch (\Exception $e) {
        }

        return [];
    }

    /**
     * @param Project                   $project
     * @param ProjectEligibilityRule    $rule
     * @param ProjectEligibilityRuleSet $ruleSet
     * @param mixed                     $result
     */
    private function logCheck(Project $project, ProjectEligibilityRule $rule, ProjectEligibilityRuleSet $ruleSet, $result)
    {
        $assessment = $this->entityManager->getRepository(ProjectEligibilityAssessment::class)->findOneBy([
            'idProject' => $project,
            'idRule'    => $rule,
            'idRuleSet' => $ruleSet,
        ], ['added' => 'DESC']);

        $checkStatus = empty($result);
        if (null === $assessment || $checkStatus !== $assessment->getStatus()) {
            $assessment = new ProjectEligibilityAssessment();

            $assessment->setIdProject($project)
                ->setIdRule($rule)
                ->setIdRuleSet($ruleSet)
                ->setStatus($checkStatus)
            ;

            $this->entityManager->persist($assessment);
            $this->entityManager->flush($assessment);
        }
    }
}
