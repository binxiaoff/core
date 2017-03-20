<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\SourceManager;

class ProjectRequestManager
{
    /** @var EntityManagerSimulator  */
    private $entityManager;
    /** @var  ProjectManager */
    private $projectManager;
    /** @var SourceManager  */
    private $sourceManager;
    /** @var  EntityManager */
    private $em;
    /** @var  WalletCreationManager */
    private $walletCreationManager;
    /** @var  LoggerInterface */
    private $logger;
    /** @var PartnerManager */
    private $partnerManager;

    /**
     * ProjectRequestManager constructor.
     * @param EntityManagerSimulator $entityManager
     * @param ProjectManager $projectManager
     * @param SourceManager $sourceManager
     * @param EntityManager $em
     * @param WalletCreationManager $walletCreationManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManagerSimulator $entityManager,
        ProjectManager $projectManager,
        SourceManager $sourceManager,
        EntityManager $em,
        WalletCreationManager $walletCreationManager,
        LoggerInterface $logger,
        PartnerManager $partnerManager
    ) {
        $this->entityManager         = $entityManager;
        $this->projectManager        = $projectManager;
        $this->sourceManager         = $sourceManager;
        $this->em                    = $em;
        $this->walletCreationManager = $walletCreationManager;
        $this->logger                = $logger;
        $this->partnerManager        = $partnerManager;
    }

    public function getMonthlyRateEstimate()
    {
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');

        return round($projects->getGlobalAverageRateOfFundedProjects(50), 1);
    }

    public function getMonthlyPaymentEstimate($amount, $period, $estimatedRate)
    {
        /** @var \PHPExcel_Calculation_Financial $oFinancial */
        $oFinancial = new \PHPExcel_Calculation_Financial();

        /** @var \tax_type $taxType */
        $taxType = $this->entityManager->getRepository('tax_type');
        $taxType->get(\tax_type::TYPE_VAT);
        $fVATRate = $taxType->rate / 100;

        $fCommission    = ($oFinancial->PMT(round(bcdiv(\projects::DEFAULT_COMMISSION_RATE_REPAYMENT, 100, 4), 2) / 12, $period, - $amount) - $oFinancial->PMT(0, $period, - $amount)) * (1 + $fVATRate);
        $monthlyPayment = round($oFinancial->PMT($estimatedRate / 100 / 12, $period, - $amount) + $fCommission);

        return $monthlyPayment;
    }

    /**
     * @param $aFormData
     * @return \projects
     */
    public function saveSimulatorRequest($aFormData)
    {
        /** @var \projects $project */
        $project = $this->entityManager->getRepository('projects');

        if (empty($aFormData['email']) || false === filter_var($aFormData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
        if (empty($aFormData['siren']) || false === preg_match('/^([0-9]{9}|[0-9]{14})$/', $aFormData['siren'])) {
            throw new \InvalidArgumentException('Invalid SIREN');
        }
        if (empty($aFormData['amount']) || false === filter_var($aFormData['amount'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid amount');
        }
        if (empty($aFormData['duration']) || false === filter_var($aFormData['duration'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid duration');
        }
        if (empty($aFormData['reason']) || false === filter_var($aFormData['reason'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid reason');
        }

        $email = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->existEmail($aFormData['email']) ? $aFormData['email'] . '-' . time() : $aFormData['email'];

        $client = new Clients();
        $client
            ->setEmail($email)
            ->setIdLangue('fr')
            ->setStatus(Clients::STATUS_ONLINE)
            ->setSource($this->sourceManager->getSource(SourceManager::SOURCE1))
            ->setSource2($this->sourceManager->getSource(SourceManager::SOURCE2))
            ->setSource3($this->sourceManager->getSource(SourceManager::SOURCE3))
            ->setSlugOrigine($this->sourceManager->getSource(SourceManager::ENTRY_SLUG));

        $aFormData['siren'] = str_replace(' ', '', $aFormData['siren']);
        $siren              = substr($aFormData['siren'], 0, 9);
        $siret              = strlen($aFormData['siren']) === 14 ? $aFormData['siren'] : '';

        $company = new Companies();
        $company->setSiren($siren)
            ->setSiret($siret)
            ->setStatusAdresseCorrespondance(1)
            ->setEmailDirigeant($email)
            ->setEmailFacture($email);

        $this->em->beginTransaction();
        try {
            $this->em->persist($client);
            $this->em->flush($client);
            $clientAddress = new ClientsAdresses();
            $clientAddress->setIdClient($client->getIdClient());
            $this->em->persist($clientAddress);
            $company->setIdClientOwner($client->getIdClient());
            $this->em->persist($company);
            $this->em->flush();
            $this->walletCreationManager->createWallet($client, WalletType::BORROWER);
            $this->em->commit();
        } catch (Exception $exception) {
            $this->em->getConnection()->rollBack();
            $this->logger->error('An error occurred while creating client ', [['class' => __CLASS__, 'function' => __FUNCTION__]]);
        }

        $project->id_company                           = $company->getIdCompany();
        $project->amount                               = $aFormData['amount'];
        $project->period                               = $aFormData['duration'];
        $project->id_borrowing_motive                  = $aFormData['reason'];
        $project->ca_declara_client                    = 0;
        $project->resultat_exploitation_declara_client = 0;
        $project->fonds_propres_declara_client         = 0;
        $project->status                               = \projects_status::DEMANDE_SIMULATEUR;
        $project->id_partner                           = $this->partnerManager->getDefaultPartner()->id;
        $project->commission_rate_funds                = \projects::DEFAULT_COMMISSION_RATE_FUNDS;
        $project->commission_rate_repayment            = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT;
        $project->create();

        $this->projectManager->addProjectStatus(Users::USER_ID_FRONT, \projects_status::DEMANDE_SIMULATEUR, $project);

        return $project;
    }
}
