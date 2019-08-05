<?php

declare(strict_types=1);

namespace Unilend\Repository\Traits;

use Doctrine\ORM\QueryBuilder;

trait PaginationHandlerTrait
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param int|null     $limit
     * @param int|null     $offset
     */
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
