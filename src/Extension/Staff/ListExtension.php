<?php

declare(strict_types=1);

namespace Unilend\Extension\Staff;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Unilend\Entity\Staff;
use Unilend\Entity\StaffStatus;

class ListExtension implements QueryCollectionExtensionInterface
{
    /**
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
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
