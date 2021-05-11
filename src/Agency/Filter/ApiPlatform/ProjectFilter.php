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
use Unilend\Core\Entity\Staff;

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

        $userParameterName = static::prefix('user') . '_borrower';

        $queryBuilder
            ->leftJoin("{$rootAlias}.borrowers", $borrowerAlias)
            ->leftJoin("{$borrowerAlias}.members", $borrowerMemberAlias)
            ->setParameter($userParameterName, $this->security->getUser())
        ;

        return "{$borrowerMemberAlias}.user = :{$userParameterName}";
    }

    private function includeParticipant(QueryBuilder $queryBuilder): ?string
    {
        $staff = $this->getStaff();

        if (null === $staff) {
            return null;
        }

        $rootAlias                = $queryBuilder->getRootAliases()[0];
        $participationAlias       = static::prefix('participation');
        $participationPoolAlias   = static::prefix('participationPool');
        $participationMemberAlias = static::prefix('participationMember');

        $companyParameterName = static::prefix('company') . '_participant';
        $usersParameterName   = static::prefix('users') . '_participant';

        $queryBuilder
            ->leftJoin("{$rootAlias}.participationPools", $participationPoolAlias)
            ->leftJoin($participationPoolAlias . '.participations', $participationAlias)
            ->leftJoin($participationAlias . '.members', $participationMemberAlias)
            ->setParameter($companyParameterName, $staff->getCompany())
            ->setParameter($usersParameterName, $staff->getManagedUsers())
        ;

        return "{$participationAlias}.participant = :{$companyParameterName} and {$participationMemberAlias}.user IN (:{$usersParameterName})";
    }

    private function includeAgent(QueryBuilder $queryBuilder): ?string
    {
        $staff = $this->getStaff();

        if (null === $staff) {
            return null;
        }

        $rootAlias        = $queryBuilder->getRootAliases()[0];
        $agentAlias       = static::prefix('agent');
        $agentMemberAlias = static::prefix('agentMember');

        $companyParameterName = static::prefix('company') . '_agent';
        $usersParameterName   = static::prefix('users') . '_agent';

        $queryBuilder
            ->leftJoin("{$rootAlias}.agent", $agentAlias)
            ->leftJoin("{$agentAlias}.members", $agentMemberAlias)
            ->setParameter($companyParameterName, $staff->getCompany())
            ->setParameter($usersParameterName, iterator_to_array($staff->getManagedUsers()))
        ;

        return "{$agentAlias}.company = :{$companyParameterName} and {$agentMemberAlias}.user IN (:{$usersParameterName})";
    }

    private static function prefix(string $name)
    {
        return static::PREFIX . '_' . $name;
    }

    private function getStaff(): ?Staff
    {
        $token = $this->security->getToken();

        if (null === $token || false === $token->hasAttribute('staff')) {
            return null;
        }

        return $token->getAttribute('staff');
    }
}
