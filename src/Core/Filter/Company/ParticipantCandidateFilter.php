<?php

declare(strict_types=1);

namespace KLS\Core\Filter\Company;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Company;
use Symfony\Component\HttpFoundation\Request;

class ParticipantCandidateFilter extends AbstractContextAwareFilter
{
    private const PARAMETER_NAME = 'eligibleParticipant';

    public function getDescription(string $resourceClass): array
    {
        $description[self::PARAMETER_NAME] = [
            'property' => self::PARAMETER_NAME,
            'type'     => 'bool',
            'required' => false,
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
        if (self::PARAMETER_NAME === $property && Company::class === $resourceClass && Request::METHOD_GET === \mb_strtoupper($operationName)) {
            $alias = $queryBuilder->getRootAliases()[0];
            $queryBuilder
                ->andWhere($alias . '.shortCode not in (:nonEligibleCompanies)')
                ->setParameter('nonEligibleCompanies', Company::NON_ELIGIBLE_TO_PARTICIPANT)
            ;
        }
    }
}
