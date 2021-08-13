<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Filter\Doctrine;

use DateTime;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use KLS\Syndication\Agency\Entity\Term;

/**
 * Filter to hide the created ahead of time covenant terms.
 */
class TermFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (false === (Term::class === $targetEntity->name)) {
            return '';
        }

        $connection = $this->getConnection();

        return $targetTableAlias . '.start_date <= ' .
            $connection->quote((new DateTime())->setTime(0, 0)->format('Y-m-d'));
    }
}
