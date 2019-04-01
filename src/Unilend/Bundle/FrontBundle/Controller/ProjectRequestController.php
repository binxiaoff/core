<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Unilend\Entity\{AttachmentType, BorrowingMotive, Clients, Companies, CompaniesBilans, Partner, ProjectAbandonReason, ProjectAttachmentType, ProjectRejectionReason, Projects, ProjectsStatus,
    Settings, Tree, Users};
use Unilend\Bundle\CoreBusinessBundle\Service\{ProjectRequestManager, ProjectStatusManager};
use Unilend\Bundle\FrontBundle\Service\{DataLayerCollector};
use Unilend\core\Loader;

class ProjectRequestController extends Controller
{
    const PAGE_ROUTE_LANDING_PAGE_START = 'project_request_landing_page_start';
    const PAGE_ROUTE_SIMULATOR_START    = 'project_request_simulator_start';
    const PAGE_ROUTE_CONTACT            = 'project_request_contact';
    const PAGE_ROUTE_FINANCE            = 'project_request_finance';
    const PAGE_ROUTE_PROSPECT           = 'project_request_prospect';
    const PAGE_ROUTE_FILES              = 'project_request_files';
    const PAGE_ROUTE_PARTNER            = 'project_request_partner';
    const PAGE_ROUTE_END                = 'project_request_end';
    const PAGE_ROUTE_EMAILS             = 'project_request_emails';
    const PAGE_ROUTE_INDEX              = 'project_request_index';
    const PAGE_ROUTE_RECOVERY           = 'project_request_recovery';
    const PAGE_ROUTE_STAND_BY           = 'project_request_stand_by';

    /**
     * @Route("/depot_de_dossier/{hash}", name="project_request_index", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Route("/depot_de_dossier/reprise/{hash}", name="project_request_recovery", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Route("/depot_de_dossier/stand_by/{hash}", name="project_request_stand_by", requirements={"hash": "[0-9a-f-]{32,36}"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(string $hash, Request $request): Response
    {
        $project = $this->checkProjectHash(self::PAGE_ROUTE_INDEX, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        return $this->redirectToRoute('home_borrower');
    }

    /**
     * @Route("/depot_de_dossier/etape1", name="project_request_landing_page_start")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function landingPageStartAction(Request $request): Response
    {
        if ($request->isMethod(Request::METHOD_GET)) {
            return $this->redirectToRoute('home_borrower', ['_fragment' => 'homeemp-section-esim']);
        }

        $translator            = $this->get('translator');
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $projectRequestManager = $this->get('unilend.service.project_request_manager');

        $amount      = $request->request->get('amount');
        $siren       = $request->request->get('siren');
        $email       = $request->request->get('email');
        $reason      = $request->request->getInt('reason');
        $duration    = $request->request->getInt('duration');
        $partnerId   = $request->request->getInt('partner');
        $companyName = $request->request->get('company_name');

        if (empty($partnerId) || null === $partner = $entityManager->getRepository(Partner::class)->find($partnerId)) {
            $partnerManager = $this->get('unilend.service.partner_manager');
            $partner        = $partnerManager->getDefaultPartner();
        }

        if ($request->getSession()->get('partnerProjectRequest')) {
            $email = null;
        }

        try {
            if (null === $amount || (null === $email && empty($request->getSession()->get('partnerProjectRequest'))) || 0 === $reason || 0 === $duration) {
                throw new \InvalidArgumentException();
            }
            if (false === empty($siren)) {
                $siren = $projectRequestManager->validateSiren($siren);
                $siren = $siren === false ? null : $siren;
                // We accept in the same field both siren and siret
                $siret = $projectRequestManager->validateSiret($request->request->get('siren'));
                $siret = $siret === false ? null : $siret;
            } else {
                $siren = null;
                $siret = null;
            }
            $user = $entityManager->getRepository(Users::class)->find(Users::USER_ID_FRONT);

            $project = $projectRequestManager->newProject($user, $partner, ProjectsStatus::STATUS_REQUEST, $amount, $siren, $siret, $companyName, $email, $duration, $reason);

            $client = $project->getIdCompany()->getIdClientOwner();
            $request->getSession()->set(DataLayerCollector::SESSION_KEY_CLIENT_EMAIL, $client->getEmail());
            $request->getSession()->set(DataLayerCollector::SESSION_KEY_BORROWER_CLIENT_ID, $client->getIdClient());

            if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
                $this->get('security.token_storage')->setToken(null);
            }

            return $this->start($project);
        } catch (\InvalidArgumentException $exception) {
            if (ProjectRequestManager::EXCEPTION_CODE_INVALID_AMOUNT === $exception->getCode()) {
                $this->addFlash('borrowerLandingPageErrors', $translator->trans('borrower-landing-page_amount-value-error'));
            } else {
                $this->addFlash('borrowerLandingPageErrors', $translator->trans('borrower-landing-page_required-fields-error'));
            }

            $request->getSession()->set('projectRequest', [
                'values' => [
                    'amount' => $amount,
                    'siren'  => $siren,
                    'email'  => $email
                ]
            ]);

            return $this->redirect($request->headers->get('referer'));
        } catch (\Exception $exception) {
            $this->get('logger')->error('An error occurred while creating client: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);

            return $this->redirectToRoute('home_borrower', ['_fragment' => 'homeemp-section-esim']);
        }
    }

    /**
     * @Route("/depot_de_dossier/simulateur", name="project_request_simulator_start", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function simulatorStartAction(Request $request): Response
    {
        if (empty($request->query->get('hash'))) {
            return $this->redirectToRoute('home_borrower');
        }

        $project = $this->checkProjectHash(self::PAGE_ROUTE_SIMULATOR_START, $request->query->get('hash'), $request);

        if ($project instanceof Response) {
            return $project;
        }

        return $this->start($project);
    }

    /**
     * @param Projects $project
     *
     * @return RedirectResponse
     */
    private function start(Projects $project): RedirectResponse
    {
        $projectRequestManager = $this->get('unilend.service.project_request_manager');
//        if ($project->getIdCompany()->getSiren()) {
//            $projectRequestManager->checkProjectRisk($project, Users::USER_ID_FRONT);
//        } elseif (BorrowingMotive::ID_MOTIVE_FRANCHISER_CREATION === $project->getIdBorrowingMotive()) {
//            return $this->redirectStatus($project, self::PAGE_ROUTE_CONTACT, ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION);
//        } else {
//            return $this->redirectStatus($project, self::PAGE_ROUTE_PROSPECT, ProjectsStatus::STATUS_CANCELLED, ProjectsStatus::NON_ELIGIBLE_REASON_UNKNOWN_SIREN);
//        }
//
//        if (ProjectsStatus::STATUS_CANCELLED == $project->getStatus()) {
//            return $this->redirectToRoute(self::PAGE_ROUTE_PROSPECT, ['hash' => $project->getHash()]);
//        }

        $numberOfProductsFound = $projectRequestManager->assignEligiblePartnerProduct($project, Users::USER_ID_FRONT, false);

        if (0 === $numberOfProductsFound) {
            return $this->redirectStatus($project, self::PAGE_ROUTE_END, ProjectsStatus::STATUS_CANCELLED, ProjectRejectionReason::PRODUCT_NOT_FOUND);
        }

        return $this->redirectToRoute(self::PAGE_ROUTE_CONTACT, ['hash' => $project->getHash()]);
    }

