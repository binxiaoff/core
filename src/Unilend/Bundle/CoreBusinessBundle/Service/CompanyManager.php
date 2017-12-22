<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatusHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

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
    /** @var WalletCreationManager */
    private $walletCreationManager;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        ProjectManager $projectManager,
        RiskDataMonitoringManager $riskDataMonitoringManager,
        WalletCreationManager $walletCreationManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager            = $entityManager;
        $this->translator               = $translator;
        $this->projectManager           = $projectManager;
        $this->riskDataMonitoringManger = $riskDataMonitoringManager;
        $this->walletCreationManager    = $walletCreationManager;
        $this->logger                   = $logger;
    }

    /**
     * @param Companies $company
     *
     * @return array|CompanyStatus[]
     */
    public function getPossibleStatus(Companies $company)
    {
        $companyStatus = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatus');

        switch ($company->getIdStatus()->getLabel()) {
            case CompanyStatus::STATUS_PRECAUTIONARY_PROCESS:
                $possibleStatus = [CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION];
                break;
            case CompanyStatus::STATUS_RECEIVERSHIP:
                $possibleStatus = [CompanyStatus::STATUS_COMPULSORY_LIQUIDATION];
                break;
            default:
                $possibleStatus = [CompanyStatus::STATUS_IN_BONIS, CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION];
        }

        return $companyStatus->findBy(['label' => $possibleStatus], ['id' => 'ASC']);
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
     * @param CompanyStatus  $newStatus
     * @param Users          $user
     * @param null|\DateTime $changedOn
     * @param null|string    $receiver
     * @param null|string    $siteContent
     * @param null|string    $mailContent
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addCompanyStatus(Companies $company, CompanyStatus $newStatus, Users $user, \DateTime $changedOn = null, $receiver = null, $siteContent = null, $mailContent = null)
    {
        $currentStatus = $company->getIdStatus();

        if ($currentStatus !== $newStatus) {
            $company->setIdStatus($newStatus);
            $this->entityManager->flush($company);

            $companyStatusHistory = new CompanyStatusHistory();
            $companyStatusHistory->setIdCompany($company)
                ->setIdStatus($newStatus)
                ->setIdUser($user)
                ->setChangedOn($changedOn)
                ->setReceiver($receiver)
                ->setMailContent($mailContent)
                ->setSiteContent($siteContent);

            $this->entityManager->persist($companyStatusHistory);
            $this->entityManager->flush($companyStatusHistory);

            if (in_array($newStatus->getLabel(), [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION])) {
                $this->riskDataMonitoringManger->stopMonitoringForSiren($company->getSiren());
                $companyProjects = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findFundedButNotRepaidProjectsByCompany($company);

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

    /**
     * @param null|string $siren
     * @param int         $userId
     *
     * @return Companies
     */
    public function createBorrowerBlankCompany($siren = null, $userId)
    {
        $clientEntity        = new Clients();
        $companyEntity       = new Companies();
        $clientAddressEntity = new ClientsAdresses();

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $clientEntity
                ->setIdLangue('fr')
                ->setStatus(Clients::STATUS_ONLINE);

            $this->entityManager->persist($clientEntity);
            $this->entityManager->flush($clientEntity);

            $clientAddressEntity->setIdClient($clientEntity);
            $this->entityManager->persist($clientAddressEntity);

            $companyEntity
                ->setSiren($siren)
                ->setIdClientOwner($clientEntity)
                ->setStatusAdresseCorrespondance(1);
            $this->entityManager->persist($companyEntity);
            $this->entityManager->flush($companyEntity);

            $this->walletCreationManager->createWallet($clientEntity, WalletType::BORROWER);

            $statusInBonis = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatus')
                ->findOneBy(['label' => CompanyStatus::STATUS_IN_BONIS]);

            $this->addCompanyStatus(
                $companyEntity,
                $statusInBonis,
                $this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($userId)
            );

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            try {
                $this->entityManager->getConnection()->rollBack();
            } catch (\Exception $connectionException) {
                $this->logger->error(
                    'Failed to rollback the transaction. Error: ' . $connectionException->getMessage() . ' - Code: ' . $connectionException->getCode(),
                    ['method' => __METHOD__, 'file' => $connectionException->getFile(), 'line' => $connectionException->getLine()]
                );
            }
            $this->logger->error(
                'Could not create blank company for SIREN: ' . $siren . ' Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        return $companyEntity;
    }
}
