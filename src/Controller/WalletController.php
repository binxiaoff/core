<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Loans, Projects, ProjectsStatus, Wallet, WalletType};
use Unilend\Repository\ClientProjectsRepository;
use Unilend\Repository\ProjectsRepository;

/**
 * @Security("is_granted('ROLE_USER')")
 */
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
     * @param UserInterface|Clients|null $user
     * @param ProjectsRepository         $projectRepository
     * @param ClientProjectsRepository   $clientProjectsRepository
     *
     * @return Response
     */
    public function loans(?UserInterface $user, ProjectsRepository $projectRepository, ClientProjectsRepository $clientProjectsRepository): Response
    {
        $template = [
            'projects' => [
                'borrower'    => $this->groupByStatusAndSort($clientProjectsRepository->getBorrowerProjects($user)),
                'submitter'   => $this->groupByStatusAndSort($clientProjectsRepository->getSubmitterProjects($user)),
                'arrangerRun' => $this->groupByStatusAndSort($clientProjectsRepository->getArrangerRunProjects($user)),
                'lender'      => [],
            ],
        ];

        $wallet = $this->entityManager->getRepository(Wallet::class)->getWalletByType($user, WalletType::LENDER);

        // En cours (HOT)
        $projectsInProgressBid = $projectRepository->createQueryBuilder('p')
            ->distinct()
            ->innerJoin('p.bids', 'b')
            ->where('b.wallet = :wallet')
            ->andWhere('p.status = :online')
            ->setParameters(['wallet' => $wallet, 'online' => ProjectsStatus::STATUS_PUBLISHED])
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
            ->where('l.wallet = :wallet')
            ->andWhere('l.status = :accepted')
            ->andWhere('p.status IN (:active)')
            ->setParameters([
                'wallet'   => $wallet,
                'accepted' => Loans::STATUS_ACCEPTED,
                'active'   => [ProjectsStatus::STATUS_FUNDED, ProjectsStatus::STATUS_CONTRACTS_REDACTED, ProjectsStatus::STATUS_CONTRACTS_SIGNED],
            ])
            ->getQuery()
            ->getResult()
        ;

        $template['projects']['lender']['active'] = $projectsActive;

        // Terminés
        $projectsFinished = $projectRepository->createQueryBuilder('p')
            ->innerJoin('p.loans', 'l')
            ->where('l.wallet = :wallet')
            ->andWhere('l.status = :accepted')
            ->andWhere('p.status IN (:finished)')
            ->setParameters([
                'wallet'   => $wallet,
                'accepted' => Loans::STATUS_ACCEPTED,
                'finished' => [ProjectsStatus::STATUS_LOST, ProjectsStatus::STATUS_FINISHED, ProjectsStatus::STATUS_CANCELLED],
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
     * @param Projects[] $projects
     *
     * @return array
     */
    private function groupByStatusAndSort(array $projects)
    {
        $groupedProjects = [];
        $statuses        = $this->entityManager->getRepository(ProjectsStatus::class)->findBy([], ['status' => 'ASC']);

        foreach ($statuses as $status) {
            $groupedProjects[$status->getStatus()] = [];
        }

        foreach ($projects as $project) {
            $groupedProjects[$project->getStatus()][] = $project;
        }

        ksort($groupedProjects);

        return $groupedProjects;
    }
}
