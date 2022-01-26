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
        if ($query->isEmpty()) {
            $queryBuilder->andWhere('1 = 0');

            // we still handle pagination to be able to return paginator in controller
            $this->handlePagination($queryBuilder, $limit, $offset);

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
