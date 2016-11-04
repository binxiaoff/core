<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\FrontBundle\Form\BorrowerContactType;
use Unilend\Bundle\FrontBundle\Form\SimpleProjectType;
use Unilend\Bundle\FrontBundle\Security\User\UserBorrower;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Unilend\core\Loader;

class BorrowerAccountController extends Controller
{
    /**
     * @Route("/espace-emprunteur/projets", name="borrower_account_projects")
     * @Template("borrower_account/projects.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function projectsAction(Request $request)
    {
        $projectsPreFunding  = $this->getProjectsPreFunding();
        $projectsFunding     = $this->getProjectsFunding();
        $projectsPostFunding = $this->getProjectsPostFunding();

        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');

        return [
            'pre_funding_projects'  => $projectsPreFunding,
            'funding_projects'      => $projectsFunding,
            'post_funding_projects' => $projectsPostFunding,
            'closing_projects'      => $request->getSession()->get('closingProjects'),
            'faq_url'               => $settings->value
        ];
    }

    /**
     * @Route("/espace-emprunteur/cloture-projet", name="borrower_account_close_project")
     * @Method("POST")
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function closeFundingProjectAction(Request $request)
    {
        if ($request->request->get('project')) {
            /** @var \projects $project */
            $project   = $this->get('unilend.service.entity_manager')->getRepository('projects');
            $projectId = $request->request->get('project');

