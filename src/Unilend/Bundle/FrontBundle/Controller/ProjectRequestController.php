<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    const PAGE_ROUTE_FILES        = 'fichiers';
    const PAGE_ROUTE_PROSPECT     = 'prospect';
    const PAGE_ROUTE_END          = 'fin';
    const PAGE_ROUTE_EMAILS       = 'emails';
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
            $amount = str_replace([',', ' '], ['.', ''], $request->request->get('montant'));

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
