<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Faker\{Factory, Generator};
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Entity\Staff;

use function get_class;
use function is_string;

abstract class AbstractFixtures extends Fixture
{
    protected Generator $faker;

    private TokenStorageInterface $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->faker = Factory::create('fr_FR');
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
        $ref = new ReflectionClass(get_class($entity));
        $property = $ref->getProperty('publicId');
        $property->setAccessible(true);
        $property->setValue($entity, $value);
    }

    /**
     * Return multiple references
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

        $user = $staff->getClient();

        $user->setCurrentStaff($staff);

        $this->tokenStorage->setToken(new JWTUserToken($user->getRoles(), $user));
    }
}
