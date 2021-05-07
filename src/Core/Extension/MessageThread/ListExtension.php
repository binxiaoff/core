<?php

declare(strict_types=1);

namespace Unilend\Core\Extension\MessageThread;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\MessageThread;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectStatus;
use Unilend\Syndication\Repository\ProjectParticipationMemberRepository;

class ListExtension implements QueryCollectionExtensionInterface
{
    private Security $security;
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    public function __construct(Security $security, ProjectParticipationMemberRepository $projectParticipationMemberRepository)
    {
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
            ->setParameters([
                'user'               => $staff->getUser(),
                'displayableStatus'  => ProjectStatus::DISPLAYABLE_STATUSES,
                'staff'              => $staff,
                'managedStaffMember' => $this->projectParticipationMemberRepository->findActiveByManager($staff),
            ])
            ->orderBy('p.title', 'ASC')
        ;
    }
}
