<?php

declare(strict_types=1);

namespace KLS\Core\DTO;

class Query
{
    private array $selects = [];
    private array $joins   = [];
    private array $clauses = [];
    private array $orders  = [];

    public function getSelects(): array
    {
        return $this->selects;
    }

    public function addSelect(string $expression): void
    {
        if (\in_array($expression, $this->selects)) {
            return;
        }

        $this->selects[] = $expression;
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * @param array $join it should contain a key to distinguish it from others, otherwise it will be ignored
     */
    public function addJoin(array $join): void
    {
        if (empty($join) || 1 !== \count($join)) {
            return;
        }

        foreach ($join as $key => $joinParts) {
            $this->joins[$key] = $joinParts;
        }
    }

    public function getClauses(): array
    {
        return $this->clauses;
    }

    public function addClause(array $clause): void
    {
        if (empty($clause)) {
            return;
        }

        if (empty($clause['expression']) || false === \array_key_exists('parameter', $clause)) {
            return;
        }

        $this->clauses[] = $clause;
    }

    public function getOrders(): array
    {
        return $this->orders;
    }

    public function addOrder(array $order): void
    {
        foreach ($order as $key => $value) {
            $this->orders[$key] = $value;
        }
    }
}
