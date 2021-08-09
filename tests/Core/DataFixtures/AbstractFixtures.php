<?php

declare(strict_types=1);

namespace KLS\Test\Core\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

abstract class AbstractFixtures extends Fixture implements FixtureGroupInterface
{
    private TokenStorageInterface $tokenStorage;
    private array $publicIdReflexionProperties = [];
    private array $idGenerator                 = [];

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public static function getGroups(): array
    {
        return ['test'];
    }

    /**
     * @param mixed $object
     *
     * @throws ReflectionException
     */
    protected function setPublicId($object, string $publicId)
    {
        /** @var string $class */
        $class = \get_class($object);

        if (false === isset($this->publicIdReflexionProperties[$class])) {
            $reflectionClass = new ReflectionClass($class);

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
     * @throws ReflectionException
     */
    protected function forceId(ObjectManager $manager, object $entity, int $value): void
    {
        $this->disableAutoIncrement($manager, $entity);

        $ref      = new ReflectionClass(\get_class($entity));
        $property = $ref->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $value);
    }

    /**
     * @param object $entity
     */
    protected function restoreAutoIncrement($entity, ObjectManager $manager): void
    {
        if (!\is_string($entity)) {
            $entity = \get_class($entity);
        }

        // @var string $entity
        [$type, $generator] = $this->idGenerator[$entity];
        unset($this->idGenerator[$entity]);

        $metadata = $manager->getClassMetadata($entity);
        $metadata->setIdGeneratorType($type);
        $metadata->setIdGenerator($generator);
    }

    protected function loginUser(User $user)
    {
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'main');

        $this->tokenStorage->setToken($token);

        return $token;
    }

    protected function loginStaff(Staff $staff)
    {
        $token = $this->loginUser($staff->getUser());

        $staff->getUser()->setCurrentStaff($staff);
        $token->setAttribute('staff', $staff);
        $token->setAttribute('company', $staff->getCompany());

        return $token;
    }

    /**
     * @param array|string[]|string $events
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

    private function disableAutoIncrement(ObjectManager $manager, object $entity): void
    {
        /** @var string $entity */
        $entity = \get_class($entity);

        /** @var ClassMetadata $metadata */
        $metadata = $manager->getClassMetadata($entity);

        if (!isset($this->idGenerator[$entity])) {
            $this->idGenerator[$entity] = [$metadata->generatorType, $metadata->idGenerator];
        }

        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
        $metadata->setIdGenerator(new AssignedGenerator());
    }
}
