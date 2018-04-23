<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Psr\Log\InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\{
    Method, Route, Security
};
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{
    RedirectResponse, Request, Response
};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientsStatus, Companies, PartnerProjectAttachment, ProjectAbandonReason, ProjectRejectionReason, Projects, ProjectsStatus, Users
};
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;
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
        $projectManager  = $this->get('unilend.service.project_manager');

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
     *
     * @return RedirectResponse
     */
    public function projectRequestFormAction(Request $request)
    {
        $translator            = $this->get('translator');
        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $entityManager         = $this->get('doctrine.orm.entity_manager');

        $formData = $request->request->get('simulator');
        $amount   = $formData['amount'] ?? null;
        $reason   = $formData['motive'] ?? null;
        $duration = $formData['duration'] ?? null;
        $siren    = $projectRequestManager->validateSiren($formData['siren']);

        try {
            if (empty($formData) || false === is_array($formData) || empty($formData['amount']) || empty($formData['motive']) || empty($formData['duration']) || empty($formData['siren'])) {
                throw new InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }
            // We accept in the same field both siren and siret
            $siret = $projectRequestManager->validateSiret($formData['siren']);
            $siret = $siret === false ? null : $siret;

            $frontUser = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_FRONT);
            /** @var UserPartner $partnerUser */
            $partnerUser = $this->getUser();

            $project = $projectRequestManager->newProject($frontUser, $partnerUser->getPartner(), $amount, $siren, $siret, null, $duration, $reason);

            $submitter = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($partnerUser->getClientId());
            $project->setIdClientSubmitter($submitter)
                ->setIdCompanySubmitter($partnerUser->getCompany());

            $entityManager->flush($project);

            $riskCheck = $projectRequestManager->checkProjectRisk($project, Users::USER_ID_FRONT);

            if (null === $riskCheck) {
                $projectRequestManager->assignEligiblePartnerProduct($project, Users::USER_ID_FRONT, true);
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
     * @param string $hash
     *
     * @return Response
     */
    public function projectRequestEligibilityAction($hash)
    {
        $project = $this->checkProjectHash($hash);

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

            $template = $template + [
                    'averageFundingDuration' => $projectManager->getAverageFundingDuration($project->getAmount()),
                    'abandonReasons'         => $projectAbandonReasonList,
                    'prospect'               => false === $this->getUser()->getPartner()->getProspect(),
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
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function projectRequestDetailsAction($hash, Request $request)
    {
        $project = $this->checkProjectHash($hash);

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
        $client                   = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());
        $projectAbandonReasonList = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')
            ->findBy(['status' => ProjectAbandonReason::STATUS_ONLINE], ['reason' => 'ASC']);

        $template                 = [
            'project'        => [
                'company'                  => $project->getIdCompany()->getName(),
                'siren'                    => $project->getIdCompany()->getSiren(),
                'amount'                   => $project->getAmount(),
                'duration'                 => $project->getPeriod(),
                'hash'                     => $project->getHash(),
                'averageFundingDuration'   => $projectManager->getAverageFundingDuration($project->getAmount()),
                'monthlyPaymentBoundaries' => $projectManager->getMonthlyPaymentBoundaries($project->getAmount(), $project->getPeriod(), $project->getCommissionRateRepayment())
            ],
            'abandonReasons' => $projectAbandonReasonList,
            'attachments'    => $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProjectAttachment')->findBy(['idPartner' => $this->getUser()->getPartner()], ['rank' => 'ASC'])
        ];

        $session = $request->getSession()->get('partnerProjectRequest');
        $errors  = isset($session['errors']) ? $session['errors'] : [];
        $values  = isset($session['values']) ? $session['values'] : [];

        $request->getSession()->remove('partnerProjectRequest');

        $template['form'] = [
            'errors' => $errors,
            'values' => [
                'contact' => [
                    'title'     => isset($values['contact']['title']) ? $values['contact']['title'] : $client->getCivilite(),
                    'lastname'  => isset($values['contact']['lastname']) ? $values['contact']['lastname'] : $client->getNom(),
                    'firstname' => isset($values['contact']['firstname']) ? $values['contact']['firstname'] : $client->getPrenom(),
                    'email'     => isset($values['contact']['email']) ? $values['contact']['email'] : $this->removeEmailSuffix($client->getEmail()),
                    'mobile'    => isset($values['contact']['mobile']) ? $values['contact']['mobile'] : $client->getTelephone(),
                    'function'  => isset($values['contact']['function']) ? $values['contact']['function'] : $client->getFonction()
                ],
                'project' => [
                    'description' => isset($values['project']['description']) ? $values['project']['description'] : $project->getComments()
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
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function projectRequestDetailsFormAction($hash, Request $request)
    {
        $project = $this->checkProjectHash($hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $errors     = [];
        $action     = $request->request->get('action', 'submit');
        $checkEmpty = 'save' !== $action;

        if (
            $checkEmpty && empty($request->request->get('contact')['title'])
            || false === empty($request->request->get('contact')['title']) && false === in_array($request->request->get('contact')['title'], [Clients::TITLE_MISS, Clients::TITLE_MISTER])) {
            $errors['contact']['title'] = true;
        }
        if ($checkEmpty && empty($request->request->get('contact')['lastname'])) {
            $errors['contact']['lastname'] = true;
        }
        if ($checkEmpty && empty($request->request->get('contact')['firstname'])) {
            $errors['contact']['firstname'] = true;
        }
        if (
            $checkEmpty
            && (
                empty($request->request->get('contact')['email'])
                || false === filter_var($request->request->get('contact')['email'], FILTER_VALIDATE_EMAIL)
            )
        ) {
            $errors['contact']['email'] = true;
        }
        if (
            $checkEmpty && empty($request->request->get('contact')['mobile'])
            || false === empty($request->request->get('contact')['mobile']) && false === Loader::loadLib('ficelle')->isMobilePhoneNumber($request->request->get('contact')['mobile'])
        ) {
            $errors['contact']['mobile'] = true;
        }
        if ($checkEmpty && empty($request->request->get('contact')['function'])) {
            $errors['contact']['function'] = true;
        }
        if ($checkEmpty && empty($request->request->get('project')['description'])) {
            $errors['project']['description'] = true;
        }

        $documents                   = $request->files->get('documents');
        $entityManager               = $this->get('doctrine.orm.entity_manager');
        $partnerAttachmentRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProjectAttachment');
        /** @var PartnerProjectAttachment[] $partnerAttachments */
        $partnerAttachments = $partnerAttachmentRepository->findBy(['idPartner' => $this->getUser()->getPartner()]);

        if ($checkEmpty) {
            foreach ($partnerAttachments as $partnerAttachment) {
                if ($partnerAttachment->getMandatory() && (false === isset($documents[$partnerAttachment->getAttachmentType()->getId()]) || false === ($documents[$partnerAttachment->getAttachmentType()->getId()] instanceof UploadedFile))) {
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

        if (isset($request->request->get('contact')['title'])) {
            $client->setCivilite($request->request->get('contact')['title']);
        }
        if (isset($request->request->get('contact')['lastname'])) {
            $client->setNom($request->request->get('contact')['lastname']);
        }
        if (isset($request->request->get('contact')['firstname'])) {
            $client->setPrenom($request->request->get('contact')['firstname']);
        }
        if (isset($request->request->get('contact')['email'])) {
            $email      = $request->request->get('contact')['email'];
            $duplicates = $clientRepository->findByEmailAndStatus($email, ClientsStatus::GRANTED_LOGIN);

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
        if (isset($request->request->get('contact')['mobile'])) {
            $client->setTelephone($request->request->get('contact')['mobile']);
        }
        if (isset($request->request->get('contact')['function'])) {
            $client->setFonction($request->request->get('contact')['function']);
        }

        $entityManager->flush($client);

        if (isset($request->request->get('project')['description'])) {
            $project->setComments($request->request->get('project')['description']);

            $entityManager->flush($project);
        }

        if ('save' === $action) {
            return $this->redirectToRoute('partner_project_request_details', ['hash' => $hash]);
        } elseif (false === in_array($project->getStatus(), [ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION, ProjectsStatus::COMPLETE_REQUEST])) {
            try {
                $termsOfSaleManager = $this->get('unilend.service.terms_of_sale_manager');
                $termsOfSaleManager->sendBorrowerEmail($project, $project->getIdCompanySubmitter());
            } catch (\Exception $exception) {
                $this->get('logger')->error(
                    'Error while sending terms of sale to partner borrower for project ' . $project->getIdProject(),
                    ['id_project' => $project->getIdProject(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }

            /** @var \projects $projectData */
            $projectData = $this->get('unilend.service.entity_manager')->getRepository('projects');
            $projectData->get($project->getIdProject());

            $projectStatusManager = $this->get('unilend.service.project_status_manager');
            $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::COMPLETE_REQUEST, $projectData);
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

        $project = $this->checkProjectHash($hash);

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

        $project = $this->checkProjectHash($hash);

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
        $project = $this->checkProjectHash($hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $translator = $this->get('translator');

        switch ($project->getStatus()) {
            case ProjectsStatus::ABANDONED:
                $template = [
                    'title' => $translator->trans('partner-project-end_status-abandon-title'),
                    'message' => $translator->trans('partner-project-end_status-abandon-message'),
                ];
                break;
            case ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION:
            case ProjectsStatus::COMPLETE_REQUEST:
            default:
                $template = [
                    'title' => $translator->trans('partner-project-end_status-completed-title'),
                    'message' => $translator->trans('partner-project-end_status-completed-message'),
                ];
                break;
        }

        return $this->render('/partner_account/project_request_end.html.twig', $template);
    }

    /**
     * @return Companies[]
     */
    private function getUserCompanies()
    {
        /** @var UserPartner $user */
        $user      = $this->getUser();
        $companies = [$user->getCompany()];

        if (in_array(UserPartner::ROLE_ADMIN, $user->getRoles())) {
            $companies = $this->getCompanyTree($user->getCompany(), $companies);
        }

        return $companies;
    }

    /**
     * @param Companies $rootCompany
     * @param array     $tree
     *
     * @return Companies[]
     */
    private function getCompanyTree(Companies $rootCompany, array $tree)
    {
        $childCompanies = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Companies')
            ->findBy(['idParentCompany' => $rootCompany]);

        foreach ($childCompanies as $company) {
            $tree[] = $company;
            $tree = $this->getCompanyTree($company, $tree);
        }

        return $tree;
    }

    /**
     * @param string $hash
     *
     * @return RedirectResponse|Projects
     */
    private function checkProjectHash($hash)
    {
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);
        $userCompanies     = $this->getUserCompanies();

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
