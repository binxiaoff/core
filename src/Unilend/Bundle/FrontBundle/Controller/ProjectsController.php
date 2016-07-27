<?php

namespace Unilend\Bundle\FrontBundle\Controller;


use Cache\Adapter\Memcache\MemcacheCachePool;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\BidManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\FrontBundle\Service\HighchartsService;
use Unilend\Bundle\FrontBundle\Service\LenderAccountDisplayManager;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Unilend\core\Loader;

class ProjectsController extends Controller
{
    /**
     * @Route("/projets-a-financer/{page}", defaults={"page" = "1"}, name="project_list")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function projectListAction($page)
    {
        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        /** @var \projects $projects */
        $projects = $this->get('unilend.service.entity_manager')->getRepository('projects');

        $limit = $this->getPaginationStartAndLimit($page)['limit'];
        $start = $this->getPaginationStartAndLimit($page)['start'];

        /** @var array $rateRange */
        $rateRange = [\bids::BID_RATE_MIN, \bids::BID_RATE_MAX];

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            && $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            /** @var BaseUser $user */
            $user = $this->getUser();
            $aTemplateVariables['projects'] = $projectDisplayManager->getProjectsForDisplay(
                null,
                'p.date_retrait_full DESC',
                $rateRange,
                $start,
                $limit,
                $user->getClientId()
            );
        } else {
            $aTemplateVariables['projects'] = $projectDisplayManager->getProjectsForDisplay(
                null,
                'p.date_retrait_full DESC',
                $rateRange,
                $start,
                $limit
            );
        }

        $aTemplateVariables['projectsInFunding']  = $projects->countSelectProjectsByStatus(\projects_status::EN_FUNDING);
        $aTemplateVariables['paginationSettings'] = $this->pagination($page, $limit);
        $aTemplateVariables['showPagination']     = true;

        return $this->render('pages/projects.html.twig', $aTemplateVariables);
    }

    /**
     * @Route("/projects/map", name="projects_map")
     * @Method("POST")
     */
    public function projectsMapViewAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            /** @var ProjectDisplayManager $projectDisplayManager */
            $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');

            //TODO get projects details for mapView and return them

        }

        return new Response('not an ajax request');
    }

    private function pagination($page, $limit)
    {
        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');

        $totalNumberProjects = $projectDisplayManager->getTotalNumberOfDisplayedProjects();
        $totalPages          = ceil($totalNumberProjects / $limit);

        $paginationSettings = [
            'itemsPerPage'      => $limit,
            'totalItems'        => $totalNumberProjects,
            'totalPages'        => $totalPages,
            'currentIndex'      => $page,
            'currentIndexItems' => $page * $limit,
            'remainingItems'    => ceil($totalNumberProjects - ($totalNumberProjects / $limit)),
            'pageUrl'           => 'projects'
        ];

        if ($totalPages > 1) {
            $paginationSettings['indexPlan'] = [$page - 1, $page, $page + 1];

            if ($page > $totalPages - 3) {
                $paginationSettings['indexPlan'] = [$totalPages - 3, $totalPages - 2, $totalPages - 1];
            } elseif ($page == $totalPages - 3) {
                $paginationSettings['indexPlan'] = [$totalPages - 4, $totalPages - 3, $totalPages - 2, $totalPages - 1];
            } elseif (4 == $page) {
                $paginationSettings['indexPlan'] = [2, 3, 4, 5];
            } elseif ($page < 4) {
                $paginationSettings['indexPlan'] = [2, 3, 4];
            }
        }

        return $paginationSettings;
    }

    private function getPaginationStartAndLimit($page)
    {
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('nombre-de-projets-par-page', 'type');
        $limit = (int) $settings->value;
        $start = ($page > 1) ? $limit * ($page - 1) : 0;

        return ['start' => $start, 'limit' => $limit];
    }

    /**
     * @Route("/projects/detail/{projectSlug}", name="project_detail")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function projectDetailAction($projectSlug, Request $request)
    {
        $project = $this->checkProjectAndRedirect($projectSlug);

        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        /** @var AuthorizationChecker $authorizationChecker */
        $authorizationChecker = $this->get('security.authorization_checker');

        $template            = [];
        $template['project'] = $projectDisplayManager->getProjectInformationForDisplay($project);
        $template['finance'] = $projectDisplayManager->getProjectFinancialData($project);
        $firstBalanceSheet = current($template['finance']);
        $template['financeColumns'] = [
            'balanceSheet' => array_keys($firstBalanceSheet['balanceSheet']),
            'assets'       => array_keys($firstBalanceSheet['assets']),
            'debts'        => array_keys($firstBalanceSheet['debts']),
        ];

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')
            && $authorizationChecker->isGranted('ROLE_LENDER')
        ) {
            /** @var BaseUser $user */
            $user = $this->getUser();
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
            $lenderAccount->get($user->getClientId(), 'id_client_owner');

            /** @var LenderAccountDisplayManager $lenderAccountDisplayManager */
            $lenderAccountDisplayManager = $this->get('unilend.frontbundle.service.lender_account_display_manager');
            $template['lenderOnProject'] = $lenderAccountDisplayManager->getLenderActivityForProject($lenderAccount, $project);

            if (false === empty($request->getSession()->get('bidMessage'))) {
                $template['lender']['bidMessage'] = $request->getSession()->get('bidMessage');
                $request->getSession()->remove('bidMessage');
            }
        }

        $template['conditions'] = [
            'bids'    => $template['project']['status'] == \projects_status::EN_FUNDING,
            'myBids'  => isset($template['lenderOnProject']) && $template['lenderOnProject']['bids']['count'] > 0,
            'finance' => $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY'),
            'history' => $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') && $authorizationChecker->isGranted('ROLE_LENDER') && ($template['project']['status'] == \projects_status::FUNDE || $template['project']['status'] >= \projects_status::REMBOURSEMENT),
            'canBid'  => isset($user) && $user instanceof UserLender && $user->hasAcceptedCurrentTerms()
        ];

        return $this->render('pages/project_detail.html.twig', $template);
    }

    /**
     * @param string $projectSlug
     * @return \projects
     */
    private function checkProjectAndRedirect($projectSlug)
    {
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

        if (false === $project->get($projectSlug, 'slug')) {
            throw $this->createNotFoundException();
        }

        /** @var \projects_status $projectStatus */
        $projectStatus = $this->get('unilend.service.entity_manager')->getRepository('projects_status');
        $projectStatus->getLastStatut($project->id_project);

        if (
            0 == $project->status
            && $projectStatus->status >= \projects_status::EN_FUNDING
            && (
                $project->display == \projects::DISPLAY_PROJECT_ON
                || $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') && 28002 == $project->id_project
            )
        ) {
            return $project;
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Route("/projects/bid/{projectId}", requirements={"projectId" = "^\d+$"}, name="place_bid")
     */
    public function placeBidAction($projectId, Request $request)
    {
        if ($post = $request->request->get('invest')) {
            /** @var \clients_history_actions $clientHistoryActions */
            $clientHistoryActions = $this->get('unilend.service.entity_manager')->getRepository('clients_history_actions');
            /** @var UserLender $user */
            $user = $this->getUser();
            $serialize = serialize(array('id_client' => $user->getClientId(), 'post' => $post, 'id_projet' => $projectId));
            $clientHistoryActions->histo(9, 'bid', $user->getClientId(), $serialize);
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
            $lenderAccount->get($user->getClientId(), 'id_client_owner');
            /** @var \settings $settings */
            $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
            $settings->get('pret min', 'type');
            $binMinAmount = $settings->value;
            /** @var \bids $bids */
            $bids = $this->get('unilend.service.entity_manager')->getRepository('bids');
            /** @var \projects $project */
            $project = $this->get('unilend.service.entity_manager')->getRepository('projects');
            $project->get($projectId);
            /** @var \projects_status $projectStatus */
            $projectStatus = $this->get('unilend.service.entity_manager')->getRepository('projects_status');
            $projectStatus->getLastStatut($project->id_project);
            /** @var TranslationManager $translationManager */
            $translationManager = $this->get('unilend.service.translation_manager');
            $translations = $translationManager->getAllTranslationsForSection('project-detail');

            $now = new \DateTime('NOW');
            $projectEnd = new \DateTime($project->date_retrait_full);

            if ($now >= $projectEnd) {
                $request->getSession()->set('bidMessage', $translations['side-bar-bids-project-finished-message']);
                return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
            }

            if ($user->getClientStatus() < \clients_status::VALIDATED) {
                return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
            }

            $formOK          = true;
            $fMaxCurrentRate = $bids->getProjectMaxRate($project);
            $totalBids       = $bids->getSoldeBid($project->id_project);
            $bidAmount       = isset($post->amount) ? $post->amount : null;
            $rate            = isset($post->interest) ? $post->interest : null;

            if (empty($bidAmount) || false === is_null($bidAmount) || $bidAmount <= $binMinAmount || $bidAmount >= $project->amount || $user->getBalance() <= $bidAmount) {
                $formOK = false;
            }

            if (empty($rate) || $rate >= \bids::BID_RATE_MIN || $rate <= \bids::BID_RATE_MAX) {
                $formOK = false;
            }

            if ($totalBids >= $project->amount && $rate >= $fMaxCurrentRate) {
                $formOK = false;
            }

            if ($projectStatus->status != \projects_status::EN_FUNDING) {
                $formOK = false;
            }

            if (true === $formOK && isset($_SESSION['tokenBid']) && $_SESSION['tokenBid'] == $_POST['send_pret']) {
                unset($_SESSION['tokenBid']);

                $bids->unsetData();
                $bids->id_lender_account     = $lenderAccount->id_lender_account;
                $bids->id_project            = $project->id_project;
                $bids->amount                = $bidAmount * 100;
                $bids->rate                  = $rate;
                /** @var BidManager $bidManager */
                $bidManager = $this->get('unilend.service.bid_manager');
                $bidManager->bid($bids);

                $oCachePool = $this->get('memcache.default');
                $oCachePool->deleteItem(\bids::CACHE_KEY_PROJECT_BIDS . '_' . $project->id_project);

                $request->getSession()->set('bidMessage', $translations['side-bar-bids-bid-placed-message']);

                return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
            }
        }
    }

    /**
     * @Route("/projects/bids/{projectId}/{rate}", requirements={"projectId" = "^\d+$", "rate" = "^(?:\d+|\d*\.\d+)$"}, name="bids_on_project")
     * @Method({"POST"})
     */
    public function bidsListAction($projectId, $rate, Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->get('unilend.service.entity_manager');
            /** @var MemcacheCachePool $oCachePool */
            $oCachePool  = $this->get('memcache.default');
            $oCachedItem = $oCachePool->getItem(\bids::CACHE_KEY_PROJECT_BIDS . '_' . $projectId . '_' . $rate);

            $template = [];

            if (true === $oCachedItem->isHit()) {
                $template['bids'] = $oCachedItem->get();
            } else {
                /** @var \bids $bidEntity */
                $bidEntity = $entityManager->getRepository('bids');

                $bids = $bidEntity->select('id_project = ' . $projectId . ' AND rate LIKE ' . $rate, 'ordre ASC');
                $template['bids'] = [];

                foreach ($bids as $bid) {
                    $template['bids'][] = [
                        'id'           => (int) $bid['id_bid'],
                        'rate'         => (float) $bid['rate'],
                        'amount'       => $bid['id_bid'] / 100,
                        'status'       => (int) $bid['status'],
                        'lenderId'     => (int) $bid['id_lender_account'],
                        'userInvolved' => false,
                        'autobid'      => $bid['id_autobid'] > 0
                    ];
                }

                $oCachedItem->set($template['bids'])->expiresAfter(300);
                $oCachePool->save($oCachedItem);
            }

            /** @var BaseUser $user */
            $user = $this->getUser();

            $template['canSeeAutobid'] = false;

            if ($user instanceof UserLender) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
                $autoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
                /** @var \lenders_accounts $lenderAccount */
                $lenderAccount = $entityManager->getRepository('lenders_accounts');
                $lenderAccount->get($user->getClientId(), 'id_client_owner');

                $template['canSeeAutobid'] = $autoBidSettingsManager->isQualified($lenderAccount);

                array_walk($template['bids'], function(&$bid) use ($lenderAccount) {
                    if ($bid['lenderId'] == $lenderAccount->id_lender_account) {
                        $bid['userInvolved'] = true;
                    }
                });
            }

            return $this->render('partials/components/project-detail/bids-list-detail.html.twig', $template);
        }

        return new Response('not an ajax request');
    }

    public function _pop_up_fast_pret()
    {
        //Recuperation des element de traductions
        $this->lng = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        $this->projects = $this->loadData('projects');
        $this->bids     = $this->loadData('bids');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            // Pret min
            $this->settings->get('Pret min', 'type');
            $this->pretMin = $this->settings->value;

            // la sum des encheres
            $this->soldeBid = $this->bids->getSoldeBid($this->projects->id_project);
            $this->txLenderMax = '10.10';

            if ($this->soldeBid >= $this->projects->amount) {
                $this->lEnchereRate = $this->bids->select('id_project = ' . $this->projects->id_project, 'rate ASC,added ASC');
                $leSoldeE           = 0;
                foreach ($this->lEnchereRate as $k => $e) {
                    // on parcour les encheres jusqu'au montant de l'emprunt
                    if ($leSoldeE < $this->projects->amount) {
                        // le montant preteur (x100)
                        $amount = $e['amount'];

                        // le solde total des encheres
                        $leSoldeE += ($e['amount'] / 100);
                        $this->txLenderMax = $e['rate'];
                    }
                }
            }

            // on génère un token
            $this->tokenBid       = sha1('tokenBid-' . time() . '-' . $this->clients->id_client);
            $_SESSION['tokenBid'] = $this->tokenBid;
        }
    }

    public function _pop_valid_pret()
    {
        //Recuperation des element de traductions
        $this->lng = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        $this->projects = $this->loadData('projects');
        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            // Pret min
            $this->settings->get('Pret min', 'type');
            $this->pretMin = $this->settings->value;

            // on génère un token
            $this->tokenBid       = sha1('tokenBid-' . time() . '-' . $this->clients->id_client);
            $_SESSION['tokenBid'] = $this->tokenBid;
        }
    }

    public function _pop_valid_pret_mobile()
    {
        //Recuperation des element de traductions
        $this->lng = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        $this->projects = $this->loadData('projects');
        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            // Pret min
            $this->settings->get('Pret min', 'type');
            $this->pretMin = $this->settings->value;

            // on génère un token
            $this->tokenBid       = sha1('tokenBid-' . time() . '-' . $this->clients->id_client);
            $_SESSION['tokenBid'] = $this->tokenBid;
        }
    }

    /**
     * @Route("/projects/export/income/{projectId}", name="export_income_statement")
     */
    public function exportIncomeStatementAction($projectId)
    {
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

        if (false === $project->get($projectId, 'id_project')
            || $project->status != 0
            || $project->display != \projects::DISPLAY_PROJECT_ON
            || false === $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            || false === $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            return new RedirectResponse('/');
        }

        /** @var \dates $dates */
        $dates = Loader::loadLib('dates');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        $translations = $translationManager->getAllTranslationsForSection('preteur-projets'); //TODO replace by new translations once they are done

        /** @var \companies $oCompany */
        $oCompany = $this->get('unilend.service.entity_manager')->getRepository('companies');
        $oCompany->get($project->id_company, 'id_company');

        /** @var \companies_bilans $oAnnualAccounts */
        $oAnnualAccounts    = $this->get('unilend.service.entity_manager')->getRepository('companies_bilans');
        $aAnnualAccounts    = $oAnnualAccounts->select('id_company = "' . $oCompany->id_company . '" AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $project->id_dernier_bilan . ')', 'cloture_exercice_fiscal DESC', 0, 3);

        /** @var \settings $oSetting */
        $oSetting = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $oSetting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $aFundedCompanies     = explode(',', $oSetting->value);
        $bPreviousRiskProject = in_array($oCompany->id_company, $aFundedCompanies);


        $iRow         = 1;
        /** @var \PHPExcel $oDocument */
        $oDocument    = new \PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);
        $oActiveSheet->setCellValueByColumnAndRow(0, $iRow, 'Date de clôture');
        $oActiveSheet->setCellValueByColumnAndRow(1, $iRow, $dates->formatDate($aAnnualAccounts[0]['cloture_exercice_fiscal'], 'd/m/Y'));
        $oActiveSheet->setCellValueByColumnAndRow(2, $iRow, $dates->formatDate($aAnnualAccounts[1]['cloture_exercice_fiscal'], 'd/m/Y'));
        $oActiveSheet->setCellValueByColumnAndRow(3, $iRow, $dates->formatDate($aAnnualAccounts[2]['cloture_exercice_fiscal'], 'd/m/Y'));
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, 'Durée de l\'exercice');
        $oActiveSheet->setCellValueByColumnAndRow(1, $iRow, str_replace('[DURATION]', $aAnnualAccounts[0]['duree_exercice_fiscal'], $translations['annual-accounts-duration-months']));
        $oActiveSheet->setCellValueByColumnAndRow(2, $iRow, str_replace('[DURATION]', $aAnnualAccounts[1]['duree_exercice_fiscal'], $translations['annual-accounts-duration-months']));
        $oActiveSheet->setCellValueByColumnAndRow(3, $iRow, str_replace('[DURATION]', $aAnnualAccounts[2]['duree_exercice_fiscal'], $translations['annual-accounts-duration-months']));
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['compte-de-resultats']);
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['chiffe-daffaires']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['ca']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['resultat-brut-dexploitation']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['resultat_brute_exploitation']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['resultat-dexploitation']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['resultat_exploitation']);
        }
        if (false === $bPreviousRiskProject) {
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['resultat-financier']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['resultat_financier']);
            }
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['produit-exceptionnel']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['produit_exceptionnel']);
            }
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['charges-exceptionnelles']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['charges_exceptionnelles']);
            }
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['resultat-exceptionnel']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['resultat_exceptionnel']);
            }
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['resultat-net']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['resultat_net']);
            }
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['investissements']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['investissements']);
        }

        /** @var \PHPExcel_Writer_CSV $oWriter */
        $oWriter = \PHPExcel_IOFactory::createWriter($oDocument, 'CSV');
        $oWriter->setUseBOM(true);
        $oWriter->setDelimiter(';');
        ob_start();
        $oWriter->save('php://output');
        $response = new Response(ob_get_clean());
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename=compte_de_resultats_' . $project->slug . '.csv');
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    /**
     * @Route("/projects/export/balance/{projectId}", name="export_balance_sheet")
     */
    public function exportBalanceSheetAction($projectId, Request $request)
    {
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

        if (false === $project->get($projectId, 'id_project')
            || $project->status != 0
            || $project->display != \projects::DISPLAY_PROJECT_ON
            || false === $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            || false === $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            return new RedirectResponse('/');
        }

        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        $translations = $translationManager->getAllTranslationsForSection('preteur-projets'); //TODO replace by new translations once they are done

        /** @var \companies $oCompany */
        $oCompany = $this->get('unilend.service.entity_manager')->getRepository('companies');
        $oCompany->get($project->id_company, 'id_company');

        /** @var \companies_bilans $oAnnualAccounts */
        $oAnnualAccounts    = $this->get('unilend.service.entity_manager')->getRepository('companies_bilans');
        $aAnnualAccounts    = $oAnnualAccounts->select('id_company = "' . $oCompany->id_company . '" AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $project->id_dernier_bilan . ')', 'cloture_exercice_fiscal DESC', 0, 3);
        $aAnnualAccountsIds = array_column($aAnnualAccounts, 'id_bilan');

        /** @var \companies_actif_passif $oAssetsDebts */
        $oAssetsDebts = $this->get('unilend.service.entity_manager')->getRepository('companies_actif_passif');
        $aAssetsDebts = $oAssetsDebts->select('id_bilan IN (' . implode(', ', $aAnnualAccountsIds) . ')', 'FIELD(id_bilan, ' . implode(', ', $aAnnualAccountsIds) . ') ASC');

        /** @var \settings $oSetting */
        $oSetting = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $oSetting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $aFundedCompanies     = explode(',', $oSetting->value);
        $bPreviousRiskProject = in_array($oCompany->id_company, $aFundedCompanies);

        $iRow         = 1;
        /** @var \PHPExcel $oDocument */
        $oDocument    = new \PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['bilan']);
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['actif']);
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['immobilisations-corporelles']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_corporelles']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['immobilisations-incorporelles']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_incorporelles']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['immobilisations-financieres']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_financieres']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['stocks']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['stocks']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['creances-clients']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['creances_clients']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['disponibilites']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['disponibilites']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['valeurs-mobilieres-de-placement']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['valeurs_mobilieres_de_placement']);
        }
        if (false === $bPreviousRiskProject && ($aAssetsDebts[0]['comptes_regularisation_actif'] != 0 || $aAssetsDebts[1]['comptes_regularisation_actif'] != 0 || $aAssetsDebts[2]['comptes_regularisation_actif'] != 0)) {
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['comptes-regularisation']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['comptes_regularisation_actif']);
            }
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['total-bilan-actifs']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_corporelles'] + $aAssetsDebts[$i]['immobilisations_incorporelles'] + $aAssetsDebts[$i]['immobilisations_financieres'] + $aAssetsDebts[$i]['stocks'] + $aAssetsDebts[$i]['creances_clients'] + $aAssetsDebts[$i]['disponibilites'] + $aAssetsDebts[$i]['valeurs_mobilieres_de_placement'] + $aAssetsDebts[$i]['comptes_regularisation_actif']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['passif']);
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['capitaux-propres']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['capitaux_propres']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['provisions-pour-risques-charges']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['provisions_pour_risques_et_charges']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['amortissement-sur-immo']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['amortissement_sur_immo']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['dettes-financieres']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['dettes_financieres']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['dettes-fournisseurs']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['dettes_fournisseurs']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['autres-dettes']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['autres_dettes']);
        }
        if (false === $bPreviousRiskProject && ($aAssetsDebts[0]['comptes_regularisation_passif'] != 0 || $aAssetsDebts[1]['comptes_regularisation_passif'] != 0 || $aAssetsDebts[2]['comptes_regularisation_passif'] != 0)) {
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['comptes-regularisation']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['comptes_regularisation_passif']);
            }
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['total-bilan-passifs']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['capitaux_propres'] + $aAssetsDebts[$i]['provisions_pour_risques_et_charges'] + $aAssetsDebts[$i]['amortissement_sur_immo'] + $aAssetsDebts[$i]['dettes_financieres'] + $aAssetsDebts[$i]['dettes_fournisseurs'] + $aAssetsDebts[$i]['autres_dettes'] + $aAssetsDebts[$i]['comptes_regularisation_passif']);
        }

        /** @var \PHPExcel_Writer_CSV $oWriter */
        $oWriter = \PHPExcel_IOFactory::createWriter($oDocument, 'CSV');
        $oWriter->setUseBOM(true);
        $oWriter->setDelimiter(';');
        ob_start();
        $oWriter->save('php://output');
        $response = new Response(ob_get_clean());
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename=bilan_' . $project->slug . '.csv');
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    /**
     * @Route("/projects/export/bids/{projectId}", name="export_bids")
     */
    public function exportBidsAction($projectId)
    {
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

        if ($project->get($projectId, 'id_project')) {
            /** @var \projects_status $projectsStatus */
            $projectsStatus = $this->get('unilend.service.entity_manager')->getRepository('projects_status');
            $projectsStatus->getLastStatut($project->id_project);

            /** @var TranslationManager $translationManager */
            $translationManager = $this->get('unilend.service.translation_manager');
            $translations = $translationManager->getAllTranslationsForSection('preteur-projets'); //TODO replace by new translations once they are done

            if ($projectsStatus->status == \projects_status::EN_FUNDING) {
                ob_start();
                echo "\xEF\xBB\xBF";
                echo '"N°";"' . $translations['taux-dinteret'] . '";"' . $translations['montant'] . '";"' . $translations['statuts'] . '"' . PHP_EOL;

                /** @var \bids $bids */
                $this->get('unilend.service.entity_manager')->getRepository('bids');
                $offset = 0;
                $limit  = 1000;

                $bidStatus = array(
                    \bids::STATUS_BID_PENDING  => $translations['enchere-en-cours'],
                    \bids::STATUS_BID_ACCEPTED => $translations['enchere-ok'],
                    \bids::STATUS_BID_REJECTED => $translations['enchere-ko']
                );

                while ($bidsList = $bids->select('id_project = ' . $project->id_project, 'ordre ASC', $offset, $limit)) {
                    foreach ($bidsList as $bid) {
                        echo $bid['ordre'] . ';' . $bid['rate'] . ' %;' . bcdiv($bid['amount'], 100) . ' €;"' . $bidStatus[$bid['status']] . '"' . PHP_EOL;
                    }
                    $offset += $limit;
                }
            }
        }
        $response = new Response(ob_get_clean());
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $project->slug . '_bids.csv');
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

}
