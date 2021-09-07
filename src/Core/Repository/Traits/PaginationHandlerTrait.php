<?php

declare(strict_types=1);

namespace KLS\Core\Repository\Traits;

use Doctrine\ORM\QueryBuilder;

trait PaginationHandlerTrait
{
    private function handlePagination(QueryBuilder $queryBuilder, ?int $limit, ?int $offset): void
    {
        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if (null !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }
    }
}
