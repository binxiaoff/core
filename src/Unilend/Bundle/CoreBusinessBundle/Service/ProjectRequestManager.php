<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
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
    /** @var  ClientManager */
    private $clientManager;

    public function __construct(
        EntityManagerSimulator $entityManager,
        ProjectManager $projectManager,
        SourceManager $sourceManager,
        EntityManager $em,
        ClientManager $clientManager
    ) {
        $this->entityManager  = $entityManager;
        $this->projectManager = $projectManager;
        $this->sourceManager  = $sourceManager;
        $this->em             = $em;
        $this->clientManager  = $clientManager;
    }

    public function getMonthlyRateEstimate()
    {
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');

        return round($projects->getGlobalAverageRateOfFundedProjects(50), 1);
    }

    public function getMonthlyPaymentEstimate($amount, $period, $estimatedRate)
    {
        /** @var \settings $settings */
        $settings = $this->entityManager->getRepository('settings');
        /** @var \PHPExcel_Calculation_Financial $oFinancial */
        $oFinancial = new \PHPExcel_Calculation_Financial();

        /** @var \tax_type $taxType */
        $taxType = $this->entityManager->getRepository('tax_type');
        $taxType->get(\tax_type::TYPE_VAT);
        $fVATRate = $taxType->rate / 100;

        $settings->get('Commission remboursement', 'type');
        $commissionRate = $settings->value;

        $fCommission    = ($oFinancial->PMT($commissionRate / 12, $period, - $amount) - $oFinancial->PMT(0, $period, - $amount)) * (1 + $fVATRate);
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
        /** @var \clients $clientRepository */
        $clientRepository = $this->entityManager->getRepository('clients');
        $email = $clientRepository->existEmail($aFormData['email']) ? $aFormData['email'] . '-' . time() : $aFormData['email'];

        $client = new Clients();
        $client
            ->setEmail($email)
            ->setIdLangue('fr')
            ->setStatus(\clients::STATUS_ONLINE)
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

        $this->clientManager->createClient($client, new ClientsAdresses(), WalletType::BORROWER, $company);

        $project->id_company                           = $company->getIdCompany();
        $project->amount                               = $aFormData['amount'];
        $project->period                               = $aFormData['duration'];
        $project->id_borrowing_motive                  = $aFormData['reason'];
        $project->ca_declara_client                    = 0;
        $project->resultat_exploitation_declara_client = 0;
        $project->fonds_propres_declara_client         = 0;
        $project->status                               = \projects_status::DEMANDE_SIMULATEUR;
        $project->create();

        $this->projectManager->addProjectStatus(\users::USER_ID_FRONT, \projects_status::DEMANDE_SIMULATEUR, $project);

        return $project;
    }
}
