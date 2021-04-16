<?php

declare(strict_types=1);

namespace Unilend\Agency\ApiPlatform\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Company;

class ProjectFilter extends AbstractContextAwareFilter
{
    private const PROPERTY = 'as';

    private const AUTHORIZED_VALUES = ['agent', 'participant', 'borrower'];

    private Security $security;

    private string $parameterPrefix;

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

        $this->parameterPrefix = (new ReflectionClass($this))->getShortName();
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

            $queryBuilder->where('0 = 1');

            if (\in_array('borrower', $value, true)) {
                $this->includeBorrower($queryBuilder);
            }

            if (\in_array('participant', $value, true)) {
                $this->includeParticipant($queryBuilder);
            }

            if (\in_array('agent', $value, true)) {
                $this->includeAgent($queryBuilder);
            }
        }
    }

    private function includeBorrower(QueryBuilder $queryBuilder)
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->leftJoin($rootAlias . '.borrowers', 'b')
            ->leftJoin('b.members', 'bm')
            ->orWhere('bm.user = :' . $this->getParameterName('user'))
            ->setParameter($this->getParameterName('user'), $this->security->getUser())
        ;
    }

    private function includeParticipant(QueryBuilder $queryBuilder)
    {
        $company = $this->getCompany();

        if (null === $company) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder->leftJoin($rootAlias . '.participations', 'p')
            ->orWhere('p.participant = :' . $this->getParameterName('company') . ' and p.participant !=' . $rootAlias . '.agent')
            ->setParameter($this->getParameterName('company'), $company)
        ;
    }

    private function includeAgent(QueryBuilder $queryBuilder)
    {
        $company = $this->getCompany();

        if (null === $company) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder->orWhere($rootAlias . '.agent = :' . $this->getParameterName('company'))
            ->setParameter($this->getParameterName('company'), $company)
        ;
    }

    private function getParameterName(string $name)
    {
        return $this->parameterPrefix . '_' . $name;
    }

    private function getCompany(): ?Company
    {
        $token = $this->security->getToken();

        if (null === $token || false === $token->getAttribute('company')) {
            return null;
        }

        return $token->getAttribute('company');
    }
}
