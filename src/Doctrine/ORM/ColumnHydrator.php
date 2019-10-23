<?php

declare(strict_types=1);

namespace Unilend\Doctrine\ORM;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use PDO;

class ColumnHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData(): array
    {
        return $this->_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
