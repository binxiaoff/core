<?php

declare(strict_types=1);

namespace Unilend\Agency\Filter\ApiPlatform;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Company;

class ProjectFilter extends AbstractContextAwareFilter
{
    private const PROPERTY = 'as';

    private const AUTHORIZED_VALUES = ['agent', 'participant', 'borrower'];

    private const PREFIX = 'ProjectFilter';

    private Security $security;

    public function __construct(
        Security $security,
        ManagerRegistry $managerRegistry,
        ?RequestStack $requestStack = null,
        LoggerInterface $logger = null,
        array $properties = null,
        NameConverterInterface $nameConverter = null
    ) {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);

        $this->security = $security;
    }

    /**
     * {@inheritDoc}
     */
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
                    'enum'        => static::AUTHORIZED_VALUES,
                ],
                'openapi' => [
                    'description' => 'Filter projects by "role" of the connected entity',
                    'type'        => 'string',
                    'property'    => static::PROPERTY,
                    'enum'        => static::AUTHORIZED_VALUES,
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if (Project::class !== $resourceClass) {
            return;
        }

        if (static::PROPERTY !== $property) {
            return;
        }

        if (\is_string($value)) {
            $value = [$value];
        }

        if ($value) {
            $queryBuilder->distinct();

            $conditions = [];

            if (\in_array('borrower', $value, true)) {
                $conditions[] = $this->includeBorrower($queryBuilder);
            }

            if (\in_array('participant', $value, true)) {
                $conditions[] = $this->includeParticipant($queryBuilder);
            }

            if (\in_array('agent', $value, true)) {
                $conditions[] = $this->includeAgent($queryBuilder);
            }

            $conditions = array_filter($conditions);

            if ($conditions) {
                $queryBuilder->andWhere($queryBuilder->expr()->orX(...$conditions));
            }
        }
    }

    private function includeBorrower(QueryBuilder $queryBuilder): string
    {
        $rootAlias           = $queryBuilder->getRootAliases()[0];
        $borrowerAlias       = self::prefix('borrower');
        $borrowerMemberAlias = static::prefix('borrowerMember');

        $userParameterName = static::prefix('user');

        $queryBuilder
            ->leftJoin("{$rootAlias}.borrowers", $borrowerAlias)
            ->leftJoin("{$borrowerAlias}.members", $borrowerMemberAlias)
            ->setParameter($userParameterName, $this->security->getUser())
        ;

        return "{$borrowerMemberAlias}.user = :{$userParameterName}";
    }

    private function includeParticipant(QueryBuilder $queryBuilder): ?string
    {
        $company = $this->getCompany();

        if (null === $company) {
            return null;
        }

        $rootAlias              = $queryBuilder->getRootAliases()[0];
        $participationAlias     = static::prefix('participation');
        $participationPoolAlias = static::prefix('participationPool');

        $companyParameterName = static::prefix('company') . '_participant';

        $queryBuilder
            ->leftJoin("{$rootAlias}.participationPools", $participationPoolAlias)
            ->leftJoin($participationPoolAlias . '.participations', $participationAlias)
            ->setParameter($companyParameterName, $company)
        ;

        return "{$participationAlias}.participant = :{$companyParameterName} and {$participationAlias}.participant !=  {$rootAlias}.agent";
    }

    private function includeAgent(QueryBuilder $queryBuilder): ?string
    {
        $company = $this->getCompany();

        if (null === $company) {
            return null;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $companyParameterName = static::prefix('company') . '_agent';

        $queryBuilder->setParameter($companyParameterName, $company);

        return "{$rootAlias}.agent = :{$companyParameterName}";
    }

    private static function prefix(string $name)
    {
        return static::PREFIX . '_' . $name;
    }

    private function getCompany(): ?Company
    {
        $token = $this->security->getToken();

        if (null === $token || false === $token->hasAttribute('company')) {
            return null;
        }

        return $token->getAttribute('company');
    }
}
