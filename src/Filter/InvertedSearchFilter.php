<?php

declare(strict_types=1);

namespace Unilend\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class InvertedSearchFilter extends AbstractContextAwareFilter
{
    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        //TODO not the priority
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * Needed to handle custom sign at the end of property
     */
    protected function extractProperties(Request $request/*, string $resourceClass*/): array
    {
        $this->properties = array_combine(
            array_map(
                static function ($property) {
                    return $property . '!';
                },
                array_keys($this->properties)
            ),
            array_values($this->properties)
        );
        $extracted        = parent::extractProperties(...\func_get_args());
        $this->properties = array_combine(
            array_map(
                static function ($property) {
                    return trim($property, '!');
                },
                array_keys($this->properties)
            ),
            array_values($this->properties)
        );

        return $extracted;
    }

    /**
     * Passes a property through the filter.
     *
     * @param string                      $property
     * @param mixed                       $value
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if (
            false === mb_strpos($property, '!')
            || false === $this->isPropertyEnabled($property)
            || false === $this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $property = $this->removeExclamationMark($property);

        $parameterName = $queryNameGenerator->generateParameterName('value');

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::LEFT_JOIN);
        }

        // TODO to discuss
        $queryBuilder->andWhere("{$alias}.{$field} NOT IN (:{$parameterName}) OR {$alias}.{$field} IS NULL")
            ->setParameter($parameterName, (array) $value)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function isPropertyEnabled(string $property): bool
    {
        return parent::isPropertyEnabled(...$this->fixArguments(\func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    protected function isPropertyMapped(string $property, string $resourceClass, bool $allowAssociation = false): bool
    {
        return parent::isPropertyMapped(...$this->fixArguments(\func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    protected function isPropertyNested(string $property): bool
    {
        return parent::isPropertyNested(...$this->fixArguments(\func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    protected function isPropertyEmbedded(string $property, string $resourceClass): bool
    {
        return parent::isPropertyEmbedded(...$this->fixArguments(\func_get_args()));
    }

    /**
     * @param array $arguments
     *
     * @return array
     */
    private function fixArguments(array $arguments)
    {
        $property = reset($arguments);
        if ($property) {
            $arguments[0] = $this->removeExclamationMark($property);
        }

        return array_values($arguments);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function removeExclamationMark(string $string)
    {
        return rtrim($string, '!');
    }
}
