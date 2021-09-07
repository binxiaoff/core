<?php

declare(strict_types=1);

namespace KLS\Core\Repository\Traits;

use Doctrine\ORM\QueryBuilder;
use RuntimeException;

trait OrderByHandlerTrait
{
    private function handleOrderBy(QueryBuilder $queryBuilder, array $orderBy): void
    {
        $aliases = $queryBuilder->getRootAliases();
        if (!isset($aliases[0])) {
            throw new RuntimeException('No alias was set before invoking getRootAlias().');
        }
        $alias = $aliases[0];

        foreach ($orderBy as $sort => $order) {
            if (false === \mb_strpos($sort, '.')) {
                $sort = $alias . '.' . $sort;
            }

            $queryBuilder->addOrderBy($sort, $order);
        }
    }
}
