<?php

declare(strict_types=1);

namespace Unilend\Core\Filter\Company;

use ApiPlatform\Core\Bridge\Doctrine\Orm\{Filter\AbstractContextAwareFilter, Util\QueryNameGeneratorInterface};
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Unilend\Core\Entity\Constant\CARegionalBank;

class CARegionalBankFilter extends AbstractContextAwareFilter
{
    private const PARAMETER_NAME = 'caRegionalBank';

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description[self::PARAMETER_NAME] = [
            'property' => self::PARAMETER_NAME,
            'type'     => 'bool',
            'required' => false,
        ];

        return $description;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        if (self::PARAMETER_NAME === $property && true === $this->normalizeValue($value)) {
            $alias = $queryBuilder->getRootAliases()[0];
            $queryBuilder
                ->andWhere($alias . '.shortCode in (:caRegionalBanks)')
                ->setParameter('caRegionalBanks', CARegionalBank::REGIONAL_BANKS)
            ;
        }
    }

    /**
     * @param $value
     *
     * @return bool|null
     */
    private function normalizeValue($value): ?bool
    {
        if (\in_array($value, [true, 'true', '1'], true)) {
            return true;
        }

        if (\in_array($value, [false, 'false', '0'], true)) {
            return false;
        }

        $this->getLogger()->notice('Invalid filter ignored', [
            'exception' => new InvalidArgumentException(sprintf(
                'Invalid boolean value for "%s" property, expected one of ( "%s" )',
                self::PARAMETER_NAME,
                implode('" | "', ['true', 'false', '1', '0'])
            )),
        ]);

        return null;
    }
}
