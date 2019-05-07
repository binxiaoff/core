<?php

declare(strict_types=1);

namespace Unilend\Form\DataTransformer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class IdentityTransformer implements DataTransformerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var string */
    private $entityClass;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string          $entityClass
     */
    public function __construct(ManagerRegistry $managerRegistry, string $entityClass)
    {
        $this->entityManager = $managerRegistry->getManagerForClass($entityClass);
        $this->entityClass   = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($entity)
    {
        if (null === $entity) {
            return null;
        }

        return $entity->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($id)
    {
        // no id? It's optional, so that's ok
        if (!$id) {
            return null;
        }

        $entity = $this->entityManager->getRepository($this->entityClass)->find($id);

        if (null === $entity) {
            throw new TransformationFailedException(sprintf(
                'An entity with id "%s" does not exist!',
                $id
            ));
        }

        return $entity;
    }
}
