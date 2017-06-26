<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Psr\Log\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\PartnerProduct;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;
use Unilend\Bundle\FrontBundle\Service\SourceManager;
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
        $borrowingMotive = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BorrowingMotive');

        $template = [
            'loanPeriods'      => $projectManager->getPossibleProjectPeriods(),
            'projectAmountMin' => $projectManager->getMinProjectAmount(),
            'projectAmountMax' => $projectManager->getMaxProjectAmount(),
            'borrowingMotives' => $borrowingMotive->findBy([], ['rank' => 'ASC']),
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
        $translator     = $this->get('translator');
        $projectManager = $this->get('unilend.service.project_manager');
        $entityManager  = $this->get('doctrine.orm.entity_manager');

        $amount   = null;
        $motive   = null;
        $duration = null;
        $siren    = null;
        $email    = null;

        try {
            $formData = $request->request->get('simulator');

            if (empty($formData) || false === is_array($formData) || empty($formData['amount']) || empty($formData['motive']) || empty($formData['duration']) || empty($formData['siren']) || empty($formData['email'])) {
                throw new InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            $minimumAmount = $projectManager->getMinProjectAmount();
            $maximumAmount = $projectManager->getMaxProjectAmount();
            $amount        = str_replace([' ', '€'], '', $formData['amount']);

            if (false === filter_var($amount, FILTER_VALIDATE_INT, ['options' => ['min_range' => $minimumAmount, 'max_range' => $maximumAmount]])) {
                throw new InvalidArgumentException($translator->trans('partner-project-request_amount-value-error'));
            }

            if (false === filter_var($formData['motive'], FILTER_VALIDATE_INT)) {
                throw new InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            $motive = $formData['motive'];

            if (false === filter_var($formData['duration'], FILTER_VALIDATE_INT)) {
                throw new InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            $duration    = $formData['duration'];
            $siren       = str_replace(' ', '', $formData['siren']);
            $sirenLength = strlen($siren);

            if (
                1 !== preg_match('/^[0-9]*$/', $siren)
                || false === in_array($sirenLength, [9, 14]) // SIRET numbers also allowed
            ) {
                throw new InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            $siren = substr($siren, 0, 9);

            if (false === filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            $email = $formData['email'];
        } catch (InvalidArgumentException $exception) {
            $this->addFlash('partnerProjectRequestErrors', $exception->getMessage());

            $request->getSession()->set('partnerProjectRequest', [
                'values' => [
                    'amount'   => $amount,
                    'siren'    => $siren,
                    'email'    => $email,
                    'motive'   => $motive,
                    'duration' => $duration
                ]
            ]);

            return $this->redirect($request->headers->get('referer'));
        }

        $sourceManager = $this->get('unilend.frontbundle.service.source_manager');

        if ($entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->existEmail($email)) {
            $email .= '-' . time();
        }

        $client = new Clients();
        $client
            ->setEmail($email)
            ->setIdLangue('fr')
            ->setStatus(Clients::STATUS_ONLINE)
            ->setSource($sourceManager->getSource(SourceManager::SOURCE1))
            ->setSource2($sourceManager->getSource(SourceManager::SOURCE2))
            ->setSource3($sourceManager->getSource(SourceManager::SOURCE3))
            ->setSlugOrigine($sourceManager->getSource(SourceManager::ENTRY_SLUG));

        $siret   = $sirenLength === 14 ? str_replace(' ', '', $formData['siren']) : '';
        $company = new Companies();
        $company->setSiren($siren)
            ->setSiret($siret)
            ->setStatusAdresseCorrespondance(1)
            ->setEmailDirigeant($email)
            ->setEmailFacture($email);

        $entityManager->beginTransaction();
        try {
            $entityManager->persist($client);

            $clientAddress = new ClientsAdresses();
            $clientAddress->setIdClient($client);

            $entityManager->persist($clientAddress);
            $entityManager->flush($clientAddress);

            $company->setIdClientOwner($client->getIdClient());

            $entityManager->persist($company);
            $entityManager->flush($company);

            $this->get('unilend.service.wallet_creation_manager')->createWallet($client, WalletType::PARTNER);

            $entityManager->commit();
        } catch (\Exception $exception) {
            $entityManager->getConnection()->rollBack();
            $this->get('logger')->error('An error occurred while creating client: ' . $exception->getMessage(), [['class' => __CLASS__, 'function' => __FUNCTION__]]);
        }

        if (empty($client->getIdClient())) {
            return $this->redirect($request->headers->get('referer'));
        }

        /** @var UserPartner $user */
        $user = $this->getUser();

        /** @var \projects $project */
        $project                                       = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $project->id_company                           = $company->getIdCompany();
        $project->amount                               = $amount;
        $project->period                               = $duration;
        $project->id_borrowing_motive                  = $motive;
        $project->ca_declara_client                    = 0;
        $project->resultat_exploitation_declara_client = 0;
        $project->fonds_propres_declara_client         = 0;
        $project->status                               = ProjectsStatus::SIMULATION;
        $project->id_partner                           = $user->getPartner()->getId();
        $project->commission_rate_funds                = \projects::DEFAULT_COMMISSION_RATE_FUNDS;
        $project->commission_rate_repayment            = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT;
        $project->id_company_submitter                 = $user->getCompany()->getIdCompany();
        $project->id_client_submitter                  = $user->getClientId();
        $project->create();

        $projectManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::SIMULATION, $project);

        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $riskCheck             = $projectRequestManager->checkProjectRisk($project, Users::USER_ID_FRONT);

        if (null === $riskCheck) {
            $projectRequestManager->assignEligiblePartnerProduct($project, Users::USER_ID_FRONT, true);
        }

        return $this->redirectToRoute('partner_project_request_eligibility', ['hash' => $project->hash]);
    }

    /**
     * @Route("partenaire/depot/eligibilite/{hash}", name="partner_project_request_eligibility", requirements={"hash":"[0-9a-z]{32,36}"})
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

        if ($project->getStatus() == ProjectsStatus::NOT_ELIGIBLE) {
            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->get('unilend.service.entity_manager')->getRepository('projects_status_history');
            $projectStatusHistory->loadLastProjectHistory($project->getIdProject());

            /** @var ProjectStatusManager $projectStatusManager */
            $projectStatusManager = $this->get('unilend.service.project_status_manager');
            $reason               = $projectStatusManager->getMainRejectionReason($projectStatusHistory->content);
            $borrowerServiceEmail = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse emprunteur']);

            switch ($reason) {
                case ProjectsStatus::NON_ELIGIBLE_REASON_UNKNOWN_SIREN:
                case ProjectsStatus::NON_ELIGIBLE_REASON_INACTIVE:
                    $translation = 'partner-project-request_not-eligible-reason-unknown-or-inactive-siren';
                    break;
                case ProjectsStatus::NON_ELIGIBLE_REASON_COMPANY_LOCATION:
                    $translation = 'partner-project-request_not-eligible-reason-company-location';
                    break;
                case ProjectsStatus::NON_ELIGIBLE_REASON_PROCEEDING:
                    $translation = 'partner-project-request_not-eligible-reason-collective-proceeding';
                    break;
                case ProjectsStatus::NON_ELIGIBLE_REASON_TOO_MUCH_PAYMENT_INCIDENT:
                case ProjectsStatus::NON_ELIGIBLE_REASON_NON_ALLOWED_PAYMENT_INCIDENT:
                case ProjectsStatus::NON_ELIGIBLE_REASON_UNILEND_XERFI_ELIMINATION_SCORE:
                case ProjectsStatus::NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_ALTARES_SCORE:
                case ProjectsStatus::NON_ELIGIBLE_REASON_LOW_ALTARES_SCORE:
                case ProjectsStatus::NON_ELIGIBLE_REASON_LOW_INFOLEGALE_SCORE:
                case ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT:
                case ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_ALTARES_SCORE:
                case ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI:
                case ProjectsStatus::NON_ELIGIBLE_REASON_EULER_GRADE_VS_ALTARES_SCORE:
                case ProjectsStatus::NON_ELIGIBLE_REASON_EULER_GRADE_VS_UNILEND_XERFI:
                case ProjectsStatus::NON_ELIGIBLE_REASON_INFOGREFFE_PRIVILEGES:
                case ProjectsStatus::NON_ELIGIBLE_REASON_ELLISPHERE_DEFAULTS:
                case ProjectsStatus::NON_ELIGIBLE_REASON_ELLISPHERE_SOCIAL_SECURITY_PRIVILEGES:
                case ProjectsStatus::NON_ELIGIBLE_REASON_ELLISPHERE_TREASURY_TAX_PRIVILEGES:
                    $translation = 'partner-project-request_not-eligible-reason-scoring';
                    break;
                case ProjectsStatus::NON_ELIGIBLE_REASON_LOW_TURNOVER:
                case ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK:
                case ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_EQUITY_CAPITAL:
                case ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES:
                    $translation = 'partner-project-request_not-eligible-reason-financial-data';
                    break;
                case ProjectsStatus::NON_ELIGIBLE_REASON_PRODUCT_NOT_FOUND:
                case ProjectsStatus::NON_ELIGIBLE_REASON_PRODUCT_BLEND:
                default:
                    $translation = 'partner-project-request_not-eligible-reason-no-product';
                    break;
            }

            $template['rejectionReason'] = $this->get('translator')->trans($translation, ['%borrowerServiceEmail%' => $borrowerServiceEmail->getValue()]);
        } else {
            $monthlyPaymentBoundaries = $projectManager->getMonthlyPaymentBoundaries($project->getAmount(), $project->getPeriod(), $project->getCommissionRateRepayment());

            $template = $template + [
                    'averageFundingDuration'   => $projectManager->getAverageFundingDuration($project->getAmount()),
                    'abandonReasons'           => $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')->findBy([], ['label' => 'ASC']),
                    'prospect'                 => false === $this->getUser()->getPartner()->getProspect(),
                    'payment'                  => [
                        'monthly' => [
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
     * @Route("partenaire/depot/details/{hash}", name="partner_project_request_details", requirements={"hash":"[0-9a-z]{32,36}"})
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

        if ($project->getStatus() == ProjectsStatus::SIMULATION) {
            return $this->redirectToRoute('partner_project_request_eligibility', ['hash' => $project->getHash()]);
        }

        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $projectManager = $this->get('unilend.service.project_manager');
        $client         = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());
        $template       = [
            'project'        => [
                'company'                  => $project->getIdCompany()->getName(),
                'siren'                    => $project->getIdCompany()->getSiren(),
                'amount'                   => $project->getAmount(),
                'duration'                 => $project->getPeriod(),
                'hash'                     => $project->getHash(),
                'averageFundingDuration'   => $projectManager->getAverageFundingDuration($project->getAmount()),
                'monthlyPaymentBoundaries' => $projectManager->getMonthlyPaymentBoundaries($project->getAmount(), $project->getPeriod(), $project->getCommissionRateRepayment())
            ],
            'abandonReasons' => $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')->findBy([], ['label' => 'ASC']),
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
     * @Route("partenaire/depot/details/{hash}", name="partner_project_request_details_form", requirements={"hash":"[0-9a-z]{32,36}"})
     * @Method("POST")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function projectRequestDetailsFormAction($hash, Request $request)
    {
        $project = $this->checkProjectHash($hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $errors = [];

        if (empty($request->request->get('contact')['title']) || false === in_array($request->request->get('contact')['title'], [Clients::TITLE_MISS, Clients::TITLE_MISTER])) {
            $errors['contact']['title'] = true;
        }
        if (empty($request->request->get('contact')['lastname'])) {
            $errors['contact']['lastname'] = true;
        }
        if (empty($request->request->get('contact')['firstname'])) {
            $errors['contact']['firstname'] = true;
        }
        if (
            empty($request->request->get('contact')['mobile'])
            || false === Loader::loadLib('ficelle')->isMobilePhoneNumber($request->request->get('contact')['mobile'])
        ) {
            $errors['contact']['mobile'] = true;
        }
        if (empty($request->request->get('contact')['function'])) {
            $errors['contact']['function'] = true;
        }
        if (empty($request->request->get('project')['description'])) {
            $errors['project']['description'] = true;
        }

        $documents                   = $request->files->get('documents');
        $entityManager               = $this->get('doctrine.orm.entity_manager');
        $partnerAttachmentRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProjectAttachment');
        $partnerAttachments          = $partnerAttachmentRepository->findBy(['idPartner' => $this->getUser()->getPartner()]);

        foreach ($partnerAttachments as $partnerAttachment) {
            if ($partnerAttachment->getMandatory() && (false === isset($documents[$partnerAttachment->getAttachmentType()->getId()]) || false === ($documents[$partnerAttachment->getAttachmentType()->getId()] instanceof UploadedFile))) {
                $errors['documents'][$partnerAttachment->getAttachmentType()->getId()] = true;
            }
        }

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

        $client->setCivilite($request->request->get('contact')['title']);
        $client->setNom($request->request->get('contact')['lastname']);
        $client->setPrenom($request->request->get('contact')['firstname']);
        $client->setTelephone($request->request->get('contact')['mobile']);
        $client->setFonction($request->request->get('contact')['function']);

        $entityManager->persist($client);

        $project->setComments($request->request->get('project')['description']);

        $entityManager->persist($project);
        $entityManager->flush();

        if (false === in_array($project->getStatus(), [ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION, ProjectsStatus::COMPLETE_REQUEST])) {
            /** @var \projects $projectData */
            $projectData = $this->get('unilend.service.entity_manager')->getRepository('projects');
            $projectData->get($project->getIdProject());

            $projectManager = $this->get('unilend.service.project_manager');
            $projectManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::COMPLETE_REQUEST, $projectData);
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
        $hash = $request->request->getAlnum('hash');

        if (1 !== preg_match('/^[0-9a-f]{32,36}$/', $hash)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $project = $this->checkProjectHash($hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        if ($project->getStatus() == ProjectsStatus::SIMULATION) {
            /** @var \projects $projectData */
            $projectData = $this->get('unilend.service.entity_manager')->getRepository('projects');
            $projectData->get($project->getIdProject());

            $projectManager = $this->get('unilend.service.project_manager');
            $projectManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::INCOMPLETE_REQUEST, $projectData);
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
        $hash = $request->request->getAlnum('hash');

        if (1 !== preg_match('/^[0-9a-f]{32,36}$/', $hash)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $project = $this->checkProjectHash($hash);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $abandonReasonRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason');

        if (
            $project->getStatus() != ProjectsStatus::ABANDONED
            && $request->request->getInt('reason')
            && ($abandonReason = $abandonReasonRepository->find($request->request->getInt('reason')))
        ) {
            /** @var \projects $projectData */
            $projectData = $this->get('unilend.service.entity_manager')->getRepository('projects');
            $projectData->get($project->getIdProject());

            $projectManager = $this->get('unilend.service.project_manager');
            $projectManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::ABANDONED, $projectData, 0, $abandonReason->getLabel());
        }

        return $this->redirectToRoute('partner_project_request_end', ['hash' => $hash]);
    }

    /**
     * @Route("partenaire/depot/fin/{hash}", name="partner_project_request_end", requirements={"hash":"[0-9a-z]{32,36}"})
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
}
