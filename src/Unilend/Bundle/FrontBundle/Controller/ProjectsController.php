<?php

namespace Unilend\Bundle\FrontBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;
use Symfony\Component\HttpFoundation\Response;

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
        /** @var array $projectsStatus */
        $projectsStatus = [
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

        $settings->get('nombre-de-projets-par-page', 'type');
        $limit = $settings->value;
        $start = ($page > 1) ? $limit * ($page - 1) : 0;

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            && $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            /** @var BaseUser $user */
            $user = $this->getUser();
            $aTemplateVariables['projects'] = $projectDisplayManager->getProjectsForDisplay(
                $projectsStatus,
                'p.date_retrait_full DESC',
                $rateRange,
                (int)$start,
                (int)$limit,
                $user->getClientId());
        } else {
            $aTemplateVariables['projects'] = $projectDisplayManager->getProjectsForDisplay(
                $projectsStatus,
                'p.date_retrait_full DESC',
                $rateRange,
                (int)$start,
                (int)$limit
                );
        }

        $totalNumberProjects = $projects->countSelectProjectsByStatus(implode(',', $projectsStatus));
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
        $aTemplateVariables['showPagination'] = true;

        return $this->render('pages/projects.html.twig', $aTemplateVariables);
    }

    /**
     * @Route("/project/{projectSlug}", name="project_show")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showProjectAction($projectSlug)
    {
        return new Response($projectSlug);

    }
}
