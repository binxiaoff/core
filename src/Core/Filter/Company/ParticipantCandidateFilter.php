<?php

declare(strict_types=1);

namespace Unilend\Core\Filter\Company;

use ApiPlatform\Core\Bridge\Doctrine\Orm\{Filter\AbstractContextAwareFilter, Util\QueryNameGeneratorInterface};
use Doctrine\ORM\QueryBuilder;
use Unilend\Core\Entity\Company;

class ParticipantCandidateFilter extends AbstractContextAwareFilter
{
    private const PARAMETER_NAME = 'eligibleParticipant';

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
        if (self::PARAMETER_NAME === $property && Company::class === $resourceClass) {
            $alias = $queryBuilder->getRootAliases()[0];
            $queryBuilder
                ->andWhere($alias . '.shortCode not in (:nonEligibleCompanies)')
                ->setParameter('nonEligibleCompanies', Company::NON_ELIGIBLE_TO_PARTICIPANT)
            ;
        }
    }
}
