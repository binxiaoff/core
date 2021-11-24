<?php

declare(strict_types=1);

namespace KLS\Core\Repository\Traits;

use Doctrine\ORM\QueryBuilder;
use KLS\Core\DTO\Query;

trait QueryHandlerTrait
{
    use PaginationHandlerTrait;

    private function buildQuery(
        QueryBuilder $queryBuilder,
        Query $query,
        ?int $offset = null,
        ?int $limit = null
    ): QueryBuilder {
        if (
            empty($query->getSelects())
            && empty($query->getJoins())
            && empty($query->getClauses())
            && empty($query->getOrders())
        ) {
            // return invalid clause or nothing ?
            $queryBuilder->andWhere('1 = 0');

            return $queryBuilder;
        }

        foreach ($query->getSelects() as $select) {
            $queryBuilder->addSelect($select);
        }

        foreach ($query->getJoins() as $join) {
            $queryBuilder->leftJoin(...$join);
        }

        foreach ($query->getClauses() as $clause) {
            $queryBuilder->andWhere($clause['expression']);

            if (false === empty($clause['parameter'])) {
                $queryBuilder->setParameter(...$clause['parameter']);
            }
        }

        foreach ($query->getOrders() as $orderBy => $orderDirection) {
            $queryBuilder->addOrderBy($orderBy, $orderDirection);
        }

        $this->handlePagination($queryBuilder, $limit, $offset);

        return $queryBuilder;
    }
}
