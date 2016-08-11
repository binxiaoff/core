<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
     * @return array|Response
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
     *
     * @param Request $request
     *
     * @Route("/espace-emprunteur/operations", name="borrower_account_operations")
     *
     * @return Response|StreamedResponse
     */
    public function operationsAction(Request $request)
    {
        if ($request->query->get('action') === 'export') {
            return $this->operationsExportCsvAction($request);
        }

        $client              = $this->getClient();
        $projectsPostFunding = $this->getProjectsPostFunding();
        $projectsIds         = array_column($projectsPostFunding, 'id_project');

        if ($request->isXmlHttpRequest()) {
            $filter = $request->query->get('filter');
            $start  = new \Datetime($filter['start']);
            $end    = new \Datetime($filter['end']);

            if ($filter['op'] !== 'all') {
                $operation = (int)$filter['op'];
            } else {
                $operation = 0;
            }

            if ($filter['project'] !== 'all' && in_array($filter['project'], $projectsIds)) {
                $projectsIds = array($filter['project']);
            }

            $borrowerOperations = $client->getDataForBorrowerOperations($projectsIds, $start, $end, $operation);

            return $this->json(['html_response' => $this->render('borrower_account/operations_ajax.html.twig', ['operations' => $borrowerOperations])->getContent()]);
        }

        $start                      = new \Datetime('NOW - 1 month');
        $end                        = new \Datetime();
        $defaultFilterDate['start'] = $start->format('d/m/Y');
        $defaultFilterDate['end']   = $end->format('d/m/Y');

        /**** Document tab *********/

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

        return $this->render(
            'borrower_account/operations.html.twig',
            [
                'default_filter_date'   => $defaultFilterDate,
                'projects_ids'          => $projectsIds,
                'invoices'              => $clientsInvoices,
                'post_funding_projects' => $projectsPostFunding
            ]
        );
    }

    /**
     * @Route("/espace-emprunteur/profil", name="borrower_account_profile")
     * @Template("borrower_account/profile.html.twig")
     */
    public function profileAction()
    {
        $client  = $this->getClient();
        $company = $this->getCompany();
        /** @var \pays_v2 $country */
        $country      = $this->get('unilend.service.entity_manager')->getRepository('pays_v2');
        $birthCountry = '';
        if ($country->get($client->id_pays_naissance)) {
            $birthCountry = $country->fr;
        }

        $nationality = '';
        if ($country->get($client->id_nationalite)) {
            $nationality = $country->fr;
        }

        $companyCountry = '';
        if ($country->get($company->id_pays)) {
            $companyCountry = $country->fr;
        }

        /** @var \attachment $attachment */
        $attachment = $this->get('unilend.service.entity_manager')->getRepository('attachment');
        $projects   = $company->getProjectsForCompany();
        $projectIds = array_column($projects, 'id_project');
        $id         = $attachment->select('id_type = ' . \attachment_type::CNI_PASSPORTE_DIRIGEANT . ' AND id_owner in (' . implode(',',
                $projectIds) . ') AND type_owner = \'' . \attachment::PROJECT . '\'', 'added ASC', '', 1);
        $idAdded    = null;
        if (isset($id[0]['added'])) {
            $idAdded = $id[0]['added'];
        }
        $idVersoAdded = null;
        $idVerso      = $attachment->select('id_type = ' . \attachment_type::CNI_PASSPORTE_VERSO . ' AND id_owner in (' . implode(',',
                $projectIds) . ') AND type_owner = \'' . \attachment::PROJECT . '\'', 'added ASC', '', 1);
        if (isset($idVerso[0]['added'])) {
            $idVersoAdded = $id[0]['added'];
        }



        return ['client' => $client, 'company' => $company, 'birthCountry' => $birthCountry, 'nationality' => $nationality, 'companyCountry' => $companyCountry, 'idAdded' => $idAdded, 'idVersoAdded' => $idVersoAdded];
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
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    private function operationsExportCsvAction(Request $request)
    {
        $client              = $this->getClient();
        $projectsPostFunding = $this->getProjectsPostFunding();
        $projectsIds         = array_column($projectsPostFunding, 'id_project');

        $filter = $request->query->get('filter');
        $start  = new \Datetime($filter['start']);
        $end    = new \Datetime($filter['end']);

        if ($filter['op'] !== 'all') {
            $operation = (int)$filter['op'];
        } else {
            $operation = 0;
        }

        if ($filter['project'] !== 'all' && in_array($filter['project'], $projectsIds)) {
            $projectsIds = array($filter['project']);
        }

        $borrowerOperations = $client->getDataForBorrowerOperations($projectsIds, $start, $end, $operation);
        $translator         = $this->get('unilend.service.translation_manager');


        $response = new StreamedResponse();
        $response->setCallback(function () use ($borrowerOperations, $translator) {
            $handle = fopen('php://output', 'w+');
            fputs($handle, "\xEF\xBB\xBF"); // add UTF-8 BOM in order to be compatible to Excel
            fputcsv($handle, ['Opération', 'Référence de projet', 'Date de l\'opération', 'Montant de l\'opération', 'Dont TVA'], ';');

            foreach ($borrowerOperations as $operation) {
                $date = (new \DateTime($operation['date']))->format('d/m/Y');
                fputcsv(
                    $handle,
                    [
                        $translator->selectTranslation('borrower-operation', $operation['type']),
                        $operation['id_project'],
                        $date,
                        number_format($operation['montant'], 2, ',', ''),
                        (empty($operation['tva']) === false) ? number_format($operation['tva'], 2, ',', '') : '0'
                    ],
                    ';'
                );
            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export-operations.csv"');

        return $response;
    }

    /**
     *
     * @Route(
     *     "/espace-emprunteur/export/lender-detail/csv/{type}/{projectId}/{repaymentOrder}",
     *     requirements={"projectId" = "\d+"},
     *     defaults={"repaymentOrder" = null},
     *     name="borrower_account_export_lender_details_csv"
     * )
     *
     * @param $type
     * @param $projectId
     * @param $repaymentOrder
     *
     * @return StreamedResponse
     */
    public function _exportCsvWithLenderDetailsAction($type, $projectId, $repaymentOrder)
    {
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $project->get($projectId, 'id_project');

        $translator = $this->get('unilend.service.translation_manager');
        switch ($type) {
            case 'l':
                $aColumnHeaders = array('ID Préteur', 'Nom ou Raison Sociale', 'Prénom', 'Mouvement', 'Montant', 'Date');
                $sType          = $translator->selectTranslation('borrower-operation', 'mouvement-deblocage-des-fonds');
                $aData          = $project->getLoansAndLendersForProject();
                $sFilename      = 'details_prets';
                break;
            case 'e':
                $aColumnHeaders = array(
                    'ID Préteur',
                    'Nom ou Raison Sociale',
                    'Prénom',
                    'Mouvement',
                    'Montant',
                    'Capital',
                    'Intérets',
                    'Date'
                );
                $sType          = $translator->selectTranslation('borrower-operation', 'mouvement-remboursement');
                $aData          = $project->getDuePaymentsAndLenders(null, $repaymentOrder);
                $oDateTime      = \DateTime::createFromFormat('Y-m-d H:i:s', $aData[0]['date']);
                $sDate          = $oDateTime->format('mY');
                $sFilename      = 'details_remboursements_' . $projectId . '_' . $sDate;
                break;
            default:
                break;
        }

        $response = new StreamedResponse();
        $response->setCallback(function () use ($aData, $sType, $aColumnHeaders) {
            $handle = fopen('php://output', 'w+');
            fputs($handle, "\xEF\xBB\xBF"); // add UTF-8 BOM in order to be compatible to Excel
            fputcsv($handle, $aColumnHeaders, ';');

            foreach ($aData as $key => $row) {
                $line = $row;
                if (empty($row['name']) === false) {
                    $line['nom']    = $row['name'];
                    $line['prenom'] = null;
                }
                $line['name'] = $sType;
                $line['date'] = (new \DateTime($row['date']))->format('d/m/Y');

                if (empty($row['amount']) === false) {
                    $line['amount'] = $row['amount'] / 100;
                }

                if (empty($row['montant']) === false) {
                    $line['montant']  = $row['montant'] / 100;
                    $line['capital']  = $row['capital'] / 100;
                    $line['interets'] = $row['interets'] / 100;
                }

                fputcsv($handle, $line, ';');
            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $sFilename . '.csv"');

        return $response;
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
