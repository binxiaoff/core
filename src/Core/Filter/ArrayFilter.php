<?php

declare(strict_types=1);

namespace KLS\Core\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Exception;

class ArrayFilter extends AbstractContextAwareFilter
{
    private const DOCTRINE_ARRAY_TYPES = [
        Type::JSON         => true,
        Type::JSON_ARRAY   => true,
        Type::SIMPLE_ARRAY => true,
        Type::TARRAY       => true,
    ];

    private const PARAMETER_ALL = 'all';
    private const PARAMETER_ANY = 'any';

    private const QUERY_PARAMETER_PREFIX = 'array_filter_term_';

    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->getProperties();
        if (null === $properties) {
            $properties = \array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $unused) {
            if (!$this->isPropertyMapped($property, $resourceClass) || !$this->isArrayField($property, $resourceClass)) {
                continue;
            }

            $propertyName = $this->normalizePropertyName($property);

            foreach ([self::PARAMETER_ALL, self::PARAMETER_ANY] as $parameter) {
                $summary                                      = 'Filters element who have ' . $parameter . ' of the listed items in the ' . $propertyName . ' property';
                $description["{$propertyName}[{$parameter}]"] = [
                    'property'      => $propertyName,
                    'type'          => 'string',
                    'required'      => false,
                    'is_collection' => true,
                    'openapi'       => [
                        'description' => $summary,
                    ],
                    'swagger' => [
                        'description' => $summary,
                    ],
                ];
            }
        }

        return $description;
    }

    /**
     * Passes a property through the filter.
     *
     * @param mixed $values
     *
     * @throws Exception
     */
    protected function filterProperty(
        string $property,
        $values,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if (
            false === \is_array($values)
            || false === $this->isPropertyEnabled($property, $resourceClass)
            || false === $this->isPropertyMapped($property, $resourceClass, true)
            || false === $this->isArrayField($property, $resourceClass)
        ) {
            return;
        }

        $values = $this->normalizeValues($values, $property);
        if (null === $values) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }

        foreach ($values as $operator => $value) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                $operator,
                $value
            );
        }
    }

    private function isArrayField(string $property, string $resourceClass): bool
    {
        return isset(self::DOCTRINE_ARRAY_TYPES[(string) $this->getDoctrineFieldType($property, $resourceClass)]);
    }

    private function normalizeValues(array $values, string $property): ?array
    {
        $operators = [self::PARAMETER_ANY, self::PARAMETER_ALL];

        foreach ($values as $operator => $value) {
            if (!\in_array($operator, $operators, true)) {
                unset($values[$operator]);
            }
        }

        if (empty($values)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(\sprintf('At least one valid operator ("%s") is required for "%s" property', \implode('", "', $operators), $property)),
            ]);

            return null;
        }

        return $values;
    }

    /**
     * @param mixed $alias
     * @param mixed $value
     *
     * @throws Exception
     */
    private function addWhere(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, $alias, string $field, string $operator, $value)
    {
        $value = (array) $value;

        $expr           = $queryBuilder->expr();
        $logicOperation = (self::PARAMETER_ALL === $operator) ? $expr->andX() : null;
        $logicOperation = (self::PARAMETER_ANY === $operator) ? $expr->orX() : $logicOperation;

        if (null === $logicOperation) {
            $this->getLogger()->critical('Ignored filter (should be impossible)', \compact($operator, $value));

            return;
        }

        foreach ($value as $index => $term) {
            $parameterName = $queryNameGenerator->generateParameterName(self::QUERY_PARAMETER_PREFIX . $index);
            $logicOperation->add("JSON_SEARCH({$alias}.{$field}, 'one', :{$parameterName}) IS NOT NULL");
            $queryBuilder->setParameter($parameterName, $term);
        }

        $queryBuilder->andWhere($logicOperation);
    }
}
