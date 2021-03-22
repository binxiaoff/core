<?php

declare(strict_types=1);

namespace Unilend\Core\Extension\MessageThread;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\{MessageThread, User};
use Unilend\Syndication\Entity\{Project,
    ProjectParticipation,
    ProjectParticipationMember,
    ProjectStatus};
use Unilend\Syndication\Repository\ProjectParticipationMemberRepository;

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private Security $security;
    /** @var ProjectParticipationMemberRepository */
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    /**
     * ListExtension constructor.
     *
     * @param Security                             $security
     * @param ProjectParticipationMemberRepository $projectParticipationMemberRepository
     */
    public function __construct(Security $security, ProjectParticipationMemberRepository $projectParticipationMemberRepository)
    {
        $this->security                = $security;
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
    }

    /**
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        if (MessageThread::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $staff = $user->getCurrentStaff();
        if (null === $staff) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->innerJoin(ProjectParticipation::class, 'pp', Join::WITH, $rootAlias . '.projectParticipation = pp.id')
            ->leftJoin('pp.projectParticipationMembers', 'ppc')
            ->innerJoin(Project::class, 'p', Join::WITH, 'p.id = pp.project')
            ->innerJoin(ProjectStatus::class, 'pst', Join::WITH, 'pst.project = p.id')
            ->andWhere(
                $queryBuilder->expr()->orX(
                    // you are the project owner
                    'p.submitterUser = :user',
                    // or you fulfill the two following conditions :
                    $queryBuilder->expr()->andX(
                        // you are non archived member of participation OR you managed a member of a participation
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->andX('ppc.staff = :staff', 'ppc.archived IS NULL'),
                            'ppc IN (:managedStaffMember)',
                        ),
                        // you are in arranger company OR your participant and the project is in displayable status
                        // part of the condition is in inner join
                        $queryBuilder->expr()->orX(
                            'pst.status in (:displayableStatus)',
                            'pp.participant = p.submitterCompany'
                        )
                    )
                )
            )
            ->setParameter('user', $staff->getUser())
            ->setParameter('displayableStatus', ProjectStatus::DISPLAYABLE_STATUSES)
            ->setParameter('staff', $staff)
            ->setParameter('managedStaffMember', $this->projectParticipationMemberRepository->findByManager($staff))
            ->orderBy('p.title', 'ASC');
    }
}
