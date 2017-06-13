<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityAssessment;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSetMember;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\WSClientBundle\Service\AltaresManager;
use Unilend\Bundle\WSClientBundle\Service\CodinfManager;
use Unilend\Bundle\WSClientBundle\Service\EulerHermesManager;
use Unilend\Bundle\WSClientBundle\Service\InfogreffeManager;
use Unilend\Bundle\WSClientBundle\Service\InfolegaleManager;

class ProjectValidator
{
    const RULE_UNKNOWN_SIREN = 'unknown_siren';

    /** @var EntityManager */
    private $entityManager;
    /** @var AltaresManager */
    private $altaresManager;
    /** @var EulerHermesManager */
    private $eulerHermesManager;
    /** @var CodinfManager */
    private $codinfManager;
    /** @var InfolegaleManager */
    private $infolegaleManager;
    /** @var InfogreffeManager */
    private $infogreffeManager;

    /**
     * @param EntityManager      $entityManager
     * @param AltaresManager     $altaresManager
     * @param EulerHermesManager $eulerHermesManager
     * @param CodinfManager      $codinfManager
     * @param InfolegaleManager  $infolegaleManager
     * @param InfogreffeManager  $infogreffeManager
     */
    public function __construct(
        EntityManager $entityManager,
        AltaresManager $altaresManager,
        EulerHermesManager $eulerHermesManager,
        CodinfManager $codinfManager,
        InfolegaleManager $infolegaleManager,
        InfogreffeManager $infogreffeManager
    )
    {
        $this->entityManager      = $entityManager;
        $this->altaresManager     = $altaresManager;
        $this->eulerHermesManager = $eulerHermesManager;
        $this->codinfManager      = $codinfManager;
        $this->infolegaleManager  = $infolegaleManager;
        $this->infogreffeManager  = $infogreffeManager;
    }

    /**
     * @param Projects $project
     *
     * @return array
     */
    public function validate(Projects $project)
    {
        $ruleRepository          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRule');
        $ruleSetRepository       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSet');
        $ruleSetMemberRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSetMember');
        $ruleSet                 = $ruleSetRepository->findOneBy(['status' => ProjectEligibilityRuleSet::STATUS_ACTIVE]);

        $rules = [
            'TC-RISK-001' => 'checkTC1'
        ];

        foreach ($rules as $reference => $checkMethod) {
            $rule          = $ruleRepository->findOneBy(['label' => $reference]);
            $ruleSetMember = $ruleSetMemberRepository->findOneBy(['idRuleSet' => $ruleSet, 'idRule' => $rule]);
            $result        = $this->checkRule($project, $ruleSetMember, $checkMethod);

            if (false === empty($result)) {
                return $result;
            }
        }

        return [];
    }

    /**
     * @param Projects $project
     *
     * @return array
     */
    private function checkTC1(Projects $project)
    {
        return [self::RULE_UNKNOWN_SIREN];
    }

    /**
     * @param Projects                        $project
     * @param ProjectEligibilityRuleSetMember $ruleSetMember
     * @param string                          $methodName
     *
     * @return mixed
     */
    private function checkRule(Projects $project, ProjectEligibilityRuleSetMember $ruleSetMember, $methodName)
    {
        $object = new \ReflectionObject($this);
        $method = $object->getMethod($methodName);
        $result = $method->invoke($this, $project);

        $assessment = new ProjectEligibilityAssessment();
        $assessment->setIdProject($project);
        $assessment->setIdRuleSetMember($ruleSetMember);
        $assessment->setStatus(empty($result));

        $this->entityManager->persist($assessment);
        $this->entityManager->flush($assessment);

        return $result;
    }
}
