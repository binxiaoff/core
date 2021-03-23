<?php

declare(strict_types=1);

namespace Unilend\Agency\Doctrine\Filter;

use DateTime;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Unilend\Agency\Entity\Term;

/**
 * Filter to hide the created ahead of time covenant terms
 */
class TermFilter extends SQLFilter
{
    /**
     * @inheritDoc
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (false === ($targetEntity->name === Term::class)) {
            return '';
        }

        $connection = $this->getConnection();

        return $targetTableAlias . '.start_date <= ' .
            $connection->quote((new DateTime())->setTime(0, 0)->format('Y-m-d'));
    }
}
