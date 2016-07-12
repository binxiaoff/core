<?php

namespace Unilend\Bundle\FrontBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Service\BidManager;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\FrontBundle\Service\HighchartsService;
use Unilend\Bundle\FrontBundle\Service\LenderAccountDisplayManager;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;

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
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        /** @var array $rateRange */
        $rateRange = [\bids::BID_RATE_MIN, \bids::BID_RATE_MAX];

        $settings->get('nombre-de-projets-par-page', 'type');
        $limit = $settings->value;
        $start = ($page > 1) ? $limit * ($page - 1) : 0;

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            && $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            /** @var BaseUser $user */
            $user = $this->getUser();
            $aTemplateVariables['projects'] = $projectDisplayManager->getProjectsForDisplay(
                'p.date_retrait_full DESC',
                $rateRange,
                (int)$start,
                (int)$limit,
                $user->getClientId());
        } else {
            $aTemplateVariables['projects'] = $projectDisplayManager->getProjectsForDisplay(
                'p.date_retrait_full DESC',
                $rateRange,
                (int)$start,
                (int)$limit
                );
        }

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

        $aTemplateVariables['paginationSettings'] = $paginationSettings;
        $aTemplateVariables['showPagination']     = true;

        return $this->render('pages/projects.html.twig', $aTemplateVariables);
    }

    /**
     * @Route("/projects/detail/{projectSlug}", name="project_detail")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showProjectDetailAction($projectSlug, Request $request)
    {
        $this->checkProjectAndRedirect($projectSlug);

        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        /** @var HighchartsService $highchartsService */
        $highchartsService = $this->get('unilend.frontbundle.service.highcharts_service');

        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $project->get($projectSlug, 'slug');

        $templateVariables            = [];

        $templateVariables['project'] = $projectDisplayManager->getProjectInformationForDisplay($project);


        $accountData                  = $projectDisplayManager->getProjectFinancialData($project);
        $templateVariables['charts']  = $highchartsService->getFinancialProjectDataCharts($accountData);
        $templateVariables['tables']  = $highchartsService->getFinancialProjectDataForTables($accountData);

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            && $this->get('security.authorization_checker')->isGranted('ROLE_LENDER'))
        {
            /** @var BaseUser $user */
            $user = $this->getUser();
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
            $lenderAccount->get($user->getClientId(), 'id_client_owner');
            /** @var LenderAccountDisplayManager $lenderAccountDisplayManager */
            $lenderAccountDisplayManager = $this->get('unilend.frontbundle.service.lender_account_display_manager');
            $templateVariables['lenderOnProject'] = $lenderAccountDisplayManager->getLenderActivityForProject($project->id_project, $lenderAccount);

            if ($lenderAccountDisplayManager->isLenderInvolvedInProject($project, $lenderAccount)) {
                $templateVariables['lenderOnProject']['bidsTable'] = $highchartsService->formatBidsForTable(['lenderOnProject']['offers']['all']);
            }

            if (false === empty($request->getSession()->get('bidMessage'))) {
                $templateVariables['lender']['bidMessage'] = $request->getSession()->get('bidMessage');
                $request->getSession()->remove('bidMessage');
            }
        }

        return $this->render('pages/project_detail.html.twig', $templateVariables);
    }

    private function checkProjectAndRedirect($projectSlug)
    {
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $project->get($projectSlug, 'slug');

        /** @var \projects_status $projectStatus */
        $projectStatus = $this->get('unilend.service.entity_manager')->getRepository('projects_status');
        $projectStatus->getLastStatut($project->id_project);

        if ($project->status == 0
            && ($project->display == \projects::DISPLAY_PROJECT_ON || $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') && 28002 == $project->id_project)) {
            if ($projectStatus->status < \projects_status::A_FUNDER) {
                return new RedirectResponse('/');
            }
            return true;
        }
        return new RedirectResponse('/error');
    }


    /**
     * @Route("/projects/bid/{projectId}", name="bid_on_project")
     */
    public function placeBidOnProject($projectId, Request $request)
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

    public function _pop_up_fast_pret()
    {
        //Recuperation des element de traductions
        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

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
        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

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
        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

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
    public function exportIncomeStatement($projectId, Request $request)
    {
        return new Response('ca viendra .... ');
    }

    /**
     * @Route("/projects/export/balance/{projectId}", name="export_balance_sheet")
     */
    public function exportBalanceSheet($projectId, Request $request)
    {
        return new Response('ca viendra .... ');
    }


}