    /**
     * @Route("/depot_de_dossier/etape2/{hash}", name="project_request_contact",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"GET"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function contactAction(string $hash, Request $request): Response
    {
        $template = [];
        $project  = $this->checkProjectHash(self::PAGE_ROUTE_CONTACT, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');

        $settingsRepository             = $entityManager->getRepository(Settings::class);
        $treeId                         = $settingsRepository->findOneBy(['type' => 'Lien conditions generales depot dossier']);
        $tree                           = $entityManager->getRepository(Tree::class)->findOneBy(['idTree' => $treeId->getValue()]);
        $template['terms_of_sale_link'] = $this->generateUrl($tree->getSlug());

        $availablePeriods         = $settingsRepository->findOneBy(['type' => 'Durée des prêts autorisées']);
        $template['loan_periods'] = explode(',', $availablePeriods->getValue());

        $session = $request->getSession()->get('projectRequest');
        $values  = $session['values'] ?? [];

        if (null === $project->getIdCompany() || null === $project->getIdCompany()->getIdClientOwner()) {
            $this->get('logger')->error('An error occurred while creating project. Client or Company is empty', [
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'id_project' => $project->getIdProject()
            ]);

            return $this->redirectToRoute('home_borrower', ['_fragment' => 'homeemp-section-esim']);
        }

        $contact   = $project->getIdCompany()->getIdClientOwner();
        $title     = $values['contact']['title'] ?? $contact->getCivilite();
        $lastName  = $values['contact']['lastname'] ?? $contact->getNom();
        $firstName = $values['contact']['firstname'] ?? $contact->getPrenom();
        $email     = $values['contact']['email'] ?? $this->removeEmailSuffix($contact->getEmail());
        $mobile    = $values['contact']['mobile'] ?? $contact->getTelephone();
        $function  = $values['contact']['function'] ?? $contact->getFonction();

        try {
            $template['activeExecutives'] = $this->get('unilend.service.external_data_manager')->getActiveExecutives($project->getIdCompany()->getSiren());
        } catch (\Exception $exception) {
            $template['activeExecutives'] = [];

            $this->get('logger')->error('An error occurred while getting active executives: ' . $exception->getMessage(), [
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine(),
                'id_project' => $project->getIdProject()
            ]);
        }
        // If one (last name) of these fields is empty, we can consider that all the field is empty
        $firstExecutiveFound = $template['activeExecutives'][0] ?? null;
        if (empty($lastName) && null !== $firstExecutiveFound) {
            $title     = $firstExecutiveFound['title'];
            $lastName  = $firstExecutiveFound['lastName'];
            $firstName = $firstExecutiveFound['firstName'];
            $function  = $firstExecutiveFound['position'];
        }

        $contactInList = false;
        if ($contact->getNom() && false === empty($template['activeExecutives'])) {
            foreach ($template['activeExecutives'] as $executive) {
                if (mb_strtolower($executive['firstName'] . $executive['lastName']) === mb_strtolower($contact->getPrenom() . $contact->getNom())) {
                    $contactInList = true;
                    break;
                }
            }
        }

        $template['form']                   = [
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
                    'title'     => false === $contactInList ? $contact->getCivilite() : '',
                    'lastName'  => false === $contactInList ? $contact->getNom() : '',
                    'firstName' => false === $contactInList ? $contact->getPrenom() : '',
                    'email'     => false === $contactInList ? $this->removeEmailSuffix($contact->getEmail()) : '',
                    'mobile'    => false === $contactInList ? $contact->getTelephone() : '',
                    'function'  => false === $contactInList ? $contact->getFonction() : ''
                ],
                'project'      => [
                    'duration'    => $values['project']['duration'] ?? $project->getPeriod(),
                    'description' => $values['project']['description'] ?? $project->getComments()
                ]
            ]
        ];
        $template['contactInList']          = $contactInList;
        $template['project']                = $project;
        $template['averageFundingDuration'] = $this->get('unilend.service.project_manager')->getAverageFundingDuration($project->getAmount());

        $lastBalanceSheet = null;
        if ($project->getIdDernierBilan()) {
            $lastBalanceSheet = $entityManager->getRepository(CompaniesBilans::class)->find($project->getIdDernierBilan());
        }
        $template['lastBalanceSheet'] = $lastBalanceSheet;

        $request->getSession()->remove('projectRequest');

        return $this->render('project_request/contact.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/etape2/{hash}", name="project_request_contact_form",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"POST"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function contactFormAction(string $hash, Request $request): Response
    {
        $project = $this->checkProjectHash(self::PAGE_ROUTE_CONTACT, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');

        $settingsRepository = $entityManager->getRepository(Settings::class);

        $availablePeriods = $settingsRepository->findOneBy(['type' => 'Durée des prêts autorisées']);

        $title       = $request->request->get('title');
        $lastName    = $request->request->get('lastName');
        $firstName   = $request->request->get('firstName');
        $email       = $request->request->get('email', FILTER_VALIDATE_EMAIL);
        $mobile      = $request->request->filter('mobile');
        $function    = $request->request->get('function');
        $duration    = $request->request->get('duration');
        $description = $request->request->get('description');

        $errors = [];
        if (empty($title) || false === in_array($title, [Clients::TITLE_MISS, Clients::TITLE_MISTER])) {
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
        if (empty($mobile)) {
            $errors['contact']['mobile'] = true;
        }
        if (empty($function)) {
            $errors['contact']['function'] = true;
        }
        if (empty($duration) || ($availablePeriods && false === in_array($duration, explode(',', $availablePeriods->getValue())))) {
            $errors['project']['duration'] = true;
        }
        if (empty($description)) {
            $errors['project']['description'] = true;
        }

        if (false === empty($errors)) {
            $request->getSession()->set('projectRequest', [
                'values' => $request->request->all(),
                'errors' => $errors
            ]);

            return $this->redirectToRoute(self::PAGE_ROUTE_CONTACT, ['hash' => $project->getHash()]);
        }

        $this->saveContactDetails($project->getIdCompany(), $email, $title, $firstName, $lastName, $function, $mobile);

        /** @var \acceptations_legal_docs $tosAcceptation */
        $tosAcceptation = $entityManagerSimulator->getRepository('acceptations_legal_docs');

