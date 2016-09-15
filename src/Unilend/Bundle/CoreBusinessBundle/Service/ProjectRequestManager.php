<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectRequestManager
{
    /** @var EntityManager  */
    private $entityManager;
    /** @var  ProjectManager */
    private $projectManager;

    public function __construct(EntityManager $entityManager, ProjectManager $projectManager)
    {
        $this->entityManager  = $entityManager;
        $this->projectManager = $projectManager;
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

        $client->id_langue = 'fr';
        $client->email     = $client->existEmail($aFormData['email']) ? $aFormData['email'] . '-' . time() : $aFormData['email'];
        $client->create();

        $clientAddress->id_client = $client->id_client;
        $clientAddress->create();

        $company->id_client_owner               = $client->id_client;
        $company->siren                         = $aFormData['siren'];
        $company->status_adresse_correspondance = 1;
        $company->email_dirigeant               = $aFormData['email'];
        $company->create();

        $project->id_company                           = $company->id_company;
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
