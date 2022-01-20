<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Filter\ApiPlatform;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Traits\ConstantsAwareTrait;
use KLS\Syndication\Agency\Entity\Project;

class ProjectFilter extends AbstractContextAwareFilter
{
    use ConstantsAwareTrait;

    public const VALUE_AGENT       = 'agent';
    public const VALUE_PARTICIPANT = 'participant';
    public const VALUE_BORROWER    = 'borrower';

    private const PROPERTY = 'as';

    public function getDescription(string $resourceClass): array
    {
        if (false === (Project::class === $resourceClass)) {
            return [];
        }

        return [
            static::PROPERTY . '[]' => [
                'required'      => false,
                'is_collection' => true,
                'type'          => 'string',
                'property'      => static::PROPERTY,
                'swagger'       => [
                    'description' => 'Filter projects by "role" of the connected entity',
                    'type'        => 'string',
                    'property'    => static::PROPERTY,
                    'enum'        => \array_values(static::getConstants('VALUE_')),
                ],
                'openapi' => [
                    'description' => 'Filter projects by "role" of the connected entity',
                    'type'        => 'string',
                    'property'    => static::PROPERTY,
                    'enum'        => \array_values(static::getConstants('VALUE_')),
                ],
            ],
        ];
    }

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        //In order to optimise the performance,
        //we merge the query with those in KLS\Syndication\Agency\Extension\ProjectExtension
        //Nothing to do where.
    }
}
