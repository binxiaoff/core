<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use ReflectionClass;

abstract class AbstractFixtures extends Fixture implements FixtureGroupInterface
{
    private array $publicIdReflexionProperties = [];

    /**
     * @inheritDoc
     */
    public static function getGroups(): array
    {
        return ['test'];
    }

    /**
     * @param $object
     * @param string $publicId
     *
     * @throws \ReflectionException
     */
    protected function setPublicId($object, string $publicId)
    {
        /** @var string $class */
        $class = \get_class($object);

        if (false === isset($this->publicIdReflexionProperties[$class])) {
            $reflectionClass =  new ReflectionClass($class);

            if ($reflectionClass->hasProperty('publicId')) {
                $property = $reflectionClass->getProperty('publicId');
                $property->setAccessible(true);
                $this->publicIdReflexionProperties[$class] = $property;
            }
        }

        if (($property = ($this->publicIdReflexionProperties[$class] ?? null))) {
            $property->setValue($object, $publicId);
        }
    }

    /**
     * @param ObjectManager         $manager
     * @param array|string[]|string $events
     * @param string                $class
     */
    protected function removeEventListenerByClass(ObjectManager $manager, $events, string $class)
    {
        if ($manager instanceof EntityManagerInterface) {
            $eventManager = $manager->getEventManager();
            foreach ((array) $events as $event) {
                foreach ($eventManager->getListeners($event) as $listener) {
                    if ($listener instanceof $class) {
                        $eventManager->removeEventListener($event, $listener);
                    }
                }
            }
        }
    }
}
