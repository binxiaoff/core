<?php

namespace Unilend\Bundle\FrontBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Service\HighchartsService;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;

class ProjectsController extends Controller
{

    /** @var  array */
    private $projectsStatus;


    public function __construct()
    {
        $this->projectsStatus = [
            \projects_status::EN_FUNDING,
            \projects_status::FUNDE,
            \projects_status::FUNDING_KO,
            \projects_status::REMBOURSEMENT,
            \projects_status::REMBOURSE,
            \projects_status::PROBLEME,
            \projects_status::RECOUVREMENT,
            \projects_status::DEFAUT,
            \projects_status::REMBOURSEMENT_ANTICIPE,
            \projects_status::PROBLEME_J_X,
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE
        ];
    }


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
                $this->projectsStatus,
                'p.date_retrait_full DESC',
                $rateRange,
                (int)$start,
                (int)$limit,
                $user->getClientId());
        } else {
            $aTemplateVariables['projects'] = $projectDisplayManager->getProjectsForDisplay(
                $this->projectsStatus,
                'p.date_retrait_full DESC',
                $rateRange,
                (int)$start,
                (int)$limit
                );
        }
        $totalNumberProjects = $projects->countSelectProjectsByStatus(implode(',', $this->projectsStatus) . ', ' . \projects_status::PRET_REFUSE, ' AND p.status = 0 AND p.display = ' . \projects::DISPLAY_PROJECT_ON);
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
    public function showProjectDetailAction($projectSlug)
    {
        $this->checkProjectAndRedirect($projectSlug);

        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        /** @var HighchartsService $highchartsService */
        $highchartsService = $this->get('unilend.frontbundle.service.highcharts_service');
        /** @var ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        /** @var \bids $bids */
        $bids = $this->get('unilend.service.entity_manager')->getRepository('bids');
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $project->get($projectSlug, 'slug');

        $templateVariables = [];
        $templateVariables['project'] = $projectDisplayManager->getProjectInformationForDisplay($projectSlug);

        $bidsOnProject                                     = $bids->select('id_project = ' . $project->id_project, 'added ASC');
        $templateVariables['allOffers']                    = $highchartsService->formatBidsForTable($bidsOnProject, true);
        $accountData                                       = $projectDisplayManager->getProjectFinancialData($project);
        $templateVariables['income']                       = $highchartsService->formatDataForIncomeStatementTable($accountData['balanceSheets']);
        $templateVariables['charts']['income']             = $highchartsService->getIncomeStatementChart($accountData['balanceSheets']);
        $templateVariables['balance']                      = $highchartsService->formatBalanceSheetDataForTable($accountData['totalYearlyAssets'], $accountData['totalYearlyDebts']);
        $templateVariables['charts']['balanceSheetAssets'] = $highchartsService->getBalanceSheetAssetsChart($accountData['totalYearlyAssets']);
        $templateVariables['charts']['balanceSheetDebts']  = $highchartsService->getBalanceSheetDebtsChart($accountData['totalYearlyDebts']);

        $bidsStatistics = $projectManager->getBidsStatistics($project);
        //$meanBidAmount  = round(array_sum(array_column($bidsStatistics, 'amount_total')) / array_sum(array_column($bidsStatistics, 'nb_bids')), 2);
        $activeBidsByRate = $bids->getNumberActiveBidsByRate($project->id_project);
        $templateVariables['alloffersOverview'] = '';
        $templateVariables['charts']['projectOffers'] = $highchartsService->getBidsChartSetting($activeBidsByRate, 6);

        $projectNavigation = $project->positionProject($project->id_project, $this->projectsStatus, 'lestatut ASC, IF(lestatut = 2, p.date_retrait_full ,"") DESC, IF(lestatut = 1, p.date_retrait_full ,"") ASC, projects_status.status DESC');

        $now = new \DateTime('NOW');
        $projectEnd = new \DateTime($project->date_retrait_full);
        if ($projectEnd  <= $now && $templateVariables['project']['status'] == \projects_status::EN_FUNDING) {
            $templateVariables['project']['projectPending'] = true;
        }


        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            && $this->get('security.authorization_checker')->isGranted('ROLE_LENDER'))
        {
            /** @var BaseUser $user */
            $user                             = $this->getUser();
            $templateVariables['currentUser'] = $projectDisplayManager->getClientBidsForProjectList($user->getClientId(), $project->id_project);
            $userBidsOnProject                = $projectDisplayManager->getClientBidsOnProject($user->getClientId(), $project->id_project);
            $templateVariables['myOffers']    = $highchartsService->formatBidsForTable($userBidsOnProject);
            $templateVariables['myOffersIds'] = array_column($userBidsOnProject, 'id_bid');
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

    public function placeBidOnProject()
    {

        if (isset($_POST['send_pret'])) {
            $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'id_projet' => $this->projects->id_project));
            $this->clients_history_actions->histo(9, 'bid', $this->clients->id_client, $serialize);

            // Si la date du jour est egale ou superieur a la date de retrait on redirige.
            if ($today >= $dateRetrait) {
                $_SESSION['messFinEnchere'] = $this->lng['preteur-projets']['mess-fin-enchere'];

                header('Location: ' . $this->lurl . '/projects/detail/' . $this->projects->slug);
                die;
            } elseif ($this->clients_status->status < \clients_status::VALIDATED) {
                header('Location: ' . $this->lurl . '/projects/detail/' . $this->projects->slug);
                die;
            }

            $montant_p = isset($_POST['montant_pret']) ? str_replace(array(' ', ','), array('', '.'), $_POST['montant_pret']) : 0;
            $montant_p = explode('.', $montant_p);
            $montant_p = $montant_p[0];

            $this->form_ok = true;

            if (empty($_POST['taux_pret'])) {
                $this->form_ok = false;
            } elseif ($_POST['taux_pret'] == '-') {
                $this->form_ok = false;
            } elseif ($_POST['taux_pret'] > 10) {
                $this->form_ok = false;
            }

            $fMaxCurrentRate = $this->bids->getProjectMaxRate($this->projects);

            if ($this->soldeBid >= $this->projects->amount && $_POST['taux_pret'] >= $fMaxCurrentRate) {
                $this->form_ok = false;
            } elseif (! isset($_POST['montant_pret']) || $_POST['montant_pret'] == '' || $_POST['montant_pret'] == '0') {
                $this->form_ok = false;
            } elseif (! is_numeric($montant_p)) {
                $this->form_ok = false;
            } elseif ($montant_p < $this->pretMin) {
                $this->form_ok = false;
            } elseif ($this->solde < $montant_p) {
                $this->form_ok = false;
            } elseif ($montant_p >= $this->projects->amount) {
                $this->form_ok = false;
            } elseif ($this->projects_status->status != \projects_status::EN_FUNDING) {
                $this->form_ok = false;
            }

            if (isset($this->params['1']) && $this->params['1'] == 'fast' && $this->form_ok == false) {
                header('Location: ' . $this->lurl . '/synthese');
                die;
            }

            $tx_p = $_POST['taux_pret'];

            if ($this->form_ok == true && isset($_SESSION['tokenBid']) && $_SESSION['tokenBid'] == $_POST['send_pret']) {
                unset($_SESSION['tokenBid']);

                /** @var \bids $bid */
                $bid = $this->loadData('bids');
                $bid->id_lender_account     = $this->lenders_accounts->id_lender_account;
                $bid->id_project            = $this->projects->id_project;
                $bid->amount                = $montant_p * 100;
                $bid->rate                  = $tx_p;

                $bidManager = $this->get('unilend.service.bid_manager');
                $bidManager->bid($bid);

                $oCachePool = $this->get('memcache.default');
                $oCachePool->deleteItem(\bids::CACHE_KEY_PROJECT_BIDS . '_' . $this->projects->id_project);

                $_SESSION['messPretOK'] = $this->lng['preteur-projets']['mess-pret-conf'];

                header('Location: ' . $this->lurl . '/projects/detail/' . $this->projects->slug);
                die;
            }
        }

    }


}
