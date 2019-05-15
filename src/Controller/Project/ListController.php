<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Loans, Project, ProjectStatusHistory, WalletType};
use Unilend\Repository\ClientProjectRepository;
use Unilend\Repository\ProjectRepository;
use Unilend\Repository\WalletRepository;
use Unilend\Service\Front\ProjectDisplayManager;

class ListController extends AbstractController
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
    public function list(ProjectDisplayManager $projectDisplayManager, Request $request, ?UserInterface $user, ProjectRepository $projectRepository): Response
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

        return $this->render('project/list/list.html.twig', $template);
    }

    /**
     * @Route("/portefeuille", name="wallet")
     *
     * @param ProjectRepository          $projectRepository
     * @param ClientProjectRepository    $clientProjectRepository
     * @param WalletRepository           $walletRepository
     * @param UserInterface|Clients|null $user
     *
     * @return Response
     */
    public function wallet(
        ProjectRepository $projectRepository,
        ClientProjectRepository $clientProjectRepository,
        WalletRepository $walletRepository,
        ?UserInterface $user
    ): Response {
        $statuses = ProjectStatusHistory::getAllProjectStatus();
        $template = [
            'projects' => [
                'borrower'    => $this->groupByStatusAndSort($clientProjectRepository->getBorrowerProjects($user), $statuses),
                'submitter'   => $this->groupByStatusAndSort($clientProjectRepository->getSubmitterProjects($user), $statuses),
                'arrangerRun' => $this->groupByStatusAndSort($clientProjectRepository->getArrangerRunProjects($user), $statuses),
                'lender'      => [],
            ],
        ];

        $wallet = $walletRepository->getWalletByType($user, WalletType::LENDER);

        // En cours (HOT)
        $projectsInProgressBid = $projectRepository->createQueryBuilder('p')
            ->distinct()
            ->innerJoin('p.tranches', 't')
            ->innerJoin('t.bids', 'b')
            ->innerJoin('p.currentProjectStatusHistory', 'psh')
            ->where('b.wallet = :wallet')
            ->andWhere('psh.status = :online')
            ->setParameters(['wallet' => $wallet, 'online' => ProjectStatusHistory::STATUS_PUBLISHED])
            ->getQuery()
            ->getResult()
        ;

        $projectsInProgressNonSignedLoan = $projectRepository->createQueryBuilder('p')
            ->distinct()
            ->innerJoin('p.tranches', 't')
            ->innerJoin('t.loans', 'l')
            ->where('l.wallet = :wallet')
            ->andWhere('l.status = :pending')
            ->setParameters(['wallet' => $wallet, 'pending' => Loans::STATUS_PENDING])
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
            ->innerJoin('p.currentProjectStatusHistory', 'psh')
            ->where('l.wallet = :wallet')
            ->andWhere('l.status = :accepted')
            ->andWhere('psh.status IN (:active)')
            ->setParameters([
                'wallet'   => $wallet,
                'accepted' => Loans::STATUS_ACCEPTED,
                'active'   => [ProjectStatusHistory::STATUS_FUNDED, ProjectStatusHistory::STATUS_CONTRACTS_REDACTED, ProjectStatusHistory::STATUS_CONTRACTS_SIGNED],
            ])
            ->getQuery()
            ->getResult()
        ;

        $template['projects']['lender']['active'] = $projectsActive;

        // Terminés
        $projectsFinished = $projectRepository->createQueryBuilder('p')
            ->innerJoin('p.tranches', 't')
            ->innerJoin('t.loans', 'l')
            ->innerJoin('p.currentProjectStatusHistory', 'psh')
            ->where('l.wallet = :wallet')
            ->andWhere('l.status = :accepted')
            ->andWhere('psh.status IN (:finished)')
            ->setParameters([
                'wallet'   => $wallet,
                'accepted' => Loans::STATUS_ACCEPTED,
                'finished' => [ProjectStatusHistory::STATUS_LOST, ProjectStatusHistory::STATUS_FINISHED, ProjectStatusHistory::STATUS_CANCELLED],
            ])
            ->getQuery()
            ->getResult()
        ;

        $template['projects']['lender']['finished'] = $projectsFinished;

        $projectsMasked = $projectRepository->createQueryBuilder('p')
            ->innerJoin('p.tranches', 't')
            ->innerJoin('t.loans', 'l')
            ->where('l.wallet = :wallet')
            ->andWhere('l.status = :refused')
            ->setParameters(['wallet' => $wallet, 'refused' => Loans::STATUS_REJECTED])
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
            $lastStatus = $project->getCurrentProjectStatusHistory();
            if ($lastStatus) {
                $groupedProjects[$lastStatus->getStatus()][] = $project;
            }
        }

        ksort($groupedProjects);

        return $groupedProjects;
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
