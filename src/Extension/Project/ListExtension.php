<?php

declare(strict_types=1);

namespace Unilend\Extension\Project;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, Project, ProjectStatus};

class ListExtension implements QueryCollectionExtensionInterface
{
    /** @var Security */
    private $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if (Project::class !== $resourceClass || $this->security->isGranted(Clients::ROLE_ADMIN)) {
            return;
        }

        /** @var Clients $user */
        $user = $this->security->getUser();

        if (!$user instanceof Clients) {
            return;
        }

        $staff = $user->getCurrentStaff();

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->distinct()
            ->innerJoin($rootAlias . '.currentStatus', 'cs')
            ->leftJoin($rootAlias . '.projectParticipations', 'p')
            ->leftJoin('p.projectParticipationContacts', 'ppc')
            ->andWhere($queryBuilder->expr()->orX(
                // if you are owner
                $rootAlias . '.submitterClient = :client',
                // or you are in owner company and you have market segment
                $queryBuilder->expr()->andX(
                    $rootAlias . '.submitterCompany = :company',
                    $queryBuilder->expr()->orX(
                        $rootAlias . '.marketSegment IN (:marketSegments)',
                        ($staff && $staff->isAdmin() ? '1 = 1' : '0 = 1')
                    )
                ),
                // or you are participant and the project is published
                $queryBuilder->expr()->andX(
                    'cs.status in (:displayableStatus)',
                    'ppc.client = :client'
                )
            ))
            ->setParameter('displayableStatus', ProjectStatus::DISPLAYABLE_STATUS)
            ->setParameter('company', $staff->getCompany())
            ->setParameter('client', $user)
            ->setParameter('marketSegments', $staff ? $staff->getMarketSegments() : [])
        ;
    }
}
