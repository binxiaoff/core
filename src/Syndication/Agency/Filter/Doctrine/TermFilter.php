<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Filter\Doctrine;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use KLS\Syndication\Agency\Entity\Term;

/**
 * Filter to hide the created ahead of time covenant terms.
 */
class TermFilter extends SQLFilter
{
    /**
     * @param mixed $targetTableAlias
     *
     * @throws Exception
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (false === (Term::class === $targetEntity->name)) {
            return '';
        }

        $date = $this->getConnection()->getDatabasePlatform()->getCurrentDateSQL();

        return $targetTableAlias . '.start_date <= ' . $date;
    }
}
