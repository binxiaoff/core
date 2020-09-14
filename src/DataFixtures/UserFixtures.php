<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Exception;
use ReflectionException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Unilend\Entity\Clients;
use Unilend\Traits\ConstantsAwareTrait;

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

    public const ADMIN = 'admin';
    public const PARTICIPANT = 'participant';
    public const AUDITOR = 'auditor';
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
        $users = array_filter(static::getConstants(), 'is_string');
        $users = array_flip($users);

        foreach (array_keys($users) as $value) {
            $user = new Clients($value . '@' . $this->getEmailDomain($value));
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
     * @param Clients $user
     *
     * @throws Exception
     */
    public function initialize(Clients $user): void
    {
         $user->setTitle($this->faker->company);
         $user->setLastName($this->faker->lastName);
         $user->setFirstName($this->faker->firstName);
         $user->setPhone('+33600000000');
         $user->setMobile('+33600000000');
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
