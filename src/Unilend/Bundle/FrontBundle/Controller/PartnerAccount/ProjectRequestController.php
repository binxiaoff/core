<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Psr\Log\InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\{Method, Route, Security};
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{BorrowingMotive, Clients, ClientsStatus, Companies, CompanyClient, PartnerProjectAttachment, ProjectAbandonReason, ProjectRejectionReason, Projects,
    ProjectsStatus, Users};
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager;
use Unilend\core\Loader;

class ProjectRequestController extends Controller
{
    /**
     * @Route("partenaire/depot", name="partner_project_request")
     * @Method("GET")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function projectRequestAction(Request $request)
    {
        $projectManager = $this->get('unilend.service.project_manager');

        $template = [
            'loanPeriods'      => $projectManager->getPossibleProjectPeriods(),
            'projectAmountMin' => $projectManager->getMinProjectAmount(),
            'projectAmountMax' => $projectManager->getMaxProjectAmount(),
            'borrowingMotives' => $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BorrowingMotive')->findBy([], ['rank' => 'ASC']),
            'form'             => $request->getSession()->get('partnerProjectRequest', [])
        ];

        $request->getSession()->remove('partnerProjectRequest');

        return $this->render('/partner_account/project_request.html.twig', $template);
    }

    /**
     * @Route("partenaire/depot", name="partner_project_request_form")
     * @Method("POST")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request $request
     * @param UserInterface|Clients $partnerUser
     *
     * @return RedirectResponse
     */
    public function projectRequestFormAction(Request $request, UserInterface $partnerUser): RedirectResponse
    {
        $translator            = $this->get('translator');
        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $partnerManager        = $this->get('unilend.service.partner_manager');

        $formData    = $request->request->get('simulator');
        $amount      = $formData['amount'] ?? null;
        $reason      = $formData['motive'] ?? null;
        $duration    = $formData['duration'] ?? null;
        $siren       = $projectRequestManager->validateSiren($formData['siren']);
        $companyName = $formData['company_name'] ?? null;

        try {
            if (empty($formData) || false === is_array($formData) || empty($formData['amount']) || empty($formData['motive']) || empty($formData['duration'])) {
                throw new InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            if (false === empty($formData['siren'])) {
                $siren = $projectRequestManager->validateSiren($formData['siren']);
                $siren = $siren === false ? null : $siren;
                // We accept in the same field both siren and siret
                $siret = $projectRequestManager->validateSiret($formData['siren']);
                $siret = $siret === false ? null : $siret;
            } else {
                $siren = null;
                $siret = null;
            }

            /** @var Users $frontUser */
            $frontUser = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_FRONT);
            /** @var Companies $partnerCompany */
            $partnerCompany = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient')->findOneBy(['idClient' => $partnerUser]);

            $project = $projectRequestManager->newProject($frontUser, $partnerManager->getPartner($partnerUser), ProjectsStatus::SIMULATION, $amount, $siren, $siret, $companyName, null, $duration, $reason);
            $project
                ->setIdClientSubmitter($partnerUser)
                ->setIdCompanySubmitter($partnerCompany);

            $entityManager->flush($project);

            if ($siren) {
                $riskCheck = $projectRequestManager->checkProjectRisk($project, Users::USER_ID_FRONT);

                if (null === $riskCheck) {
                    $projectRequestManager->assignEligiblePartnerProduct($project, Users::USER_ID_FRONT, true);
                }
            } elseif (BorrowingMotive::ID_MOTIVE_FRANCHISER_CREATION !== $project->getIdBorrowingMotive()) {
                throw new \InvalidArgumentException();
            }

            return $this->redirectToRoute('partner_project_request_eligibility', ['hash' => $project->getHash()]);
        } catch (InvalidArgumentException $exception) {
            $errorMessageTranslationLabel = 'partner-project-request_required-fields-error';
            if (ProjectRequestManager::EXCEPTION_CODE_INVALID_AMOUNT === $exception->getCode()) {
                $errorMessageTranslationLabel = 'partner-project-request_amount-value-error';
            }

            $this->addFlash('partnerProjectRequestErrors', $translator->trans($errorMessageTranslationLabel));

            $request->getSession()->set('partnerProjectRequest', [
                'values' => [
                    'amount'   => $amount,
                    'siren'    => $siren,
                    'motive'   => $reason,
                    'duration' => $duration
                ]
            ]);

            return $this->redirect($request->headers->get('referer'));
        } catch (\Exception $exception) {
            $this->get('logger')->error('An error occurred while creating project: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);
        }

        return $this->redirectToRoute('partner_project_request');
    }

    /**
     * @Route("partenaire/depot/eligibilite/{hash}", name="partner_project_request_eligibility", requirements={"hash":"[a-z0-9-]{32,36}"})
     * @Method("GET")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param string                $hash
     * @param UserInterface|Clients $partnerUser
     *
     * @return Response
     */
    public function projectRequestEligibilityAction($hash, UserInterface $partnerUser): Response
    {
        $project = $this->checkProjectHash($partnerUser, $hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        if ($project->getStatus() > ProjectsStatus::SIMULATION) {
            return $this->redirectToRoute('partner_project_request_details', ['hash' => $project->getHash()]);
        }

        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $projectManager = $this->get('unilend.service.project_manager');
        $template       = [
            'amount'   => $project->getAmount(),
            'duration' => $project->getPeriod(),
            'company'  => $project->getIdCompany()->getName(),
            'siren'    => $project->getIdCompany()->getSiren(),
            'hash'     => $project->getHash()
        ];

        if ($project->getStatus() === ProjectsStatus::NOT_ELIGIBLE) {
            $projectRequestManager       = $this->get('unilend.service.project_request_manager');
            $template['rejectionReason'] = $projectRequestManager->getPartnerMainRejectionReasonMessage($project);
        } else {
            $monthlyPaymentBoundaries = $projectManager->getMonthlyPaymentBoundaries($project->getAmount(), $project->getPeriod(), $project->getCommissionRateRepayment());
            $projectAbandonReasonList = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')
                ->findBy(['status' => ProjectRejectionReason::STATUS_ONLINE], ['reason' => 'ASC']);
            $partner                  = $this->get('unilend.service.partner_manager')->getPartner($partnerUser);

            $template = $template + [
                    'averageFundingDuration' => $projectManager->getAverageFundingDuration($project->getAmount()),
                    'abandonReasons'         => $projectAbandonReasonList,
                    'prospect'               => false === $partner->getProspect(),
                    'payment'                => [
                        'monthly'   => [
                            'min' => $monthlyPaymentBoundaries['minimum'],
                            'max' => $monthlyPaymentBoundaries['maximum']
                        ],
                        'interests' => [
                            'min' => $monthlyPaymentBoundaries['minimum'] * $project->getPeriod() - $project->getAmount(),
                            'max' => $monthlyPaymentBoundaries['maximum'] * $project->getPeriod() - $project->getAmount()
                        ]
                    ]
                ];
        }

        return $this->render('/partner_account/project_request_eligibility.html.twig', $template);
    }

    /**
     * @Route("partenaire/depot/details/{hash}", name="partner_project_request_details", requirements={"hash":"[a-z0-9-]{32,36}"})
     * @Method("GET")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param string                $hash
     * @param Request               $request
     * @param UserInterface|Clients $partnerUser
     *
     * @return Response
     */
    public function projectRequestDetailsAction($hash, Request $request, UserInterface $partnerUser): Response
    {
        $project = $this->checkProjectHash($partnerUser, $hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        if ($project->getStatus() === ProjectsStatus::SIMULATION) {
            return $this->redirectToRoute('partner_project_request_eligibility', ['hash' => $project->getHash()]);
        } elseif ($project->getStatus() === ProjectsStatus::ABANDONED) {
            return $this->redirectToRoute('partner_project_request_end', ['hash' => $project->getHash()]);
        }

        $entityManager            = $this->get('doctrine.orm.entity_manager');
        $projectManager           = $this->get('unilend.service.project_manager');
        $client                   = $project->getIdCompany()->getIdClientOwner();
        $projectAbandonReasonList = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')
            ->findBy(['status' => ProjectAbandonReason::STATUS_ONLINE], ['reason' => 'ASC']);
        $partner                  = $this->get('unilend.service.partner_manager')->getPartner($partnerUser);

        try {
            $activeExecutives = $this->get('unilend.service.external_data_manager')->getActiveExecutives($project->getIdCompany()->getSiren());
        } catch (\Exception $exception) {
            $activeExecutives = [];

            $this->get('logger')->error('An error occurred while getting active executives: ' . $exception->getMessage(), [
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine(),
                'id_project' => $project->getIdProject()
            ]);
        }

        $template = [
            'project'                  => $project,
            'averageFundingDuration'   => $projectManager->getAverageFundingDuration($project->getAmount()),
            'monthlyPaymentBoundaries' => $projectManager->getMonthlyPaymentBoundaries($project->getAmount(), $project->getPeriod(), $project->getCommissionRateRepayment()),
            'abandonReasons'           => $projectAbandonReasonList,
            'attachments'              => $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProjectAttachment')->findBy(['idPartner' => $partner],
                ['rank' => 'ASC']),
            'activeExecutives'         => $activeExecutives
        ];

        $session = $request->getSession()->get('partnerProjectRequest');
        $values  = $session['values'] ?? [];

        $request->getSession()->remove('partnerProjectRequest');

        $title     = $values['contact']['title'] ?? $client->getCivilite();
        $lastName  = $values['contact']['lastName'] ?? $client->getNom();
        $firstName = $values['contact']['firstName'] ?? $client->getPrenom();
        $email     = $values['contact']['email'] ?? $this->removeEmailSuffix($client->getEmail());
        $mobile    = $values['contact']['mobile'] ?? $client->getTelephone();
        $function  = $values['contact']['function'] ?? $client->getFonction();
        // If one (last name) of these fields is empty, we can consider that all the field is empty
        $firstExecutiveFound = $template['activeExecutives'][0] ?? null;
        if (empty($lastName) && null !== $firstExecutiveFound) {
            $title     = $firstExecutiveFound['title'];
            $lastName  = $firstExecutiveFound['lastName'];
            $firstName = $firstExecutiveFound['firstName'];
            $function  = $firstExecutiveFound['position'];
        }

        $contactInList = false;
        if ($client->getNom() && false === empty($template['activeExecutives'])) {
            foreach ($template['activeExecutives'] as $executive) {
                if (mb_strtolower($executive['firstName'] . $executive['lastName']) === mb_strtolower($client->getPrenom() . $client->getNom())) {
                    $contactInList = true;
                    break;
                }
            }
        }
        $template['contactInList'] = $contactInList;
        $template['form']          = [
            'errors' => $session['errors'] ?? [],
            'values' => [
                'contact'      => [
                    'title'     => $title,
                    'lastName'  => $lastName,
                    'firstName' => $firstName,
                    'email'     => $email,
                    'mobile'    => $mobile,
                    'function'  => $function
                ],
                'otherContact' => [
                    'title'     => false === $contactInList ? $client->getCivilite() : '',
                    'lastName'  => false === $contactInList ? $client->getNom() : '',
                    'firstName' => false === $contactInList ? $client->getPrenom() : '',
                    'email'     => false === $contactInList ? $this->removeEmailSuffix($client->getEmail()) : '',
                    'mobile'    => false === $contactInList ? $client->getTelephone() : '',
                    'function'  => false === $contactInList ? $client->getFonction() : ''
                ],
                'project'      => [
                    'description' => $values['project']['description'] ?? $project->getComments()
                ]
            ]
        ];

        return $this->render('/partner_account/project_request_details.html.twig', $template);
    }

    /**
     * @Route("partenaire/depot/details/{hash}", name="partner_project_request_details_form", requirements={"hash":"[a-z0-9-]{32,36}"})
     * @Method("POST")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param string                $hash
     * @param Request               $request
     * @param UserInterface|Clients $partnerUser
     *
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function projectRequestDetailsFormAction($hash, Request $request, UserInterface $partnerUser): Response
    {
        $project = $this->checkProjectHash($partnerUser, $hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $errors      = [];
        $submit      = false === $request->request->has('save');
        $title       = $request->request->get('title');
        $lastName    = $request->request->get('lastName');
        $firstName   = $request->request->get('firstName');
        $email       = $request->request->get('email', FILTER_VALIDATE_EMAIL);
        $mobile      = $request->request->filter('mobile');
        $function    = $request->request->get('function');
        $description = $request->request->get('description');
        $documents   = $request->files->get('documents');

        $entityManager = $this->get('doctrine.orm.entity_manager');
        $partner       = $this->get('unilend.service.partner_manager')->getPartner($partnerUser);
        /** @var PartnerProjectAttachment[] $partnerAttachments */
        $partnerAttachments = $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProjectAttachment')->findBy(['idPartner' => $partner]);
        if ($submit) {
            if (empty($title) || false === empty($title) && false === in_array($title, [Clients::TITLE_MISS, Clients::TITLE_MISTER])) {
                $errors['contact']['title'] = true;
            }
            if (empty($lastName)) {
                $errors['contact']['lastName'] = true;
            }
            if (empty($firstName)) {
                $errors['contact']['firstName'] = true;
            }
            if (empty($email)) {
                $errors['contact']['email'] = true;
            }
            if (empty($mobile) || false === empty($mobile) && false === Loader::loadLib('ficelle')->isMobilePhoneNumber($mobile)) {
                $errors['contact']['mobile'] = true;
            }
            if (empty($function)) {
                $errors['contact']['function'] = true;
            }
            if (empty($description)) {
                $errors['project']['description'] = true;
            }

            foreach ($partnerAttachments as $partnerAttachment) {
                if (
                    $partnerAttachment->getMandatory()
                    && (
                        false === isset($documents[$partnerAttachment->getAttachmentType()->getId()])
                        || false === ($documents[$partnerAttachment->getAttachmentType()->getId()] instanceof UploadedFile)
                    )
                ) {
                    $errors['documents'][$partnerAttachment->getAttachmentType()->getId()] = true;
                }
            }
        }

        /** @var Clients $client */
        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $client           = $clientRepository->find($project->getIdCompany()->getIdClientOwner());

        if (empty($errors)) {
            $attachmentManager = $this->get('unilend.service.attachment_manager');

            foreach ($partnerAttachments as $partnerAttachment) {
                if (isset($documents[$partnerAttachment->getAttachmentType()->getId()]) && $documents[$partnerAttachment->getAttachmentType()->getId()] instanceof UploadedFile) {
                    try {
                        $attachment = $attachmentManager->upload($client, $partnerAttachment->getAttachmentType(), $documents[$partnerAttachment->getAttachmentType()->getId()]);
                        $attachmentManager->attachToProject($attachment, $project);
                    } catch (\Exception $exception) {
                        $errors['documents'][$partnerAttachment->getAttachmentType()->getId()] = true;
                    }
                }
            }
        }

        if (false === empty($errors)) {
            $request->getSession()->set('partnerProjectRequest', [
                'errors' => $errors,
                'values' => $request->request->all()
            ]);

            return $this->redirectToRoute('partner_project_request_details', ['hash' => $hash]);
        }

        if ($title) {
            $client->setCivilite($title);
        }
        if ($lastName) {
            $client->setNom($lastName);
        }
        if ($firstName) {
            $client->setPrenom($firstName);
        }
        if ($email) {
            $duplicates = $clientRepository->findGrantedLoginAccountByEmail($email);

            if (false === empty($duplicates)) {
                if ($this->removeEmailSuffix($client->getEmail()) === $email) {
                    $email = $client->getEmail();
                } else {
                    $email = $email . '-' . time();
                }
            }

            $client->setEmail($email);

            $this->get('unilend.service.client_status_manager')->addClientStatus($client, Users::USER_ID_FRONT, ClientsStatus::STATUS_VALIDATED);

            $project
                ->getIdCompany()
                ->setEmailDirigeant($email)
                ->setEmailFacture($email);

            $entityManager->flush($project->getIdCompany());
        }
        if ($mobile) {
            $client->setTelephone($mobile);
        }
        if ($function) {
            $client->setFonction($function);
        }

        $entityManager->flush($client);

        if ($description) {
            $project->setComments($description);

            $entityManager->flush($project);
        }

        if (false === $submit) {
            return $this->redirectToRoute('partner_project_request_details', ['hash' => $hash]);
        } elseif (false === in_array($project->getStatus(), [ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION, ProjectsStatus::COMPLETE_REQUEST])) {
            try {
                $termsOfSaleManager = $this->get('unilend.service.terms_of_sale_manager');
                $termsOfSaleManager->sendBorrowerEmail($project, $project->getIdCompanySubmitter());
            } catch (\Exception $exception) {
                $this->get('logger')->error('Error while sending terms of sale to partner borrower for project ' . $project->getIdProject(), [
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__,
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine()
                ]);
            }

            $this->get('unilend.service.project_status_manager')->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::COMPLETE_REQUEST, $project);
        }

        return $this->redirectToRoute('partner_project_request_end', ['hash' => $hash]);
    }

