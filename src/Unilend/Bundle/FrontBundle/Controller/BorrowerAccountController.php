<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\FrontBundle\Form\SimpleProjectType;
use Unilend\Bundle\FrontBundle\Security\User\UserBorrower;

class BorrowerAccountController extends Controller
{

    /**
     * @param Request $request
     *
     * @Route("/espace-emprunteur/projets", name="borrower_account_projects")
     * @Template("borrower_account/projects.html.twig")
     *
     * @return array
     */
    public function projectsAction(Request $request)
    {
        $projectForm = $this->createForm(SimpleProjectType::class);
        $projectForm->handleRequest($request);

        if ($projectForm->isSubmitted() && $projectForm->isValid()) {
            $formData       = $projectForm->getData();
            $projectManager = $this->get('unilend.service.project_manager');
            $fMinAmount     = $projectManager->getMinProjectAmount();
            $fMaxAmount     = $projectManager->getMaxProjectAmount();

            $translator = $this->get('unilend.service.translation_manager');
            $error      = false;
            if (empty($formData['amount']) || $fMinAmount > $formData['amount'] || $fMaxAmount < $formData['amount']) {
                $error = true;
                $this->addFlash('error', $translator->selectTranslation('borrower-demand', 'amount_error'));
            }
            if (empty($formData['duration'])) {
                $error = true;
                $this->addFlash('error', $translator->selectTranslation('borrower-demand', 'duration_error'));
            }
            if (empty($formData['message'])) {
                $error = true;
                $this->addFlash('error', $translator->selectTranslation('borrower-demand', 'message_error'));
            }
            if (false === $error) {
                $company = $this->getCompany();

                $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

                $project->id_company                           = $company->id_company;
                $project->amount                               = str_replace(array(',', ' '), array('.', ''), $formData['amount']);
                $project->ca_declara_client                    = 0;
                $project->resultat_exploitation_declara_client = 0;
                $project->fonds_propres_declara_client         = 0;
                $project->comments                             = $formData['message'];
                $project->period                               = $formData['duration'];
                $project->create();

                $projectManager->addProjectStatus(\users::USER_ID_FRONT, \projects_status::A_TRAITER, $project);

                $this->addFlash('success', $translator->selectTranslation('borrower-demand', 'success'));

                return $this->redirect($this->generateUrl($request->get('_route')) . '#profile-newrequest');
            }
        }

        $projectsPreFunding  = $this->getProjectsPreFunding();
        $projectsFunding     = $this->getProjectsFunding();
        $projectsPostFunding = $this->getProjectsPostFunding();

        return ['pre_funding_projects' => $projectsPreFunding, 'funding_projects' => $projectsFunding, 'post_funding_projects' => $projectsPostFunding, 'project_form' => $projectForm->createView()];
    }

    /**
     * @Route("/espace-emprunteur/operations", name="borrower_account_operations")
     * @Template("borrower_account/operations.html.twig")
     */
    public function operationsAction()
    {
        $projectsPostFunding = $this->getProjectsPostFunding();

        $oDateTimeStart             = new \datetime('NOW - 1 month');
        $oDateTimeEnd               = new \datetime('NOW');
        $defaultFilterDate['start'] = $oDateTimeStart->format('d/m/Y');
        $defaultFilterDate['end']   = $oDateTimeEnd->format('d/m/Y');


        /** @var \projects_pouvoir $projectsPouvoir */
        $projectsPouvoir = $this->get('unilend.service.entity_manager')->getRepository('projects_pouvoir');
        /** @var \clients_mandats $clientsMandat */
        $clientsMandat = $this->get('unilend.service.entity_manager')->getRepository('clients_mandats');

        foreach ($projectsPostFunding as $iKey => $aProject) {
            $projectsPostFunding[$iKey]['pouvoir'] = $projectsPouvoir->select('id_project = ' . $aProject['id_project']);
            $projectsPostFunding[$iKey]['mandat']  = $clientsMandat->select('id_project = ' . $aProject['id_project'], 'updated DESC');

            foreach ($projectsPostFunding[$iKey]['mandat'] as $mandatKey => $mandat) {
                if (\clients_mandats::STATUS_PENDING == $mandat['status']) {
                    $projectsPostFunding[$iKey]['mandat'][$mandatKey]['status-trad'] = 'mandat-en-cours';
                }
            }
        }
        /** @var \factures $oInvoices */
        $oInvoices       = $this->get('unilend.service.entity_manager')->getRepository('factures');
        $company         = $this->getCompany();
        $client          = $this->getClient();
        $clientsInvoices = $oInvoices->select('id_company = ' . $company->id_company, 'date DESC');
        foreach ($clientsInvoices as $iKey => $aInvoice) {
            switch ($aInvoice['type_commission']) {
                case \factures::TYPE_COMMISSION_FINANCEMENT :
                    $clientsInvoices[$iKey]['url'] = '/pdf/facture_EF/' . $client->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
                case \factures::TYPE_COMMISSION_REMBOURSEMENT:
                    $clientsInvoices[$iKey]['url'] = '/pdf/facture_ER/' . $client->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
            }
        }

        return ['invoices' => $clientsInvoices, 'default_filter_date' => $defaultFilterDate, 'post_funding_projects' => $projectsPostFunding];
    }