            if (
                $projectId == (int) $projectId
                && $project->get($projectId)
                && $project->id_company == $this->getCompany()->id_company
            ) {
                $session = $request->getSession();

                $closingProjects = $session->get('closingProjects', []);
                $closingProjects[$project->id_project] = true;

                $session->set('closingProjects', $closingProjects);

                $closingDate = new \DateTime();
                $closingDate->modify('+5 minutes');

                $project->date_retrait_full = $closingDate->format('Y-m-d H:i:s');
                $project->date_retrait      = $closingDate->format('Y-m-d');
                $project->update();
            }
        }

        return new RedirectResponse($this->generateUrl('borrower_account_projects'));
    }

    /**
     * @Route("/espace-emprunteur/nouvelle-demande", name="borrower_account_new_demand")
     * @Template("borrower_account/new_demand.html.twig")
     *
     * @param Request $request
     * @return array|Response
     */
    public function newDemandAction(Request $request)
    {
        $projectForm = $this->createForm(SimpleProjectType::class);
        $projectForm->handleRequest($request);

        if ($projectForm->isSubmitted() && $projectForm->isValid()) {
            $formData       = $projectForm->getData();
            $projectManager = $this->get('unilend.service.project_manager');
            $fMinAmount     = $projectManager->getMinProjectAmount();
            $fMaxAmount     = $projectManager->getMaxProjectAmount();

            $translator = $this->get('translator');
            $error      = false;
            if (empty($formData['amount']) || $fMinAmount > $formData['amount'] || $fMaxAmount < $formData['amount']) {
                $error = true;
                $this->addFlash('error', $translator->trans('borrower-demand_amount-error'));
            }
            if (empty($formData['duration'])) {
                $error = true;
                $this->addFlash('error', $translator->trans('borrower-demand_duration-error'));
            }
            if (empty($formData['message'])) {
                $error = true;
                $this->addFlash('error', $translator->trans('borrower-demand_message-error'));
            }
            if (false === $error) {
                $company = $this->getCompany();

                /** @var \projects $project */
                $project = $this->get('unilend.service.entity_manager')->getRepository('projects');
                $project->id_company                           = $company->id_company;
                $project->amount                               = str_replace(array(',', ' '), array('.', ''), $formData['amount']);
                $project->ca_declara_client                    = 0;
                $project->resultat_exploitation_declara_client = 0;
                $project->fonds_propres_declara_client         = 0;
                $project->comments                             = $formData['message'];
                $project->period                               = $formData['duration'];
                $project->status                               = \projects_status::A_TRAITER;
                $project->create();

                $projectManager->addProjectStatus(\users::USER_ID_FRONT, \projects_status::A_TRAITER, $project);

                $this->addFlash('success', $translator->trans('borrower-demand_success'));

                return $this->redirect($this->generateUrl($request->get('_route')));
            }
        }

        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');

        return ['project_form' => $projectForm->createView(), 'faq_url' => $settings->value];
    }

    /**
     * @Route("/espace-emprunteur/operations", name="borrower_account_operations")
     *
     * @param Request $request
     * @return Response|StreamedResponse
     */
    public function operationsAction(Request $request)
    {
        if ($request->query->get('action') === 'export') {
            return $this->operationsExportCsv($request);
        }

        if ($request->query->get('action') === 'print') {
            return $this->operationsPrint($request);
        }

        $client              = $this->getClient();
        $projectsPostFunding = $this->getProjectsPostFunding();
        $projectsIds         = array_column($projectsPostFunding, 'id_project');

        if ($request->isXmlHttpRequest()) {
            $filter = $request->query->get('filter');
            $start  = \DateTime::createFromFormat('d/m/Y', $filter['start']);
            $end    = \DateTime::createFromFormat('d/m/Y', $filter['end']);

            if ($filter['op'] !== 'all') {
                $operation = (int)$filter['op'];
            } else {
                $operation = 0;
            }

            if ($filter['project'] !== 'all' && in_array($filter['project'], $projectsIds)) {
                $projectsIds = array($filter['project']);
            }

            $borrowerOperations = $client->getDataForBorrowerOperations($projectsIds, $start, $end, $operation);

            return $this->json([
                'count'         => count($borrowerOperations),
                'html_response' => $this->render('borrower_account/operations_ajax.html.twig', ['operations' => $borrowerOperations])->getContent()
            ]);
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
            $projectsPostFunding[$iKey]['pouvoir'] = $projectsPouvoir->select('id_project = ' . $aProject['id_project'])[0];
            $projectsPostFunding[$iKey]['mandat']  = $clientsMandat->select('id_project = ' . $aProject['id_project'], 'updated DESC')[0];
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

        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');

        return $this->render(
            'borrower_account/operations.html.twig',
            [
                'default_filter_date'   => $defaultFilterDate,
                'projects_ids'          => $projectsIds,
                'invoices'              => $clientsInvoices,
                'post_funding_projects' => $projectsPostFunding,
                'faq_url'               => $settings->value
            ]
        );
    }

    /**
     * @Route("/espace-emprunteur/profil", name="borrower_account_profile")
     * @Template("borrower_account/profile.html.twig")
     *
     * @return array
     */
    public function profileAction()
    {
        $client  = $this->getClient();
        $company = $this->getCompany();

        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');

        return [
            'client'         => $client,
            'company'        => $company,
            'faq_url'        => $settings->value
        ];
    }

    /**
     * @Route("/espace-emprunteur/contact", name="borrower_account_contact")
     * @Template("borrower_account/contact.html.twig")
     *
     * @return array
     */
    public function contactAction(Request $request)
    {
        $company = $this->getCompany();

        $contactForm = $this->createForm(BorrowerContactType::class);
        $contactForm->handleRequest($request);

        if ($contactForm->isSubmitted() && $contactForm->isValid()) {
            $formData = $contactForm->getData();
            $translator = $this->get('translator');
            $error    = false;

            if (empty($formData['first_name'])) {
                $error = true;
                $this->addFlash('error', $translator->trans('common-validator_first-name-empty'));
            }
            if (empty($formData['last_name'])) {
                $error = true;
                $this->addFlash('error', $translator->trans('common-validator_last-name-empty'));
            }
            if (empty($formData['mobile']) || strlen($formData['mobile']) < 9 || strlen($formData['mobile']) > 14) {
                $error = true;
                $this->addFlash('error', $translator->trans('common-validator_phone-number-invalid'));
            }
            if (empty($formData['email']) || false == filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $error = true;
                $this->addFlash('error', $translator->trans('common-validator_email-address-invalid'));
            }
            if (empty($formData['message'])) {
                $error = true;
                $this->addFlash('error', $translator->trans('common-validator_email-message-empty'));
            }

            if (false === $error) {
                $filePath = '';
                if (isset($_FILES['attachment']['name'] ) && $_FILES['attachment']['name'] !== '') {
                    $oUpload = new \upload;
                    $path = $this->get('kernel')->getRootDir() . '/../';
                    $oUpload->setUploadDir($path, 'protected/contact/');
                    $oUpload->doUpload('attachment');
                    $filePath = $path . 'protected/contact/' . $oUpload->getName();
                }

                /** @var \settings $oSettings */
                $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
                $settings->get('Facebook', 'type');
                $facebookURL = $settings->value;

                $settings->get('Twitter', 'type');
                $twitterURL = $settings->value;

                $aVariables = array(
                    'surl'     => $this->get('assets.packages')->getUrl(''),
                    'url'      => $request->getBaseUrl(),
                    'prenom_c' => $formData['first_name'],
                    'projets'  => $this->generateUrl('projects_list'),
                    'lien_fb'  => $facebookURL,
                    'lien_tw'  => $twitterURL
                );

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('demande-de-contact', $aVariables);
                $message->setTo($formData['email']);
                $mailer = $this->get('mailer');
                $mailer->send($message);

                $settings->get('Adresse emprunteur', 'type');

                $aReplacements = array(
                    '[siren]'     => $company->siren,
                    '[company]'   => $company->name,
                    '[prenom]'    => $formData['first_name'],
                    '[nom]'       => $formData['last_name'],
                    '[email]'     => $formData['email'],
                    '[telephone]' => $formData['mobile'],
                    '[demande]'   => $translator->trans('borrower-contact_subject-option-' . $formData['subject']),
                    '[message]'   => $formData['message'],
                    '[SURL]'      => $this->get('assets.packages')->getUrl('')
                );

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-demande-de-contact-emprunteur', $aReplacements, false);
                $message->setTo(trim($settings->value));
                if (empty($filePath) === false) {
                    $message->attach(\Swift_Attachment::fromPath($filePath));
                }
                $mailer = $this->get('mailer');
                $mailer->send($message);

                @unlink($filePath);
                $this->addFlash('success', $translator->trans('borrower-contact_success-message'));
            }
        }

        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');

        return ['contact_form' => $contactForm->createView(), 'company_siren' => $company->siren, 'company_name' => $company->name, 'faq_url' => $settings->value];
    }

    /**
     * @param Request $request
     * @return StreamedResponse
     */
    private function operationsExportCsv(Request $request)
    {
        $client              = $this->getClient();
        $projectsPostFunding = $this->getProjectsPostFunding();
        $projectsIds         = array_column($projectsPostFunding, 'id_project');

        $filter = $request->query->get('filter');
        $start  = \DateTime::createFromFormat('d/m/Y', $filter['start']);
        $end    = \DateTime::createFromFormat('d/m/Y', $filter['end']);

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
     * @param Request $request
     * @return StreamedResponse
     */
    private function operationsPrint(Request $request)
    {
        $client              = $this->getClient();
        $projectsPostFunding = $this->getProjectsPostFunding();
        $projectsIds         = array_column($projectsPostFunding, 'id_project');

        $filter = $request->query->get('filter');
        $start  = \Datetime::createFromFormat('d/m/Y', $filter['start']);
        $end    = \Datetime::createFromFormat('d/m/Y', $filter['end']);

        if ($filter['op'] !== 'all') {
            $operation = (int)$filter['op'];
        } else {
            $operation = 0;
        }

        if ($filter['project'] !== 'all' && in_array($filter['project'], $projectsIds)) {
            $projectsIds = array($filter['project']);
        }

        /** @var array $config */
        $rootDir = $this->get('kernel')->getRootDir() . '/..';

        include $rootDir . '/config.php';
        include $rootDir . '/apps/default/bootstrap.php';
        include $rootDir . '/apps/default/controllers/pdf.php';

        $pdfCommand    = new \Command('pdf', 'setDisplay', 'fr');
        $pdfController = new \pdfController($pdfCommand, $config, 'default');
        $pdfController->setContainer($this->container);
        $pdfController->initialize();

        $fileName = 'operations_emprunteur_' . date('Y-m-d') . '.pdf';
        $fullPath = $rootDir . '/protected/operations_export_pdf/' . $client->id_client . '/' . $fileName;

        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        $pdfController->lng['espace-emprunteur']                 = $translationManager->getAllTranslationsForSection('espace-emprunteur');
        $pdfController->lng['preteur-operations-vos-operations'] = $translationManager->getAllTranslationsForSection('preteur-operations-vos-operations');
        $pdfController->lng['preteur-operations-pdf']            = $translationManager->getAllTranslationsForSection('preteur-operations-pdf');
        $pdfController->aBorrowerOperations = $client->getDataForBorrowerOperations($projectsIds, $start, $end, $operation);
        $pdfController->companies = $this->get('unilend.service.entity_manager')->getRepository('companies');
        $pdfController->companies->get($client->id_client, 'id_client_owner');
        $pdfController->setDisplay('operations_emprunteur_pdf_html');
        $pdfController->WritePdf($fullPath, 'operations');

        $response = new StreamedResponse();
        $response->setCallback(function () use ($fullPath) {
            $handle = fopen('php://output', 'w+');
            readfile($fullPath);
            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }

    /**
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
     * @return StreamedResponse
     */
    public function exportCsvWithLenderDetailsAction($type, $projectId, $repaymentOrder)
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

    /**
     * @Route("/espace-emprunteur/securite/{token}", name="borrower_account_security")
     * @Template("borrower_account/security.html.twig")
     *
     * @param Request $request
     * @param $token
     * @return array
     */
    public function securityAction(Request $request, $token)
    {
        /** @var \temporary_links_login $temporaryLinks */
        $temporaryLinks   = $this->get('unilend.service.entity_manager')->getRepository('temporary_links_login');
        $isLinkExpired = false;

        if (false === $temporaryLinks->get($token, 'token')) {
            return RedirectResponse::create($this->generateUrl('home'));
        }

        $now         = new \datetime();
        $linkExpires = new \datetime($temporaryLinks->expires);

        if ($linkExpires <= $now) {
            $isLinkExpired = true;
        } else {
            $temporaryLinks->accessed = $now->format('Y-m-d H:i:s');
            $temporaryLinks->update();

            /** @var \clients $client */
            $client = $this->get('unilend.service.entity_manager')->getRepository('clients');

            $client->get($temporaryLinks->id_client);
            if ($request->isMethod('POST')) {
                $translator = $this->get('translator');
                /** @var \ficelle $ficelle */
                $ficelle = Loader::loadLib('ficelle');
                $formData = $request->request->get('borrower_security');
                $error = false;
                if (empty($formData['password']) || false === $ficelle->password_fo($formData['password'], 6)) {
                    $error = true;
                    $this->addFlash('error', $translator->trans('common-validator_password-invalid'));
                }
                if ($formData['password'] !== $formData['repeated_password']) {
                    $error = true;
                    $this->addFlash('error', $translator->trans('common-validator_password-not-equal'));
                }
                if (empty($formData['question'])) {
                    $error = true;
                    $this->addFlash('error', $translator->trans('common-validator_secret-question-invalid'));
                }
                if (empty($formData['answer'])) {
                    $error = true;
                    $this->addFlash('error', $translator->trans('common-validator_secret-answer-invalid'));
                }
                if (false === $error) {
                    $borrower = $this->get('unilend.frontbundle.security.user_provider')->loadUserByUsername($client->email);
                    $password = $this->get('security.password_encoder')->encodePassword($borrower, $formData['password']);
                    $client->password         = $password;
                    $client->secrete_question = $formData['question'];
                    $client->secrete_reponse  = md5($formData['answer']);
                    $client->status           = 1;
                    $client->update();
                    return $this->redirect($this->generateUrl('login'));
                }
            }
        }

        return ['expired' => $isLinkExpired];
    }

    /**
     * @return array
     */
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
            switch ($project['status']) {
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

    /**
     * @return array
     */
    private function getProjectsFunding()
    {
        /** @var \bids $bids */
        $bids = $this->get('unilend.service.entity_manager')->getRepository('bids');
        /** @var \projects $projects */
        $projects        = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $projectsFunding = $this->getCompany()->getProjectsForCompany(null, [\projects_status::EN_FUNDING]);

        foreach ($projectsFunding as $key => $project) {
            $projects->get($project['id_project']);

            $projectsFunding[$key] = $projectsFunding[$key] + [
                'average_ir'       => round($projects->getAverageInterestRate(), 2),
                'funding_progress' => min(100, round((1 - ($project['amount'] - $bids->getSoldeBid($project['id_project'])) / $project['amount']) * 100, 1)),
                'ended'            => \DateTime::createFromFormat('Y-m-d H:i:s', $project['date_retrait_full'])
            ];
        }
        return $projectsFunding;
    }

    /**
     * @return array
     */
    private function getProjectsPostFunding()
    {
        $aStatusPostFunding = array_merge([\projects_status::FUNDE, \projects_status::FUNDING_KO, \projects_status::PRET_REFUSE], \projects_status::$afterRepayment);

        /** @var ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        /** @var \projects $projects */
        $projects            = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $projectsPostFunding = $this->getCompany()->getProjectsForCompany(null, $aStatusPostFunding);
        /** @var \echeanciers_emprunteur $repaymentSchedule */
        $repaymentSchedule = $this->get('unilend.service.entity_manager')->getRepository('echeanciers_emprunteur');

        foreach ($projectsPostFunding as $key => $project) {
            $projects->get($project['id_project']);

            if (false === in_array($project['status'],[\projects_status::REMBOURSEMENT_ANTICIPE,\projects_status::REMBOURSE])) {
               $aNextRepayment = $repaymentSchedule->select(
                   'status_emprunteur = 0 AND id_project = ' . $project['id_project'],
                   'date_echeance_emprunteur ASC',
                   '',
                   1
               )[0];
            } else {
                $aNextRepayment = 0;
            }

            $projectsPostFunding[$key] = $projectsPostFunding[$key] + [
                'average_ir'          => round($projects->getAverageInterestRate(), 2),
                'outstanding_capital' => $this->calculateOutstandingCapital($project['id_project']),
                'monthly_payment'     => ($aNextRepayment['montant'] + $aNextRepayment['commission'] + $aNextRepayment['tva']) / 100,
                'next_maturity_date'  => \DateTime::createFromFormat('Y-m-d H:i:s', $aNextRepayment['date_echeance_emprunteur']),
                'ended'               => $projectManager->getProjectEndDate($projects)
            ];
        }

        usort($projectsPostFunding, function ($firstArray, $secondArray) {
            return $firstArray['date_retrait'] < $secondArray['date_retrait'];
        });

        return $projectsPostFunding;
    }

    /**
     * @return \clients
     */
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

    /**
     * @return \companies
     */
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

    /**
     * @param int $projectId
     * @return float
     */
    private function calculateOutstandingCapital($projectId)
    {
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->get('unilend.service.entity_manager')->getRepository('echeanciers');

        $aPayment      = $repaymentSchedule->getLastOrder($projectId);
        $iPaymentOrder = (isset($aPayment)) ? $aPayment['ordre'] + 1 : 1;

        return $repaymentSchedule->getRemainingCapitalAtDue($projectId, $iPaymentOrder);
    }
}
