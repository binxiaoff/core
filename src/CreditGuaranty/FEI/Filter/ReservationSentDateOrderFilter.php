<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use Symfony\Component\HttpFoundation\Request;

class ReservationSentDateOrderFilter extends AbstractContextAwareFilter
{
    private const PROPERTY_NAME     = 'order';
    private const PARAMETER_NAME    = 'sentDate';
    private const AUTHORIZED_VALUES = ['ASC', 'DESC'];

    public function getDescription(string $resourceClass): array
    {
        $description = [];

        if (false === (Reservation::class === $resourceClass)) {
            return $description;
        }

        $description[\sprintf('%s[%s]', self::PROPERTY_NAME, self::PARAMETER_NAME)] = [
            'property'      => self::PARAMETER_NAME,
            'type'          => 'string',
            'required'      => false,
            'is_collection' => true,
            'swagger'       => [
                'description' => 'Filter reservations by "sentDate"',
                'type'        => 'string',
                'property'    => static::PARAMETER_NAME,
                'enum'        => static::AUTHORIZED_VALUES,
            ],
            'openapi' => [
                'description' => 'Filter reservations by "sentDate"',
                'type'        => 'string',
                'property'    => static::PARAMETER_NAME,
                'enum'        => static::AUTHORIZED_VALUES,
            ],
        ];

        return $description;
    }

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        if (Reservation::class !== $resourceClass) {
            return;
        }

        if (Request::METHOD_GET !== \mb_strtoupper($operationName)) {
            return;
        }

        if (static::PROPERTY_NAME !== $property) {
            return;
        }

        if (false === \is_array($value) || false === \array_key_exists(self::PARAMETER_NAME, $value)) {
            return;
        }

        $direction = \mb_strtoupper($value[self::PARAMETER_NAME]);

        if (false === \in_array($direction, self::AUTHORIZED_VALUES)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->leftJoin("{$rootAlias}.statuses", 'rss', Join::WITH, 'rss.status = :sentStatus')
            ->setParameter('sentStatus', ReservationStatus::STATUS_SENT)
        ;

        // Cannot call variable as second parameter of orderBy()
        // phpcs: Potential SQL injection with direct variable usage in orderBy with param #2
        if ('ASC' === $direction) {
            $queryBuilder->orderBy('rss.added', 'ASC');
        } elseif ('DESC' === $direction) {
            $queryBuilder->orderBy('rss.added', 'DESC');
        }
    }
}
