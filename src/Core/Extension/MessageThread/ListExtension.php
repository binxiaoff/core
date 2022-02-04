<?php

declare(strict_types=1);

namespace KLS\Core\Extension\MessageThread;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\MessageThread;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationMemberRepository;
use Symfony\Component\Security\Core\Security;

class ListExtension implements QueryCollectionExtensionInterface
{
    private Security $security;
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    public function __construct(
        Security $security,
        ProjectParticipationMemberRepository $projectParticipationMemberRepository
    ) {
        $this->security                             = $security;
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        if (MessageThread::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $token = $this->security->getToken();

        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        if (false === ($staff instanceof Staff)) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->innerJoin($rootAlias . '.projectParticipation', 'mpp') // The participation of the thread
            ->innerJoin(
                ProjectParticipation::class,
                'pp',
                Join::WITH,
                'mpp.project = pp.project AND pp.participant = :company'
            ) // Via the previous participation, we can find the participation of the current staff
            ->leftJoin(
                ProjectParticipationMember::class,
                'ppm',
                Join::WITH,
                'ppm.projectParticipation = pp.id AND ppm.archived IS NULL'
            )
            ->innerJoin(Project::class, 'p', Join::WITH, 'p.id = pp.project')
            ->innerJoin(ProjectStatus::class, 'pst', Join::WITH, 'pst.id = p.currentStatus')
            ->andWhere($queryBuilder->expr()->orX(
                // you are the project owner
                'p.submitterUser = :user',
                // or you fulfill the two following conditions :
                $queryBuilder->expr()->andX(
                    // 1. you are a non archived member of participation OR you managed a member of a participation
                    $queryBuilder->expr()->orX(
                        'ppm.staff = :staff',
                        'ppm IN (:managedStaffMember)',
                    ),
                    // 2. you are in arranger company OR your participant and the project is in displayable status
                    $queryBuilder->expr()->orX(
                        'p.submitterCompany = :company',
                        'pst.status in (:displayableStatus)'
                    )
                )
            ))
            ->setParameter('company', $staff->getCompany())
            ->setParameter('displayableStatus', ProjectStatus::DISPLAYABLE_STATUSES)
            ->setParameter('staff', $staff)
            ->setParameter('user', $staff->getUser())
            ->setParameter(
                'managedStaffMember',
                $this->projectParticipationMemberRepository->findActiveByManager($staff)
            )
            ->orderBy('p.title', 'ASC')
        ;
    }
}
