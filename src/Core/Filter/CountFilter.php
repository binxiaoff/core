<?php

declare(strict_types=1);

namespace Unilend\Core\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;

class CountFilter extends AbstractContextAwareFilter
{
    private const PARAMETER_MAX   = 'max';
    private const PARAMETER_MIN   = 'min';
    private const PARAMETER_EXACT = 'exact';

    private const COMPARAISON_OPERATOR = [
        self::PARAMETER_MAX   => '<=',
        self::PARAMETER_MIN   => '>=',
        self::PARAMETER_EXACT => '=',
    ];

    /**
     * Gets the description of this filter for the given resource.
     *
     * Returns an array with the filter parameter names as keys and array with the following data as values:
     *   - property: the property where the filter is applied
     *   - type: the type of the filter
     *   - required: if this filter is required
     *   - strategy: the used strategy
     *   - is_collection (optional): is this filter is collection
     *   - swagger (optional): additional parameters for the path operation,
     *     e.g. 'swagger' => [
     *       'description' => 'My Description',
     *       'name' => 'My Name',
     *       'type' => 'integer',
     *     ]
     *   - openapi (optional): additional parameters for the path operation in the version 3 spec,
     *     e.g. 'openapi' => [
     *       'description' => 'My Description',
     *       'name' => 'My Name',
     *       'schema' => [
     *          'type' => 'integer',
     *       ]
     *     ]
     * The description can contain additional data specific to a filter.
     *
     * @see \ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer::getFiltersParameters
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->getProperties();
        if (null === $properties) {
            $properties = \array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $unused) {
            if ($this->isPropertyMapped($property, $resourceClass) && $this->isCountableField($property, $resourceClass)) {
                $propertyName = $this->normalizePropertyName($property);

                foreach ([self::PARAMETER_MAX => 'at most', self::PARAMETER_MIN => 'at least', self::PARAMETER_EXACT => 'exactly'] as $parameter => $summary) {
                    $summary                                      = 'Filters collection properties who have ' . $parameter . ' X items in their collection';
                    $description["{$propertyName}[{$parameter}]"] = [
                        'property'      => $propertyName,
                        'type'          => 'integer',
                        'required'      => false,
                        'is_collection' => false,
                        'openapi'       => [
                            'description' => $summary,
                        ],
                        'swagger' => [
                            'description' => $summary,
                        ],
                    ];
                }
            }
        }

        return $description;
    }

    /**
     * Passes a property through the filter.
     *
     * @param mixed $values
     */
    protected function filterProperty(
        string $property,
        $values,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        if (
            false === \is_array($values)
            || false === $this->isPropertyEnabled($property, $resourceClass)
            || false === $this->isPropertyMapped($property, $resourceClass, true)
            || false === $this->isCountableField($property, $resourceClass)
        ) {
            return;
        }
        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }

        foreach ($values as $operator => $value) {
            if (\in_array($operator, [self::PARAMETER_MAX, self::PARAMETER_MIN, self::PARAMETER_EXACT], true)) {
                $comparaison   = self::COMPARAISON_OPERATOR[$operator];
                $parameterName = $queryNameGenerator->generateParameterName($property);
                $queryBuilder->andWhere("SIZE({$alias}.{$field}) {$comparaison} :{$parameterName}")
                    ->setParameter($parameterName, $value, Type::INTEGER)
                 ;
            }
        }
    }

    private function isCountableField(string $property, string $resourceClass): bool
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $metadata      = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        return $metadata->isCollectionValuedAssociation($propertyParts['field']);
    }
}