        $treeId = $settingsRepository->findOneBy(['type' => 'Lien conditions generales depot dossier']);

        if ($tosAcceptation->get($treeId->getValue(), 'id_client = ' . $project->getIdCompany()->getIdClientOwner()->getIdClient() . ' AND id_legal_doc')) {
            $tosAcceptation->update();
        } else {
            $tosAcceptation->id_legal_doc = $treeId->getValue();
            $tosAcceptation->id_client    = $project->getIdCompany()->getIdClientOwner()->getIdClient();
            $tosAcceptation->create();
        }

        $project->setPeriod($duration);
        $project->setComments($description);

        $entityManager->flush($project);

        if (ProjectsStatus::STATUS_CANCELLED == $project->getStatus()) {
            return $this->redirectToRoute(self::PAGE_ROUTE_FINANCE, ['hash' => $project->getHash()]);
        }

        return $this->redirectStatus($project, self::PAGE_ROUTE_FINANCE, ProjectsStatus::STATUS_REVIEW);
    }

    /**
     * @Route("/depot_de_dossier/etape3/{hash}", name="project_request_finance",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"GET"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function financeAction(string $hash, Request $request): Response
    {
        $template = [];
        $project  = $this->checkProjectHash(self::PAGE_ROUTE_FINANCE, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        if (BorrowingMotive::ID_MOTIVE_FRANCHISER_CREATION === $project->getIdBorrowingMotive()) {
            return $this->redirectStatus($project, self::PAGE_ROUTE_END, ProjectsStatus::STATUS_REVIEW);
        }

        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \companies_actif_passif $companyAssetsDebts */
        $companyAssetsDebts = $entityManagerSimulator->getRepository('companies_actif_passif');
        /** @var \companies_bilans $annualAccountsData */
        $annualAccountsData = $entityManagerSimulator->getRepository('companies_bilans');
        $partner            = $project->getIdPartner();

        $template['attachmentTypes'] = [];
        if ($partner) {
            $partnerAttachmentTypes = $partner->getAttachmentTypes(true);
            foreach ($partnerAttachmentTypes as $partnerAttachmentType) {
                $template['attachmentTypes'][] = $partnerAttachmentType->getAttachmentType();
            }
        }
        $balanceSheetValues['altaresCapitalStock']     = null;
        $balanceSheetValues['altaresOperationIncomes'] = null;
        $balanceSheetValues['altaresRevenue']          = null;
        $annualAccounts                                = $annualAccountsData->select('id_company = ' . $project->getIdCompany()->getIdCompany(), 'cloture_exercice_fiscal DESC', 0, 1);

        $request->getSession()->remove('companyBalanceSheetValues');

        if (false === empty($annualAccounts)) {
            $companyAssetsDebts->get($annualAccounts[0]['id_bilan'], 'id_bilan');
            $annualAccountsData->get($annualAccounts[0]['id_bilan']);
            $incomeStatement                               = $this->get('unilend.service.company_balance_sheet_manager')->getIncomeStatement($annualAccountsData);
            $balanceSheetValues['altaresCapitalStock']     = $companyAssetsDebts->capitaux_propres;
            $balanceSheetValues['altaresOperationIncomes'] = $incomeStatement['details']['project-detail_finance-column-resultat-exploitation'];
            $balanceSheetValues['altaresRevenue']          = $incomeStatement['details']['project-detail_finance-column-ca'];
        }

        $request->getSession()->set('companyBalanceSheetValues', $balanceSheetValues);

        $session = $request->getSession()->get('projectRequest');
        $values  = isset($session['values']) ? $session['values'] : [];

        $template['form']['errors'] = isset($session['errors']) ? $session['errors'] : [];

        if (empty($project->getIdCompany()->getRcs())) {
            $template['rcs']            = false;
            $template['form']['values'] = [];

            if (isset($values['ag_2035'])) {
                $template['form']['values']['ag_2035'] = $values['ag_2035'];
            } elseif (false === empty($project->getCaDeclaraClient())) {
                $template['form']['values']['ag_2035'] = $project->getCaDeclaraClient();
            } elseif (null !== $balanceSheetValues['altaresRevenue']) {
                $template['form']['values']['ag_2035'] = $balanceSheetValues['altaresRevenue'];
            } else {
                $template['form']['values']['ag_2035'] = '';
            }
        } else {
            $template['rcs']            = true;
            $template['form']['values'] = [];

            if (isset($values['dl'])) {
                $template['form']['values']['dl'] = $values['dl'];
            } elseif (false === empty($project->getFondsPropresDeclaraClient())) {
                $template['form']['values']['dl'] = $project->getFondsPropresDeclaraClient();
            } elseif (null !== $balanceSheetValues['altaresCapitalStock']) {
                $template['form']['values']['dl'] = $balanceSheetValues['altaresCapitalStock'];
            } else {
                $template['form']['values']['dl'] = '';
            }

            if (isset($values['fl'])) {
                $template['form']['values']['fl'] = $values['fl'];
            } elseif (false === empty($project->getCaDeclaraClient())) {
                $template['form']['values']['fl'] = $project->getCaDeclaraClient();
            } elseif (null !== $balanceSheetValues['altaresRevenue']) {
                $template['form']['values']['fl'] = $balanceSheetValues['altaresRevenue'];
            } else {
                $template['form']['values']['fl'] = '';
            }

            if (isset($values['gg'])) {
                $template['form']['values']['gg'] = $values['gg'];
            } elseif (false === empty($project->getResultatExploitationDeclaraClient())) {
                $template['form']['values']['gg'] = $project->getResultatExploitationDeclaraClient();
            } elseif (null !== $balanceSheetValues['altaresOperationIncomes']) {
                $template['form']['values']['gg'] = $balanceSheetValues['altaresOperationIncomes'];
            } else {
                $template['form']['values']['gg'] = '';
            }
        }

        $projectManager = $this->get('unilend.service.project_manager');

        $template['project'] = [
            'amount'                   => $project->getAmount(),
            'averageFundingDuration'   => $projectManager->getAverageFundingDuration($project->getAmount()),
            'monthlyPaymentBoundaries' => $projectManager->getMonthlyPaymentBoundaries($project->getAmount(), $project->getPeriod(), $project->getCommissionRateRepayment()),
            'hash'                     => $project->getHash()
        ];

        $request->getSession()->remove('projectRequest');

        return $this->render('project_request/finance.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/etape3/{hash}", name="project_request_finance_form",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"POST"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function financeFormAction(string $hash, Request $request): Response
    {
        $project = $this->checkProjectHash(self::PAGE_ROUTE_FINANCE, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        $attachmentManager = $this->get('unilend.service.attachment_manager');
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $logger            = $this->get('logger');

        $errors = [];
        $values = $request->request->get('finance');
        $values = is_array($values) ? $values : [];

        if (empty($project->getIdCompany()->getRcs())) {
            if (false === isset($values['ag_2035']) || $values['ag_2035'] === '') {
                $errors['ag_2035'] = true;
            }
        } else {
            if (false === isset($values['dl']) || $values['dl'] === '') {
                $errors['dl'] = true;
            }
            if (false === isset($values['fl']) || $values['fl'] === '') {
                $errors['fl'] = true;
            }
            if (false === isset($values['gg']) || $values['gg'] === '') {
                $errors['gg'] = true;
            }
        }
        $taxReturnFile = $request->files->get('accounts');
        if ($taxReturnFile instanceof UploadedFile && $project->getIdCompany()->getIdClientOwner() instanceof Clients) {
            try {
                $attachmentType = $entityManager->getRepository(AttachmentType::class)->find(AttachmentType::DERNIERE_LIASSE_FISCAL);
                $attachment     = $attachmentManager->upload($project->getIdCompany()->getIdClientOwner(), $attachmentType, $taxReturnFile, false);
                $attachmentManager->attachToProject($attachment, $project);
            } catch (\Exception $exception) {
                $logger->error('Cannot upload the file. Error : ' . $exception->getMessage(), ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
                $errors['accounts'] = true;
            }
        } else {
            $errors['accounts'] = true;
        }

        if (false === empty($errors)) {
            $request->getSession()->set('projectRequest', [
                'values' => $values,
                'errors' => $errors
            ]);

            return $this->redirectToRoute(self::PAGE_ROUTE_FINANCE, ['hash' => $project->getHash()]);
        }

        if ('true' === $request->request->get('extra_files')) {
            $files     = $request->files->all();
            $fileTypes = $request->request->get('files', []);
            foreach ($files as $inputName => $file) {
                if ('accounts' !== $inputName && $file instanceof UploadedFile && false === empty($fileTypes[$inputName])) {
                    $attachmentTypeId = $fileTypes[$inputName];
                    try {
                        $attachmentType = $entityManager->getRepository(AttachmentType::class)->find($attachmentTypeId);
                        $attachment     = $attachmentManager->upload($project->getIdCompany()->getIdClientOwner(), $attachmentType, $file, false);
                        $attachmentManager->attachToProject($attachment, $project);
                    } catch (\Exception $exception) {
                        $logger->error('Cannot upload the file. Error : ' . $exception->getMessage(), ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
                        continue;
                    }
                }
            }
        }

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        if (empty($project->getIdCompany()->getRcs())) {
            $project->setCaDeclaraClient($ficelle->cleanFormatedNumber($values['ag_2035']));
            $updateDeclaration = true;
        } else {
            $updateDeclaration = false;
            $values['dl']      = $ficelle->cleanFormatedNumber($values['dl']);
            $values['fl']      = $ficelle->cleanFormatedNumber($values['fl']);
            $values['gg']      = $ficelle->cleanFormatedNumber($values['gg']);

            $balanceSheetValues = $request->getSession()->get('companyBalanceSheetValues');

            if ($balanceSheetValues['altaresCapitalStock'] != $values['dl']) {
                $project->setFondsPropresDeclaraClient($values['dl']);
                $updateDeclaration = true;
            } elseif (false === empty($project->getFondsPropresDeclaraClient()) && $balanceSheetValues['altaresCapitalStock'] == $values['dl']) {
                $project->setFondsPropresDeclaraClient(0);
                $updateDeclaration = true;
            }

            if ($balanceSheetValues['altaresRevenue'] != $values['fl']) {
                $project->setCaDeclaraClient($values['fl']);
                $updateDeclaration = true;
            } elseif (false === empty($project->getCaDeclaraClient()) && $balanceSheetValues['altaresRevenue'] == $values['fl']) {
                $project->setCaDeclaraClient(0);
                $updateDeclaration = true;
            }

            if ($balanceSheetValues['altaresOperationIncomes'] != $values['gg']) {
                $project->setResultatExploitationDeclaraClient($values['gg']);
                $updateDeclaration = true;
            } elseif (false === empty($project->getResultatExploitationDeclaraClient()) && $balanceSheetValues['altaresOperationIncomes'] == $values['gg']) {
                $project->setResultatExploitationDeclaraClient(0);
                $updateDeclaration = true;
            }
        }

        if ($updateDeclaration) {
            $entityManager->flush($project);
        }

        if (isset($values['dl']) && $values['dl'] < 0) {
            return $this->redirectStatus($project, self::PAGE_ROUTE_END, ProjectsStatus::STATUS_CANCELLED, ProjectRejectionReason::NEGATIVE_EQUITY_CAPITAL);
        }

        if (isset($values['fl']) && $values['fl'] < ProjectRequestManager::MINIMUM_REVENUE) {
            return $this->redirectStatus($project, self::PAGE_ROUTE_END, ProjectsStatus::STATUS_CANCELLED, ProjectRejectionReason::LOW_TURNOVER);
        }

        if (isset($values['gg']) && $values['gg'] < 0) {
            return $this->redirectStatus($project, self::PAGE_ROUTE_END, ProjectsStatus::STATUS_CANCELLED, ProjectRejectionReason::NEGATIVE_RAW_OPERATING_INCOMES);
        }

        if (isset($values['ag_2035']) && $values['ag_2035'] < ProjectRequestManager::MINIMUM_REVENUE) {
            return $this->redirectStatus($project, self::PAGE_ROUTE_END, ProjectsStatus::STATUS_CANCELLED, ProjectRejectionReason::LOW_TURNOVER);
        }

        if ('true' === $request->request->get('extra_files')) {
            return $this->redirectToRoute(self::PAGE_ROUTE_FILES, ['hash' => $project->getHash()]);
        }

        $this->sendSubscriptionConfirmationEmail($project);

        return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $project->getHash()]);
    }

    /**
     * @Route("/depot_de_dossier/partenaire/{hash}", name="project_request_partner",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"GET"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function partnerAction(string $hash, Request $request): Response
    {
        $template = [];
        $project  = $this->checkProjectHash(self::PAGE_ROUTE_PARTNER, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $partnerAttachments     = $project->getIdPartner()->getAttachmentTypes();
        /** @var \settings $settings */
        $settings = $entityManagerSimulator->getRepository('settings');

        $labels                      = [];
        $template['attachmentTypes'] = [];
        foreach ($partnerAttachments as $partnerAttachment) {
            $template['attachmentTypes'][] = $partnerAttachment->getAttachmentType();
            $labels[]                      = $partnerAttachment->getAttachmentType()->getLabel();
        }

        array_multisort($labels, SORT_ASC, $template['attachmentTypes']);

        $settings->get('Lien conditions generales depot dossier', 'type');

        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');
        $tree->get(['id_tree' => $settings->value]);
        $template['terms_of_sale_link'] = $this->generateUrl($tree->slug);

        $settings->get('Adresse emprunteur', 'type');
        $template['borrower_service_email'] = $settings->value;

        $settings->get('Durée des prêts autorisées', 'type');
        $template['loan_periods'] = explode(',', $settings->value);

        $session = $request->getSession()->get('projectRequest');
        $values  = isset($session['values']) ? $session['values'] : [];

        $template['form'] = [
            'errors' => isset($session['errors']) ? $session['errors'] : [],
            'values' => [
                'contact' => [
                    'civility'  => isset($values['contact']['civility']) ? $values['contact']['civility'] : $project->getIdCompany()->getIdClientOwner()->getCivilite(),
                    'lastname'  => isset($values['contact']['lastname']) ? $values['contact']['lastname'] : $project->getIdCompany()->getIdClientOwner()->getNom(),
                    'firstname' => isset($values['contact']['firstname']) ? $values['contact']['firstname'] : $project->getIdCompany()->getIdClientOwner()->getPrenom(),
                    'email'     => isset($values['contact']['email']) ? $values['contact']['email'] : $this->removeEmailSuffix($project->getIdCompany()->getIdClientOwner()->getEmail()),
                    'mobile'    => isset($values['contact']['mobile']) ? $values['contact']['mobile'] : $project->getIdCompany()->getIdClientOwner()->getTelephone(),
                    'function'  => isset($values['contact']['function']) ? $values['contact']['function'] : $project->getIdCompany()->getIdClientOwner()->getFonction()
                ],
                'project' => [
                    'duration'    => isset($values['project']['duration']) ? $values['project']['duration'] : $project->getPeriod(),
                    'description' => isset($values['project']['description']) ? $values['project']['description'] : $project->getComments()
                ]
            ]
        ];

        $template['project'] = [
            'company_name' => $project->getIdCompany()->getName(),
            'siren'        => $project->getIdCompany()->getSiren(),
            'amount'       => $project->getAmount(),
            'hash'         => $project->getHash()
        ];

        $request->getSession()->remove('projectRequest');

        return $this->render('project_request/partner.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/partenaire/{hash}", name="project_request_partner_form",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"POST"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function partnerFormAction(string $hash, Request $request): Response
    {
        $project = $this->checkProjectHash(self::PAGE_ROUTE_PARTNER, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $attachmentManager      = $this->get('unilend.service.attachment_manager');
        $logger                 = $this->get('logger');
        /** @var \settings $settings */
        $settings = $entityManagerSimulator->getRepository('settings');

        $settings->get('Durée des prêts autorisées', 'type');
        $loanPeriods = explode(',', $settings->value);

        $errors = [];

        if (empty($request->request->get('contact')['civility']) || false === in_array($request->request->get('contact')['civility'], ['Mme', 'M.'])) {
            $errors['contact']['civility'] = true;
        }
        if (empty($request->request->get('contact')['lastname'])) {
            $errors['contact']['lastname'] = true;
        }
        if (empty($request->request->get('contact')['firstname'])) {
            $errors['contact']['firstname'] = true;
        }
        if (empty($request->request->get('contact')['email']) || false === filter_var($request->request->get('contact')['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact']['email'] = true;
        }
        if (empty($request->request->get('contact')['mobile'])) {
            $errors['contact']['mobile'] = true;
        }
        if (empty($request->request->get('contact')['function'])) {
            $errors['contact']['function'] = true;
        }
        if (empty($request->request->get('project')['duration']) || false === in_array($request->request->get('project')['duration'], $loanPeriods)) {
            $errors['project']['duration'] = true;
        }
        if (empty($request->request->get('project')['description'])) {
            $errors['project']['description'] = true;
        }
        if (empty($request->request->get('terms'))) {
            $errors['terms'] = true;
        }

        if (false === empty($errors)) {
            $request->getSession()->set('projectRequest', [
                'values' => $request->request->all(),
                'errors' => $errors
            ]);

            return $this->redirectToRoute(self::PAGE_ROUTE_PARTNER, ['hash' => $project->getHash()]);
        }

        $this->saveContactDetails(
            $project->getIdCompany(),
            $request->request->get('contact')['email'],
            $request->request->get('contact')['civility'],
            $request->request->get('contact')['firstname'],
            $request->request->get('contact')['lastname'],
            $request->request->get('contact')['function'],
            $request->request->get('contact')['mobile']
        );

        /** @var \acceptations_legal_docs $tosAcceptation */
        $tosAcceptation = $entityManagerSimulator->getRepository('acceptations_legal_docs');
        $settings->get('Lien conditions generales depot dossier', 'type');

        if ($tosAcceptation->get($settings->value, 'id_client = ' . $project->getIdCompany()->getIdClientOwner()->getIdClient() . ' AND id_legal_doc')) {
            $tosAcceptation->update();
        } else {
            $tosAcceptation->id_legal_doc = $settings->value;
            $tosAcceptation->id_client    = $project->getIdCompany()->getIdClientOwner()->getIdClient();
            $tosAcceptation->create();
        }

        if ($duration = filter_var($request->request->get('project')['duration'], FILTER_VALIDATE_INT)) {
            $project->setPeriod($duration);
        }
        $project->setComments($request->request->get('project')['description']);

        $entityManager->flush($project);

        $files     = $request->files->all();
        $fileTypes = $request->request->get('files', []);
        foreach ($files as $inputName => $file) {
            if ($file instanceof UploadedFile && false === empty($fileTypes[$inputName])) {
                $attachmentTypeId = $fileTypes[$inputName];
                try {
                    $attachmentType = $entityManager->getRepository(AttachmentType::class)->find($attachmentTypeId);
                    $attachment     = $attachmentManager->upload($project->getIdCompany()->getIdClientOwner(), $attachmentType, $file, false);
                    $attachmentManager->attachToProject($attachment, $project);
                } catch (\Exception $exception) {
                    $logger->error('Cannot upload the file. Error : ' . $exception->getMessage(), ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
                    continue;
                }
            }
        }

        $this->sendSubscriptionConfirmationEmail($project);

        return $this->redirectStatus($project, self::PAGE_ROUTE_END, ProjectsStatus::STATUS_REVIEW);
    }

    /**
     * @Route("/depot_de_dossier/prospect/{hash}", name="project_request_prospect",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"GET"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function prospectAction(string $hash, Request $request): Response
    {
        $project = $this->checkProjectHash(self::PAGE_ROUTE_PROSPECT, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        $session = $request->getSession()->get('projectRequest');
        $values  = isset($session['values']) ? $session['values'] : [];

        $template = [
            'form'    => [
                'errors' => isset($session['errors']) ? $session['errors'] : [],
                'values' => [
                    'civility'  => isset($values['civility']) ? $values['civility'] : $project->getIdCompany()->getIdClientOwner()->getCivilite(),
                    'lastname'  => isset($values['lastname']) ? $values['lastname'] : $project->getIdCompany()->getIdClientOwner()->getNom(),
                    'firstname' => isset($values['firstname']) ? $values['firstname'] : $project->getIdCompany()->getIdClientOwner()->getPrenom(),
                    'email'     => isset($values['email']) ? $values['email'] : $this->removeEmailSuffix($project->getIdCompany()->getIdClientOwner()->getEmail()),
                    'mobile'    => isset($values['mobile']) ? $values['mobile'] : $project->getIdCompany()->getIdClientOwner()->getTelephone(),
                    'function'  => isset($values['function']) ? $values['function'] : $project->getIdCompany()->getIdClientOwner()->getFonction()
                ]
            ],
            'project' => [
                'hash' => $project->getHash()
            ]
        ];

        $request->getSession()->remove('projectRequest');

        return $this->render('project_request/prospect.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/prospect/{hash}", name="project_request_prospect_form",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"POST"})
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function prospectFormAction(string $hash, Request $request): Response
    {
        $project = $this->checkProjectHash(self::PAGE_ROUTE_PROSPECT, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        $errors = [];

        if (empty($request->request->get('civility')) || false === in_array($request->request->get('civility'), ['Mme', 'M.'])) {
            $errors['civility'] = true;
        }
        if (empty($request->request->get('lastname'))) {
            $errors['lastname'] = true;
        }
        if (empty($request->request->get('firstname'))) {
            $errors['firstname'] = true;
        }
        if (empty($request->request->get('email')) || false === filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = true;
        }
        if (empty($request->request->get('mobile'))) {
            $errors['mobile'] = true;
        }
        if (empty($request->request->get('function'))) {
            $errors['function'] = true;
        }

        if (false === empty($errors)) {
            $request->getSession()->set('projectRequest', [
                'values' => $request->request->all(),
                'errors' => $errors
            ]);

            return $this->redirectToRoute(self::PAGE_ROUTE_PROSPECT, ['hash' => $project->getHash()]);
        }

        $this->saveContactDetails(
            $project->getIdCompany(),
            $request->request->get('email'),
            $request->request->get('civility'),
            $request->request->get('firstname'),
            $request->request->get('lastname'),
            $request->request->get('function'),
            $request->request->get('mobile')
        );

        return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $project->getHash()]);
    }

    /**
     * @Route("/depot_de_dossier/fichiers/{hash}", name="project_request_files",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"GET"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function filesAction(string $hash, Request $request): Response
    {
        $project = $this->checkProjectHash(self::PAGE_ROUTE_FILES, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        $projectManager = $this->get('unilend.service.project_manager');

        $template = [
            'project' => [
                'amount'                   => $project->getAmount(),
                'averageFundingDuration'   => $projectManager->getAverageFundingDuration($project->getAmount()),
                'monthlyPaymentBoundaries' => $projectManager->getMonthlyPaymentBoundaries($project->getAmount(), $project->getPeriod(), $project->getCommissionRateRepayment()),
                'hash'                     => $project->getHash()
            ]
        ];

        $attachmentTypes    = [];
        $partnerAttachments = $project->getIdPartner()->getAttachmentTypes();
        foreach ($partnerAttachments as $partnerAttachment) {
            $attachmentTypes[$partnerAttachment->getAttachmentType()->getId()] = $partnerAttachment->getAttachmentType();
        }

        $maxItemsByAttachmentType = [];
        $entityManager            = $this->get('doctrine.orm.entity_manager');
        $projectAttachmentTypes   = $entityManager->getRepository(ProjectAttachmentType::class)->findBy([
            'idType' => $attachmentTypes
        ]);
        foreach ($projectAttachmentTypes as $projectAttachmentType) {
            $maxItemsByAttachmentType[$projectAttachmentType->getIdType()->getId()] = $projectAttachmentType->getMaxItems();
        }

        $itemsByAttachmentType = [];
        $projectAttachments    = $project->getAttachments();
        foreach ($projectAttachments as $projectAttachment) {
            $attachmentTypeId = $projectAttachment->getAttachment()->getType()->getId();

            if (false === isset($itemsByAttachmentType[$attachmentTypeId])) {
                $itemsByAttachmentType[$attachmentTypeId] = 0;
            }

            ++$itemsByAttachmentType[$attachmentTypeId];

            if (
                false === isset($maxItemsByAttachmentType[$attachmentTypeId]) && $itemsByAttachmentType[$attachmentTypeId] > 1
                || isset($maxItemsByAttachmentType[$attachmentTypeId]) && $itemsByAttachmentType[$attachmentTypeId] >= $maxItemsByAttachmentType[$attachmentTypeId]
            ) {
                unset($attachmentTypes[$attachmentTypeId]);
            }
        }

        $template['attachment_types'] = $attachmentTypes;

        /** @var \projects_status_history $projectStatusHistory */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $projectStatusHistory   = $entityManagerSimulator->getRepository('projects_status_history');
        $projectStatusHistory->loadLastProjectHistory($project->getIdProject());

        if (false === empty($projectStatusHistory->content)) {
            $oDOMElement = new \DOMDocument();
            $oDOMElement->loadHTML($projectStatusHistory->content);
            $oList = $oDOMElement->getElementsByTagName('ul');
            if ($oList->length > 0 && $oList->item(0)->childNodes->length > 0) {
                $template['attachments_list'] = $oList->item(0)->C14N();
            }
        }

        return $this->render('project_request/files.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/fichiers/{hash}", name="project_request_files_form",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"POST"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function filesFormAction(string $hash, Request $request): Response
    {
        $project = $this->checkProjectHash(self::PAGE_ROUTE_FILES, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        $attachmentManager = $this->get('unilend.service.attachment_manager');
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $logger            = $this->get('logger');

        $files     = $request->files->all();
        $fileTypes = $request->request->get('files', []);
        foreach ($files as $inputName => $file) {
            if ($file instanceof UploadedFile && false === empty($fileTypes[$inputName])) {
                $attachmentTypeId = $fileTypes[$inputName];
                try {
                    $attachmentType = $entityManager->getRepository(AttachmentType::class)->find($attachmentTypeId);
                    $attachment     = $attachmentManager->upload($project->getIdCompany()->getIdClientOwner(), $attachmentType, $file, false);
                    $attachmentManager->attachToProject($attachment, $project);
                } catch (\Exception $exception) {
                    $logger->error('Cannot upload the file. Error : ' . $exception->getMessage(), ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
                    continue;
                }
            }
        }

        $this->sendCommercialEmail($project, 'notification-ajout-document-dossier');

        return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $project->getHash()]);
    }

    /**
     * @Route("/depot_de_dossier/fin/{hash}", name="project_request_end",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"GET"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function endAction(string $hash, Request $request): Response
    {
        $project = $this->checkProjectHash(self::PAGE_ROUTE_END, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        $translator   = $this->get('translator');
        $addMoreFiles = false;

        switch ($project->getStatus()) {
            case ProjectsStatus::STATUS_CANCELLED:
                $title    = $translator->trans('project-request_end-page-aborted-title');
                $subtitle = $translator->trans('project-request_end-page-aborted-subtitle');
                $message  = $translator->trans('project-request_end-page-aborted-message');
                break;
            case ProjectsStatus::STATUS_REVIEW:
                $title    = $translator->trans('project-request_end-page-processing-title');
                $subtitle = $translator->trans('project-request_end-page-processing-subtitle');
                $message  = $translator->trans('project-request_end-page-analysis-in-progress-message');
                break;
            default:
                $title    = $translator->trans('project-request_end-page-rejection-title');
                $subtitle = $translator->trans('project-request_end-page-rejection-subtitle');

                $projectRequestManager = $this->get('unilend.service.project_request_manager');
                $message               = $projectRequestManager->getMainRejectionReasonMessage($project);
                break;
        }

        $template = [
            'addMoreFiles' => $addMoreFiles,
            'message'      => $message,
            'title'        => $title,
            'subtitle'     => $subtitle,
            'project'      => [
                'hash' => $project->getHash()
            ]
        ];

        return $this->render('project_request/end.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/emails/{hash}", name="project_request_emails",
     *     requirements={"hash": "[0-9a-f-]{32,36}"}, methods={"GET"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function emailsAction(string $hash, Request $request): Response
    {
        $project = $this->checkProjectHash(self::PAGE_ROUTE_EMAILS, $hash, $request);

        if ($project instanceof Response) {
            return $project;
        }

        /** @var ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->get('unilend.service.project_status_manager');
        $abandonReason        = $this->get('doctrine.orm.entity_manager')->getRepository(ProjectAbandonReason::class)
            ->findBy(['label' => ProjectAbandonReason::UNSUBSCRIBE_FROM_EMAIL_REMINDER]);

        $projectStatusManager->abandonProject($project, $abandonReason, Users::USER_ID_FRONT);

        return $this->render('project_request/emails.html.twig');
    }

    /**
     * @param Projects $project
     */
    private function sendSubscriptionConfirmationEmail(Projects $project): void
    {
        $client   = $project->getIdCompany()->getIdClientOwner();
        $keywords = [
            'firstName'           => $client->getPrenom(),
            'companyName'         => $project->getIdCompany()->getName(),
            'continueRequestLink' => $this->generateUrl('project_request_recovery', ['hash' => $project->getHash()], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        $sRecipient = $client->getEmail();
        $sRecipient = $this->removeEmailSuffix(trim($sRecipient));
        $message    = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-depot-de-dossier', $keywords);

        try {
            $message->setTo($sRecipient);
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning(
                'Could not send email: confirmation-depot-de-dossier - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Projects $project
     * @param string   $emailType
     */
    private function sendCommercialEmail(Projects $project, string $emailType): void
    {
        if ($project->getIdCommercial()) {
            $user = $project->getIdCommercial();

            $aReplacements = [
                '[ID_PROJET]'      => $project->getIdProject(),
                '[LIEN_BO_PROJET]' => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_admin') . '/dossiers/edit/' . $project->getIdProject(),
                '[RAISON_SOCIALE]' => $project->getIdCompany()->getName(),
                '[SURL]'           => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_default')
            ];

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($emailType, $aReplacements, false);
            try {
                $message->setTo(trim($user->getEmail()));
                $mailer = $this->get('mailer');
                $mailer->send($message);
            } catch (\Exception $exception) {
                $this->get('logger')->warning(
                    'Could not send email: ' . $emailType . ' - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'email address' => trim($user->getEmail()), 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }
        }
    }

    /**
     * Check that hash is present in URL and valid
     * If hash is valid, check status and redirect to appropriate page
     *
     * @param string  $route
     * @param string  $hash
     * @param Request $request
     *
     * @return Response|Projects
     */
    private function checkProjectHash(string $route, string $hash, Request $request)
    {
        if (1 !== preg_match('/^[a-z0-9-]{32,36}$/', $hash)) {
            throw new NotFoundHttpException('Invalid project hash');
        }

        /** @var Response|Projects $project */
        $project = $this->get('doctrine.orm.entity_manager')->getRepository(Projects::class)->findOneBy(['hash' => $hash]);

        if (null === $project) {
            return $this->redirectToRoute('home_borrower');
        }

        if (self::PAGE_ROUTE_EMAILS === $route) {
            return $project;
        }

        switch ($project->getStatus()) {
            case ProjectsStatus::STATUS_CANCELLED:
                if (false === in_array($route, [self::PAGE_ROUTE_END, self::PAGE_ROUTE_PROSPECT])) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $hash]);
                }
                break;
            case ProjectsStatus::STATUS_REQUEST:
                if ($route !== self::PAGE_ROUTE_CONTACT && empty($request->getSession()->get('partnerProjectRequest'))) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_CONTACT, ['hash' => $hash]);
                } elseif ($route !== self::PAGE_ROUTE_PARTNER && false === empty($request->getSession()->get('partnerProjectRequest'))) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_PARTNER, ['hash' => $hash]);
                }
                break;
            case ProjectsStatus::STATUS_REVIEW:
                if (false === in_array($route, [self::PAGE_ROUTE_FINANCE, self::PAGE_ROUTE_END, self::PAGE_ROUTE_FILES])) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_FINANCE, ['hash' => $hash]);
                }
                break;
            default: // Should correspond to "Revue analyste" and above
                if ($route !== self::PAGE_ROUTE_END) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $hash]);
                }
                break;
        }

        return $project;
    }

    /**
     * Redirect to corresponding route and update status
     *
     * @param Projects $project
     * @param string   $route
     * @param int      $projectStatus
     * @param string   $message
     *
     * @return RedirectResponse
     */
    private function redirectStatus(Projects $project, string $route, int $projectStatus, string $message = ''): RedirectResponse
    {
        /** @var ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->get('unilend.service.project_status_manager');

        if ($project->getStatus() !== $projectStatus) {
            switch ($projectStatus) {
                case ProjectsStatus::STATUS_CANCELLED:
                    $rejectionReason = $this->get('doctrine.orm.entity_manager')->getRepository(ProjectRejectionReason::class)
                        ->findBy(['label' => $message]);
                    try {
                        $projectStatusManager->rejectProject($project, $projectStatus, $rejectionReason, Users::USER_ID_FRONT);
                    } catch (\Exception $exception) {
                        $this->get('logger')->error('Could not update project status into ' . $projectStatus . '. Error: ' . $exception->getMessage(), [
                            'id_project' => $project->getIdProject(),
                            'class'      => __CLASS__,
                            'function'   => __FUNCTION__,
                            'file'       => $exception->getFile(),
                            'line'       => $exception->getLine()
                        ]);
                    }
                    break;
                default:
                    $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, $projectStatus, $project, 0, $message);
                    break;
            }
        }

        return $this->redirectToRoute($route, ['hash' => $project->getHash()]);
    }

    /**
     * @param string $email
     *
     * @return string
     */
    private function removeEmailSuffix(string $email): string
    {
        return preg_replace('/^(.*)-[0-9]+$/', '$1', $email);
    }

    /**
     * @param Companies $company
     * @param string    $email
     * @param string    $formOfAddress
     * @param string    $firstName
     * @param string    $lastName
     * @param string    $position
     * @param string    $mobilePhone
     */
    private function saveContactDetails(Companies $company, string $email, string $formOfAddress, string $firstName, string $lastName, string $position, string $mobilePhone): void
    {
        /** @var \ficelle $ficelle */
        $ficelle          = Loader::loadLib('ficelle');
        $entityManager    = $this->get('doctrine.orm.entity_manager');
        $clientRepository = $entityManager->getRepository(Clients::class);

        if ($this->removeEmailSuffix($company->getIdClientOwner()->getEmail()) !== $email) {
            if ($clientRepository->existEmail($email)) {
                $email = $email . '-' . time();
            }
        } else {
            $email = $company->getIdClientOwner()->getEmail();
        }

        $client = $company->getIdClientOwner();

        $client
            ->setEmail($email)
            ->setCivilite($formOfAddress)
            ->setPrenom($firstName)
            ->setNom($lastName)
            ->setFonction($position)
            ->setTelephone($mobilePhone)
            ->setIdLangue('fr')
            ->setSlug($ficelle->generateSlug($firstName . '-' . $lastName));

        $company
            ->setEmailDirigeant($email)
            ->setEmailFacture($email);

        try {
            $entityManager->flush([$company, $client]);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Cannot update the company. Error ' . $exception->getMessage(), [
                'id_company' => $company->getIdCompany(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine()
            ]);
        }
    }
}
