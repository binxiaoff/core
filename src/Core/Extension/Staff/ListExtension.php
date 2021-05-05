<?php

declare(strict_types=1);

namespace Unilend\Core\Extension\Staff;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\StaffStatus;

class ListExtension implements QueryCollectionExtensionInterface
{
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        if (Staff::class !== $resourceClass) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->innerJoin($rootAlias . '.currentStatus', 'status')
            ->andWhere('status.status <> :archivedStatus')
            ->setParameter('archivedStatus', StaffStatus::STATUS_ARCHIVED)
        ;
    }
}
