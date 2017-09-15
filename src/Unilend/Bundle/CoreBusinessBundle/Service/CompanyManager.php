<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatusHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;

class CompanyManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var TranslatorInterface */
    private $translator;

    /** @var ProjectManager $projectManager */
    private $projectManager;

    /** @var  RiskDataMonitoringManager */
    private $riskDataMonitoringManger;

    public function __construct(EntityManager $entityManager, TranslatorInterface $translator, ProjectManager $projectManager, RiskDataMonitoringManager $riskDataMonitoringManager)
    {
        $this->entityManager            = $entityManager;
        $this->translator               = $translator;
        $this->projectManager           = $projectManager;
        $this->riskDataMonitoringManger = $riskDataMonitoringManager;
    }

    /**
     * @param Companies $company
     *
     * @return array|CompanyStatus[]
     */
    public function getPossibleStatus(Companies $company)
    {
        $companyStatus = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatus');

        switch ($company->getIdStatus()) {
            case CompanyStatus::STATUS_PRECAUTIONARY_PROCESS:
                $possibleStatus = [CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION];
                break;
            case CompanyStatus::STATUS_RECEIVERSHIP:
                $possibleStatus = [CompanyStatus::STATUS_COMPULSORY_LIQUIDATION];
                break;
            default:
                $possibleStatus = [CompanyStatus::STATUS_IN_BONIS, CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION];
        }

        return $companyStatus->findCompanyStatusByLabel($possibleStatus);
    }

    /**
     * @param string $statusLabel
     * @return string
     */
    public function getCompanyStatusNameByLabel($statusLabel)
    {
        return $this->translator->trans('company-status_label-' . str_replace('_', '-', $statusLabel));
    }

    /**
     * @param Companies      $company
     * @param CompanyStatus  $companyStatus
     * @param Users          $user
     * @param null|\DateTime $changedOn
     * @param null|string    $receiver
     * @param null|string    $siteContent
     * @param null|string    $mailContent
     */
    public function addCompanyStatus(Companies $company, CompanyStatus $companyStatus, Users $user, \DateTime $changedOn = null, $receiver = null, $siteContent = null, $mailContent = null)
    {
        $currentStatus = $company->getIdStatus();

        $companyStatusHistory = new CompanyStatusHistory();
        $companyStatusHistory->setIdCompany($company)
            ->setIdStatus($companyStatus)
            ->setIdUser($user)
            ->setChangedOn($changedOn)
            ->setReceiver($receiver)
            ->setMailContent($mailContent)
            ->setSiteContent($siteContent);

        $this->entityManager->persist($companyStatusHistory);
        $this->entityManager->flush($companyStatusHistory);

        $company->setIdStatus($companyStatus);
        $this->entityManager->flush($company);

        if (
            $currentStatus->getId() !== $companyStatus->getId()
            && in_array($companyStatus->getLabel(), [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION])
        ) {
            $this->riskDataMonitoringManger->stopMonitoringForSiren($company->getSiren());
            $companyProjects = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findFundedButNotRepaidProjectsByCompany($company->getIdCompany());

            foreach ($companyProjects as $project) {
                if (ProjectsStatus::PROBLEME !== $project->getStatus()) {
                    $this->projectManager->addProjectStatus($user->getIdUser(), ProjectsStatus::PROBLEME, $project);
                }

                $directDebitEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Prelevements');
                /** @var Prelevements[] $upcomingDirectDebits */
                $upcomingDirectDebits = $directDebitEntity->findUpcomingDirectDebitsByProject($project);

                foreach ($upcomingDirectDebits as $directDebit) {
                    $directDebit->setStatus(Prelevements::STATUS_TEMPORARILY_BLOCKED);
                    $this->entityManager->flush($directDebit);
                }
            }
        }
    }
}