    /**
     * @Route("partenaire/depot/eligibilite", name="partner_project_request_submit")
     * @Method("POST")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function projectRequestSubmitAction(Request $request)
    {
        $hash = $request->request->get('hash');

        if (1 !== preg_match('/^[0-9a-f-]{32,36}$/', $hash)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $project = $this->checkProjectHash($partnerUser, $hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        if ($project->getStatus() === ProjectsStatus::SIMULATION) {
            /** @var \projects $projectData */
            $projectData = $this->get('unilend.service.entity_manager')->getRepository('projects');
            $projectData->get($project->getIdProject());

            $projectStatusManager = $this->get('unilend.service.project_status_manager');
            $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::INCOMPLETE_REQUEST, $projectData);
        }

        return $this->redirectToRoute('partner_project_request_details', ['hash' => $project->getHash()]);
    }

    /**
     * @Route("partenaire/depot/abandon", name="partner_project_request_abandon")
     * @Method("POST")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function projectRequestAbandon(Request $request)
    {
        $hash = $request->request->get('hash');

        if (1 !== preg_match('/^[0-9a-f-]{32,36}$/', $hash)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $project = $this->checkProjectHash($partnerUser, $hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $abandonReasonRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason');

        if (
            $project->getStatus() !== ProjectsStatus::ABANDONED
            && $request->request->get('reason')
            && ($abandonReason = $abandonReasonRepository->findBy(['idAbandon' => $request->request->get('reason')]))
        ) {
            $projectStatusManager = $this->get('unilend.service.project_status_manager');
            $projectStatusManager->abandonProject($project, $abandonReason, Users::USER_ID_FRONT);
        }

        return $this->redirectToRoute('partner_project_request_end', ['hash' => $hash]);
    }

    /**
     * @Route("partenaire/depot/fin/{hash}", name="partner_project_request_end", requirements={"hash":"[a-z0-9-]{32,36}"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param string $hash
     *
     * @return Response
     */
    public function projectRequestEndAction($hash)
    {
        $project = $this->checkProjectHash($partnerUser, $hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $translator = $this->get('translator');

        switch ($project->getStatus()) {
            case ProjectsStatus::ABANDONED:
                $template = [
                    'title'   => $translator->trans('partner-project-end_status-abandon-title'),
                    'message' => $translator->trans('partner-project-end_status-abandon-message'),
                ];
                break;
            case ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION:
            case ProjectsStatus::COMPLETE_REQUEST:
            default:
                $template = [
                    'title'   => $translator->trans('partner-project-end_status-completed-title'),
                    'message' => $translator->trans('partner-project-end_status-completed-message'),
                ];
                break;
        }

        return $this->render('/partner_account/project_request_end.html.twig', $template);
    }

    /**
     * @param Clients $partnerUser
     * @param string  $hash
     *
     * @return RedirectResponse|Projects
     */
    private function checkProjectHash(Clients $partnerUser, $hash)
    {
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $partnerManager    = $this->get('unilend.service.partner_manager');
        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);
        $userCompanies     = $partnerManager->getUserCompanies($partnerUser);

        if (false === ($project instanceof Projects) || false === in_array($project->getIdCompanySubmitter(), $userCompanies)) {
            return $this->redirectToRoute('partner_projects_list');
        }

        return $project;
    }

    /**
     * @param string $email
     *
     * @return string
     */
    private function removeEmailSuffix($email)
    {
        return preg_replace('/^(.*)-[0-9]+$/', '$1', $email);
    }
}
