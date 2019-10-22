<?php

declare(strict_types=1);

namespace Unilend\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Unilend\Entity\Project;
use Unilend\Repository\ProjectRepository;

class ProjectDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var ProjectRepository
     */
    private $repository;

    /**
     * ProjectDataProvider constructor.
     *
     * @param ProjectRepository $repository
     */
    public function __construct(
        ProjectRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Retrieves an item.
     *
     * @param string           $resourceClass
     * @param array|int|string $id
     * @param string|null      $operationName
     * @param array            $context
     *
     * @return Project|null
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Project
    {
        return $this->repository->findOneBy(['id' => $id]) ?? $this->repository->findOneBy(['hash' => $id]);
    }

    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     * @param array       $context
     *
     * @return bool
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Project::class === $resourceClass;
    }
}
