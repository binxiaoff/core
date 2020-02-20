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
    /**
     * @var Security
     */
    private $security;

    /**
     * @param Security $security
     */
    public function __construct(
        Security $security
    ) {
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

        $staff = $user->getStaff();

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere($queryBuilder->expr()->orX(
                $rootAlias . '.submitterClient = :client',
                $rootAlias . '.submitterCompany = :company ' . 'AND (' . $rootAlias . '.marketSegment IN (:marketSegments)' . ($staff && $staff->isAdmin() ? ' OR 1 = 1' : '') . ')'
            ))
            ->setParameters([
                'company'        => $staff->getCompany(),
                'client'         => $user,
                'marketSegments' => $staff ? $staff->getMarketSegments() : [],
            ])
        ;
    }
}
