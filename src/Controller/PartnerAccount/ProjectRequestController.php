<?php

namespace Unilend\Controller\PartnerAccount;

use Psr\Log\InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{BorrowingMotive, Clients, ClientsStatus, CompanyClient, PartnerProjectAttachment, ProjectAbandonReason, ProjectRejectionReason, Projects, ProjectsStatus, Users};
use Unilend\Service\ProjectRequestManager;
use Unilend\core\Loader;

class ProjectRequestController extends Controller
{
    /**
     * @Route("partenaire/depot", name="partner_project_request", methods={"GET"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function projectRequestAction(Request $request): Response
    {
        $projectManager = $this->get('unilend.service.project_manager');

        $template = [
            'loanPeriods'      => $projectManager->getPossibleProjectPeriods(),
            'projectAmountMin' => $projectManager->getMinProjectAmount(),
            'projectAmountMax' => $projectManager->getMaxProjectAmount(),
            'borrowingMotives' => $this->get('doctrine.orm.entity_manager')->getRepository(BorrowingMotive::class)->findBy([], ['rank' => 'ASC']),
            'form'             => $request->getSession()->get('partnerProjectRequest', [])
        ];

        $request->getSession()->remove('partnerProjectRequest');

        return $this->render('/partner_account/project_request.html.twig', $template);
    }

    /**
     * @Route("partenaire/depot", name="partner_project_request_form", methods={"POST"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return RedirectResponse
     */
    public function projectRequestFormAction(Request $request, ?UserInterface $partnerUser): RedirectResponse
    {
        $translator            = $this->get('translator');
        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $partnerManager        = $this->get('unilend.service.partner_manager');

        $formData    = $request->request->get('simulator');
        $amount      = $formData['amount'] ?? null;
        $duration    = $formData['duration'] ?? null;

        try {
            if (empty($formData) || false === is_array($formData) || empty($formData['amount']) || empty($formData['duration'])) {
                throw new InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            /** @var Users $frontUser */
            $frontUser = $entityManager->getRepository(Users::class)->find(Users::USER_ID_FRONT);
            /** @var CompanyClient $partnerRole */
            $partnerRole    = $entityManager->getRepository(CompanyClient::class)->findOneBy(['idClient' => $partnerUser]);
            $partnerCompany = null;
            if ($partnerRole) {
                $partnerCompany = $partnerRole->getIdCompany();
            }

            $project = $projectRequestManager->newProject($frontUser, $partnerManager->getPartner($partnerUser), ProjectsStatus::STATUS_REQUEST, $amount, null, null, null, null, $duration);
            $project
                ->setIdClientSubmitter($partnerUser)
                ->setIdCompanySubmitter($partnerCompany);

            $entityManager->flush($project);

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
     * @Route("partenaire/depot/eligibilite/{hash}", name="partner_project_request_eligibility",
     *     requirements={"hash":"[a-z0-9-]{32,36}"}, methods={"GET"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param string                     $hash
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     */
    public function projectRequestEligibilityAction(string $hash, ?UserInterface $partnerUser): Response
    {
        $project = $this->checkProjectHash($partnerUser, $hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        if ($project->getStatus() > ProjectsStatus::STATUS_REQUEST) {
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

        if ($project->getStatus() === ProjectsStatus::STATUS_CANCELLED) {
            $projectRequestManager       = $this->get('unilend.service.project_request_manager');
            $template['rejectionReason'] = $projectRequestManager->getPartnerMainRejectionReasonMessage($project);
        } else {
            $monthlyPaymentBoundaries = $projectManager->getMonthlyPaymentBoundaries($project->getAmount(), $project->getPeriod(), $project->getCommissionRateRepayment());
            $projectAbandonReasonList = $entityManager->getRepository(ProjectAbandonReason::class)
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
     * @Route("partenaire/depot/details/{hash}", name="partner_project_request_details",
     *     requirements={"hash":"[a-z0-9-]{32,36}"}, methods={"GET"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param string                     $hash
     * @param Request                    $request
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     */
    public function projectRequestDetailsAction(string $hash, Request $request, ?UserInterface $partnerUser): Response
    {
        $project = $this->checkProjectHash($partnerUser, $hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        if ($project->getStatus() === ProjectsStatus::STATUS_CANCELLED) {
            return $this->redirectToRoute('partner_project_request_end', ['hash' => $project->getHash()]);
        }

        $entityManager            = $this->get('doctrine.orm.entity_manager');
        $projectManager           = $this->get('unilend.service.project_manager');
        $client                   = $project->getIdCompany()->getIdClientOwner();
        $projectAbandonReasonList = $entityManager->getRepository(ProjectAbandonReason::class)
            ->findBy(['status' => ProjectAbandonReason::STATUS_ONLINE], ['reason' => 'ASC']);
        $partner                  = $this->get('unilend.service.partner_manager')->getPartner($partnerUser);

        try {
            $activeExecutives = [];
//            $activeExecutives = $this->get('unilend.service.external_data_manager')->getActiveExecutives($project->getIdCompany()->getSiren());
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
            'attachments'              => $entityManager->getRepository(PartnerProjectAttachment::class)->findBy(['idPartner' => $partner], ['rank' => 'ASC']),
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
     * @Route("partenaire/depot/details/{hash}", name="partner_project_request_details_form",
     *     requirements={"hash":"[a-z0-9-]{32,36}"}, methods={"POST"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param string                     $hash
     * @param Request                    $request
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function projectRequestDetailsFormAction(string $hash, Request $request, ?UserInterface $partnerUser): Response
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
        $partnerAttachments = $entityManager->getRepository(PartnerProjectAttachment::class)->findBy(['idPartner' => $partner]);
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
        $clientRepository = $entityManager->getRepository(Clients::class);
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
            $duplicates = $clientRepository->findGrantedLoginAccountsByEmail($email);

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
            $project->setDescription($description);

            $entityManager->flush($project);
        }

        if (false === $submit) {
            return $this->redirectToRoute('partner_project_request_details', ['hash' => $hash]);
        } elseif (false === in_array($project->getStatus(), [ProjectsStatus::STATUS_CANCELLED, ProjectsStatus::STATUS_REVIEW])) {
            try {
                $termsOfSaleManager = $this->get('unilend.service.terms_of_sale_manager');
                $termsOfSaleManager->sendBorrowerEmail($project, $project->getIdCompanySubmitter());
            } catch (\Exception $exception) {
                $this->get('logger')->error('Error while sending terms of sale to partner borrower for project ' . $project->getIdProject() . ': ' . $exception->getMessage(), [
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__,
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine()
                ]);
            }

            $this->get('unilend.service.project_status_manager')->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::STATUS_REVIEW, $project);
        }

        return $this->redirectToRoute('partner_project_request_end', ['hash' => $hash]);
    }

    /**
     * @Route("partenaire/depot/publier/{hash}", name="partner_project_request_online", requirements={"hash":"[a-z0-9-]{32,36}"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param string                     $hash
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function projectRequestOnlineAction(string $hash, ?UserInterface $partnerUser): Response
    {
        $project = $this->checkProjectHash($partnerUser, $hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $projectStatusManager = $this->get('unilend.service.project_status_manager');
        $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::STATUS_ONLINE, $project);

        return $this->redirectToRoute('partner_projects_list');
    }

    /**
     * @Route("partenaire/depot/eligibilite", name="partner_project_request_submit", methods={"POST"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     */
    public function projectRequestSubmitAction(Request $request, ?UserInterface $partnerUser): Response
    {
        $hash = $request->request->get('hash');

        if (1 !== preg_match('/^[0-9a-f-]{32,36}$/', $hash)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $project = $this->checkProjectHash($partnerUser, $hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        if ($project->getStatus() === ProjectsStatus::STATUS_REQUEST) {
            /** @var \projects $projectData */
            $projectData = $this->get('unilend.service.entity_manager')->getRepository('projects');
            $projectData->get($project->getIdProject());

            $projectStatusManager = $this->get('unilend.service.project_status_manager');
            $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::STATUS_REQUEST, $projectData);
        }

        return $this->redirectToRoute('partner_project_request_details', ['hash' => $project->getHash()]);
    }

    /**
     * @Route("partenaire/depot/abandon", name="partner_project_request_abandon", methods={"POST"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     */
    public function projectRequestAbandon(Request $request, ?UserInterface $partnerUser): Response
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
        $abandonReasonRepository = $entityManager->getRepository(ProjectAbandonReason::class);

        if (
            $project->getStatus() !== ProjectsStatus::STATUS_CANCELLED
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
     * @param string                     $hash
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     */
    public function projectRequestEndAction(string $hash, ?UserInterface $partnerUser): Response
    {
        $project = $this->checkProjectHash($partnerUser, $hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $translator = $this->get('translator');

        switch ($project->getStatus()) {
            case ProjectsStatus::STATUS_CANCELLED:
                $template = [
                    'title'   => $translator->trans('partner-project-end_status-abandon-title'),
                    'message' => $translator->trans('partner-project-end_status-abandon-message'),
                ];
                break;
            case ProjectsStatus::STATUS_REVIEW:
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
        $projectRepository = $entityManager->getRepository(Projects::class);
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