    /**
     * @Route("/espace-emprunteur/profil", name="borrower_account_profile")
     * @Template("borrower_account/profile.html.twig")
     */
    public function profileAction()
    {

    }

    /**
     * @Route("/espace-emprunteur/contact", name="borrower_account_contact")
     * @Template("borrower_account/contact.html.twig")
     */
    public function contactAction()
    {

    }

    /**
     * @Route("/espace-emprunteur/faq", name="borrower_account_faq")
     * @Template("borrower_account/faq.html.twig")
     */
    public function faqAction()
    {

    }

    /**
     * @Route("/espace-emprunteur/operation/csv", name="borrower_operation_export_csv")
     */
    public function operationExportCsvAction($project, $start, $end, $transaction)
    {
        $client = $this->getClient();
        $aBorrowerOperations = $client->getDataForBorrowerOperations(
            $project,
            $start,
            $end,
            $transaction
        );

        $sFilename      = 'operations';
        $aColumnHeaders = array('Opération', 'Référence de projet', 'Date de l\'opération', 'Montant de l\'opération', 'Dont TVA');

        foreach ($aBorrowerOperations as $aOperation) {
            $aData[] = array(
                $this->lng['espace-emprunteur']['operations-type-' . $aOperation['type']],
                $aOperation['id_project'],
                $this->dates->formatDateMysqltoShortFR($aOperation['date']),
                number_format($aOperation['montant'], 2, ',', ''),
                (empty($aOperation['tva']) === false) ? number_format($aOperation['tva'], 2, ',', '') : '0'
            );
        }

        $this->exportCSV($aColumnHeaders, $aData, $sFilename);
    }

    private function getProjectsPreFunding()
    {
        $statusPreFunding   = array(
            \projects_status::A_FUNDER,
            \projects_status::A_TRAITER,
            \projects_status::COMITE,
            \projects_status::EN_ATTENTE_PIECES,
            \projects_status::PREP_FUNDING,
            \projects_status::REJETE,
            \projects_status::REJET_ANALYSTE,
            \projects_status::REJET_COMITE,
            \projects_status::REVUE_ANALYSTE
        );
        $projectsPreFunding = $this->getCompany()->getProjectsForCompany(null, $statusPreFunding);

        foreach ($projectsPreFunding as $key => $project) {
            switch ($project['project_status']) {
                case \projects_status::EN_ATTENTE_PIECES:
                case \projects_status::A_TRAITER:
                    $projectsPreFunding[$key]['project_status_label'] = 'waiting-for-documents';
                    break;
                case \projects_status::REVUE_ANALYSTE:
                case \projects_status::COMITE:
                    $projectsPreFunding[$key]['project_status_label'] = 'analyzing';
                    break;
                case \projects_status::REJET_ANALYSTE:
                case \projects_status::REJET_COMITE:
                case \projects_status::REJETE:
                    $projectsPreFunding[$key]['project_status_label'] = 'refused';
                    break;
                case \projects_status::PREP_FUNDING:
                case \projects_status::A_FUNDER:
                    $projectsPreFunding[$key]['project_status_label'] = 'waiting-for-being-on-line';
                    break;
            }
            $predictAmountAutoBid                        = $this->get('unilend.service.autobid_settings_manager')->predictAmount($project['risk'], $project['period']);
            $projectsPreFunding[$key]['predict_autobid'] = round(($predictAmountAutoBid / $project['amount']) * 100, 1);
        }
        return $projectsPreFunding;
    }

