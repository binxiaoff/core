<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, ClientsStatus, Companies, CompanyStatus, CompanyStatusHistory, Prelevements, ProjectsStatus, Users, WalletType};

class CompanyManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var ProjectStatusManager */
    private $projectStatusManager;
    /** @var ClientCreationManager */
    private $clientCreationManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ProjectStatusManager   $projectStatusManager
     * @param ClientCreationManager  $clientCreationManager
     * @param TranslatorInterface    $translator
     * @param LoggerInterface        $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProjectStatusManager $projectStatusManager,
        ClientCreationManager $clientCreationManager,
        TranslatorInterface $translator,
        LoggerInterface $logger
    )
    {
        $this->entityManager         = $entityManager;
        $this->projectStatusManager  = $projectStatusManager;
        $this->clientCreationManager = $clientCreationManager;
        $this->translator            = $translator;
        $this->logger                = $logger;
    }

    /**
     * @param Companies $company
     *
     * @return array|CompanyStatus[]
     */
    public function getPossibleStatus(Companies $company)
    {
        $possibleStatus = [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION];

        if (CompanyStatus::STATUS_IN_BONIS === $company->getIdStatus()->getLabel()) {
            $possibleStatus[] = CompanyStatus::STATUS_IN_BONIS;
        }

        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatus')->findBy(['label' => $possibleStatus], ['id' => 'ASC']);
    }

    /**
     * @param string $statusLabel
     *
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
                $companyProjects = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findFundedButNotRepaidProjectsByCompany($company);

                foreach ($companyProjects as $project) {
                    if (ProjectsStatus::STATUS_LOSS !== $project->getStatus()) {
                        $this->projectStatusManager->addProjectStatus($user, ProjectsStatus::STATUS_LOSS, $project);
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
     * @param Users       $user
     * @param null|string $email
     * @param null|string $siren
     * @param null|string $siret
     * @param null|string $companyName
     *
     * @return Companies
     * @throws \Exception
     */
    public function createBorrowerCompany(Users $user, ?string $email = null, ?string $siren = null, ?string $siret = null, ?string $companyName = null): Companies
    {
        $client  = new Clients();
        $company = new Companies();

        $this->entityManager->getConnection()->beginTransaction();
        try {
            if (null !== $email) {
                $duplicates = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findGrantedLoginAccountsByEmail($email);
                if (false === empty($duplicates)) {
                    $email .= '-' . time();
                }
            }

            $client
                ->setEmail($email)
                ->setIdLangue('fr');

            $this->entityManager->persist($client);
            $this->entityManager->flush($client);

            $company
                ->setSiren($siren)
                ->setSiret($siret)
                ->setName($companyName)
                ->setStatusAdresseCorrespondance(1)
                ->setEmailDirigeant($email)
                ->setEmailFacture($email)
                ->setIdClientOwner($client)
                ->setSector(rand(1, 14));

            $this->entityManager->persist($company);
            $this->entityManager->flush($company);

            $this->clientCreationManager->createAccount($client, WalletType::BORROWER, $user->getIdUser(), ClientsStatus::STATUS_VALIDATED);

            $statusInBonis = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatus')->findOneBy(['label' => CompanyStatus::STATUS_IN_BONIS]);
            $this->addCompanyStatus($company, $statusInBonis, $user);
            $this->entityManager->getConnection()->commit();

        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error(
                'Could not create company for SIREN: ' . $siren . ' Error: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);

            throw $exception;
        }

        return $company;
    }

    /**
     * @param null|string $siren
     * @param int         $userId
     *
     * @return Companies
     */
    public function createBorrowerBlankCompany($siren = null, $userId)
    {
        $clientEntity  = new Clients();
        $companyEntity = new Companies();

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $clientEntity->setIdLangue('fr');

            $this->entityManager->persist($clientEntity);
            $this->entityManager->flush($clientEntity);

            $companyEntity
                ->setSiren($siren)
                ->setIdClientOwner($clientEntity)
                ->setStatusAdresseCorrespondance(1);
            $this->entityManager->persist($companyEntity);
            $this->entityManager->flush($companyEntity);

            $this->clientCreationManager->createAccount($clientEntity, WalletType::BORROWER, $userId, ClientsStatus::STATUS_VALIDATED);

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
