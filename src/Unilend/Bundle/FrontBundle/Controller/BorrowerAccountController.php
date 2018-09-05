<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Knp\Snappy\GeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response, StreamedResponse};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, Companies, Factures, OperationSubType, OperationType, Projects, ProjectsStatus, Users, Virements, Wallet, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\{BorrowerOperationsManager, ProjectStatusManager};
use Unilend\Bundle\FrontBundle\Form\{BorrowerContactType, SimpleProjectType};

// Must have ROLE_BORROWER to access to these pages
class BorrowerAccountController extends Controller
{
    /**
     * @Route("/espace-emprunteur/projets", name="borrower_account_projects")
     * @Template("borrower_account/projects.html.twig")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return array
     */
    public function projectsAction(Request $request, ?UserInterface $client): array
    {
        $projectsPreFunding  = $this->getProjectsPreFunding($client);
        $projectsFunding     = $this->getProjectsFunding($client);
        $projectsPostFunding = $this->getProjectsPostFunding($client);

        return [
            'pre_funding_projects'   => $projectsPreFunding,
            'funding_projects'       => $projectsFunding,
            'post_funding_projects'  => $projectsPostFunding
        ];
    }

    /**
     * @Route("/espace-emprunteur/cloture-projet", name="borrower_account_close_project", methods={"POST"})
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return RedirectResponse
     */
    public function closeFundingProjectAction(Request $request, ?UserInterface $client): RedirectResponse
    {
        if ($request->request->get('project')) {
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $projectId     = $request->request->getDigits('project');
            /** @var Projects $project */
            $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
            $company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);

            if ($project && $project->getIdCompany() === $company) {
                $session = $request->getSession();

                $closingProjects                           = $session->get('closingProjects', []);
                $closingProjects[$project->getIdProject()] = true;

                $session->set('closingProjects', $closingProjects);

                $closingDate = new \DateTime();
                $closingDate->modify('+5 minutes');

                $project->setDateRetrait($closingDate);
                $entityManager->flush($project);
            }
        }

