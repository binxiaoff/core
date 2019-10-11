<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Loans, Project, ProjectStatus};
use Unilend\Repository\{ClientProjectRepository, ProjectRepository};

class ListController extends AbstractController
{
    /**
     * @Route("/projets", name="list_projects")
     *
     * @param UserInterface|Clients|null $client
     * @param Request                    $request
     * @param ProjectRepository          $projectRepository
     *
     * @throws NonUniqueResultException
     *
     * @return Response
     */
    public function list(
        ?UserInterface $client,
        Request $request,
        ProjectRepository $projectRepository
    ): Response {
        $page          = $request->query->get('page', 1);
        $limit         = 20;
        $sortDirection = $request->query->get('sortDirection', 'DESC');
        $sortType      = $request->query->get('sortType', 'expectedClosingDate');
        $sort          = ['cpsh.status' => 'ASC', $sortType => $sortDirection];
        $projects      = $projectRepository->findListableByClient($client, $sort, $limit, ($page - 1) * $limit);
        $projectCount  = $projectRepository->countListableByClient($client);

        $template = [
            'sortDirection'  => $sortDirection,
            'sortType'       => $sortType,
            'showSortable'   => true,
            'showPagination' => true,
            'currentPage'    => $page,
            'pagination'     => $this->pagination($projectCount, $page, 20),
            'projects'       => $projects,
        ];

        return $this->render('project/list/list.html.twig', $template);
    }

    /**
     * @Route("/portefeuille", name="wallet")
     *
     * @param ProjectRepository          $projectRepository
     * @param ClientProjectRepository    $clientProjectRepository
     * @param UserInterface|Clients|null $user
     *
     * @return Response
     */
    public function wallet(
        ProjectRepository $projectRepository,
        ClientProjectRepository $clientProjectRepository,
        ?UserInterface $user
    ): Response {
        $statuses = ProjectStatus::getPossibleStatuses();
        $template = [
            'projects' => [
                'borrowerSubmitter' => $this->groupByStatusAndSort($clientProjectRepository->getBorrowerSubmitterProjects($user), $statuses),
                'arrangerRun'       => $this->groupByStatusAndSort($clientProjectRepository->getArrangerRunProjects($user), $statuses),
                'lender'            => [],
            ],
        ];

        // En cours (HOT)
        $projectsInProgressBid = $projectRepository->createQueryBuilder('p')
            ->distinct()
            ->innerJoin('p.tranches', 't')
            ->innerJoin('t.bids', 'b')
            ->innerJoin('p.currentStatus', 'psh')
            ->where('b.lender = :lender')
            ->andWhere('psh.status = :online')
            ->setParameters(['lender' => $user->getCompany(), 'online' => ProjectStatus::STATUS_PUBLISHED])
            ->getQuery()
            ->getResult()
        ;

        $projectsInProgressNonSignedLoan = $projectRepository->createQueryBuilder('p')
            ->distinct()
            ->innerJoin('p.tranches', 't')
            ->innerJoin('t.loans', 'l')
            ->where('l.lender = :lender')
            ->andWhere('l.status = :pending')
            ->setParameters(['lender' => $user->getCompany(), 'pending' => Loans::STATUS_PENDING])
            ->getQuery()
            ->getResult()
        ;

        $template['projects']['lender']['inProgress'] = array_merge((array) $projectsInProgressBid, (array) $projectsInProgressNonSignedLoan);

        $inProgressCount = count($template['projects']['lender']['inProgress']) + count($projectsInProgressNonSignedLoan);

        $template['projects']['lender']['inProgressCount'] = [
            'pending' => $inProgressCount,
            'refused' => count($template['projects']['lender']['inProgress']) - $inProgressCount,
        ];

        // Actifs (COLD)
        $projectsActive = $projectRepository->createQueryBuilder('p')
            ->innerJoin('p.tranches', 't')
            ->innerJoin('t.loans', 'l')
            ->innerJoin('p.currentStatus', 'psh')
            ->where('l.lender = :lender')
            ->andWhere('l.status = :accepted')
            ->andWhere('psh.status IN (:active)')
            ->setParameters([
                'lender'   => $user->getCompany(),
                'accepted' => Loans::STATUS_ACCEPTED,
                'active'   => [ProjectStatus::STATUS_INTERESTS_COLLECTED, ProjectStatus::STATUS_OFFERS_COLLECTED, ProjectStatus::STATUS_CONTRACTS_SIGNED],
            ])
            ->getQuery()
            ->getResult()
        ;

        $template['projects']['lender']['active'] = $projectsActive;

        // Terminés
        $projectsFinished = $projectRepository->createQueryBuilder('p')
            ->innerJoin('p.tranches', 't')
            ->innerJoin('t.loans', 'l')
            ->innerJoin('p.currentStatus', 'psh')
            ->where('l.lender = :lender')
            ->andWhere('l.status = :accepted')
            ->andWhere('psh.status IN (:finished)')
            ->setParameters([
                'lender'   => $user->getCompany(),
                'accepted' => Loans::STATUS_ACCEPTED,
                'finished' => ProjectStatus::STATUS_REPAID,
            ])
            ->getQuery()
            ->getResult()
        ;

        $template['projects']['lender']['finished'] = $projectsFinished;

        $projectsMasked = $projectRepository->createQueryBuilder('p')
            ->innerJoin('p.tranches', 't')
            ->innerJoin('t.loans', 'l')
            ->where('l.lender = :lender')
            ->andWhere('l.status = :refused')
            ->setParameters(['lender' => $user->getCompany(), 'refused' => Loans::STATUS_REJECTED])
            ->getQuery()
            ->getResult()
        ;

        $template['projects']['lender']['masked'] = $projectsMasked;

        return $this->render('project/list/wallet.html.twig', $template);
    }

    /**
     * @param Project[] $projects
     * @param array     $statuses
     *
     * @return array
     */
    private function groupByStatusAndSort(array $projects, array $statuses)
    {
        $groupedProjects = array_fill_keys($statuses, []);

        foreach ($projects as $project) {
            $lastStatus = $project->getCurrentStatus();
            if ($lastStatus) {
                $groupedProjects[$lastStatus->getStatus()][] = $project;
            }
        }

        ksort($groupedProjects);

        return $groupedProjects;
    }

    /**
     * @param int $totalNumberProjects
     * @param int $page
     * @param int $limit
     *
     * @return array
     */
    private function pagination(int $totalNumberProjects, int $page, int $limit): array
    {
        $totalPages = $limit ? ceil($totalNumberProjects / $limit) : 1;

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
