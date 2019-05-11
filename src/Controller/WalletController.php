<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Loans, Project, ProjectStatusHistory, WalletType};
use Unilend\Repository\ClientProjectRepository;
use Unilend\Repository\ProjectRepository;
use Unilend\Repository\WalletRepository;

class WalletController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
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
    public function loans(
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
            ->innerJoin('p.bids', 'b')
            ->innerJoin('p.currentProjectStatusHistory', 'psh')
            ->where('b.wallet = :wallet')
            ->andWhere('psh.status = :online')
            ->setParameters(['wallet' => $wallet, 'online' => ProjectStatusHistory::STATUS_PUBLISHED])
            ->getQuery()
            ->getResult()
        ;

        $projectsInProgressNonSignedLoan = $projectRepository->createQueryBuilder('p')
            ->distinct()
            ->innerJoin('p.loans', 'l')
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
            ->innerJoin('p.loans', 'l')
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
            ->innerJoin('p.loans', 'l')
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
            ->innerJoin('p.loans', 'l')
            ->where('l.wallet = :wallet')
            ->andWhere('l.status = :refused')
            ->setParameters(['wallet' => $wallet, 'refused' => Loans::STATUS_REJECTED])
            ->getQuery()
            ->getResult()
        ;

        $template['projects']['lender']['masked'] = $projectsMasked;

        return $this->render('wallet/index.html.twig', $template);
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
}
