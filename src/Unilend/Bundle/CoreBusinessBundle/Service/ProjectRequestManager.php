<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Service\SourceManager;

class ProjectRequestManager
{
    /** @var EntityManager  */
    private $entityManager;
    /** @var  ProjectManager */
    private $projectManager;
    private $sourceManager;

    public function __construct(EntityManager $entityManager, ProjectManager $projectManager, SourceManager $sourceManager)
    {
        $this->entityManager  = $entityManager;
        $this->projectManager = $projectManager;
        $this->sourceManager  = $sourceManager;
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

        $fCommission    = ($oFinancial->PMT(bcdiv(\projects::DEFAULT_COMMISSION_RATE_REPAYMENT, 100, 2) / 12, $period, - $amount) - $oFinancial->PMT(0, $period, - $amount)) * (1 + $fVATRate);
        $monthlyPayment = round($oFinancial->PMT($estimatedRate / 100 / 12, $period, - $amount) + $fCommission);

        return $monthlyPayment;
    }

    public function saveSimulatorRequest($aFormData)
    {
        /** @var \projects $project */
        $project = $this->entityManager->getRepository('projects');
        /** @var \clients $client */
        $client = $this->entityManager->getRepository('clients');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->entityManager->getRepository('clients_adresses');
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');

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

        $client->id_langue    = 'fr';
        $client->email        = $client->existEmail($aFormData['email']) ? $aFormData['email'] . '-' . time() : $aFormData['email'];
        $client->source       = $this->sourceManager->getSource(SourceManager::SOURCE1);
        $client->source2      = $this->sourceManager->getSource(SourceManager::SOURCE2);
        $client->source3      = $this->sourceManager->getSource(SourceManager::SOURCE3);
        $client->slug_origine = $this->sourceManager->getSource(SourceManager::ENTRY_SLUG);
        $client->status       = \clients::STATUS_ONLINE;
        $client->create();

        $clientAddress->id_client = $client->id_client;
        $clientAddress->create();

        $aFormData['siren'] = str_replace(' ', '', $aFormData['siren']);

        $company->id_client_owner               = $client->id_client;
        $company->siren                         = substr($aFormData['siren'], 0, 9);
        $company->siret                         = strlen($aFormData['siren']) === 14 ? $aFormData['siren'] : '';
        $company->status_adresse_correspondance = 1;
        $company->email_dirigeant               = $aFormData['email'];
        $company->create();

        /** @var \partner $partner */
        $partner = $this->entityManager->getRepository('partner');

        $project->id_company                           = $company->id_company;
        $project->amount                               = $aFormData['amount'];
        $project->period                               = $aFormData['duration'];
        $project->id_borrowing_motive                  = $aFormData['reason'];
        $project->ca_declara_client                    = 0;
        $project->resultat_exploitation_declara_client = 0;
        $project->fonds_propres_declara_client         = 0;
        $project->status                               = \projects_status::DEMANDE_SIMULATEUR;
        $project->id_partner                           = $project->getPartnerId($partner);
        $project->commission_rate_funds                = \projects::DEFAULT_COMMISSION_RATE_FUNDS;
        $project->commission_rate_repayment            = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT;
        $project->create();

        $this->projectManager->addProjectStatus(\users::USER_ID_FRONT, \projects_status::DEMANDE_SIMULATEUR, $project);

        return $project;
    }
}
