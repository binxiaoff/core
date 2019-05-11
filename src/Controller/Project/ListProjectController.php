<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Project};
use Unilend\Repository\ProjectRepository;
use Unilend\Service\Front\ProjectDisplayManager;

class ListProjectController extends AbstractController
{
    /**
     * @Route("/projets", name="list_projects")
     *
     * @param ProjectDisplayManager      $projectDisplayManager
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     * @param ProjectRepository          $projectRepository
     *
     * @return Response
     */
    public function projectsList(ProjectDisplayManager $projectDisplayManager, Request $request, ?UserInterface $user, ProjectRepository $projectRepository): Response
    {
        $page          = $request->query->get('page', 1);
        $sortDirection = $request->query->get('sortDirection', 'DESC');
        $sortType      = $request->query->get('sortType', 'expectedClosingDate');
        $sort          = ['cpsh.status' => 'ASC', $sortType => $sortDirection];
        /** @var Project[] $projects */
        $projects = $projectRepository->findByStatus([ProjectDisplayManager::STATUS_DISPLAYABLE], $sort);

        foreach ($projects as $index => $project) {
            if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($project, $user)) {
                unset($projects[$index]);
            }
        }

        $template = [
            'sortDirection'  => $sortDirection,
            'sortType'       => $sortType,
            'showSortable'   => true,
            'showPagination' => true,
            'currentPage'    => $page,
            'pagination'     => $this->pagination($projectDisplayManager, $page, 20),
            'projects'       => $projects,
        ];

        return $this->render('demo/projects_list.html.twig', $template);
    }

    /**
     * @param ProjectDisplayManager $projectDisplayManager
     * @param int                   $page
     * @param int                   $limit
     *
     * @return array
     */
    private function pagination(ProjectDisplayManager $projectDisplayManager, int $page, int $limit): array
    {
        $totalNumberProjects = $projectDisplayManager->getTotalNumberOfDisplayedProjects(null);
        $totalPages          = $limit ? ceil($totalNumberProjects / $limit) : 1;

        $paginationSettings = [
            'itemsPerPage'      => $limit,
            'totalItems'        => $totalNumberProjects,
            'totalPages'        => $totalPages,
            'currentIndex'      => $page,
            'currentIndexItems' => min($page * $limit, $totalNumberProjects),
            'remainingItems'    => $limit ? ceil($totalNumberProjects - ($totalNumberProjects / $limit)) : 0,
            'pageUrl'           => 'projects',
        ];

        if ($totalPages > 1) {
            $paginationSettings['indexPlan'] = [$page - 1, $page, $page + 1];

            if ($page > $totalPages - 3) {
                $paginationSettings['indexPlan'] = [$totalPages - 3, $totalPages - 2, $totalPages - 1];
            } elseif ($page === $totalPages - 3) {
                $paginationSettings['indexPlan'] = [$totalPages - 4, $totalPages - 3, $totalPages - 2, $totalPages - 1];
            } elseif (4 === $page) {
                $paginationSettings['indexPlan'] = [2, 3, 4, 5];
            } elseif ($page < 4) {
                $paginationSettings['indexPlan'] = [2, 3, 4];
            }
        }

        return $paginationSettings;
    }
}
