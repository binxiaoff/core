<?php

namespace Unilend\Repository\Traits;

use Doctrine\ORM\QueryBuilder;
use RuntimeException;

trait OrderByHandlerTrait
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $orderBy
     */
    private function handleOrderBy(QueryBuilder $queryBuilder, array $orderBy): void
    {
        $aliases = $queryBuilder->getRootAliases();
        if (!isset($aliases[0])) {
            throw new RuntimeException('No alias was set before invoking getRootAlias().');
        }
        $alias = $aliases[0];

        foreach ($orderBy as $sort => $order) {
            if (false === mb_strpos($sort, '.')) {
                $queryBuilder->addOrderBy($alias . '.' . $sort, $order);
            }
        }
    }
}
