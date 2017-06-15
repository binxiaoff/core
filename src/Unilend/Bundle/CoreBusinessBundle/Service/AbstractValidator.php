<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityAssessment;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

abstract class AbstractValidator
{
    /** @var EntityManager */
    protected $entityManager;

    const CHECK_RULE_METHODS = [
        'TC-RISK-001'      => 'checkSiren',
        'TC-RISK-002'      => 'checkActiveCompany',
        'TC-RISK-003'      => 'checkCollectiveProceeding',
        'TC-RISK-005'      => 'checkPaymentIncidents',
        'TC-RISK-006'      => 'checkAltaresScore',
        'TC-RISK-007'      => 'checkCapitalStock',
        'TC-RISK-008'      => 'checkGrossOperatingSurplus',
        'TC-RISK-009'      => 'checkEliminationXerfiScore',
        'TC-RISK-010'      => 'checkAltaresScoreVsXerfiScore',
        'TC-RISK-011'      => 'checkEulerHermesTrafficLight',
        'TC-RISK-013'      => 'checkInfolegaleScore',
        'TC-RISK-015'      => 'checkEulerHermesGrade',
        'BLEND-RISK-001'   => 'TBD',
        'BLEND-RISK-002'   => 'TBD',
        'BLEND-RISK-003'   => 'TBD',
        'BLEND-RISK-004'   => 'TBD',
        'BLEND-RISK-005'   => 'TBD',
        'BLEND-RISK-006'   => 'TBD',
        'BLEND-RISK-007'   => 'TBD',
        'BLEND-RISK-008'   => 'TBD',
        'BLEND-RISK-009'   => 'TBD',
        'BLEND-RISK-010'   => 'TBD',
        'BLEND-RISK-011'   => 'TBD',
        'BLEND-RISK-012'   => 'TBD',
        'BLEND-RISK-013'   => 'TBD',
        'BLEND-RISK-014'   => 'TBD',
        'BLEND-RISK-015'   => 'TBD',
        'BLEND-RISK-016'   => 'TBD',
        'UNILEND-RISK-001' => 'TBD',
        'UNILEND-RISK-002' => 'TBD',
    ];

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    abstract public function validate();

    protected function checkRule($ruleName, ...$arguments)
    {
        $method = new \ReflectionMethod($this, self::CHECK_RULE_METHODS[$ruleName]);
        $method->setAccessible(true);
        $result = $method->invoke($this, ...$arguments);

        foreach ($arguments as $argument) {
            if ($argument instanceof Projects) {
                $ruleRepository          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRule');
                $ruleSetRepository       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSet');
                $ruleSetMemberRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSetMember');
                $ruleSet                 = $ruleSetRepository->findOneBy(['status' => ProjectEligibilityRuleSet::STATUS_ACTIVE]);

                $rule          = $ruleRepository->findOneBy(['label' => $ruleName]);
                $ruleSetMember = $ruleSetMemberRepository->findOneBy([
                    'idRuleSet' => $ruleSet,
                    'idRule'    => $rule
                ]);

                $assessment = new ProjectEligibilityAssessment();
                $assessment->setIdProject($argument);
                $assessment->setIdRuleSetMember($ruleSetMember);
                $assessment->setStatus(empty($result));

                $this->entityManager->persist($assessment);
                $this->entityManager->flush($assessment);
            }
            break;
        }

        return $result;
    }
}