    private function getProjectsFunding()
    {
        /** @var \bids $bids */
        $bids = $this->get('unilend.service.entity_manager')->getRepository('bids');
        /** @var \projects $projects */
        $projects        = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $projectsFunding = $this->getCompany()->getProjectsForCompany(null, \projects_status::EN_FUNDING);

        foreach ($projectsFunding as $key => $project) {
            $projectsFunding[$key]['average_ir']       = $projects->getAverageInterestRate($project['id_project'], $project['project_status']);
            $iSumBids                                  = $bids->getSoldeBid($project['id_project']);
            $projectsFunding[$key]['funding_progress'] = ((1 - ($project['amount'] - $iSumBids) / $project['amount']) * 100);
            $projectsFunding[$key]['ended']            = \DateTime::createFromFormat('Y-m-d H:i:s', $project['date_retrait_full']);
        }
        return $projectsFunding;
    }

    private function getProjectsPostFunding()
    {
        $aStatusPostFunding = array(
            \projects_status::DEFAUT,
            \projects_status::FUNDE,
            \projects_status::PROBLEME,
            \projects_status::RECOUVREMENT,
            \projects_status::REMBOURSE,
            \projects_status::REMBOURSEMENT,
            \projects_status::REMBOURSEMENT_ANTICIPE
        );

        /** @var \projects $projects */
        $projects            = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $projectsPostFunding = $this->getCompany()->getProjectsForCompany(null, $aStatusPostFunding);
        $repaymentSchedule   = $this->get('unilend.service.entity_manager')->getRepository('echeanciers_emprunteur');

        foreach ($projectsPostFunding as $key => $project) {
            $projectsPostFunding[$key]['average_ir']          = $projects->getAverageInterestRate($project['id_project'], $project['project_status']);
            $projectsPostFunding[$key]['outstanding_capital'] = $this->calculateOutstandingCapital($project['id_project']);
            $aNextRepayment                                   = $repaymentSchedule->select(
                'status_emprunteur = 0 AND id_project = ' . $project['id_project'],
                'date_echeance_emprunteur ASC',
                '',
                1
            );
            $aNextRepayment                                   = array_shift($aNextRepayment);
            $projectsPostFunding[$key]['monthly_payment']     = ($aNextRepayment['montant'] + $aNextRepayment['commission'] + $aNextRepayment['tva']) / 100;
            $projectsPostFunding[$key]['next_maturity_date']  = \DateTime::createFromFormat('Y-m-d H:i:s', $aNextRepayment['date_echeance_emprunteur']);
            $projects->get($project['id_project']);
            $projectsPostFunding[$key]['ended'] = $this->get('unilend.service.project_manager')->getProjectEndDate($projects);
        }

        usort($projectsPostFunding, function ($aFirstArray, $aSecondArray) {
            return $aFirstArray['date_retrait'] < $aSecondArray['date_retrait'];
        });

        return $projectsPostFunding;
    }

    private function getClient()
    {
        /** @var UserBorrower $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($clientId);

        return $client;
    }

    private function getCompany()
    {
        /** @var UserBorrower $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();
        /** @var \companies $company */
        $company = $this->get('unilend.service.entity_manager')->getRepository('companies');
        $company->get($clientId, 'id_client_owner');

        return $company;
    }

    private function calculateOutstandingCapital($projectId)
    {
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->get('unilend.service.entity_manager')->getRepository('echeanciers');

        $aPayment      = $repaymentSchedule->getLastOrder($projectId);
        $iPaymentOrder = (isset($aPayment)) ? $aPayment['ordre'] + 1 : 1;

        return $repaymentSchedule->reste_a_payer_ra($projectId, $iPaymentOrder);
    }
}
