<?php

declare(strict_types=1);

namespace Unilend\Core\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\Entity\Staff;

abstract class AbstractFixtures extends Fixture
{
    protected Generator $faker;

    private TokenStorageInterface $tokenStorage;
    private array $idGenerator = [];

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->faker        = Factory::create('fr_FR');
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param $entity
     * @param string $value
     *
     * @throws ReflectionException
     */
    protected function forcePublicId($entity, string $value): void
    {
        $ref      = new ReflectionClass(get_class($entity));
        $property = $ref->getProperty('publicId');
        $property->setAccessible(true);
        $property->setValue($entity, $value);
    }

    /**
     * @param ObjectManager $manager
     * @param object        $entity
     * @param int           $value
     *
     * @throws ReflectionException
     */
    protected function forceId(ObjectManager $manager, object $entity, int $value): void
    {
        $this->disableAutoIncrement($manager, $entity);
        $ref      = new ReflectionClass(get_class($entity));
        $property = $ref->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $value);
    }

    /**
     * Return multiple references.
     *
     * @param array $names
     *
     * @return object[]
     */
    protected function getReferences(array $names): array
    {
        return array_map(function (string $name) {
            return $this->getReference($name);
        }, array_combine($names, $names));
    }

    /**
     * @param Staff|string $staff
     */
    protected function login($staff): void
    {
        if (is_string($staff)) {
            $staff = $this->getReference($staff);
        }

        $user = $staff->getUser();

        $user->setCurrentStaff($staff);

        $token = new JWTUserToken($user->getRoles(), $user);
        $token->setAttribute('staff', $staff);

        $this->tokenStorage->setToken($token);
    }

    /**
     * @param object        $entity
     * @param ObjectManager $manager
     */
    protected function restoreAutoIncrement($entity, ObjectManager $manager): void
    {
        if (!is_string($entity)) {
            $entity = get_class($entity);
        }
        // @var string $entity
        [$type, $generator] = $this->idGenerator[$entity];
        unset($this->idGenerator[$entity]);
        $metadata = $manager->getClassMetadata($entity);
        $metadata->setIdGeneratorType($type);
        $metadata->setIdGenerator($generator);
    }

    /**
     * @param ObjectManager $manager
     * @param object        $entity
     */
    private function disableAutoIncrement(ObjectManager $manager, object $entity): void
    {
        $entity = get_class($entity);
        /** @var ClassMetadata $metadata */
        $metadata = $manager->getClassMetadata($entity);
        /** @var string $entity */
        if (!isset($this->idGenerator[$entity])) {
            $this->idGenerator[$entity] = [$metadata->generatorType, $metadata->idGenerator];
        }
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
        $metadata->setIdGenerator(new AssignedGenerator());
    }
}
