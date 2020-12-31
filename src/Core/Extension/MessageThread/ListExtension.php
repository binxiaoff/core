<?php

declare(strict_types=1);

namespace Unilend\Core\Extension\MessageThread;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\{MessageThread, User};
use Unilend\Syndication\Entity\{Project, ProjectParticipation, ProjectStatus};

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private Security $security;
    /**
     * ListExtension constructor.
     *
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security                = $security;
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
            ->leftJoin("pp.projectParticipationMembers", 'ppc')
            ->innerJoin(Project::class, 'p', Join::WITH, 'p.id = pp.project')
            ->innerJoin(ProjectStatus::class, 'pst', Join::WITH, 'pst.project = p.id')
            ->andWhere(
                $queryBuilder->expr()->orX(
                    // Submitter condition
                    $queryBuilder->expr()->andX(
                        'p.submitterCompany = :company',
                        $queryBuilder->expr()->orX(
                            'p.marketSegment IN (:marketSegments)',
                            ($staff && $staff->isAdmin() ? '1 = 1' : '0 = 1')
                        )
                    ),
                    // Participant condition
                    'ppc.staff = :staff AND ppc.archived IS NULL'
                )
            )
            ->setParameter('staff', $staff)
            ->setParameter('company', $staff->getCompany())
            ->setParameter('marketSegments', $staff->getMarketSegments())
            ->orderBy('p.title', 'ASC');
    }
}
