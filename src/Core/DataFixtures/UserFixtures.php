<?php

declare(strict_types=1);

namespace Unilend\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Exception;
use ReflectionException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Traits\ConstantsAwareTrait;

class UserFixtures extends AbstractFixtures
{
    use ConstantsAwareTrait;

    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $passwordEncoder;

    /**
     * @param TokenStorageInterface        $tokenStorage
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(TokenStorageInterface $tokenStorage, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct($tokenStorage);
        $this->passwordEncoder = $passwordEncoder;
    }

    public const ADMIN = 'a';
    public const PARTICIPANT = 'b';
    public const AUDITOR = 'c';
    public const ACCOUNTANT = 'accountant';
    public const OPERATOR = 'operator';
    public const MANAGER = 'manager';
    public const UNITIALIZED = 'unitialized';
    public const NO_STAFF = 'no_staff';
    public const INACTIVE = 'inactive';
    public const EXTBANK_INVITED = 'extbank_invited';
    public const EXTBANK_INITIALIZED = 'extbank_initialized';

    private const INITIALIZED_USERS = [
        self::ADMIN,
        self::PARTICIPANT,
        self::AUDITOR,
        self::ACCOUNTANT,
        self::OPERATOR,
        self::MANAGER,
        self::EXTBANK_INITIALIZED,
        self::NO_STAFF,
        self::INACTIVE,
    ];

    /**
     * @param ObjectManager $manager
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $users = array_filter(static::getConstants(), '\is_string');
        $users = array_flip($users);

        foreach (array_keys($users) as $value) {
            $user = new User($value . '@x.xx');
            $this->forcePublicId($user, $value);
            $manager->persist($user);
            $this->addReference($value, $user);
            $users[$value] = $user;
        }

        $manager->flush();

        foreach (static::INITIALIZED_USERS as $value) {
            $user = $users[$value];
            $this->initialize($user);
            $manager->persist($user);
        }

        $manager->flush();
    }

    /**
     * @param User $user
     *
     * @throws Exception
     */
    public function initialize(User $user): void
    {
         $user->setLastName($this->faker->lastName);
         $user->setFirstName($this->faker->firstName);
         $user->setPhone('+33600000000');
         $user->setJobFunction('Job function');
         $user->setPlainPassword('0000');
         $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPlainPassword()));
    }

    /**
     * @param string $username
     *
     * @return string
     */
    private function getEmailDomain(string $username): string
    {
        return false !== strpos($username, 'extbank') ? 'extbank.com' : 'ca-lendingservices.com';
    }
}
