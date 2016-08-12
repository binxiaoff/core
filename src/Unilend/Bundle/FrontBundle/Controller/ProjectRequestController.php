<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Translation\Translator;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\core\Loader;
use Unilend\librairies\Altares;

class ProjectRequestController extends Controller
{
    const PAGE_ROUTE_LANDING_PAGE = 'lp-depot-de-dossier';
    const PAGE_ROUTE_STEP_2       = 'project_request_contact';
    const PAGE_ROUTE_STEP_3       = 'project_request_finance';
    const PAGE_ROUTE_PROSPECT     = 'project_request_prospect';
    const PAGE_ROUTE_FILES        = 'project_request_files';
    const PAGE_ROUTE_END          = 'project_request_end';
    const PAGE_ROUTE_EMAILS       = 'project_request_emails';
    const PAGE_ROUTE_INDEX        = 'project_request_index';
    const PAGE_ROUTE_RECOVERY     = 'project_request_recovery';
    const PAGE_ROUTE_STAND_BY     = 'project_request_stand_by';

    /** @var \clients */
    private $client;

    /** @var \companies */
    private $company;

    /** @var \projects */
    private $project;

    /** @var int */
    private $projectStatus;

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
     * @return Response
     */
    public function indexAction($hash)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_INDEX, $hash);

        if ($response instanceof Response) {
            return $response;
        }

        return $this->redirectToRoute(self::PAGE_ROUTE_LANDING_PAGE);
    }

    /**
     * @Route("/depot_de_dossier/etape1", name="project_request_start")
     * @Method({"POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function startAction(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        $message = '';
        $errors  = [];
        $amount  = null;
        $siren   = null;
        $email   = null;

        /** @var Translator $translator */
        $translator = $this->get('translator');

        if (empty($request->request->get('montant'))) {
            $message = $translator->trans('borrower-landing-page_required-fields-error');
            $errors['amount'] = true;
        } else {
            $amount = str_replace(' ', '', $request->request->get('montant'));

            $settings->get('Somme à emprunter min', 'type');
            $minimumAmount = $settings->value;

            $settings->get('Somme à emprunter max', 'type');
            $maximumAmount = $settings->value;

            if (false === filter_var($amount, FILTER_VALIDATE_INT)) {
                $message = $translator->trans('borrower-landing-page_required-fields-error');
                $errors['amount'] = true;
            } elseif ($amount < $minimumAmount || $amount > $maximumAmount) {
                $message = $translator->trans('borrower-landing-page_amount-value-error');
                $errors['amount'] = true;
            }
        }

        if (
            empty($request->request->get('siren'))
            || false === filter_var($request->request->get('siren'), FILTER_VALIDATE_INT)
            || strlen($request->request->get('siren')) !== 9
        ) {
            $message = $translator->trans('borrower-landing-page_required-fields-error');
            $errors['siren'] = true;
        } else {
            $siren = $request->request->get('siren');
        }

        if (
            empty($request->request->get('email'))
            || false === filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)
        ) {
            $message = $translator->trans('borrower-landing-page_required-fields-error');
            $errors['email'] = true;
        } else {
            $email = $request->request->get('email');
        }

        if (false === empty($errors)) {
            $request->getSession()->set('project_request', [
                'message' => $message,
                'values'  => [
                    'amount' => $amount,
                    'siren'  => $siren,
                    'email'  => $email
                ],
                'errors'  => $errors
            ]);

            return $this->redirect($request->headers->get('referer'));
        }

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->get('security.token_storage')->setToken(null);
        }

        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');

        if ($client->existEmail($email)) {
            $email .= '-' . time();
        }

        $client->email     = $email;
        $client->id_langue = 'fr';
        $client->status    = \clients::STATUS_ONLINE;

        if (empty($client->create())) {
            return $this->redirect($request->headers->get('referer'));
        }

        /** @var \clients_adresses $address */
        $address = $entityManager->getRepository('clients_adresses');
        $address->id_client = $client->id_client;
        $address->create();

        $this->company = $entityManager->getRepository('companies');
        $this->company->id_client_owner               = $client->id_client;
        $this->company->siren                         = $siren;
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
        $this->project->create();

        $settings->get('Altares email alertes', 'type');
        $alertEmail = $settings->value;

        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');

        try {
            $altares = new Altares();
            $result  = $altares->getEligibility($siren);
        } catch (\Exception $exception) {
            $logger->error(
                'Calling Altares::getEligibility() using SIREN ' . $siren . ' - Exception message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]
            );

            mail($alertEmail, '[ALERTE] ERREUR ALTARES 2', 'Date ' . date('Y-m-d H:i:s') . '' . $exception->getMessage());

            return $this->redirectStatus(self::PAGE_ROUTE_STEP_2, \projects_status::COMPLETUDE_ETAPE_2);
        }

        if (false === empty($result->exception)) {
            $logger->error(
                'Altares error code: ' . $result->exception->code . ' - Altares error description: ' . $result->exception->description . ' - Altares error: ' . $result->exception->erreur,
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]
            );

            mail($alertEmail, '[ALERTE] ERREUR ALTARES 1', 'Date ' . date('Y-m-d H:i:s') . 'SIREN : ' . $siren . ' | ' . $result->exception->code . ' | ' . $result->exception->description . ' | ' . $result->exception->erreur);

            return $this->redirectStatus(self::PAGE_ROUTE_STEP_2, \projects_status::COMPLETUDE_ETAPE_2);
        }

        $this->project->retour_altares = $result->myInfo->codeRetour;

        $altares->setCompanyData($this->company, $result->myInfo);

        switch ($result->myInfo->eligibility) {
            case 'Oui':
                $altares->setProjectData($this->project, $result->myInfo);
                $altares->setCompanyBalance($this->company);

                /** @var \companies_bilans $companyAccount */
                $companyAccount = $entityManager->getRepository('companies_bilans');

                $this->project->id_dernier_bilan = $companyAccount->select('id_company = ' . $this->company->id_company, 'cloture_exercice_fiscal DESC', 0, 1)[0]['id_bilan'];
                $this->project->update();

                $companyCreationDate = new \DateTime($this->company->date_creation);
                if ($companyCreationDate->diff(new \DateTime())->days < \projects::MINIMUM_CREATION_DAYS_PROSPECT) {
                    return $this->redirectStatus(self::PAGE_ROUTE_PROSPECT, \projects_status::PAS_3_BILANS);
                }

                return $this->redirectStatus(self::PAGE_ROUTE_STEP_2, \projects_status::COMPLETUDE_ETAPE_2);
            case 'Non':
            default:
                $this->project->update();

                if (in_array($result->myInfo->codeRetour, [Altares::RESPONSE_CODE_NEGATIVE_CAPITAL_STOCK, Altares::RESPONSE_CODE_NEGATIVE_RAW_OPERATING_INCOMES])) {
                    return $this->redirectStatus(self::PAGE_ROUTE_PROSPECT, \projects_status::NOTE_EXTERNE_FAIBLE, $result->myInfo->motif);
                }

                return $this->redirectStatus(self::PAGE_ROUTE_END, \projects_status::NOTE_EXTERNE_FAIBLE, $result->myInfo->motif);
        }
    }

    /**
     * @Route("/depot_de_dossier/etape2/{hash}", name="project_request_contact", requirements={"hash": "[0-9a-f]{32}"})
     * @Method({"GET"})
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function contactAction($hash, Request $request)
    {
        $template = [];
        $response = $this->checkProjectHash(self::PAGE_ROUTE_STEP_2, $hash);

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

        // @todo arbitrary default value
        $template['averageFundingDuration'] = 15;
        $settings->get('Durée moyenne financement', 'type');
        foreach (json_decode($settings->value) as $averageFundingDuration) {
            if ($this->project->amount >= $averageFundingDuration->min && $this->project->amount <= $averageFundingDuration->max) {
                $template['averageFundingDuration'] = $averageFundingDuration->heures / 24;
            }
        }

        $settings->get('Lien conditions generales depot dossier', 'type');

        /** @var \tree $tree */
        $tree = $entityManager->getRepository('tree');
        $tree->get(['id_tree' => $settings->value]);
        $template['termsOfSaleLink'] = $this->generateUrl($tree->slug);

        /** @var \project_need $projectNeeds */
        $projectNeeds = $entityManager->getRepository('project_need');
        $template['projectNeeds'] = array_column($projectNeeds->select(null, 'label ASC'), 'label', 'id_project_need');

        $settings->get('Durée des prêts autorisées', 'type');
        $template['loanPeriods'] = explode(',', $settings->value);

        $session = $request->getSession()->get('project_request');
        $values  = isset($session['values']) ? $session['values'] : [];

        $template['form'] = [
            'errors' => isset($session['errors']) ? $session['errors'] : [],
            'values' => [
                'contact' => [
                    'civility'   => isset($values['contact']['civility']) ? $values['contact']['civility'] : $this->client->civilite,
                    'lastname'   => isset($values['contact']['lastname']) ? $values['contact']['lastname'] : $this->client->nom,
                    'firstname'  => isset($values['contact']['firstname']) ? $values['contact']['firstname'] : $this->client->prenom,
                    'email'      => isset($values['contact']['email']) ? $values['contact']['email'] : $this->removeEmailSuffix($this->client->email),
                    'email_conf' => isset($values['contact']['email_conf']) ? $values['contact']['email_conf'] : '',
                    'mobile'     => isset($values['contact']['mobile']) ? $values['contact']['mobile'] : $this->client->telephone,
                    'function'   => isset($values['contact']['function']) ? $values['contact']['function'] : $this->client->fonction
                ],
                'manager' => isset($values['manager']) ? $values['manager'] : (isset($advisorClient) ? 'no' : 'yes'),
                'advisor' => [
                    'civility'   => isset($values['advisor']['civility']) ? $values['advisor']['civility'] : (isset($advisorClient) ? $advisorClient->civilite : ''),
                    'lastname'   => isset($values['advisor']['lastname']) ? $values['advisor']['lastname'] : (isset($advisorClient) ? $advisorClient->nom : ''),
                    'firstname'  => isset($values['advisor']['firstname']) ? $values['advisor']['firstname'] : (isset($advisorClient) ? $advisorClient->prenom : ''),
                    'email'      => isset($values['advisor']['email']) ? $values['advisor']['email'] : (isset($advisorClient) ? $this->removeEmailSuffix($advisorClient->email) : ''),
                    'email_conf' => isset($values['advisor']['email_conf']) ? $values['advisor']['email_conf'] : '',
                    'mobile'     => isset($values['advisor']['mobile']) ? $values['advisor']['mobile'] : (isset($advisorClient) ? $advisorClient->telephone : ''),
                    'function'   => isset($values['advisor']['function']) ? $values['advisor']['function'] : (isset($advisorClient) ? $advisorClient->fonction : '')
                ],
                'project' => [
                    'duration'    => isset($values['project']['duration']) ? $values['project']['duration'] : $this->project->period,
                    'description' => isset($values['project']['description']) ? $values['project']['description'] : $this->project->comments,
                    'need'        => isset($values['project']['need']) ? $values['project']['need'] : $this->project->id_project_need
                ]
            ]
        ];

        $template['project'] = [
            'companyName' => $this->company->name,
            'siren'       => $this->company->siren,
            'amount'      => $this->project->amount,
            'hash'        => $this->project->hash
        ];

        $request->getSession()->remove('project_request');

        return $this->render('pages/project_request/contact.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/etape2/{hash}", name="project_request_contact_form", requirements={"hash": "[0-9a-f]{32}"})
     * @Method({"POST"})
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function contactFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_STEP_2, $hash);

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
        if (empty($request->request->get('contact')['email_conf']) || $request->request->get('contact')['email'] !== $request->request->get('contact')['email_conf']) {
            $errors['contact']['email_conf'] = true;
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
        if (empty($request->request->get('project')['need'])) {
            $errors['project']['need'] = true;
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
            if (empty($request->request->get('advisor')['email_conf']) || $request->request->get('advisor')['email'] !== $request->request->get('advisor')['email_conf']) {
                $errors['advisor']['email_conf'] = true;
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
            $request->getSession()->set('project_request', [
                'values'  => $request->request->all(),
                'errors'  => $errors
            ]);

            return $this->redirectToRoute(self::PAGE_ROUTE_STEP_2, ['hash' => $this->project->hash]);
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

        $this->project->period          = $request->request->get('project')['duration'];
        $this->project->comments        = $request->request->get('project')['description'];
        $this->project->id_project_need = $request->request->get('project')['need'];
        $this->project->update();

        return $this->redirectStatus(self::PAGE_ROUTE_STEP_3, \projects_status::COMPLETUDE_ETAPE_3);
    }

    /**
     * @Route("/depot_de_dossier/etape3/{hash}", name="project_request_finance", requirements={"hash": "[0-9a-f]{32}"})
     * @Method({"GET"})
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function financeAction($hash, Request $request)
    {
        $template = [];
        $response = $this->checkProjectHash(self::PAGE_ROUTE_STEP_3, $hash);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \companies_actif_passif $companyAssetsDebts */
        $companyAssetsDebts = $entityManager->getRepository('companies_actif_passif');
        /** @var \companies_bilans $annualAccountsEntity */
        $annualAccountsEntity = $entityManager->getRepository('companies_bilans');

        $this->attachmentType = $entityManager->getRepository('attachment_type');
        $attachmentTypes      = $this->attachmentType->getAllTypesForProjects('fr', true, array(
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
        ));
        $template['attachmentTypes'] = $this->attachmentType->changeLabelWithDynamicContent($attachmentTypes);

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

        $session = $request->getSession()->get('project_request');
        $values  = isset($session['values']) ? $session['values'] : [];

        $template['form'] = [
            'errors' => isset($session['errors']) ? $session['errors'] : [],
            'values' => [
                'dl' => isset($values['dl']) ? $values['dl'] : (empty($this->project->fonds_propres_declara_client) ? (empty($altaresCapitalStock) ? '' : $altaresCapitalStock) : $this->project->fonds_propres_declara_client),
                'fl' => isset($values['fl']) ? $values['fl'] : (empty($this->project->ca_declara_client) ? (empty($altaresRevenue) ? '' : $altaresRevenue) : $this->project->ca_declara_client),
                'gg' => isset($values['gg']) ? $values['gg'] : (empty($this->project->resultat_exploitation_declara_client) ? (empty($altaresOperationIncomes) ? '' : $altaresOperationIncomes) : $this->project->resultat_exploitation_declara_client)
            ]
        ];

        $template['project'] = [
            'hash' => $this->project->hash
        ];

        $request->getSession()->remove('project_request');

        return $this->render('pages/project_request/finance.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/etape3/{hash}", name="project_request_finance_form", requirements={"hash": "[0-9a-f]{32}"})
     * @Method({"POST"})
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function financeFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_STEP_3, $hash);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        $errors = [];
        $values = $request->request->get('finance');
        $values = is_array($values) ? $values : [];
        $files  = $request->files->all();

        if (false === isset($values['dl']) || $values['dl'] === '') {
            $errors['dl'] = true;
        }
        if (false === isset($values['fl']) || $values['fl'] === '') {
            $errors['fl'] = true;
        }
        if (false === isset($values['gg']) || $values['gg'] === '') {
            $errors['gg'] = true;
        }
        if (empty($files['accounts']) || false === ($files['accounts'] instanceof UploadedFile)) {
            $errors['accounts'] = true;
        } elseif (false === $this->uploadAttachment('accounts', \attachment_type::DERNIERE_LIASSE_FISCAL)) {
            $errors['accounts'] = [
                'message' => $this->upload->getErrorType()
            ];
        }

        if (false === empty($errors)) {
            $request->getSession()->set('project_request', [
                'values'  => $values,
                'errors'  => $errors
            ]);

            return $this->redirectToRoute(self::PAGE_ROUTE_STEP_3, ['hash' => $this->project->hash]);
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

        $updateDeclaration = false;
        $values['dl']       = $ficelle->cleanFormatedNumber($values['dl']);
        $values['fl']       = $ficelle->cleanFormatedNumber($values['fl']);
        $values['gg']       = $ficelle->cleanFormatedNumber($values['gg']);

        /** @var \companies_actif_passif $companyAssetsDebts */
        $companyAssetsDebts = $entityManager->getRepository('companies_actif_passif');
        /** @var \companies_bilans $annualAccountsEntity */
        $annualAccountsEntity = $entityManager->getRepository('companies_bilans');

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

        if ($altaresCapitalStock != $values['dl']) {
            $this->project->fonds_propres_declara_client = $values['dl'];
            $updateDeclaration = true;
        } elseif (false === empty($this->project->fonds_propres_declara_client) && $altaresCapitalStock == $values['dl']) {
            $this->project->fonds_propres_declara_client = 0;
            $updateDeclaration = true;
        }

        if ($altaresOperationIncomes != $values['fl']) {
            $this->project->resultat_exploitation_declara_client = $values['fl'];
            $updateDeclaration = true;
        } elseif (false === empty($this->project->resultat_exploitation_declara_client) && $altaresOperationIncomes == $values['fl']) {
            $this->project->resultat_exploitation_declara_client = 0;
            $updateDeclaration = true;
        }

        if ($altaresRevenue != $values['gg']) {
            $this->project->ca_declara_client = $values['gg'];
            $updateDeclaration = true;
        } elseif (false === empty($this->project->ca_declara_client) && $altaresRevenue == $values['gg']) {
            $this->project->ca_declara_client = 0;
            $updateDeclaration = true;
        }

        if ($updateDeclaration) {
            $this->project->update();
        }

        if ($values['dl'] < 0) {
            return $this->redirectStatus(self::PAGE_ROUTE_PROSPECT, \projects_status::NOTE_EXTERNE_FAIBLE, 'Fonds propres négatifs');
        }

        if ($values['fl'] < 0) {
            return $this->redirectStatus(self::PAGE_ROUTE_PROSPECT, \projects_status::NOTE_EXTERNE_FAIBLE, 'REX négatif');
        }

        if ($values['gg'] < \projects::MINIMUM_REVENUE) {
            return $this->redirectStatus(self::PAGE_ROUTE_PROSPECT, \projects_status::NOTE_EXTERNE_FAIBLE, 'CA trop faibles');
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
     * @Route("/depot_de_dossier/prospect/{hash}", name="project_request_prospect", requirements={"hash": "[0-9a-f]{32}"})
     * @Method({"GET"})
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function prospectAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_PROSPECT, $hash);

        if ($response instanceof Response) {
            return $response;
        }

        $session = $request->getSession()->get('project_request');
        $values  = isset($session['values']) ? $session['values'] : [];

        $template = [
            'form' => [
                'errors'  => isset($session['errors']) ? $session['errors'] : [],
                'values'  => [
                    'civility'   => isset($values['civility']) ? $values['civility'] : $this->client->civilite,
                    'lastname'   => isset($values['lastname']) ? $values['lastname'] : $this->client->nom,
                    'firstname'  => isset($values['firstname']) ? $values['firstname'] : $this->client->prenom,
                    'email'      => isset($values['email']) ? $values['email'] : $this->removeEmailSuffix($this->client->email),
                    'email_conf' => isset($values['email_conf']) ? $values['email_conf'] : '',
                    'mobile'     => isset($values['mobile']) ? $values['mobile'] : $this->client->telephone,
                    'function'   => isset($values['function']) ? $values['function'] : $this->client->fonction
                ]
            ],
            'project' => [
                'hash' => $this->project->hash
            ]
        ];

        $request->getSession()->remove('project_request');

        return $this->render('pages/project_request/prospect.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/prospect/{hash}", name="project_request_prospect_form", requirements={"hash": "[0-9a-f]{32}"})
     * @Method({"POST"})
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function prospectFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_PROSPECT, $hash);

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
        if (empty($request->request->get('email_conf')) || $request->request->get('email') !== $request->request->get('email_conf')) {
            $errors['email_conf'] = true;
        }
        if (empty($request->request->get('mobile'))) {
            $errors['mobile'] = true;
        }
        if (empty($request->request->get('function'))) {
            $errors['function'] = true;
        }

        if (false === empty($errors)) {
            $request->getSession()->set('project_request', [
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
     * @Method({"GET"})
     *
     * @param string $hash
     * @return Response
     */
    public function filesAction($hash)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_FILES, $hash);

        if ($response instanceof Response) {
            return $response;
        }

        $template = [
            'project' => [
                'hash' => $this->project->hash
            ]
        ];

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        $this->attachmentType        = $entityManager->getRepository('attachment_type');
        $attachmentTypes             = $this->attachmentType->getAllTypesForProjects('fr', true, [
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
        $template['attachmentTypes'] = $this->attachmentType->changeLabelWithDynamicContent($attachmentTypes);

        /** @var \projects_last_status_history $projectLastStatusHistory */
        $projectLastStatusHistory = $entityManager->getRepository('projects_last_status_history');
        $projectLastStatusHistory->get($this->project->id_project, 'id_project');

        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $entityManager->getRepository('projects_status_history');
        $projectStatusHistory->get($projectLastStatusHistory->id_project_status_history, 'id_project_status_history');

        if (false === empty($projectStatusHistory->content)) {
            $oDOMElement = new \DOMDocument();
            $oDOMElement->loadHTML($projectStatusHistory->content);
            $oList = $oDOMElement->getElementsByTagName('ul');
            if ($oList->length > 0 && $oList->item(0)->childNodes->length > 0) {
                $template['attachmentsList'] = $oList->item(0)->C14N();
            }
        }

        return $this->render('pages/project_request/files.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/fichiers/{hash}", name="project_request_files_form", requirements={"hash": "[0-9a-f]{32}"})
     * @Method({"POST"})
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function filesFormAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_FILES, $hash);

        if ($response instanceof Response) {
            return $response;
        }

        $files  = empty($request->request->get('files')) ? [] : $request->request->get('files');

        foreach ($request->files->all() as $fileName => $file) {
            if ($file instanceof UploadedFile && false === empty($files[$fileName])) {
                $this->uploadAttachment($fileName, $request->request->get('files')[$fileName]);
            }
        }

        $this->sendCommercialEmail('notification-ajout-document-dossier');

        return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $this->project->hash]);
    }

    /**
     * @Route("/depot_de_dossier/fin/{hash}", name="project_request_end", requirements={"hash": "[0-9a-f]{32}"})
     * @Method({"GET"})
     *
     * @param string $hash
     * @param Request $request
     * @return Response
     */
    public function endAction($hash, Request $request)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_END, $hash);

        if ($response instanceof Response) {
            return $response;
        }

        /** @var Translator $translator */
        $translator = $this->get('translator');

        $addMoreFiles = false;
        $message      = $translator->trans('project-request_end-page-not-entitled-message');

        switch ($this->projectStatus) {
            case \projects_status::ABANDON:
                $message = $translator->trans('project-request_end-page-aborded-message');
                break;
            CASE \projects_status::PAS_3_BILANS:
                $message = $translator->trans('project-request_end-page-not-3-annual-accounts-message');
                break;
            case \projects_status::REVUE_ANALYSTE:
            case \projects_status::COMITE:
            case \projects_status::PREP_FUNDING:
                $message = $translator->trans('project-request_end-page-analysis-in-progress-message');
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
                }
                break;
        }

        $template = [
            'addMoreFiles' => $addMoreFiles,
            'message'      => $message,
            'project'      => [
                'hash' => $this->project->hash
            ]
        ];

        return $this->render('pages/project_request/end.html.twig', $template);
    }

    /**
     * @Route("/depot_de_dossier/emails/{hash}", name="project_request_emails", requirements={"hash": "[0-9a-f]{32}"})
     * @Method({"GET"})
     *
     * @param string $hash
     * @return Response
     */
    public function emailsAction($hash)
    {
        $response = $this->checkProjectHash(self::PAGE_ROUTE_EMAILS, $hash);

        if ($response instanceof Response) {
            return $response;
        }

        $this->project->stop_relances = 1;
        $this->project->update();

        $this->sendCommercialEmail('notification-stop-relance-dossier');

        return $this->render('pages/project_request/emails.html.twig');
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
     * @return Response|null
     */
    private function checkProjectHash($route, $hash)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        $this->project = $entityManager->getRepository('projects');

        if (false === $this->project->get($hash, 'hash')) {
            return $this->redirectToRoute(self::PAGE_ROUTE_LANDING_PAGE);
        }

        /** @var \projects_status $projectStatus */
        $projectStatus = $entityManager->getRepository('projects_status');
        $this->client  = $entityManager->getRepository('clients');
        $this->company = $entityManager->getRepository('companies');

        $this->company->get($this->project->id_company);
        $this->client->get($this->company->id_client_owner);

        $projectStatus->getLastStatut($this->project->id_project);
        $this->projectStatus = $projectStatus->status;

        if (self::PAGE_ROUTE_EMAILS === $route) {
            return null;
        }

        switch ($this->projectStatus) {
            case \projects_status::PAS_3_BILANS:
            case \projects_status::NOTE_EXTERNE_FAIBLE:
                if (false === in_array($route, [self::PAGE_ROUTE_END, self::PAGE_ROUTE_PROSPECT])) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_END, ['hash' => $hash]);
                }
                break;
            case \projects_status::COMPLETUDE_ETAPE_2:
                if ($route !== self::PAGE_ROUTE_STEP_2) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_STEP_2, ['hash' => $hash]);
                }
                break;
            case \projects_status::COMPLETUDE_ETAPE_3:
                if ($this->project->process_fast == 1 && false === in_array($route, [self::PAGE_ROUTE_END, self::PAGE_ROUTE_FILES])) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_FILES, ['hash' => $hash]);
                } elseif ($this->project->process_fast == 0 && $route !== self::PAGE_ROUTE_STEP_3) {
                    return $this->redirectToRoute(self::PAGE_ROUTE_STEP_3, ['hash' => $hash]);
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

        if ($this->projectStatus != $projectStatus) {
            $oProjectManager->addProjectStatus(\users::USER_ID_FRONT, $projectStatus, $this->project, 0, $rejectionMessage);
        }

        return $this->redirectToRoute($route, ['hash' => $this->project->hash]);
    }

    private function removeEmailSuffix($sEmail)
    {
        return preg_replace('/^(.*)-[0-9]+$/', '$1', $sEmail);
    }
}