        return new RedirectResponse($this->generateUrl('borrower_account_projects'));
    }

    /**
     * @Route("/espace-emprunteur/nouvelle-demande", name="borrower_account_new_demand")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function newDemandAction(Request $request, ?UserInterface $client): Response
    {
        $projectForm = $this->createForm(SimpleProjectType::class);
        $projectForm->handleRequest($request);

        if ($projectForm->isSubmitted() && $projectForm->isValid()) {
            $formData       = $projectForm->getData();
            $projectManager = $this->get('unilend.service.project_manager');
            $fMinAmount     = $projectManager->getMinProjectAmount();
            $fMaxAmount     = $projectManager->getMaxProjectAmount();
            $entityManager  = $this->get('doctrine.orm.entity_manager');

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
                $company               = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
                $partnerManager        = $this->get('unilend.service.partner_manager');
                $projectRequestManager = $this->get('unilend.service.project_request_manager');

                $amount    = str_replace([',', ' '], ['.', ''], $formData['amount']);
                $frontUser = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_FRONT);

                try {
                    $project = $projectRequestManager->createProjectByCompany($frontUser, $company, $partnerManager->getDefaultPartner(), ProjectsStatus::COMPLETE_REQUEST, $amount,
                        $formData['duration'], null, $formData['message']);
                } catch (\Exception $exception) {
                    $this->addFlash('error', $translator->trans('borrower-demand_error'));
                    $this->get('logger')->error('Project Creation failed. Exception : ' . $exception->getMessage(), [
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'file'       => $exception->getFile(),
                        'line'       => $exception->getLine(),
                        'id_company' => $company->getIdCompany()
                    ]);

                    return ['project_form' => $projectForm->createView()];
                }

                /** @var ProjectStatusManager $projectStatusManager */
                $projectStatusManager = $this->get('unilend.service.project_status_manager');
                $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::COMPLETE_REQUEST, $project);

                $this->addFlash('success', $translator->trans('borrower-demand_success'));

                return $this->redirect($this->generateUrl($request->get('_route')));
            }
        }

        return $this->render('borrower_account/new_demand.html.twig', ['project_form' => $projectForm->createView()]);
    }

    /**
     * @Route("/espace-emprunteur/operations", name="borrower_account_operations")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function operationsAction(Request $request, ?UserInterface $client): Response
    {
        $projectsPostFunding = $this->getProjectsPostFunding($client);
        $projectsIds         = array_column($projectsPostFunding, 'id_project');
        $filter              = $request->query->get('filter');
        $entityManager       = $this->get('doctrine.orm.entity_manager');

        if (
            isset($filter['start'], $filter['end'], $filter['op'])
            && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filter['start'])
            && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filter['end'])
        ) {
            $start     = \DateTime::createFromFormat('d/m/Y', $filter['start']);
            $end       = \DateTime::createFromFormat('d/m/Y', $filter['end']);
            $operation = filter_var($filter['op'], FILTER_SANITIZE_STRING);

            if ($filter['project'] !== 'all' && in_array($filter['project'], $projectsIds)) {
                $projectsIds = [$filter['project']];
            }
            $borrowerOperationsManager = $this->get('unilend.service.borrower_operations_manager');

            $borrowerWallet     = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::BORROWER);
            $borrowerOperations = $borrowerOperationsManager->getBorrowerOperations($borrowerWallet, $start, $end, $projectsIds, $operation);

            if ($request->query->get('action') === 'export') {
                return $this->operationsExportCsv($borrowerOperations);
            }

            if ($request->query->get('action') === 'print') {
                return $this->operationsPrint($borrowerWallet, $borrowerOperations);
            }

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'count'         => count($borrowerOperations),
                    'html_response' => $this->render('borrower_account/operations_ajax.html.twig', ['operations' => $borrowerOperations])->getContent()
                ]);
            }
        } else {
            if ($request->query->get('action') === 'export') {
                throw new RouteNotFoundException('Invalid operation CSV export parameters');
            }
            if ($request->query->get('action') === 'print') {
                throw new RouteNotFoundException('Invalid operation PDF export parameters');
            }
        }

        $start                      = new \DateTime('NOW - 1 month');
        $end                        = new \DateTime();
        $defaultFilterDate['start'] = $start->format('d/m/Y');
        $defaultFilterDate['end']   = $end->format('d/m/Y');

        /**** Document tab *********/
        /** @var \factures $oInvoices */
        $oInvoices = $this->get('unilend.service.entity_manager')->getRepository('factures');
        /** @var Companies $company */
        $company         = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
        $clientsInvoices = $oInvoices->select('id_company = ' . $company->getIdCompany(), 'date DESC');

        foreach ($clientsInvoices as $index => $invoice) {
            switch ($invoice['type_commission']) {
                case Factures::TYPE_COMMISSION_FUNDS:
                    $clientsInvoices[$index]['url'] = $this->generateUrl('borrower_invoice_funds_commission', ['clientHash' => $client->getHash(), 'idProject' => $invoice['id_project']]);
                    break;
                case Factures::TYPE_COMMISSION_REPAYMENT:
                    $clientsInvoices[$index]['url'] = $this->generateUrl('borrower_invoice_payment_commission',
                        ['clientHash' => $client->getHash(), 'idProject' => $invoice['id_project'], 'order' => $invoice['ordre']]);
                    break;
            }
        }

        $thirdPartyWireTransfersOuts = $entityManager->getRepository('UnilendCoreBusinessBundle:Virements')
            ->findWireTransferToThirdParty($client, [
                Virements::STATUS_PENDING,
                Virements::STATUS_CLIENT_VALIDATED,
                Virements::STATUS_VALIDATED,
                Virements::STATUS_SENT
            ]);

        return $this->render(
            'borrower_account/operations.html.twig', [
                'default_filter_date'            => $defaultFilterDate,
                'projects_ids'                   => $projectsIds,
                'invoices'                       => $clientsInvoices,
                'post_funding_projects'          => $projectsPostFunding,
                'third_party_wire_transfer_outs' => $thirdPartyWireTransfersOuts,
                'operationTypes'                 => [
                    OperationType::LENDER_LOAN,
                    OperationType::BORROWER_WITHDRAW,
                    OperationSubType::BORROWER_COMMISSION_FUNDS,
                    OperationSubType::BORROWER_COMMISSION_REPAYMENT,
                    BorrowerOperationsManager::OP_BORROWER_DIRECT_DEBIT,
                    BorrowerOperationsManager::OP_WIRE_TRANSFER_IN,
                    BorrowerOperationsManager::OP_LENDER_MONTHLY_REPAYMENT,
                    BorrowerOperationsManager::OP_LENDER_EARLY_REPAYMENT,
                    BorrowerOperationsManager::OP_LENDER_RECOVERY_REPAYMENT,
                    OperationType::BORROWER_PROJECT_CHARGE_REPAYMENT
                ]
            ]
        );
    }

    /**
     * @Route("/espace-emprunteur/profil", name="borrower_account_profile")
     * @Template("borrower_account/profile.html.twig")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return array
     */
    public function profileAction(?UserInterface $client): array
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $company       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
        $bankAccount   = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($client);

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
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return array
     */
    public function contactAction(Request $request, ?UserInterface $client): array
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var Companies $company */
        $company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);

        $contactForm = $this->createForm(BorrowerContactType::class);
        $contactForm->handleRequest($request);

        if ($contactForm->isSubmitted() && $contactForm->isValid()) {
            $formData   = $contactForm->getData();
            $translator = $this->get('translator');
            $error      = false;

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
                $file     = $request->files->get('attachment');
                if ($file instanceof UploadedFile) {
                    $uploadDestination = $this->getParameter('path.protected') . 'contact/';
                    $file              = $file->move($uploadDestination, $file->getClientOriginalName());
                    $filePath          = $file->getPathname();
                }

                $keywords = [
                    'firstName' => $formData['first_name']
                ];

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('demande-de-contact-emprunteur', $keywords);

                try {
                    $message->setTo($formData['email']);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                } catch (\Exception $exception) {
                    $this->addFlash('error', $translator->trans('common-validator_email-address-invalid'));

                    return [
                        'contact_form'  => $contactForm->createView(),
                        'company_siren' => $company->getSiren(),
                        'company_name'  => $company->getName()
                    ];
                }

                $keywords = [
                    '[siren]'     => $company->getSiren(),
                    '[company]'   => $company->getName(),
                    '[prenom]'    => $formData['first_name'],
                    '[nom]'       => $formData['last_name'],
                    '[email]'     => $formData['email'],
                    '[telephone]' => $formData['mobile'],
                    '[demande]'   => $translator->trans('borrower-contact_subject-option-' . $formData['subject']),
                    '[message]'   => $formData['message'],
                    '[SURL]'      => $this->get('assets.packages')->getUrl('')
                ];

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message   = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-demande-de-contact-emprunteur', $keywords, false);
                $recipient = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse emprunteur'])->getValue();
                $recipient = trim($recipient);

                try {
                    if (false === empty($filePath)) {
                        $message->attach(\Swift_Attachment::fromPath($filePath));
                    }
                    $message->setTo($recipient);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                } catch (\Exception $exception) {
                    $this->get('logger')->error(
                        'Could not send email : notification-demande-de-contact-emprunteur - Exception: ' . $exception->getMessage(),
                        ['id_mail_template' => $message->getTemplateId(), 'email address' => $recipient, 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                }

                @unlink($filePath);
                $this->addFlash('success', $translator->trans('borrower-contact_success-message'));
            }
        }

        return [
            'contact_form'  => $contactForm->createView(),
            'company_siren' => $company->getSiren(),
            'company_name'  => $company->getName()
        ];
    }

    /**
     * @param array $borrowerOperations
     *
     * @return StreamedResponse
     */
    private function operationsExportCsv(array $borrowerOperations): StreamedResponse
    {
        $translator = $this->get('translator');

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
                        $translator->trans('borrower-operation_' . $operation['label']),
                        $operation['idProject'],
                        $date,
                        number_format($operation['amount'], 2, ',', ''),
                        (empty($operation['vat']) === false) ? number_format($operation['vat'], 2, ',', '') : '0'
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
     * @param Wallet $wallet
     * @param array  $borrowerOperations
     *
     * @return Response
     */
    private function operationsPrint(Wallet $wallet, array $borrowerOperations): Response
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $company       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $wallet->getIdClient()]);

        $pdfContent = $this->renderView('pdf/borrower_operations.html.twig', [
            'operations'        => $borrowerOperations,
            'client'            => $wallet->getIdClient(),
            'company'           => $company,
            'available_balance' => $wallet->getAvailableBalance()
        ]);

        /** @var GeneratorInterface $snappy */
        $snappy = $this->get('knp_snappy.pdf');

        return new Response(
            $snappy->getOutputFromHtml($pdfContent),
            200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', 'operations_emprunteur_' . date('Y-m-d') . '.pdf')
            ]
        );
    }

    /**
     * @Route(
     *     "/espace-emprunteur/export/lender-detail/csv/{type}/{projectId}/{repaymentOrder}",
     *     requirements={"projectId": "\d+"},
     *     defaults={"repaymentOrder": null},
     *     name="borrower_account_export_lender_details_csv"
     * )
     *
     * @param string   $type
     * @param int      $projectId
     * @param int|null $repaymentOrder
     *
     * @return StreamedResponse
     */
    public function exportCsvWithLenderDetailsAction(string $type, int $projectId, ?int $repaymentOrder): StreamedResponse
    {
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $project->get($projectId, 'id_project');

        $translator = $this->get('translator');
        switch ($type) {
            case 'l':
                $columnHeaders = ['ID Préteur', 'Nom ou Raison Sociale', 'Prénom', 'Mouvement', 'Montant', 'Date'];
                $label         = $translator->trans('borrower-operation_' . OperationType::LENDER_LOAN);
                $data          = $project->getLoansAndLendersForProject();
                $filename      = 'details_prets';
                break;
            case 'e':
                $columnHeaders = [
                    'ID Préteur',
                    'Nom ou Raison Sociale',
                    'Prénom',
                    'Mouvement',
                    'Montant',
                    'Capital',
                    'Intérets',
                    'Date'
                ];
                $label         = $translator->trans('borrower-operation_' . BorrowerOperationsManager::OP_LENDER_MONTHLY_REPAYMENT);
                $data          = $project->getDuePaymentsAndLenders(null, $repaymentOrder);
                $dateTime      = \DateTime::createFromFormat('Y-m-d H:i:s', $data[0]['date']);
                $date          = $dateTime->format('mY');
                $filename      = 'details_remboursements_' . $projectId . '_' . $date;
                break;
            default:
                break;
        }

        $response = new StreamedResponse();
        $response->setCallback(function () use ($data, $label, $columnHeaders) {
            $handle = fopen('php://output', 'w+');
            fputs($handle, "\xEF\xBB\xBF"); // add UTF-8 BOM in order to be compatible to Excel
            fputcsv($handle, $columnHeaders, ';');

            foreach ($data as $key => $row) {
                $line = $row;
                if (empty($row['name']) === false) {
                    $line['nom']    = $row['name'];
                    $line['prenom'] = null;
                }
                $line['name'] = $label;
                $line['date'] = (new \DateTime($row['date']))->format('d/m/Y');

                if (empty($row['amount']) === false) {
                    $line['amount'] = $row['amount'] / 100;
                }

                if (empty($row['montant']) === false) {
                    $line['montant']  = number_format(bcdiv($row['montant'], 100, 2), '2', ',', ' ');
                    $line['capital']  = number_format(bcdiv($row['capital'], 100, 2), '2', ',', ' ');
                    $line['interets'] = number_format(bcdiv($row['interets'], 100, 2), '2', ',', ' ');
                }

                fputcsv($handle, $line, ';');
            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');

        return $response;
    }

    /**
     * @Route("/espace-emprunteur/securite/{securityToken}", name="borrower_account_security", requirements={"securityToken": "[0-9a-f]+"})
     *
     * @param string  $securityToken
     * @param Request $request
     *
     * @return Response
     */
    public function securityAction(string $securityToken, Request $request): Response
    {
        /** @var \temporary_links_login $temporaryLinks */
        $temporaryLinks = $this->get('unilend.service.entity_manager')->getRepository('temporary_links_login');
        $isLinkExpired  = false;
        $displayForm    = true;

        if (false === $temporaryLinks->get($securityToken, 'token')) {
            return $this->redirectToRoute('home');
        }

        $now         = new \DateTime();
        $linkExpires = new \DateTime($temporaryLinks->expires);

        if ($linkExpires <= $now) {
            $isLinkExpired = true;
        } else {
            $temporaryLinks->accessed = $now->format('Y-m-d H:i:s');
            $temporaryLinks->update();

            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var Clients $client */
            $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($temporaryLinks->id_client);

            if (false === $client->isGrantedLogin()) {
                $displayForm = false;
                $translator  = $this->get('translator');
                $this->addFlash('error', $translator->trans('borrower-profile_security-offline-account'));
            } elseif ($request->isMethod(Request::METHOD_POST)) {
                $translator = $this->get('translator');
                $formData   = $request->request->get('borrower_security', []);
                $error      = false;

                try {
                    if (empty($formData['password'])) {
                        throw new \Exception('password empty');
                    }
                    $password = $this->get('security.password_encoder')->encodePassword($client, $formData['password']);
                } catch (\Exception $exception) {
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
                    $client
                        ->setPassword($password)
                        ->setSecreteQuestion(filter_var($formData['question'], FILTER_SANITIZE_STRING))
                        ->setSecreteReponse(md5($formData['answer']));
                    $entityManager->flush($client);

                    return $this->redirectToRoute('login');
                } else {
                    return $this->redirectToRoute('borrower_account_security', ['securityToken' => $securityToken]);
                }
            }
        }

        return $this->render('borrower_account/security.html.twig', [
            'securityToken' => $securityToken,
            'expired'       => $isLinkExpired,
            'displayForm'   => $displayForm
        ]);
    }

    /**
     * @param Clients $client
     *
     * @return array
     */
    private function getProjectsPreFunding(Clients $client): array
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $company            = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
        $statusPreFunding   = [
            ProjectsStatus::COMPLETE_REQUEST,
            ProjectsStatus::COMMERCIAL_REVIEW,
            ProjectsStatus::COMMERCIAL_REJECTION,
            ProjectsStatus::ANALYSIS_REVIEW,
            ProjectsStatus::COMITY_REVIEW,
            ProjectsStatus::ANALYSIS_REJECTION,
            ProjectsStatus::COMITY_REJECTION,
            ProjectsStatus::PREP_FUNDING,
            ProjectsStatus::A_FUNDER
        ];
        $projectsPreFunding = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findByCompany($company, $statusPreFunding);

        return $projectsPreFunding;
    }

    /**
     * @param Clients $client
     *
     * @return array
     */
    private function getProjectsFunding(Clients $client): array
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $company         = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
        $projectsFunding = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findByCompany($company, [ProjectsStatus::EN_FUNDING]);

        return $projectsFunding;
    }

    /**
     * @param Clients $client
     *
     * @return array
     */
    private function getProjectsPostFunding(Clients $client): array
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $company             = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
        $statusPostFunding   = array_merge([ProjectsStatus::FUNDE, ProjectsStatus::FUNDING_KO, ProjectsStatus::PRET_REFUSE], ProjectsStatus::AFTER_REPAYMENT);
        $projectsPostFunding = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findByCompany($company, $statusPostFunding);

        return $projectsPostFunding;
    }
}
