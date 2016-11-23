<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Altares;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Service\DataLayerCollector;
use Unilend\Bundle\FrontBundle\Service\SourceManager;
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

    /** @var \clients */
    private $client;

    /** @var \companies */
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
     * @Route("/depot_de_dossier/{hash}", name="project_request_index", requirements={"hash": "[0-9a-f]{32}"})
     * @Route("/depot_de_dossier/reprise/{hash}", name="project_request_recovery", requirements={"hash": "[0-9a-f]{32}"})
     * @Route("/depot_de_dossier/stand_by/{hash}", name="project_request_stand_by", requirements={"hash": "[0-9a-f]{32}"})
     *
     * @param string $hash
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
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        $amount  = null;
        $siren   = null;
        $email   = null;

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
                'values'  => [
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

        $this->client = $entityManager->getRepository('clients');

        if ($this->client->existEmail($email)) {
            $email .= '-' . time();
        }

        $this->client->email     = $email;
        $this->client->id_langue = 'fr';
        $this->client->status    = \clients::STATUS_ONLINE;

        $sourceManager = $this->get('unilend.frontbundle.service.source_manager');

        $this->client->source       = $sourceManager->getSource(SourceManager::SOURCE1);
        $this->client->source2      = $sourceManager->getSource(SourceManager::SOURCE2);
        $this->client->source3      = $sourceManager->getSource(SourceManager::SOURCE3);
        $this->client->slug_origine = $sourceManager->getSource(SourceManager::ENTRY_SLUG);

        if (empty($this->client->create())) {
            return $this->redirect($request->headers->get('referer'));
        } else{
            $request->getSession()->set(DataLayerCollector::SESSION_KEY_CLIENT_EMAIL, $this->client->email);
            $request->getSession()->set(DataLayerCollector::SESSION_KEY_BORROWER_CLIENT_ID, $this->client->id_client);
        }

        /** @var \clients_adresses $address */
        $address = $entityManager->getRepository('clients_adresses');
        $address->id_client = $this->client->id_client;
        $address->create();

        $this->company = $entityManager->getRepository('companies');
        $this->company->id_client_owner               = $this->client->id_client;
        $this->company->siren                         = $siren;
        $this->company->siret                         = $sirenLength === 14 ? str_replace(' ', '', $request->request->get('siren')) : '';
        $this->company->status_adresse_correspondance = 1;
        $this->company->email_dirigeant               = $email;
        $this->company->email_facture                 = $email;
        $this->company->create();

        $this->project = $entityManager->getRepository('projects');
        $this->project->id_company                           = $this->company->id_company;
        $this->project->amount                               = $amount;
        $this->project->ca_declara_client                    = 0;
        $this->project->resultat_exploitation_declara_client = 0;
        $this->project->fonds_propres_declara_client         = 0;
        $this->project->status                               = \projects_status::DEMANDE_SIMULATEUR;
        $this->project->create();

        return $this->start(\projects_status::COMPLETUDE_ETAPE_2);
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

        return $this->start(\projects_status::DEMANDE_SIMULATEUR);
    }

    /**
     * @param int $status
     * @return Response
     */
    private function start($status)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        $settings->get('Altares email alertes', 'type');
        $alertEmail = $settings->value;

        $settingsAltaresStatus = $entityManager->getRepository('settings');
        $settingsAltaresStatus->get('Altares status', 'type');
        $altaresStatus = $settingsAltaresStatus->value;

        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');

        try {
            $altares = $this->get('unilend.service.altares');
            $result  = $altares->isEligible($this->project);

            $altares->setCompanyData($this->company);

            if (false === $result['eligible']) {
                return $this->redirectToRoute(self::PAGE_ROUTE_PROSPECT, ['hash' => $this->project->hash]);
            }

            $altares->setProjectData($this->project);
            $altares->setCompanyBalance($this->company);

            /** @var \companies_bilans $companyAccount */
            $companyAccount = $entityManager->getRepository('companies_bilans');
            $balanceSheets  = $companyAccount->select('id_company = ' . $this->company->id_company, 'cloture_exercice_fiscal DESC', 0, 1);

            if (isset($balanceSheets[0]['id_bilan'])) {
                $this->project->id_dernier_bilan = $balanceSheets[0]['id_bilan'];
                $this->project->update();
            }
        } catch (\Exception $exception) {
            if ($altaresStatus) {
                $settingsAltaresStatus->value = 0;
                $settingsAltaresStatus->update();

                $logger->error(
                    $exception->getMessage(),
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $this->company->siren]
                );

                mail($alertEmail, '[ALERTE] Altares is down', 'Date ' . date('Y-m-d H:i:s') . '. ' . $exception->getMessage());
            }

            $this->project->retour_altares = Altares::RESPONSE_CODE_WS_ERROR;
            $this->project->update();
        }

        if (! $altaresStatus) {
            $settingsAltaresStatus->value = 1;
            $settingsAltaresStatus->update();

            mail($alertEmail, '[INFO] Altares is up', 'Date ' . date('Y-m-d H:i:s') . '. Altares is up now.');
        }

        return $this->redirectStatus(self::PAGE_ROUTE_CONTACT, $status);
    }

    /**
     * @Route("/depot_de_dossier/etape2/{hash}", name="project_request_contact", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("GET")
     *
     * @param string $hash
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

        /** @var EntityManager $entityManager */
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
        $borrowingMotive = $entityManager->getRepository('borrowing_motive');
        $template['borrowing_motives']  = $borrowingMotive->select();

        $settings->get('Durée des prêts autorisées', 'type');
        $template['loan_periods'] = explode(',', $settings->value);

        $session = $request->getSession()->get('projectRequest');
        $values  = isset($session['values']) ? $session['values'] : [];

        $template['form'] = [
            'errors' => isset($session['errors']) ? $session['errors'] : [],
            'values' => [
                'contact' => [
                    'civility'  => isset($values['contact']['civility']) ? $values['contact']['civility'] : $this->client->civilite,
                    'lastname'  => isset($values['contact']['lastname']) ? $values['contact']['lastname'] : $this->client->nom,
                    'firstname' => isset($values['contact']['firstname']) ? $values['contact']['firstname'] : $this->client->prenom,
                    'email'     => isset($values['contact']['email']) ? $values['contact']['email'] : $this->removeEmailSuffix($this->client->email),
                    'mobile'    => isset($values['contact']['mobile']) ? $values['contact']['mobile'] : $this->client->telephone,
                    'function'  => isset($values['contact']['function']) ? $values['contact']['function'] : $this->client->fonction
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
            'company_name'           => $this->company->name,
            'siren'                  => $this->company->siren,
            'amount'                 => $this->project->amount,
            'averageFundingDuration' => $this->get('unilend.service.project_manager')->getAverageFundingDuration($this->project->amount),
            'hash'                   => $this->project->hash
        ];

        $request->getSession()->remove('projectRequest');

        return $this->render('pages/project_request/contact.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/etape2/{hash}", name="project_request_contact_form", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("POST")
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function contactFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_CONTACT, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var EntityManager $entityManager */
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
            if (empty($request->request->get('advisor')['civility']) || false === in_array($request->request->get('advisor')['civility'], ['Mme', 'M.'])) {
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
                'values'  => $request->request->all(),
                'errors'  => $errors
            ]);

            return $this->redirectToRoute(self::PAGE_ROUTE_CONTACT, ['hash' => $this->project->hash]);
        }

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        $email   = $request->request->get('contact')['email'];

        if ($this->client->existEmail($email) && $this->removeEmailSuffix($this->client->email) !== $email) {
            $email = $email . '-' . time();
        }

        $this->client->email     = $email;
        $this->client->civilite  = $request->request->get('contact')['civility'];
        $this->client->prenom    = $request->request->get('contact')['firstname'];
        $this->client->nom       = $request->request->get('contact')['lastname'];
        $this->client->fonction  = $request->request->get('contact')['function'];
        $this->client->telephone = $request->request->get('contact')['mobile'];
        $this->client->id_langue = 'fr';
        $this->client->slug      = $ficelle->generateSlug($this->client->prenom . '-' . $this->client->nom);
        $this->client->update();

        $this->company->email_dirigeant = $email;
        $this->company->email_facture   = $email;
        $this->company->update();

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
                $advisorAddress = $entityManager->getRepository('clients_adresses');
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

            if ($tosAcceptation->get($settings->value, 'id_client = ' . $this->client->id_client . ' AND id_legal_doc')) {
                $tosAcceptation->update();
            } else {
                $tosAcceptation->id_legal_doc = $settings->value;
                $tosAcceptation->id_client    = $this->client->id_client;
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
        } catch (\Exception $exception) {
            $this->get('logger')->warning($exception->getMessage(), ['method' => __METHOD__, 'line' => __LINE__]);
        }

        if (empty($products)){
            return $this->redirectStatus(self::PAGE_ROUTE_PROSPECT, \projects_status::NOTE_EXTERNE_FAIBLE, 'Eligible à aucun produit');
        }

        return $this->redirectStatus(self::PAGE_ROUTE_FINANCE, \projects_status::COMPLETUDE_ETAPE_3);
    }

    /**
     * @Route("/depot_de_dossier/etape3/{hash}", name="project_request_finance", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("GET")
     *
     * @param string $hash
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

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \companies_actif_passif $companyAssetsDebts */
        $companyAssetsDebts = $entityManager->getRepository('companies_actif_passif');
        /** @var \companies_bilans $annualAccountsEntity */
        $annualAccountsEntity = $entityManager->getRepository('companies_bilans');

        $this->attachmentType         = $entityManager->getRepository('attachment_type');
        $attachmentTypes              = $this->attachmentType->getAllTypesForProjects('fr', true, [
            \attachment_type::PRESENTATION_ENTRERPISE,
            \attachment_type::RIB,
            \attachment_type::CNI_PASSPORTE_DIRIGEANT,
            \attachment_type::CNI_PASSPORTE_VERSO,
            \attachment_type::DERNIERE_LIASSE_FISCAL,
            \attachment_type::LIASSE_FISCAL_N_1,
            \attachment_type::LIASSE_FISCAL_N_2,
            \attachment_type::RAPPORT_CAC,
            \attachment_type::PREVISIONNEL,
            \attachment_type::BALANCE_CLIENT,
            \attachment_type::BALANCE_FOURNISSEUR,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_1,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_1,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_2,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_2,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_3,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_3,
            \attachment_type::SITUATION_COMPTABLE_INTERMEDIAIRE,
            \attachment_type::DERNIERS_COMPTES_CONSOLIDES,
            \attachment_type::STATUTS,
            \attachment_type::PRESENTATION_PROJET,
            \attachment_type::DERNIERE_LIASSE_FISCAL_HOLDING,
            \attachment_type::KBIS_HOLDING,
            \attachment_type::AUTRE1,
            \attachment_type::AUTRE2
        ]);
        $template['attachment_types'] = $this->attachmentType->changeLabelWithDynamicContent($attachmentTypes);

        $altaresCapitalStock     = 0;
        $altaresOperationIncomes = 0;
        $altaresRevenue          = 0;
        $annualAccounts          = $annualAccountsEntity->select('id_company = ' . $this->company->id_company, 'cloture_exercice_fiscal DESC', 0, 1);

        if (false === empty($annualAccounts)) {
            $companyAssetsDebts->get($annualAccounts[0]['id_bilan'], 'id_bilan');

            $altaresCapitalStock     = $companyAssetsDebts->capitaux_propres;
            $altaresOperationIncomes = $annualAccounts[0]['resultat_exploitation'];
            $altaresRevenue          = $annualAccounts[0]['ca'];
        }

        $session = $request->getSession()->get('projectRequest');
        $values  = isset($session['values']) ? $session['values'] : [];

        $template['form']['errors'] = isset($session['errors']) ? $session['errors'] : [];

        if (empty($this->company->rcs)) {
            $template['form']['values'] = [
                'ag_2035' => isset($values['ag_2035']) ? $values['ag_2035'] : (empty($this->project->ca_declara_client) ? (empty($altaresRevenue) ? '' : $altaresRevenue) : $this->project->ca_declara_client),
                ];
            $template['rcs'] = false;
        } else {
            $template['form']['values'] = [
                'dl' => isset($values['dl']) ? $values['dl'] : (empty($this->project->fonds_propres_declara_client) ? (empty($altaresCapitalStock) ? '' : $altaresCapitalStock) : $this->project->fonds_propres_declara_client),
                'fl' => isset($values['fl']) ? $values['fl'] : (empty($this->project->ca_declara_client) ? (empty($altaresRevenue) ? '' : $altaresRevenue) : $this->project->ca_declara_client),
                'gg' => isset($values['gg']) ? $values['gg'] : (empty($this->project->resultat_exploitation_declara_client) ? (empty($altaresOperationIncomes) ? '' : $altaresOperationIncomes) : $this->project->resultat_exploitation_declara_client)
            ];
            $template['rcs'] = true;
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
     * @Route("/depot_de_dossier/etape3/{hash}", name="project_request_finance_form", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("POST")
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function financeFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_FINANCE, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        $errors = [];
        $values = $request->request->get('finance');
        $values = is_array($values) ? $values : [];
        $files  = $request->files->all();

        if (empty($this->company->rcs)) {
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
                'values'  => $values,
                'errors'  => $errors
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

        if (empty($this->company->rcs)) {
            $this->project->ca_declara_client = $values['ag_2035'];
            $updateDeclaration = true;
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
            $annualAccounts          = $annualAccountsEntity->select('id_company = ' . $this->company->id_company, 'cloture_exercice_fiscal DESC', 0, 1);

            if (false === empty($annualAccounts)) {
                $companyAssetsDebts->get($annualAccounts[0]['id_bilan'], 'id_bilan');

                $altaresCapitalStock     = $companyAssetsDebts->capitaux_propres;
                $altaresRevenue          = $annualAccounts[0]['ca'];
                $altaresOperationIncomes = $annualAccounts[0]['resultat_exploitation'];
            }

            if ($altaresCapitalStock != $values['dl']) {
                $this->project->fonds_propres_declara_client = $values['dl'];
                $updateDeclaration = true;
            } elseif (false === empty($this->project->fonds_propres_declara_client) && $altaresCapitalStock == $values['dl']) {
                $this->project->fonds_propres_declara_client = 0;
                $updateDeclaration = true;
            }

            if ($altaresRevenue != $values['fl']) {
                $this->project->ca_declara_client = $values['fl'];
                $updateDeclaration = true;
            } elseif (false === empty($this->project->ca_declara_client) && $altaresRevenue == $values['fl']) {
                $this->project->ca_declara_client = 0;
                $updateDeclaration = true;
            }

            if ($altaresOperationIncomes != $values['gg']) {
                $this->project->resultat_exploitation_declara_client = $values['gg'];
                $updateDeclaration = true;
            } elseif (false === empty($this->project->resultat_exploitation_declara_client) && $altaresOperationIncomes == $values['gg']) {
                $this->project->resultat_exploitation_declara_client = 0;
                $updateDeclaration = true;
            }
        }

        if ($updateDeclaration) {
            $this->project->update();
        }

        if (isset($values['dl']) && $values['dl'] < 0) {
            return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::NOTE_EXTERNE_FAIBLE, 'Fonds propres négatifs');
        }

        if (isset($values['fl']) && $values['fl'] < \projects::MINIMUM_REVENUE) {
            return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::NOTE_EXTERNE_FAIBLE, 'CA trop faible');
        }

        if (isset($values['gg']) &&$values['gg'] < 0) {
            return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::NOTE_EXTERNE_FAIBLE, 'REX négatif');
        }

        if (isset($values['ag_2035']) &&$values['ag_2035'] < \projects::MINIMUM_REVENUE) {
            return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::NOTE_EXTERNE_FAIBLE, 'CA trop faible');
        }

        if ('true' === $request->request->get('extra_files')) {
            $this->project->process_fast = 1;
            $this->project->update();

            return $this->redirectToRoute(self::PAGE_ROUTE_FILES, ['hash' => $this->project->hash]);
        }

        $this->sendSubscriptionConfirmationEmail();

        return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::A_TRAITER);
    }

    /**
     * @Route("/depot_de_dossier/partenaire/{hash}", name="project_request_partner", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("GET")
     *
     * @param string $hash
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

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        $this->attachmentType         = $entityManager->getRepository('attachment_type');
        $attachmentTypes              = $this->attachmentType->getAllTypesForProjects('fr', true, [
            \attachment_type::PRESENTATION_ENTRERPISE,
            \attachment_type::RIB,
            \attachment_type::CNI_PASSPORTE_DIRIGEANT,
            \attachment_type::CNI_PASSPORTE_VERSO,
            \attachment_type::DERNIERE_LIASSE_FISCAL,
            \attachment_type::LIASSE_FISCAL_N_1,
            \attachment_type::LIASSE_FISCAL_N_2,
            \attachment_type::RAPPORT_CAC,
            \attachment_type::PREVISIONNEL,
            \attachment_type::BALANCE_CLIENT,
            \attachment_type::BALANCE_FOURNISSEUR,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_1,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_1,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_2,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_2,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_3,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_3,
            \attachment_type::SITUATION_COMPTABLE_INTERMEDIAIRE,
            \attachment_type::DERNIERS_COMPTES_CONSOLIDES,
            \attachment_type::STATUTS,
            \attachment_type::PRESENTATION_PROJET,
            \attachment_type::DERNIERE_LIASSE_FISCAL_HOLDING,
            \attachment_type::KBIS_HOLDING,
            \attachment_type::AUTRE1,
            \attachment_type::AUTRE2
        ]);
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
                    'civility'  => isset($values['contact']['civility']) ? $values['contact']['civility'] : $this->client->civilite,
                    'lastname'  => isset($values['contact']['lastname']) ? $values['contact']['lastname'] : $this->client->nom,
                    'firstname' => isset($values['contact']['firstname']) ? $values['contact']['firstname'] : $this->client->prenom,
                    'email'     => isset($values['contact']['email']) ? $values['contact']['email'] : $this->removeEmailSuffix($this->client->email),
                    'mobile'    => isset($values['contact']['mobile']) ? $values['contact']['mobile'] : $this->client->telephone,
                    'function'  => isset($values['contact']['function']) ? $values['contact']['function'] : $this->client->fonction
                ],
                'project' => [
                    'duration' => isset($values['project']['duration']) ? $values['project']['duration'] : $this->project->period
                ]
            ]
        ];

        $template['project'] = [
            'company_name' => $this->company->name,
            'siren'        => $this->company->siren,
            'amount'       => $this->project->amount,
            'hash'         => $this->project->hash
        ];

        $request->getSession()->remove('projectRequest');

        return $this->render('pages/project_request/partner.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/partenaire/{hash}", name="project_request_partner_form", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("POST")
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function partnerFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_PARTNER, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var EntityManager $entityManager */
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
        if (empty($request->request->get('terms'))) {
            $errors['terms'] = true;
        }

        if (false === empty($errors)) {
            $request->getSession()->set('projectRequest', [
                'values'  => $request->request->all(),
                'errors'  => $errors
            ]);

            return $this->redirectToRoute(self::PAGE_ROUTE_PARTNER, ['hash' => $this->project->hash]);
        }

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        $email   = $request->request->get('contact')['email'];

        if ($this->client->existEmail($email) && $this->removeEmailSuffix($this->client->email) !== $email) {
            $email = $email . '-' . time();
        }

        $this->client->email     = $email;
        $this->client->civilite  = $request->request->get('contact')['civility'];
        $this->client->prenom    = $request->request->get('contact')['firstname'];
        $this->client->nom       = $request->request->get('contact')['lastname'];
        $this->client->fonction  = $request->request->get('contact')['function'];
        $this->client->telephone = $request->request->get('contact')['mobile'];
        $this->client->id_langue = 'fr';
        $this->client->slug      = $ficelle->generateSlug($this->client->prenom . '-' . $this->client->nom);
        $this->client->update();

        $this->company->email_dirigeant = $email;
        $this->company->email_facture   = $email;
        $this->company->update();

        /** @var \acceptations_legal_docs $tosAcceptation */
        $tosAcceptation = $entityManager->getRepository('acceptations_legal_docs');
        $settings->get('Lien conditions generales depot dossier', 'type');

        if ($tosAcceptation->get($settings->value, 'id_client = ' . $this->client->id_client . ' AND id_legal_doc')) {
            $tosAcceptation->update();
        } else {
            $tosAcceptation->id_legal_doc = $settings->value;
            $tosAcceptation->id_client    = $this->client->id_client;
            $tosAcceptation->create();
        }

        $this->project->period = $request->request->get('project')['duration'];
        $this->project->update();

        $files  = $request->request->get('files', []);

        foreach ($request->files->all() as $fileName => $file) {
            if ($file instanceof UploadedFile && false === empty($files[$fileName])) {
                $this->uploadAttachment($fileName, $files[$fileName]);
            }
        }

        $this->sendSubscriptionConfirmationEmail();

        return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::A_TRAITER);
    }

    /**
     * @Route("/depot_de_dossier/prospect/{hash}", name="project_request_prospect", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("GET")
     *
     * @param string $hash
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
                    'civility'  => isset($values['civility']) ? $values['civility'] : $this->client->civilite,
                    'lastname'  => isset($values['lastname']) ? $values['lastname'] : $this->client->nom,
                    'firstname' => isset($values['firstname']) ? $values['firstname'] : $this->client->prenom,
                    'email'     => isset($values['email']) ? $values['email'] : $this->removeEmailSuffix($this->client->email),
                    'mobile'    => isset($values['mobile']) ? $values['mobile'] : $this->client->telephone,
                    'function'  => isset($values['function']) ? $values['function'] : $this->client->fonction
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
     * @Route("/depot_de_dossier/prospect/{hash}", name="project_request_prospect_form", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("POST")
     *
     * @param string $hash
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
                'values'  => $request->request->all(),
                'errors'  => $errors
            ]);

            return $this->redirectToRoute(self::PAGE_ROUTE_PROSPECT, ['hash' => $this->project->hash]);
        }

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        $email   = $request->request->get('email');

        if ($this->client->existEmail($email) && $this->removeEmailSuffix($this->client->email) !== $email) {
            $email = $email . '-' . time();
        }

        $this->client->email     = $email;
        $this->client->civilite  = $request->request->get('civility');
        $this->client->prenom    = $request->request->get('firstname');
        $this->client->nom       = $request->request->get('lastname');
        $this->client->fonction  = $request->request->get('function');
        $this->client->telephone = $request->request->get('mobile');
        $this->client->id_langue = 'fr';
        $this->client->slug      = $ficelle->generateSlug($this->client->prenom . '-' . $this->client->nom);
        $this->client->update();

        $this->company->email_dirigeant = $email;
        $this->company->email_facture   = $email;
        $this->company->update();

        return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $this->project->hash]);
    }

    /**
     * @Route("/depot_de_dossier/fichiers/{hash}", name="project_request_files", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("GET")
     *
     * @param string $hash
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

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        /** @var \attachment $attachment */
        $attachment  = $entityManager->getRepository('attachment');
        $attachments = array_column($attachment->select('type_owner = "' . \attachment::PROJECT . '" AND id_owner = ' . $this->project->id_project), 'id_type');

        $this->attachmentType         = $entityManager->getRepository('attachment_type');
        $attachmentTypes              = $this->attachmentType->getAllTypesForProjects('fr', true, [
            \attachment_type::PRESENTATION_ENTRERPISE,
            \attachment_type::RIB,
            \attachment_type::CNI_PASSPORTE_DIRIGEANT,
            \attachment_type::CNI_PASSPORTE_VERSO,
            \attachment_type::DERNIERE_LIASSE_FISCAL,
            \attachment_type::LIASSE_FISCAL_N_1,
            \attachment_type::LIASSE_FISCAL_N_2,
            \attachment_type::RAPPORT_CAC,
            \attachment_type::PREVISIONNEL,
            \attachment_type::BALANCE_CLIENT,
            \attachment_type::BALANCE_FOURNISSEUR,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_1,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_1,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_2,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_2,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_3,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_3,
            \attachment_type::SITUATION_COMPTABLE_INTERMEDIAIRE,
            \attachment_type::DERNIERS_COMPTES_CONSOLIDES,
            \attachment_type::STATUTS,
            \attachment_type::PRESENTATION_PROJET,
            \attachment_type::DERNIERE_LIASSE_FISCAL_HOLDING,
            \attachment_type::KBIS_HOLDING,
            \attachment_type::AUTRE1,
            \attachment_type::AUTRE2
        ]);

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
     * @Route("/depot_de_dossier/fichiers/{hash}", name="project_request_files_form", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("POST")
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function filesFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_FILES, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        $files  = $request->request->get('files', []);

        foreach ($request->files->all() as $fileName => $file) {
            if ($file instanceof UploadedFile && false === empty($files[$fileName])) {
                $this->uploadAttachment($fileName, $files[$fileName]);
            }
        }

        $this->sendCommercialEmail('notification-ajout-document-dossier');

        return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $this->project->hash]);
    }

    /**
     * @Route("/depot_de_dossier/fin/{hash}", name="project_request_end", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("GET")
     *
     * @param string $hash
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
            case \projects_status::ABANDON:
                $message  = $translator->trans('project-request_end-page-aborted-message');
                $title    = $translator->trans('project-request_end-page-aborted-title');
                $subtitle = $translator->trans('project-request_end-page-aborted-subtitle');
                break;
            CASE \projects_status::PAS_3_BILANS:
                $message  = $translator->trans('project-request_end-page-not-3-annual-accounts-message');
                $title    = $translator->trans('project-request_end-page-aborted-title');
                $subtitle = $translator->trans('project-request_end-page-aborted-subtitle');
                break;
            case \projects_status::REVUE_ANALYSTE:
            case \projects_status::COMITE:
            case \projects_status::PREP_FUNDING:
                $message  = $translator->trans('project-request_end-page-analysis-in-progress-message');
                $title    = $translator->trans('project-request_end-page-processing-title');
                $subtitle = $translator->trans('project-request_end-page-processing-subtitle');
                break;
            case \projects_status::COMPLETUDE_ETAPE_3:
            case \projects_status::A_TRAITER:
            case \projects_status::EN_ATTENTE_PIECES:
                $addMoreFiles = true;

                if (1 == $this->project->process_fast) {
                    $message = $translator->trans('project-request_end-page-fast-process-message');
                } else {
                    $message = $translator->trans('project-request_end-page-main-content');
                }
                break;
            case \projects_status::NOTE_EXTERNE_FAIBLE:
                $title    = $translator->trans('project-request_end-page-rejection-title');
                $subtitle = $translator->trans('project-request_end-page-rejection-subtitle');

                switch ($this->project->retour_altares) {
                    case Altares::RESPONSE_CODE_PROCEDURE:
                        $message = $translator->trans('project-request_end-page-collective-proceeding-message');
                        break;
                    case Altares::RESPONSE_CODE_INACTIVE:
                    case Altares::RESPONSE_CODE_UNKNOWN_SIREN:
                        $message = $translator->trans('project-request_end-page-no-siren-message');
                        break;
                    case Altares::RESPONSE_CODE_NOT_REGISTERED:
                        $message = $translator->trans('project-request_end-page-not-commercial-company-message');
                        break;
                    case Altares::RESPONSE_CODE_NEGATIVE_CAPITAL_STOCK:
                    case Altares::RESPONSE_CODE_NEGATIVE_RAW_OPERATING_INCOMES:
                        $message = $translator->trans('project-request_end-page-negative-operating-result-message');
                        break;
                    case Altares::RESPONSE_CODE_ELIGIBLE:
                        if (
                            $this->project->fonds_propres_declara_client < 0
                            || $this->project->resultat_exploitation_declara_client < 0
                            || $this->project->ca_declara_client <= \projects::MINIMUM_REVENUE
                        ) {
                            $message = $translator->trans('project-request_end-page-negative-operating-result-message');
                        }
                        break;
                    default:
                        $message = $translator->trans('project-request_end-page-external-rating-rejection-default-message');
                        break;
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
     * @Route("/depot_de_dossier/emails/{hash}", name="project_request_emails", requirements={"hash": "[0-9a-f]{32}"})
     * @Method("GET")
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function emailsAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_EMAILS, $hash, $request);

        if ($response instanceof Response) {
            return $response;
        }

        $this->project->stop_relances = 1;
        $this->project->update();

        $this->sendCommercialEmail('notification-stop-relance-dossier');

        return $this->render('pages/project_request/emails.html.twig');
    }

    /**
     * @return int[]
     */
    private function getMonthlyPaymentBoundaries()
    {
        $financialCalculation = new \PHPExcel_Calculation_Financial();

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        /** @var \project_period $projectPeriod */
        $projectPeriod = $entityManager->getRepository('project_period');
        $projectPeriod->getPeriod($this->project->period);

        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $entityManager->getRepository('project_rate_settings');
        $rateSettings = $projectRateSettings->getSettings(null, $projectPeriod->id_period);

        $minimumRate = min(array_column($rateSettings, 'rate_min'));
        $maximumRate = max(array_column($rateSettings, 'rate_max'));

        /** @var \tax_type $taxType */
        $taxType = $entityManager->getRepository('tax_type');
        $taxType->get(\tax_type::TYPE_VAT);
        $vatRate = $taxType->rate / 100;

        $settings->get('Commission remboursement', 'type');
        $commission = ($financialCalculation->PMT($settings->value / 12, $this->project->period, - $this->project->amount) - $financialCalculation->PMT(0, $this->project->period, - $this->project->amount)) * (1 + $vatRate);

        return [
            'minimum' => round($financialCalculation->PMT($minimumRate / 100 / 12, $this->project->period, - $this->project->amount) + $commission),
            'maximum' => round($financialCalculation->PMT($maximumRate / 100 / 12, $this->project->period, - $this->project->amount) + $commission)
        ];
    }

    private function sendSubscriptionConfirmationEmail()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        /** @var \mail_templates $mailTemplate */
        $mailTemplate = $entityManager->getRepository('mail_templates');
        $mailTemplate->get('confirmation-depot-de-dossier', 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');

        if (false === empty($this->project->id_prescripteur)) {
            /** @var \prescripteurs $advisor */
            $advisor = $entityManager->getRepository('prescripteurs');

            $advisor->get($this->project->id_prescripteur);
            $client->get($advisor->id_client);
        } else {
            $client = $this->client;
        }

        $settings->get('Facebook', 'type');
        $facebookLink = $settings->value;

        $settings->get('Twitter', 'type');
        $twitterLink = $settings->value;

        $keywords = [
            'prenom'               => $client->prenom,
            'raison_sociale'       => $this->company->name,
            'lien_reprise_dossier' => $this->generateUrl('project_request_recovery', ['hash' => $this->project->hash], UrlGeneratorInterface::ABSOLUTE_URL),
            'lien_fb'              => $facebookLink,
            'lien_tw'              => $twitterLink,
            'sujet'                => htmlentities($mailTemplate->subject, null, 'UTF-8'),
            'surl'                 => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_default'),
            'url'                  => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_default')
        ];

        $sRecipient = $client->email;
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
            /** @var EntityManager $entityManager */
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
                '[RAISON_SOCIALE]' => $this->company->name,
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
     * @param string $route
     * @param string $hash
     * @param Request $request
     * @return Response|null
     */
    private function checkProjectHash($route, $hash, Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        $this->project = $entityManager->getRepository('projects');

        if (false === $this->project->get($hash, 'hash')) {
            return $this->redirectToRoute('home_borrower');
        }

        $this->client  = $entityManager->getRepository('clients');
        $this->company = $entityManager->getRepository('companies');

        $this->company->get($this->project->id_company);
        $this->client->get($this->company->id_client_owner);

        if (self::PAGE_ROUTE_EMAILS === $route) {
            return null;
        }

        switch ($this->project->status) {
            case \projects_status::DEMANDE_SIMULATEUR:
                if ($route !== self::PAGE_ROUTE_SIMULATOR_START && empty($this->project->retour_altares)) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_SIMULATOR_START, ['hash' => $hash]);
                } elseif ($route !== self::PAGE_ROUTE_CONTACT && false === empty($this->project->retour_altares)) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_CONTACT, ['hash' => $hash]);
                }
                break;
            case \projects_status::PAS_3_BILANS:
            case \projects_status::NOTE_EXTERNE_FAIBLE:
                if (false === in_array($route, [self::PAGE_ROUTE_END, self::PAGE_ROUTE_PROSPECT])) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $hash]);
                }
                break;
            case \projects_status::COMPLETUDE_ETAPE_2:
                if ($route !== self::PAGE_ROUTE_CONTACT && empty($request->getSession()->get('partnerProjectRequest'))) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_CONTACT, ['hash' => $hash]);
                } elseif ($route !== self::PAGE_ROUTE_PARTNER && false === empty($request->getSession()->get('partnerProjectRequest'))) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_PARTNER, ['hash' => $hash]);
                }
                break;
            case \projects_status::COMPLETUDE_ETAPE_3:
                if ($this->project->process_fast == 1 && false === in_array($route, [self::PAGE_ROUTE_END, self::PAGE_ROUTE_FILES])) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_FILES, ['hash' => $hash]);
                } elseif ($this->project->process_fast == 0 && $route !== self::PAGE_ROUTE_FINANCE) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_FINANCE, ['hash' => $hash]);
                }
                break;
            case \projects_status::A_TRAITER:
            case \projects_status::EN_ATTENTE_PIECES:
            case \projects_status::ATTENTE_ANALYSTE:
                if (false === in_array($route, [self::PAGE_ROUTE_END, self::PAGE_ROUTE_FILES])) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_FILES, ['hash' => $hash]);
                }
                break;
            case \projects_status::ABANDON:
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
     * @param string $rejectionMessage
     * @return Response
     */
    private function redirectStatus($route, $projectStatus, $rejectionMessage = '')
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
        $oProjectManager = $this->get('unilend.service.project_manager');

        if ($this->project->status != $projectStatus) {
            $oProjectManager->addProjectStatus(\users::USER_ID_FRONT, $projectStatus, $this->project, 0, $rejectionMessage);
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
}
