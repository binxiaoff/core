<?php

declare(strict_types=1);

namespace Unilend\Extension\ProjectParticipation;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, Project, ProjectParticipation, ProjectStatus};

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private $security;
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param Security               $security
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security      = $security;
        $this->entityManager = $entityManager;
    }

    /**
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (ProjectParticipation::class !== $resourceClass) {
            return;
        }
        /** @var Clients $user */
        $user = $this->security->getUser();
        if (!$user instanceof Clients) {
            return;
        }

        $staff = $user->getCurrentStaff();

        $expressionBuilder = $this->entityManager->getExpressionBuilder();
        $subQueryBuilder   = $this->entityManager->createQueryBuilder();
        $subQueryBuilder->select('sub_project')
            ->from(Project::class, 'sub_project')
            ->innerJoin('sub_project.currentStatus', 'sub_cs')
            ->innerJoin('sub_project.projectParticipations', 'sub_participation')
            ->innerJoin('sub_participation.projectParticipationMembers', 'sub_member')
            ->where('sub_member.staff = :staff')
        ;
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->distinct()
            ->leftJoin("{$rootAlias}.projectParticipationMembers", 'ppc')
            ->innerJoin("{$rootAlias}.project", 'p')
            ->andWhere(
                $expressionBuilder->orX(
                    // Submitter condition
                    $expressionBuilder->andX(
                        'p.submitterCompany = :company',
                        $queryBuilder->expr()->orX(
                            'p.marketSegment IN (:marketSegments)',
                            ($staff && $staff->isAdmin() ? '1 = 1' : '0 = 1')
                        )
                    ),
                    // Participant condition
                    $expressionBuilder->andX(
                        'ppc.archived IS NULL',
                        '(p.offerVisibility = :private AND ppc.staff = :staff) OR p.offerVisibility in (:nonPrivate)',
                        $expressionBuilder->in('p.id', $subQueryBuilder->getDQL())
                    )
                )
            )
            ->setParameter('staff', $staff)
            ->setParameter('private', Project::OFFER_VISIBILITY_PRIVATE)
            ->setParameter('nonPrivate', [Project::OFFER_VISIBILITY_PARTICIPANT, Project::OFFER_VISIBILITY_PUBLIC])
            ->setParameter('company', $user->getCompany())
            ->setParameter('marketSegments', $user->getCurrentStaff()->getMarketSegments())
        ;
    }
}
