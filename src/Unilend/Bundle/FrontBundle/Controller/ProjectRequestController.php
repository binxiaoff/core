<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\DataLayerCollector;
use Unilend\Bundle\FrontBundle\Service\SourceManager;
use Unilend\core\Loader;
use Doctrine\ORM\EntityManager;

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

    /** @var Clients */
    private $client;

    /** @var Companies */
    private $company;

    /** @var \projects */
    private $project;

    /** @var \upload */
    private $upload;

    /** @var \attachment */
    private $attachment;

    /** @var \attachment_type */
    private $attachmentType;

    /** @var \attachment_helper */
    private $attachmentHelper;

    /**
     * @Route("/depot_de_dossier/{hash}", name="project_request_index", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Route("/depot_de_dossier/reprise/{hash}", name="project_request_recovery", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Route("/depot_de_dossier/stand_by/{hash}", name="project_request_stand_by", requirements={"hash": "[0-9a-f-]{32,36}"})
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function indexAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_INDEX, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        return $this->redirectToRoute('home_borrower');
    }

    /**
     * @Route("/depot_de_dossier/etape1", name="project_request_landing_page_start")
     *
     * @param Request $request
     * @return Response
     */
    public function landingPageStartAction(Request $request)
    {
        if ($request->isMethod('GET')) {
            return $this->redirect($this->generateUrl('home_borrower') . '#homeemp-section-esim');
        }
        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        $amount = null;
        $siren  = null;
        $email  = null;

        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        if (empty($request->request->get('amount'))) {
            $this->addFlash('borrowerLandingPageErrors', $translator->trans('borrower-landing-page_required-fields-error'));
        } else {
            $amount = str_replace(' ', '', $request->request->get('amount'));

            $settings->get('Somme à emprunter min', 'type');
            $minimumAmount = $settings->value;

            $settings->get('Somme à emprunter max', 'type');
            $maximumAmount = $settings->value;

            if (false === filter_var($amount, FILTER_VALIDATE_INT)) {
                $this->addFlash('borrowerLandingPageErrors', $translator->trans('borrower-landing-page_required-fields-error'));
            } elseif ($amount < $minimumAmount || $amount > $maximumAmount) {
                $this->addFlash('borrowerLandingPageErrors', $translator->trans('borrower-landing-page_amount-value-error'));
            }
        }

        $siren       = str_replace(' ', '', $request->request->get('siren'));
        $sirenLength = strlen($siren);

        if (
            empty($siren)
            || false === filter_var($siren, FILTER_VALIDATE_INT)
            || false === in_array($sirenLength, [9, 14]) // SIRET numbers also allowed
        ) {
            $this->addFlash('borrowerLandingPageErrors', $translator->trans('borrower-landing-page_required-fields-error'));
        } else {
            $siren = substr($siren, 0, 9);
        }

        if ($request->getSession()->get('partnerProjectRequest')) {
            $email = '';
        } elseif (
            empty($request->request->get('email'))
            || false === filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)
        ) {
            $this->addFlash('borrowerLandingPageErrors', $translator->trans('borrower-landing-page_required-fields-error'));
        } else {
            $email = $request->request->get('email');
        }

        if ($this->get('session')->getFlashBag()->has('borrowerLandingPageErrors')) {
            $request->getSession()->set('projectRequest', [
                'values' => [
                    'amount' => $amount,
                    'siren'  => $siren,
                    'email'  => $email
                ]
            ]);

            return $this->redirect($request->headers->get('referer'));
        }

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->get('security.token_storage')->setToken(null);
        }

        if (14 === $sirenLength) {
            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->info(
                'Project ' . $this->project->id_project . ' requested with SIRET value: ' . $request->request->get('siren'),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'projectId' => $this->project->id_project]
            );
        }

        /** @var \clients $clientRepository */
        $clientRepository = $entityManager->getRepository('clients');

        if ($clientRepository->existEmail($email)) {
            $email .= '-' . time();
        }

        $sourceManager = $this->get('unilend.frontbundle.service.source_manager');

        $this->client = new Clients();
        $this->client
            ->setEmail($email)
            ->setIdLangue('fr')
            ->setStatus(\clients::STATUS_ONLINE)
            ->setSource($sourceManager->getSource(SourceManager::SOURCE1))
            ->setSource2($sourceManager->getSource(SourceManager::SOURCE2))
            ->setSource3($sourceManager->getSource(SourceManager::SOURCE3))
            ->setSlugOrigine($sourceManager->getSource(SourceManager::ENTRY_SLUG));

        $siret = $sirenLength === 14 ? str_replace(' ', '', $request->request->get('siren')) : '';
        $this->company = new Companies();
        $this->company->setSiren($siren)
            ->setSiret($siret)
            ->setStatusAdresseCorrespondance(1)
            ->setEmailDirigeant($email)
            ->setEmailFacture($email);

        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        $em->beginTransaction();
        try {
            $em->persist($this->client);
            $em->flush();
            $clientAddress = new ClientsAdresses();
            $clientAddress->setIdClient($this->client->getIdClient());
            $em->persist($clientAddress);
            $this->company->setIdClientOwner($this->client->getIdClient());
            $em->persist($this->company);
            $em->flush();
            $this->get('unilend.service.wallet_creation_manager')->createWallet($this->client, WalletType::BORROWER);
            $em->commit();
        } catch (\Exception $exception) {
            $em->getConnection()->rollBack();
            $this->get('logger')->error('An error occurred while creating client: ' . $exception->getMessage(), [['class' => __CLASS__, 'function' => __FUNCTION__]]);
        }

        if (empty($this->client->getIdClient())) {
            return $this->redirect($request->headers->get('referer'));
        } else {
            $request->getSession()->set(DataLayerCollector::SESSION_KEY_CLIENT_EMAIL, $this->client->getEmail());
            $request->getSession()->set(DataLayerCollector::SESSION_KEY_BORROWER_CLIENT_ID, $this->client->getIdClient());
        }

        $partnerId = $request->request->getInt('partner');

        if (empty($partnerId) || null === $em->getRepository('UnilendCoreBusinessBundle:Partner')->find($partnerId)) {
            $partnerManager = $this->get('unilend.service.partner_manager');
            $partnerId      = $partnerManager->getDefaultPartner()->id;
        }

        $this->project                                       = $entityManager->getRepository('projects');
        $this->project->id_company                           = $this->company->getIdCompany();
        $this->project->amount                               = $amount;
        $this->project->ca_declara_client                    = 0;
        $this->project->resultat_exploitation_declara_client = 0;
        $this->project->fonds_propres_declara_client         = 0;
        $this->project->status                               = \projects_status::INCOMPLETE_REQUEST;
        $this->project->id_partner                           = $partnerId;
        $this->project->create();

        $projectManager = $this->get('unilend.service.project_manager');
        $projectManager->addProjectStatus(Users::USER_ID_FRONT, \projects_status::INCOMPLETE_REQUEST, $this->project);

        return $this->start();
    }

    /**
     * @Route("/depot_de_dossier/simulateur", name="project_request_simulator_start")
     * @Method("GET")
     *
     * @param Request $request
     * @return Response
     */
    public function simulatorStartAction(Request $request)
    {
        if (empty($request->query->get('hash'))) {
            return $this->redirectToRoute('home_borrower');
        }

        $response = $this->checkProjectHash(self::PAGE_ROUTE_SIMULATOR_START, $request->query->get('hash'), $request);

        if ($response instanceof Response) {
            return $response;
        }

        return $this->start();
    }

    /**
     * @return Response
     */
    private function start()
    {
        $projectRequestManager = $this->get('unilend.service.project_request_manager');

        if (null === $projectRequestManager->checkProjectRisk($this->project, Users::USER_ID_FRONT)) {
            return $this->redirectStatus(self::PAGE_ROUTE_CONTACT, \projects_status::INCOMPLETE_REQUEST);
        }

        return $this->redirectToRoute(self::PAGE_ROUTE_PROSPECT, ['hash' => $this->project->hash]);
    }

    /**
     * @Route("/depot_de_dossier/etape2/{hash}", name="project_request_contact", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("GET")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function contactAction($hash, Request $request)
    {
        $template = [];
        $response = $this->checkProjectHash(self::PAGE_ROUTE_CONTACT, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        if (false === empty($this->project->id_prescripteur)) {
            /** @var \prescripteurs $advisor */
            $advisor = $entityManager->getRepository('prescripteurs');
            /** @var \clients $advisorClient */
            $advisorClient = $entityManager->getRepository('clients');

            $advisor->get($this->project->id_prescripteur);
            $advisorClient->get($advisor->id_client);
        }

        $settings->get('Lien conditions generales depot dossier', 'type');

        /** @var \tree $tree */
        $tree = $entityManager->getRepository('tree');
        $tree->get(['id_tree' => $settings->value]);
        $template['terms_of_sale_link'] = $this->generateUrl($tree->slug);

        /** @var \borrowing_motive $borrowingMotive */
        $borrowingMotive               = $entityManager->getRepository('borrowing_motive');
        $template['borrowing_motives'] = $borrowingMotive->select('rank');

        $settings->get('Durée des prêts autorisées', 'type');
        $template['loan_periods'] = explode(',', $settings->value);

        $session = $request->getSession()->get('projectRequest');
        $values  = isset($session['values']) ? $session['values'] : [];

        $template['form'] = [
            'errors' => isset($session['errors']) ? $session['errors'] : [],
            'values' => [
                'contact' => [
                    'civility'  => isset($values['contact']['civility']) ? $values['contact']['civility'] : $this->client->getCivilite(),
                    'lastname'  => isset($values['contact']['lastname']) ? $values['contact']['lastname'] : $this->client->getNom(),
                    'firstname' => isset($values['contact']['firstname']) ? $values['contact']['firstname'] : $this->client->getPrenom(),
                    'email'     => isset($values['contact']['email']) ? $values['contact']['email'] : $this->removeEmailSuffix($this->client->getEmail()),
                    'mobile'    => isset($values['contact']['mobile']) ? $values['contact']['mobile'] : $this->client->getTelephone(),
                    'function'  => isset($values['contact']['function']) ? $values['contact']['function'] : $this->client->getFonction()
                ],
                'manager' => isset($values['manager']) ? $values['manager'] : (isset($advisorClient) ? 'no' : 'yes'),
                'advisor' => [
                    'civility'  => isset($values['advisor']['civility']) ? $values['advisor']['civility'] : (isset($advisorClient) ? $advisorClient->civilite : ''),
                    'lastname'  => isset($values['advisor']['lastname']) ? $values['advisor']['lastname'] : (isset($advisorClient) ? $advisorClient->nom : ''),
                    'firstname' => isset($values['advisor']['firstname']) ? $values['advisor']['firstname'] : (isset($advisorClient) ? $advisorClient->prenom : ''),
                    'email'     => isset($values['advisor']['email']) ? $values['advisor']['email'] : (isset($advisorClient) ? $this->removeEmailSuffix($advisorClient->email) : ''),
                    'mobile'    => isset($values['advisor']['mobile']) ? $values['advisor']['mobile'] : (isset($advisorClient) ? $advisorClient->telephone : ''),
                    'function'  => isset($values['advisor']['function']) ? $values['advisor']['function'] : (isset($advisorClient) ? $advisorClient->fonction : '')
                ],
                'project' => [
                    'duration'    => isset($values['project']['duration']) ? $values['project']['duration'] : $this->project->period,
                    'description' => isset($values['project']['description']) ? $values['project']['description'] : $this->project->comments,
                    'motive'      => isset($values['project']['motive']) ? $values['project']['motive'] : $this->project->id_borrowing_motive
                ]
            ]
        ];

        $template['project'] = [
            'company_name'           => $this->company->getName(),
            'siren'                  => $this->company->getSiren(),
            'amount'                 => $this->project->amount,
            'averageFundingDuration' => $this->get('unilend.service.project_manager')->getAverageFundingDuration($this->project->amount),
            'hash'                   => $this->project->hash
        ];

        $request->getSession()->remove('projectRequest');

        return $this->render('pages/project_request/contact.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/etape2/{hash}", name="project_request_contact_form", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("POST")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function contactFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_CONTACT, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        $settings->get('Durée des prêts autorisées', 'type');
        $loanPeriods = explode(',', $settings->value);

        $errors = [];

        if (empty($request->request->get('contact')['civility']) || false === in_array($request->request->get('contact')['civility'], [\clients::TITLE_MISS, \clients::TITLE_MISTER])) {
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
        if (empty($request->request->get('manager')) || false === in_array($request->request->get('manager'), ['yes', 'no'])) {
            $errors['manager'] = true;
        }
        if (empty($request->request->get('project')['duration']) || false === in_array($request->request->get('project')['duration'], $loanPeriods)) {
            $errors['project']['duration'] = true;
        }
        if (empty($request->request->get('project')['motive'])) {
            $errors['project']['motive'] = true;
        }
        if (empty($request->request->get('project')['description'])) {
            $errors['project']['description'] = true;
        }
        if ('no' === $request->request->get('manager')) {
            if (empty($request->request->get('advisor')['civility']) || false === in_array($request->request->get('advisor')['civility'], [\clients::TITLE_MISS, \clients::TITLE_MISTER])) {
                $errors['advisor']['civility'] = true;
            }
            if (empty($request->request->get('advisor')['lastname'])) {
                $errors['advisor']['lastname'] = true;
            }
            if (empty($request->request->get('advisor')['firstname'])) {
                $errors['advisor']['firstname'] = true;
            }
            if (empty($request->request->get('advisor')['email']) || false === filter_var($request->request->get('advisor')['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['advisor']['email'] = true;
            }
            if (empty($request->request->get('advisor')['mobile'])) {
                $errors['advisor']['mobile'] = true;
            }
            if (empty($request->request->get('advisor')['function'])) {
                $errors['advisor']['function'] = true;
            }
        } elseif ('yes' === $request->request->get('manager') && empty($request->request->get('terms'))) {
            $errors['terms'] = true;
        }

        if (false === empty($errors)) {
            $request->getSession()->set('projectRequest', [
                'values' => $request->request->all(),
                'errors' => $errors
            ]);

            return $this->redirectToRoute(self::PAGE_ROUTE_CONTACT, ['hash' => $this->project->hash]);
        }

        $this->saveContactDetails($request->request->get('contact')['email'],
            $request->request->get('contact')['civility'],
            $request->request->get('contact')['firstname'],
            $request->request->get('contact')['lastname'],
            $request->request->get('contact')['function'],
            $request->request->get('contact')['mobile']
        );

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        if ('no' === $request->request->get('manager')) {
            /** @var \prescripteurs $advisor */
            $advisor = $entityManager->getRepository('prescripteurs');
            /** @var \clients $advisorClient */
            $advisorClient = $entityManager->getRepository('clients');
            $sourceManager = $this->get('unilend.frontbundle.service.source_manager');

            if (false === empty($this->project->id_prescripteur)) {
                $advisor->get($this->project->id_prescripteur);
                $advisorClient->get($advisor->id_client);
            }

            $email = $request->request->get('advisor')['email'];

            if ($advisorClient->existEmail($email) && $this->removeEmailSuffix($advisorClient->email) !== $email) {
                $email = $email . '-' . time();
            }

            $advisorClient->email     = $email;
            $advisorClient->civilite  = $request->request->get('advisor')['civility'];
            $advisorClient->prenom    = $request->request->get('advisor')['firstname'];
            $advisorClient->nom       = $request->request->get('advisor')['lastname'];
            $advisorClient->fonction  = $request->request->get('advisor')['function'];
            $advisorClient->telephone = $request->request->get('advisor')['mobile'];
            $advisorClient->slug      = $ficelle->generateSlug($advisorClient->prenom . '-' . $advisorClient->nom);

            $advisorClient->source       = $sourceManager->getSource(SourceManager::SOURCE1);
            $advisorClient->source2      = $sourceManager->getSource(SourceManager::SOURCE2);
            $advisorClient->source3      = $sourceManager->getSource(SourceManager::SOURCE3);
            $advisorClient->slug_origine = $sourceManager->getSource(SourceManager::ENTRY_SLUG);

            if (empty($advisorClient->id_client)) {
                $advisorClient->create();

                /** @var \clients_adresses $advisorAddress */
                $advisorAddress            = $entityManager->getRepository('clients_adresses');
                $advisorAddress->id_client = $advisorClient->id_client;
                $advisorAddress->civilite  = $request->request->get('advisor')['civility'];
                $advisorAddress->prenom    = $request->request->get('advisor')['firstname'];
                $advisorAddress->nom       = $request->request->get('advisor')['lastname'];
                $advisorAddress->telephone = $request->request->get('advisor')['mobile'];
                $advisorAddress->create();

                /** @var \companies $advisorCompany */
                $advisorCompany = $entityManager->getRepository('companies');
                $advisorCompany->create();

                $advisor->id_client = $advisorClient->id_client;
                $advisor->id_entite = $advisorCompany->id_company;
                $advisor->create();

                $this->project->id_prescripteur = $advisor->id_prescripteur;
            } else {
                $advisorClient->update();
            }
        } else {
            $this->project->id_prescripteur = 0;

            /** @var \acceptations_legal_docs $tosAcceptation */
            $tosAcceptation = $entityManager->getRepository('acceptations_legal_docs');
            $settings->get('Lien conditions generales depot dossier', 'type');

            if ($tosAcceptation->get($settings->value, 'id_client = ' . $this->client->getIdClient() . ' AND id_legal_doc')) {
                $tosAcceptation->update();
            } else {
                $tosAcceptation->id_legal_doc = $settings->value;
                $tosAcceptation->id_client    = $this->client->getIdClient();
                $tosAcceptation->create();
            }
        }

        $this->project->period              = $request->request->get('project')['duration'];
        $this->project->comments            = $request->request->get('project')['description'];
        $this->project->id_borrowing_motive = $request->request->get('project')['motive'];
        $this->project->update();

        $productManager = $this->get('unilend.service_product.product_manager');
        try {
            $products = $productManager->findEligibleProducts($this->project);
            if (count($products) === 1 && isset($products[0]) && $products[0] instanceof \product) {
                $this->project->id_product = $products[0]->id_product;
                $this->project->update();
            }

            if (empty($products)) {
                return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::NOT_ELIGIBLE, \projects_status::NON_ELIGIBLE_REASON_PRODUCT_NOT_FOUND);
            }
        } catch (\Exception $exception) {
            $this->get('logger')->warning($exception->getMessage(), ['method' => __METHOD__, 'line' => __LINE__]);
        }

        return $this->redirectStatus(self::PAGE_ROUTE_FINANCE, \projects_status::COMPLETE_REQUEST);
    }

    /**
     * @Route("/depot_de_dossier/etape3/{hash}", name="project_request_finance", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("GET")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function financeAction($hash, Request $request)
    {
        $template = [];
        $response = $this->checkProjectHash(self::PAGE_ROUTE_FINANCE, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \companies_actif_passif $companyAssetsDebts */
        $companyAssetsDebts = $entityManager->getRepository('companies_actif_passif');
        /** @var \companies_bilans $annualAccountsEntity */
        $annualAccountsEntity = $entityManager->getRepository('companies_bilans');
        $partnerManager       = $this->get('unilend.service.partner_manager');

        $this->attachmentType         = $entityManager->getRepository('attachment_type');
        $attachmentTypes              = $this->attachmentType->getAllTypesForProjects('fr', true, $partnerManager->getAttachmentTypesByPartner($this->project->id_partner));
        $template['attachment_types'] = $this->attachmentType->changeLabelWithDynamicContent($attachmentTypes);

        $altaresCapitalStock     = 0;
        $altaresOperationIncomes = 0;
        $altaresRevenue          = 0;
        $annualAccounts          = $annualAccountsEntity->select('id_company = ' . $this->company->getIdCompany(), 'cloture_exercice_fiscal DESC', 0, 1);

        if (false === empty($annualAccounts)) {
            $companyAssetsDebts->get($annualAccounts[0]['id_bilan'], 'id_bilan');
            $annualAccountsEntity->get($annualAccounts[0]['id_bilan']);
            $incomeStatement         = $this->get('unilend.service.company_balance_sheet_manager')->getIncomeStatement($annualAccountsEntity);
            $altaresCapitalStock     = $companyAssetsDebts->capitaux_propres;
            $altaresOperationIncomes = $incomeStatement['details']['project-detail_finance-column-resultat-exploitation'];
            $altaresRevenue          = $incomeStatement['details']['project-detail_finance-column-ca'];
        }

        $session = $request->getSession()->get('projectRequest');
        $values  = isset($session['values']) ? $session['values'] : [];

        $template['form']['errors'] = isset($session['errors']) ? $session['errors'] : [];

        if (empty($this->company->getRcs())) {
            $template['form']['values'] = [
                'ag_2035' => isset($values['ag_2035']) ? $values['ag_2035'] : (empty($this->project->ca_declara_client) ? (empty($altaresRevenue) ? '' : $altaresRevenue) : $this->project->ca_declara_client),
            ];
            $template['rcs']            = false;
        } else {
            $template['form']['values'] = [
                'dl' => isset($values['dl']) ? $values['dl'] : (empty($this->project->fonds_propres_declara_client) ? (empty($altaresCapitalStock) ? '' : $altaresCapitalStock) : $this->project->fonds_propres_declara_client),
                'fl' => isset($values['fl']) ? $values['fl'] : (empty($this->project->ca_declara_client) ? (empty($altaresRevenue) ? '' : $altaresRevenue) : $this->project->ca_declara_client),
                'gg' => isset($values['gg']) ? $values['gg'] : (empty($this->project->resultat_exploitation_declara_client) ? (empty($altaresOperationIncomes) ? '' : $altaresOperationIncomes) : $this->project->resultat_exploitation_declara_client)
            ];
            $template['rcs']            = true;
        }

        $template['project'] = [
            'amount'                   => $this->project->amount,
            'averageFundingDuration'   => $this->get('unilend.service.project_manager')->getAverageFundingDuration($this->project->amount),
            'monthlyPaymentBoundaries' => $this->getMonthlyPaymentBoundaries(),
            'hash'                     => $this->project->hash
        ];

        $request->getSession()->remove('projectRequest');

        return $this->render('pages/project_request/finance.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/etape3/{hash}", name="project_request_finance_form", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("POST")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function financeFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_FINANCE, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        $errors = [];
        $values = $request->request->get('finance');
        $values = is_array($values) ? $values : [];
        $files  = $request->files->all();

        if (empty($this->company->getRcs())) {
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

        if (empty($files['accounts']) || false === ($files['accounts'] instanceof UploadedFile)) {
            $errors['accounts'] = true;
        } elseif (false === $this->uploadAttachment('accounts', \attachment_type::DERNIERE_LIASSE_FISCAL)) {
            $errors['accounts'] = [
                'message' => $this->upload->getErrorType()
            ];
        }

        if (false === empty($errors)) {
            $request->getSession()->set('projectRequest', [
                'values' => $values,
                'errors' => $errors
            ]);

            return $this->redirectToRoute(self::PAGE_ROUTE_FINANCE, ['hash' => $this->project->hash]);
        }

        if ('true' === $request->request->get('extra_files')) {
            foreach ($files as $fileName => $file) {
                if ('accounts' !== $fileName && $file instanceof UploadedFile && false === empty($request->request->get('files')[$fileName])) {
                    $this->uploadAttachment($fileName, $request->request->get('files')[$fileName]);
                }
            }
        }

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        if (empty($this->company->getRcs())) {
            $this->project->ca_declara_client = $values['ag_2035'];
            $updateDeclaration                = true;
        } else {
            $updateDeclaration = false;
            $values['dl']      = $ficelle->cleanFormatedNumber($values['dl']);
            $values['fl']      = $ficelle->cleanFormatedNumber($values['fl']);
            $values['gg']      = $ficelle->cleanFormatedNumber($values['gg']);

            /** @var \companies_actif_passif $companyAssetsDebts */
            $companyAssetsDebts = $entityManager->getRepository('companies_actif_passif');
            /** @var \companies_bilans $annualAccountsEntity */
            $annualAccountsEntity = $entityManager->getRepository('companies_bilans');

            $altaresCapitalStock     = 0;
            $altaresRevenue          = 0;
            $altaresOperationIncomes = 0;
            $annualAccounts          = $annualAccountsEntity->select('id_company = ' . $this->company->getIdCompany(), 'cloture_exercice_fiscal DESC', 0, 1);

            if (false === empty($annualAccounts)) {
                $companyAssetsDebts->get($annualAccounts[0]['id_bilan'], 'id_bilan');

                $altaresCapitalStock     = $companyAssetsDebts->capitaux_propres;
                $altaresRevenue          = $annualAccounts[0]['ca'];
                $altaresOperationIncomes = $annualAccounts[0]['resultat_exploitation'];
            }

            if ($altaresCapitalStock != $values['dl']) {
                $this->project->fonds_propres_declara_client = $values['dl'];
                $updateDeclaration                           = true;
            } elseif (false === empty($this->project->fonds_propres_declara_client) && $altaresCapitalStock == $values['dl']) {
                $this->project->fonds_propres_declara_client = 0;
                $updateDeclaration                           = true;
            }

            if ($altaresRevenue != $values['fl']) {
                $this->project->ca_declara_client = $values['fl'];
                $updateDeclaration                = true;
            } elseif (false === empty($this->project->ca_declara_client) && $altaresRevenue == $values['fl']) {
                $this->project->ca_declara_client = 0;
                $updateDeclaration                = true;
            }

            if ($altaresOperationIncomes != $values['gg']) {
                $this->project->resultat_exploitation_declara_client = $values['gg'];
                $updateDeclaration                                   = true;
            } elseif (false === empty($this->project->resultat_exploitation_declara_client) && $altaresOperationIncomes == $values['gg']) {
                $this->project->resultat_exploitation_declara_client = 0;
                $updateDeclaration                                   = true;
            }
        }

        if ($updateDeclaration) {
            $this->project->update();
        }

        if (isset($values['dl']) && $values['dl'] < 0) {
            return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::NOT_ELIGIBLE, \projects_status::NON_ELIGIBLE_REASON_NEGATIVE_EQUITY_CAPITAL);
        }

        if (isset($values['fl']) && $values['fl'] < \projects::MINIMUM_REVENUE) {
            return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::NOT_ELIGIBLE, \projects_status::NON_ELIGIBLE_REASON_LOW_TURNOVER);
        }

        if (isset($values['gg']) && $values['gg'] < 0) {
            return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::NOT_ELIGIBLE, \projects_status::NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES);
        }

        if (isset($values['ag_2035']) && $values['ag_2035'] < \projects::MINIMUM_REVENUE) {
            return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::NOT_ELIGIBLE, \projects_status::NON_ELIGIBLE_REASON_LOW_TURNOVER);
        }

        if ('true' === $request->request->get('extra_files')) {
            return $this->redirectToRoute(self::PAGE_ROUTE_FILES, ['hash' => $this->project->hash]);
        }

        $this->sendSubscriptionConfirmationEmail();

        return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $this->project->hash]);
    }

    /**
     * @Route("/depot_de_dossier/partenaire/{hash}", name="project_request_partner", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("GET")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function partnerAction($hash, Request $request)
    {
        $template = [];
        $response = $this->checkProjectHash(self::PAGE_ROUTE_PARTNER, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings       = $entityManager->getRepository('settings');
        $partnerManager = $this->get('unilend.service.partner_manager');

        $this->attachmentType         = $entityManager->getRepository('attachment_type');
        $attachmentTypes              = $this->attachmentType->getAllTypesForProjects('fr', true, $partnerManager->getAttachmentTypesByPartner($this->project->id_partner));
        $template['attachment_types'] = $this->attachmentType->changeLabelWithDynamicContent($attachmentTypes);

        $settings->get('Lien conditions generales depot dossier', 'type');

        /** @var \tree $tree */
        $tree = $entityManager->getRepository('tree');
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
                    'civility'  => isset($values['contact']['civility']) ? $values['contact']['civility'] : $this->client->getCivilite(),
                    'lastname'  => isset($values['contact']['lastname']) ? $values['contact']['lastname'] : $this->client->getNom(),
                    'firstname' => isset($values['contact']['firstname']) ? $values['contact']['firstname'] : $this->client->getPrenom(),
                    'email'     => isset($values['contact']['email']) ? $values['contact']['email'] : $this->removeEmailSuffix($this->client->getEmail()),
                    'mobile'    => isset($values['contact']['mobile']) ? $values['contact']['mobile'] : $this->client->getTelephone(),
                    'function'  => isset($values['contact']['function']) ? $values['contact']['function'] : $this->client->getFonction()
                ],
                'project' => [
                    'duration'    => isset($values['project']['duration']) ? $values['project']['duration'] : $this->project->period,
                    'description' => isset($values['project']['description']) ? $values['project']['description'] : $this->project->comments
                ]
            ]
        ];

        $template['project'] = [
            'company_name' => $this->company->getName(),
            'siren'        => $this->company->getSiren(),
            'amount'       => $this->project->amount,
            'hash'         => $this->project->hash
        ];

        $request->getSession()->remove('projectRequest');

        return $this->render('pages/project_request/partner.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/partenaire/{hash}", name="project_request_partner_form", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("POST")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function partnerFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_PARTNER, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

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

            return $this->redirectToRoute(self::PAGE_ROUTE_PARTNER, ['hash' => $this->project->hash]);
        }

        $this->saveContactDetails($request->request->get('contact')['email'],
            $request->request->get('contact')['civility'],
            $request->request->get('contact')['firstname'],
            $request->request->get('contact')['lastname'],
            $request->request->get('contact')['function'],
            $request->request->get('contact')['mobile']
        );

        /** @var \acceptations_legal_docs $tosAcceptation */
        $tosAcceptation = $entityManager->getRepository('acceptations_legal_docs');
        $settings->get('Lien conditions generales depot dossier', 'type');

        if ($tosAcceptation->get($settings->value, 'id_client = ' . $this->client->getIdClient() . ' AND id_legal_doc')) {
            $tosAcceptation->update();
        } else {
            $tosAcceptation->id_legal_doc = $settings->value;
            $tosAcceptation->id_client    = $this->client->getIdClient();
            $tosAcceptation->create();
        }

        $this->project->period   = $request->request->get('project')['duration'];
        $this->project->comments = $request->request->get('project')['description'];
        $this->project->update();

        $files = $request->request->get('files', []);

        foreach ($request->files->all() as $fileName => $file) {
            if ($file instanceof UploadedFile && false === empty($files[$fileName])) {
                $this->uploadAttachment($fileName, $files[$fileName]);
            }
        }

        $this->sendSubscriptionConfirmationEmail();

        return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::COMPLETE_REQUEST);
    }

    /**
     * @Route("/depot_de_dossier/prospect/{hash}", name="project_request_prospect", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("GET")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function prospectAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_PROSPECT, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        $session = $request->getSession()->get('projectRequest');
        $values  = isset($session['values']) ? $session['values'] : [];

        $template = [
            'form'    => [
                'errors' => isset($session['errors']) ? $session['errors'] : [],
                'values' => [
                    'civility'  => isset($values['civility']) ? $values['civility'] : $this->client->getCivilite(),
                    'lastname'  => isset($values['lastname']) ? $values['lastname'] : $this->client->getNom(),
                    'firstname' => isset($values['firstname']) ? $values['firstname'] : $this->client->getPrenom(),
                    'email'     => isset($values['email']) ? $values['email'] : $this->removeEmailSuffix($this->client->getEmail()),
                    'mobile'    => isset($values['mobile']) ? $values['mobile'] : $this->client->getTelephone(),
                    'function'  => isset($values['function']) ? $values['function'] : $this->client->getFonction()
                ]
            ],
            'project' => [
                'hash' => $this->project->hash
            ]
        ];

        $request->getSession()->remove('projectRequest');

        return $this->render('pages/project_request/prospect.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/prospect/{hash}", name="project_request_prospect_form", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("POST")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function prospectFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_PROSPECT, $hash, $request);

        if ($response instanceof Response) {
            return $response;
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

            return $this->redirectToRoute(self::PAGE_ROUTE_PROSPECT, ['hash' => $this->project->hash]);
        }

        $this->saveContactDetails($request->request->get('email'),
            $request->request->get('civility'),
            $request->request->get('firstname'),
            $request->request->get('lastname'),
            $request->request->get('function'),
            $request->request->get('mobile')
        );

        return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $this->project->hash]);
    }

    /**
     * @Route("/depot_de_dossier/fichiers/{hash}", name="project_request_files", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("GET")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function filesAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_FILES, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        $template = [
            'project' => [
                'amount'                   => $this->project->amount,
                'averageFundingDuration'   => $this->get('unilend.service.project_manager')->getAverageFundingDuration($this->project->amount),
                'monthlyPaymentBoundaries' => $this->getMonthlyPaymentBoundaries(),
                'hash'                     => $this->project->hash
            ]
        ];

        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        /** @var \attachment $attachment */
        $attachment     = $entityManager->getRepository('attachment');
        $partnerManager = $this->get('unilend.service.partner_manager');

        $attachments          = array_column($attachment->select('type_owner = "' . \attachment::PROJECT . '" AND id_owner = ' . $this->project->id_project), 'id_type');
        $this->attachmentType = $entityManager->getRepository('attachment_type');
        $attachmentTypes      = $this->attachmentType->getAllTypesForProjects('fr', true, $partnerManager->getAttachmentTypesByPartner($this->project->id_partner));

        foreach ($attachmentTypes as $attachmentIndex => $attachmentType) {
            if (in_array($attachmentType['id'], $attachments)) {
                unset($attachmentTypes[$attachmentIndex]);
            }
        }

        $template['attachment_types'] = $this->attachmentType->changeLabelWithDynamicContent($attachmentTypes);

        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $entityManager->getRepository('projects_status_history');
        $projectStatusHistory->loadLastProjectHistory($this->project->id_project);

        if (false === empty($projectStatusHistory->content)) {
            $oDOMElement = new \DOMDocument();
            $oDOMElement->loadHTML($projectStatusHistory->content);
            $oList = $oDOMElement->getElementsByTagName('ul');
            if ($oList->length > 0 && $oList->item(0)->childNodes->length > 0) {
                $template['attachments_list'] = $oList->item(0)->C14N();
            }
        }

        return $this->render('pages/project_request/files.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/fichiers/{hash}", name="project_request_files_form", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("POST")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function filesFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_FILES, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        $files = $request->request->get('files', []);

        foreach ($request->files->all() as $fileName => $file) {
            if ($file instanceof UploadedFile && false === empty($files[$fileName])) {
                $this->uploadAttachment($fileName, $files[$fileName]);
            }
        }

        $this->sendCommercialEmail('notification-ajout-document-dossier');

        return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $this->project->hash]);
    }

    /**
     * @Route("/depot_de_dossier/fin/{hash}", name="project_request_end", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("GET")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function endAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_END, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        $addMoreFiles = false;
        $message      = $translator->trans('project-request_end-page-not-entitled-message');
        $title        = $translator->trans('project-request_end-page-success-title');
        $subtitle     = $translator->trans('project-request_end-page-success-subtitle');

        switch ($this->project->status) {
            case \projects_status::ABANDONED:
                $message  = $translator->trans('project-request_end-page-aborted-message');
                $title    = $translator->trans('project-request_end-page-aborted-title');
                $subtitle = $translator->trans('project-request_end-page-aborted-subtitle');
                break;
            case \projects_status::ANALYSIS_REVIEW:
            case \projects_status::COMITY_REVIEW:
            case \projects_status::PREP_FUNDING:
                $message  = $translator->trans('project-request_end-page-analysis-in-progress-message');
                $title    = $translator->trans('project-request_end-page-processing-title');
                $subtitle = $translator->trans('project-request_end-page-processing-subtitle');
                break;
            case \projects_status::COMPLETE_REQUEST:
            case \projects_status::COMMERCIAL_REVIEW:
                $addMoreFiles = true;
                $message      = $translator->trans('project-request_end-page-main-content');
                break;
            case \projects_status::NOT_ELIGIBLE:
                $title    = $translator->trans('project-request_end-page-rejection-title');
                $subtitle = $translator->trans('project-request_end-page-rejection-subtitle');

                /** @var \projects_status_history $projectStatusHistory */
                $projectStatusHistory = $this->get('unilend.service.entity_manager')->getRepository('projects_status_history');
                $projectStatusHistory->loadLastProjectHistory($this->project->id_project);

                $rejectReasons = explode(',', $projectStatusHistory->content);

                // Display only one reason (priority defined in TST-51)
                if (in_array(\projects_status::NON_ELIGIBLE_REASON_PROCEEDING, $rejectReasons)) {
                    $message = $translator->trans('project-request_end-page-collective-proceeding-message');
                } elseif (
                    in_array(\projects_status::NON_ELIGIBLE_REASON_INACTIVE, $rejectReasons)
                    || in_array(\projects_status::NON_ELIGIBLE_REASON_UNKNOWN_SIREN, $rejectReasons)
                ) {
                    $message = $translator->trans('project-request_end-page-no-siren-message');
                } elseif (
                    in_array(\projects_status::NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK, $rejectReasons)
                    || in_array(\projects_status::NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES, $rejectReasons)
                    || in_array(\projects_status::NON_ELIGIBLE_REASON_NEGATIVE_EQUITY_CAPITAL, $rejectReasons)
                    || in_array(\projects_status::NON_ELIGIBLE_REASON_LOW_TURNOVER, $rejectReasons)
                ) {
                    $message = $translator->trans('project-request_end-page-negative-operating-result-message');
                } elseif (in_array(\projects_status::NON_ELIGIBLE_REASON_PRODUCT_NOT_FOUND, $rejectReasons)) {
                    $message = $translator->trans('project-request_end-page-product-not-found-message');
                } else {
                    $message = $translator->trans('project-request_end-page-external-rating-rejection-default-message');
                }
                break;
        }

        $template = [
            'addMoreFiles' => $addMoreFiles,
            'message'      => $message,
            'title'        => $title,
            'subtitle'     => $subtitle,
            'project'      => [
                'hash' => $this->project->hash
            ]
        ];

        return $this->render('pages/project_request/end.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/emails/{hash}", name="project_request_emails", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Method("GET")
     *
     * @param string  $hash
     * @param Request $request
     * @return Response
     */
    public function emailsAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_EMAILS, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        $projectManager = $this->get('unilend.service.project_manager');
        $projectManager->addProjectStatus(Users::USER_ID_FRONT, \projects_status::ABANDONED, $this->project, 0, 'client_not_interested');

        return $this->render('pages/project_request/emails.html.twig');
    }

    /**
     * @return int[]
     */
    private function getMonthlyPaymentBoundaries()
    {
        $financialCalculation = new \PHPExcel_Calculation_Financial();

        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        /** @var \project_period $projectPeriod */
        $projectPeriod = $entityManager->getRepository('project_period');
        $projectPeriod->getPeriod($this->project->period);

        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $entityManager->getRepository('project_rate_settings');
        $rateSettings        = $projectRateSettings->getSettings(null, $projectPeriod->id_period);

        $minimumRate = min(array_column($rateSettings, 'rate_min'));
        $maximumRate = max(array_column($rateSettings, 'rate_max'));

        /** @var \tax_type $taxType */
        $taxType = $entityManager->getRepository('tax_type');
        $taxType->get(\tax_type::TYPE_VAT);
        $vatRate = $taxType->rate / 100;

        if (false === empty($this->project->commission_rate_repayment)) {
            $commissionRateRepayment = round(bcdiv(\projects::DEFAULT_COMMISSION_RATE_REPAYMENT, 100, 4), 2);
        } else {
            $commissionRateRepayment = round(bcdiv($this->project->commission_rate_repayment, 100, 4), 2);
        }
        $commission = ($financialCalculation->PMT($commissionRateRepayment / 12, $this->project->period, -$this->project->amount) - $financialCalculation->PMT(0, $this->project->period, -$this->project->amount)) * (1 + $vatRate);

        return [
            'minimum' => round($financialCalculation->PMT($minimumRate / 100 / 12, $this->project->period, -$this->project->amount) + $commission),
            'maximum' => round($financialCalculation->PMT($maximumRate / 100 / 12, $this->project->period, -$this->project->amount) + $commission)
        ];
    }

    private function sendSubscriptionConfirmationEmail()
    {
        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        /** @var \mail_templates $mailTemplate */
        $mailTemplate = $entityManager->getRepository('mail_templates');
        $mailTemplate->get('confirmation-depot-de-dossier', 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');

        if (false === empty($this->project->id_prescripteur)) {
            /** @var \prescripteurs $advisor */
            $advisor = $entityManager->getRepository('prescripteurs');

            $advisor->get($this->project->id_prescripteur);
            $client = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($advisor->id_client);
        } else {
            $client = $this->client;
        }

        $settings->get('Facebook', 'type');
        $facebookLink = $settings->value;

        $settings->get('Twitter', 'type');
        $twitterLink = $settings->value;

        $keywords = [
            'prenom'               => $client->getPrenom(),
            'raison_sociale'       => $this->company->getName(),
            'lien_reprise_dossier' => $this->generateUrl('project_request_recovery', ['hash' => $this->project->hash], UrlGeneratorInterface::ABSOLUTE_URL),
            'lien_fb'              => $facebookLink,
            'lien_tw'              => $twitterLink,
            'sujet'                => htmlentities($mailTemplate->subject, null, 'UTF-8'),
            'surl'                 => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_default'),
            'url'                  => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_default')
        ];

        $sRecipient = $client->getEmail();
        $sRecipient = $this->removeEmailSuffix(trim($sRecipient));

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($mailTemplate->type, $keywords);
        $message->setTo($sRecipient);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    /**
     * @param string $emailType
     */
    private function sendCommercialEmail($emailType)
    {
        if ($this->project->id_commercial > 0) {
            /** @var EntityManagerSimulator $entityManager */
            $entityManager = $this->get('unilend.service.entity_manager');

            /** @var \users $user */
            $user = $entityManager->getRepository('users');
            $user->get($this->project->id_commercial, 'id_user');

            /** @var \mail_templates $mailTemplate */
            $mailTemplate = $entityManager->getRepository('mail_templates');
            $mailTemplate->get($emailType, 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');

            $aReplacements = [
                '[ID_PROJET]'      => $this->project->id_project,
                '[LIEN_BO_PROJET]' => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_admin') . '/dossiers/edit/' . $this->project->id_project,
                '[RAISON_SOCIALE]' => $this->company->getName(),
                '[SURL]'           => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_default')
            ];

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($mailTemplate->type, $aReplacements, false);
            $message->setTo(trim($user->email));
            $mailer = $this->get('mailer');
            $mailer->send($message);
        }
    }

    /**
     * @param string $fieldName
     * @param int    $attachmentType
     * @return bool
     */
    private function uploadAttachment($fieldName, $attachmentType)
    {
        if (false === ($this->attachment instanceof \attachment)) {
            $this->attachment = $this->get('unilend.service.entity_manager')->getRepository('attachment');
        }

        if (false === ($this->attachmentType instanceof \attachment_type)) {
            $this->attachmentType = $this->get('unilend.service.entity_manager')->getRepository('attachment_type');
        }

        if (false === ($this->upload instanceof \upload)) {
            $this->upload = Loader::loadLib('upload');
        }

        if (false === ($this->attachmentHelper instanceof \attachment_helper)) {
            $this->attachmentHelper = Loader::loadLib('attachment_helper', [$this->attachment, $this->attachmentType, $this->getParameter('kernel.root_dir') . '/../']);
        }

        $resultUpload = false;
        if (isset($_FILES[$fieldName]['name']) && $fileInfo = pathinfo($_FILES[$fieldName]['name'])) {
            $fileName     = $fileInfo['filename'] . '_' . $this->project->id_project;
            $resultUpload = $this->attachmentHelper->upload($this->project->id_project, \attachment::PROJECT, $attachmentType, $fieldName, $this->upload, $fileName);
        }

        return $resultUpload;
    }

    /**
     * Check that hash is present in URL and valid
     * If hash is valid, check status and redirect to appropriate page
     * @param string  $route
     * @param string  $hash
     * @param Request $request
     * @return Response|null
     */
    private function checkProjectHash($route, $hash, Request $request)
    {
        if (1 !== preg_match('/^[a-z0-9-]{32,36}$/', $hash)) {
            throw new NotFoundHttpException('Invalid project hash');
        }

        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        $this->project = $entityManager->getRepository('projects');

        if (false === $this->project->get($hash, 'hash')) {
            return $this->redirectToRoute('home_borrower');
        }

        $this->company = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->project->id_company);
        $this->client = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->company->getIdClientOwner());

        if (self::PAGE_ROUTE_EMAILS === $route) {
            return null;
        }

        switch ($this->project->status) {
            case \projects_status::NOT_ELIGIBLE:
            case \projects_status::IMPOSSIBLE_AUTO_EVALUATION:
                if (false === in_array($route, [self::PAGE_ROUTE_END, self::PAGE_ROUTE_PROSPECT])) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $hash]);
                }
                break;
            case \projects_status::INCOMPLETE_REQUEST:
                if (empty($this->project->id_company_rating_history) && $route !== self::PAGE_ROUTE_SIMULATOR_START) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_SIMULATOR_START, ['hash' => $hash]);
                } elseif (false === empty($this->project->id_company_rating_history) && $route !== self::PAGE_ROUTE_CONTACT && empty($request->getSession()->get('partnerProjectRequest'))) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_CONTACT, ['hash' => $hash]);
                } elseif (false === empty($this->project->id_company_rating_history) && $route !== self::PAGE_ROUTE_PARTNER && false === empty($request->getSession()->get('partnerProjectRequest'))) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_PARTNER, ['hash' => $hash]);
                }
                break;
            case \projects_status::COMPLETE_REQUEST:
                if (false === in_array($route, [self::PAGE_ROUTE_FINANCE, self::PAGE_ROUTE_END, self::PAGE_ROUTE_FILES])) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_FINANCE, ['hash' => $hash]);
                }
                break;
            case \projects_status::COMMERCIAL_REVIEW:
            case \projects_status::PENDING_ANALYSIS:
                if (false === in_array($route, [self::PAGE_ROUTE_END, self::PAGE_ROUTE_FILES])) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_FILES, ['hash' => $hash]);
                }
                break;
            case \projects_status::ABANDONED:
            default: // Should correspond to "Revue analyste" and above
                if ($route !== self::PAGE_ROUTE_END) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $hash]);
                }
                break;
        }

        return null;
    }

    /**
     * Redirect to corresponding route and update status
     * @param string $route
     * @param int    $projectStatus
     * @param string $message
     * @return Response
     */
    private function redirectStatus($route, $projectStatus, $message = '')
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
        $oProjectManager = $this->get('unilend.service.project_manager');

        if ($this->project->status != $projectStatus) {
            $oProjectManager->addProjectStatus(Users::USER_ID_FRONT, $projectStatus, $this->project, 0, $message);
        }

        return $this->redirectToRoute($route, ['hash' => $this->project->hash]);
    }

    /**
     * @param string $email
     * @return string
     */
    private function removeEmailSuffix($email)
    {
        return preg_replace('/^(.*)-[0-9]+$/', '$1', $email);
    }

    /**
     * @param string $email
     * @param string $formOfAddress
     * @param string $firstName
     * @param string $lastName
     * @param string $position
     * @param string $mobilePhone
     */
    private function saveContactDetails($email, $formOfAddress, $firstName, $lastName, $position, $mobilePhone)
    {
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        /** @var \clients $clientRepository */
        $clientRepository = $this->get('unilend.service.entity_manager')->getRepository('clients');
        if ($clientRepository->existEmail($email) && $this->removeEmailSuffix($this->client->getEmail()) !== $email) {
            $email = $email . '-' . time();
        }

        $this->client->setEmail($email)
            ->setCivilite($formOfAddress)
            ->setPrenom($firstName)
            ->setNom($lastName)
            ->setFonction($position)
            ->setTelephone($mobilePhone)
            ->setIdLangue('fr')
            ->setSlug($ficelle->generateSlug($firstName . '-' . $lastName));

        $this->company->setEmailDirigeant($email)
            ->setEmailFacture($email);

        $this->get('doctrine.orm.entity_manager')->flush();
    }
}
