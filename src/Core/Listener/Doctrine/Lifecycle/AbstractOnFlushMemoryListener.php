<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Lifecycle;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

/**
 * Inspired by: https://github.com/nmallare/platform/blob/master/src/Oro/Bundle/ActivityListBundle/EventListener/ActivityListListener.php.
 *
 * todo: it can also collect the updated entities and deleted (as an array or a non managed entity object, since the entity has already been deleted) entities in the future.
 */
abstract class AbstractOnFlushMemoryListener
{
    protected const SUPPORTED_ENTITY_CLASSES = [];

    private array $insertedEntities = [];

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->collectInsertedEntities($args->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions());
    }

    /**
     * @param PostFlushEventArgs $args
     */
    abstract public function postFlush(PostFlushEventArgs $args): void;

    /**
     * We define this method as the only access to the insertedEntities, in order to delete the inserted entity from the array when we trait it.
     * It prevents our application from a infinite loop.
     *
     * @return mixed|null
     */
    final protected function shiftInsertedEntity()
    {
        return array_shift($this->insertedEntities);
    }

    /**
     * @param array $entities
     */
    private function collectInsertedEntities(array $entities): void
    {
        foreach ($entities as $hash => $entity) {
            if (empty($this->insertedEntities[$hash]) && $this->isSupportedEntity($entity)) {
                $this->insertedEntities[$hash] = $entity;
            }
        }
    }

    /**
     * @param object|string $entityOrClass
     *
     * @return string
     */
    private function getEntityClass($entityOrClass): string
    {
        return is_object($entityOrClass)
            ? ClassUtils::getClass($entityOrClass)
            : ClassUtils::getRealClass($entityOrClass);
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    private function isSupportedEntity(object $entity): bool
    {
        if (false === defined('static::SUPPORTED_ENTITY_CLASSES')) {
            throw new \LogicException(sprintf('You must define the SUPPORTED_ENTITY_CLASSES by using the trait %s', __TRAIT__));
        }

        return in_array($this->getEntityClass($entity), static::SUPPORTED_ENTITY_CLASSES, true);
    }
}
