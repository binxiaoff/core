<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\GraphQl\Resolver\Stage;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Symfony\EventListener\ValidatorInterface;

/**
 * Validate stage of GraphQL resolvers.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ValidateStage implements ValidateStageInterface
{
    private $resourceMetadataCollectionFactory;
    private $validator;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ValidatorInterface $validator)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($object, string $resourceClass, string $operationName, array $context): void
    {
        $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $operation = $resourceMetadataCollection->getOperation($operationName);

        if (!($operation->canValidate() ?? true)) {
            return;
        }

        $this->validator->validate($object, $operation->getValidationContext() ?? []);
    }
}
