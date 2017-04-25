<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Unilend\Bundle\CoreBusinessBundle\Entity\Factures;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\FrontBundle\Form\BorrowerContactType;
use Unilend\Bundle\FrontBundle\Form\SimpleProjectType;
use Unilend\Bundle\FrontBundle\Security\User\UserBorrower;
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

        return [
            'pre_funding_projects'  => $projectsPreFunding,
            'funding_projects'      => $projectsFunding,
            'post_funding_projects' => $projectsPostFunding,
            'closing_projects'      => $request->getSession()->get('closingProjects')
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
                filter_var($projectId, FILTER_VALIDATE_INT)
                && $project->get($projectId)
                && $project->id_company == $this->getCompany()->id_company
            ) {
                $session = $request->getSession();

                $closingProjects = $session->get('closingProjects', []);
                $closingProjects[$project->id_project] = true;

                $session->set('closingProjects', $closingProjects);

                $closingDate = new \DateTime();
                $closingDate->modify('+5 minutes');

                $project->date_retrait = $closingDate->format('Y-m-d H:i:s');
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
                $company        = $this->getCompany();
                $partnerManager = $this->get('unilend.service.partner_manager');

                /** @var \projects $project */
                $project = $this->get('unilend.service.entity_manager')->getRepository('projects');
                $project->id_company                           = $company->id_company;
                $project->amount                               = str_replace(array(',', ' '), array('.', ''), $formData['amount']);
                $project->ca_declara_client                    = 0;
                $project->resultat_exploitation_declara_client = 0;
                $project->fonds_propres_declara_client         = 0;
                $project->comments                             = $formData['message'];
                $project->period                               = $formData['duration'];
                $project->status                               = \projects_status::COMPLETE_REQUEST;
                $project->id_partner                           = $partnerManager->getDefaultPartner()->getId();
                $project->commission_rate_funds                = \projects::DEFAULT_COMMISSION_RATE_FUNDS;
                $project->commission_rate_repayment            = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT;
                $project->create();

                $projectManager->addProjectStatus(Users::USER_ID_FRONT, \projects_status::COMPLETE_REQUEST, $project);

                $this->addFlash('success', $translator->trans('borrower-demand_success'));

                return $this->redirect($this->generateUrl($request->get('_route')));
            }
        }

        return ['project_form' => $projectForm->createView()];
    }

    /**
     * @Route("/espace-emprunteur/operations", name="borrower_account_operations")
     *
     * @param Request $request
     * @return Response
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

            if (
                isset($filter['start'], $filter['end'], $filter['op'])
                && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filter['start'])
                && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filter['end'])
            ) {
                $start     = \DateTime::createFromFormat('d/m/Y', $filter['start']);
                $end       = \DateTime::createFromFormat('d/m/Y', $filter['end']);
                $operation = ($filter['op'] !== 'all' && filter_var($filter['op'], FILTER_VALIDATE_INT)) ? $filter['op'] : 0;

                if ($filter['project'] !== 'all' && in_array($filter['project'], $projectsIds)) {
                    $projectsIds = [$filter['project']];
                }

                $borrowerOperations = $client->getDataForBorrowerOperations($projectsIds, $start, $end, $operation);

                return $this->json([
                    'count'         => count($borrowerOperations),
                    'html_response' => $this->render('borrower_account/operations_ajax.html.twig', ['operations' => $borrowerOperations])->getContent()
                ]);
            }
        }

        $start                      = new \DateTime('NOW - 1 month');
        $end                        = new \DateTime();
        $defaultFilterDate['start'] = $start->format('d/m/Y');
        $defaultFilterDate['end']   = $end->format('d/m/Y');

        /**** Document tab *********/

        /** @var \projects_pouvoir $projectsPouvoir */
        $projectsPouvoir = $this->get('unilend.service.entity_manager')->getRepository('projects_pouvoir');
        /** @var \clients_mandats $clientsMandat */
        $clientsMandat = $this->get('unilend.service.entity_manager')->getRepository('clients_mandats');

        foreach ($projectsPostFunding as $iKey => $aProject) {
            $pouvoir = $projectsPouvoir->select('id_project = ' . $aProject['id_project']);
            if ($pouvoir) {
                $projectsPostFunding[$iKey]['pouvoir']  = $pouvoir[0];
            } else {
                $projectsPostFunding[$iKey]['pouvoir'] = [];
            }

            $mandat = $clientsMandat->select('id_project = ' . $aProject['id_project'], 'updated DESC');
            if ($mandat) {
                $projectsPostFunding[$iKey]['mandat']  = $mandat[0];
            } else {
                $projectsPostFunding[$iKey]['mandat'] = [];
            }
        }

        /** @var \factures $oInvoices */
        $oInvoices       = $this->get('unilend.service.entity_manager')->getRepository('factures');
        $company         = $this->getCompany();
        $client          = $this->getClient();
        $clientsInvoices = $oInvoices->select('id_company = ' . $company->id_company, 'date DESC');

        foreach ($clientsInvoices as $iKey => $aInvoice) {
            switch ($aInvoice['type_commission']) {
                case Factures::TYPE_COMMISSION_FUNDS:
                    $clientsInvoices[$iKey]['url'] = '/pdf/facture_EF/' . $client->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
                case Factures::TYPE_COMMISSION_REPAYMENT:
                    $clientsInvoices[$iKey]['url'] = '/pdf/facture_ER/' . $client->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
            }
        }

        $thirdPartyWireTransfersOuts = $this->get('doctrine.orm.entity_manager')
                                            ->getRepository('UnilendCoreBusinessBundle:Virements')
                                            ->findWireTransferToThirdParty($client->id_client, [
                                                Virements::STATUS_PENDING,
                                                Virements::STATUS_CLIENT_VALIDATED,
                                                Virements::STATUS_VALIDATED,
                                                Virements::STATUS_SENT
                                            ]);

        return $this->render(
            'borrower_account/operations.html.twig',
            [
                'default_filter_date'            => $defaultFilterDate,
                'projects_ids'                   => $projectsIds,
                'invoices'                       => $clientsInvoices,
                'post_funding_projects'          => $projectsPostFunding,
                'third_party_wire_transfer_outs' => $thirdPartyWireTransfersOuts,
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
        $client      = $this->getClient();
        $company     = $this->getCompany();
        $bankAccount = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($client->id_client);

        return [
            'client'      => $client,
            'company'     => $company,
            'bankAccount' => $bankAccount
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
                $file = $request->files->get('attachment');
                if ($file instanceof UploadedFile) {
                    $uploadDestination = $this->getParameter('path.protected') . 'contact/';
                    $file = $file->move($uploadDestination, $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension());
                    $filePath = $file->getPathname();
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

        return ['contact_form' => $contactForm->createView(), 'company_siren' => $company->siren, 'company_name' => $company->name];
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

        if (
            false === isset($filter['start'], $filter['end'], $filter['op'])
            && 1 !== preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filter['start'])
            && 1 !== preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filter['end'])
        ) {
            throw new RouteNotFoundException('Invalid operation CSV export parameters');
        }

        $filter    = $request->query->get('filter');
        $start     = \DateTime::createFromFormat('d/m/Y', $filter['start']);
        $end       = \DateTime::createFromFormat('d/m/Y', $filter['end']);
        $operation = ($filter['op'] !== 'all' && filter_var($filter['op'], FILTER_VALIDATE_INT)) ? $filter['op'] : 0;

        if ($filter['project'] !== 'all' && in_array($filter['project'], $projectsIds)) {
            $projectsIds = array($filter['project']);
        }

        $borrowerOperations = $client->getDataForBorrowerOperations($projectsIds, $start, $end, $operation);
        $translator         = $this->get('translator');

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
                        $translator->trans('borrower-operation_' . $operation['type']),
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

        if (
            false === isset($filter['start'], $filter['end'], $filter['op'])
            && 1 !== preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filter['start'])
            && 1 !== preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filter['end'])
        ) {
            throw new RouteNotFoundException('Invalid operation CSV export parameters');
        }

        $filter    = $request->query->get('filter');
        $start     = \DateTime::createFromFormat('d/m/Y', $filter['start']);
        $end       = \DateTime::createFromFormat('d/m/Y', $filter['end']);
        $operation = ($filter['op'] !== 'all' && filter_var($filter['op'], FILTER_VALIDATE_INT)) ? $filter['op'] : 0;

        if ($filter['project'] !== 'all' && in_array($filter['project'], $projectsIds)) {
            $projectsIds = array($filter['project']);
        }

        $rootDir = $this->get('kernel')->getRootDir() . '/..';

        include $rootDir . '/apps/default/bootstrap.php';
        include $rootDir . '/apps/default/controllers/pdf.php';

        $pdfCommand    = new \Command('pdf', 'setDisplay', 'fr');
        $pdfController = new \pdfController($pdfCommand, 'default', $request);
        $pdfController->setContainer($this->container);
        $pdfController->initialize();

        $fileName = 'operations_emprunteur_' . date('Y-m-d') . '.pdf';
        $fullPath = $rootDir . '/protected/operations_export_pdf/' . $client->id_client . '/' . $fileName;

        $translator = $this->get('translator');

        $pdfController->translator          = $translator;
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
     *     requirements={"projectId": "\d+"},
     *     defaults={"repaymentOrder": null},
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

        $translator = $this->get('translator');
        switch ($type) {
            case 'l':
                $aColumnHeaders = array('ID Préteur', 'Nom ou Raison Sociale', 'Prénom', 'Mouvement', 'Montant', 'Date');
                $sType          = $translator->trans('borrower-operation_mouvement-deblocage-des-fonds');
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
                $sType          = $translator->trans('borrower-operation_mouvement-remboursement');
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
     * @Route("/espace-emprunteur/securite/{token}", name="borrower_account_security", requirements={"token": "[0-9a-f]+"})
     * @Template("borrower_account/security.html.twig")
     *
     * @param Request $request
     * @param $token
     * @return Response
     */
    public function securityAction($token, Request $request)
    {
        /** @var \temporary_links_login $temporaryLinks */
        $temporaryLinks = $this->get('unilend.service.entity_manager')->getRepository('temporary_links_login');
        $isLinkExpired  = false;

        if (false === $temporaryLinks->get($token, 'token')) {
            return $this->redirectToRoute('home');
        }

        $now         = new \DateTime();
        $linkExpires = new \DateTime($temporaryLinks->expires);

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
                $ficelle  = Loader::loadLib('ficelle');
                $formData = $request->request->get('borrower_security', []);
                $error    = false;

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
                    $formData['question'] = filter_var($formData['question'], FILTER_SANITIZE_STRING);

                    $borrower = $this->get('unilend.frontbundle.security.user_provider')->loadUserByUsername($client->email);
                    $password = $this->get('security.password_encoder')->encodePassword($borrower, $formData['password']);

                    $client->password         = $password;
                    $client->secrete_question = $formData['question'];
                    $client->secrete_reponse  = md5($formData['answer']);
                    $client->status           = 1;
                    $client->update();

                    return $this->redirectToRoute('login');
                }
            }
        }

        return $this->render('borrower_account/security.html.twig', ['expired' => $isLinkExpired]);
    }

    /**
     * @return array
     */
    private function getProjectsPreFunding()
    {
        $statusPreFunding   = array(
            \projects_status::COMPLETE_REQUEST,
            \projects_status::COMMERCIAL_REVIEW,
            \projects_status::COMMERCIAL_REJECTION,
            \projects_status::ANALYSIS_REVIEW,
            \projects_status::COMITY_REVIEW,
            \projects_status::ANALYSIS_REJECTION,
            \projects_status::COMITY_REJECTION,
            \projects_status::PREP_FUNDING,
            \projects_status::A_FUNDER
        );
        $projectsPreFunding = $this->getCompany()->getProjectsForCompany(null, $statusPreFunding);

        foreach ($projectsPreFunding as $key => $project) {
            switch ($project['status']) {
                case \projects_status::COMPLETE_REQUEST:
                case \projects_status::COMMERCIAL_REVIEW:
                    $projectsPreFunding[$key]['project_status_label'] = 'waiting-for-documents';
                    break;
                case \projects_status::ANALYSIS_REVIEW:
                case \projects_status::COMITY_REVIEW:
                    $projectsPreFunding[$key]['project_status_label'] = 'analyzing';
                    break;
                case \projects_status::COMMERCIAL_REJECTION:
                case \projects_status::ANALYSIS_REJECTION:
                case \projects_status::COMITY_REJECTION:
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
                'ended'            => \DateTime::createFromFormat('Y-m-d H:i:s', $project['date_retrait'])
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

        foreach ($projectsPostFunding as $index => $project) {
            $projects->get($project['id_project']);
            $nextRepayment = [
                'montant'                  => 0,
                'commission'               => 0,
                'tva'                      => 0,
                'date_echeance_emprunteur' => date('Y-m-d H:i:s'),
            ];

            if (false === in_array($project['status'], [\projects_status::REMBOURSEMENT_ANTICIPE, \projects_status::REMBOURSE])) {
               $repayment = $repaymentSchedule->select(
                   'id_project = ' . $project['id_project'] . ' AND status_emprunteur = 0',
                   'date_echeance_emprunteur ASC',
                   '',
                   1
               );
               if (false === empty($repayment[0])) {
                   $nextRepayment = $repayment[0];
               }
            }

            $projectsPostFunding[$index] = $projectsPostFunding[$index] + [
                'average_ir'          => round($projects->getAverageInterestRate(), 2),
                'outstanding_capital' => $this->calculateOutstandingCapital($project['id_project']),
                'monthly_payment'     => ($nextRepayment['montant'] + $nextRepayment['commission'] + $nextRepayment['tva']) / 100,
                'next_maturity_date'  => \DateTime::createFromFormat('Y-m-d H:i:s', $nextRepayment['date_echeance_emprunteur']),
                'ended'               => $projectManager->getProjectEndDate($projects)
            ];
        }

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
        $lastOrder         = $repaymentSchedule->getLastOrder($projectId);

        if (false === $lastOrder) {
            return 0.0;
        }

        $paymentOrder = $lastOrder['ordre'] + 1;

        return $repaymentSchedule->getRemainingCapitalAtDue($projectId, $paymentOrder);
    }
}
