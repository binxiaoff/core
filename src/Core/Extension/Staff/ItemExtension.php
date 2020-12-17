<?php

declare(strict_types=1);

namespace Unilend\Core\Extension\Staff;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\StaffStatus;

class ItemExtension implements QueryItemExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        string $operationName = null,
        array $context = []
    ) {
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
