<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use InvalidArgumentException;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\{EquatableInterface, UserInterface};
use Symfony\Component\Serializer\Annotation\{Groups, SerializedName};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\DTO\GoogleRecaptchaResult;
use Unilend\Core\Entity\Interfaces\{StatusInterface, TraceableStatusAwareInterface};
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, RoleableTrait, TimestampableTrait};
use Unilend\Core\Validator\Constraints\{Password as AssertPassword};

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get"
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *             "normalization_context": {"groups": {"user:read", "user:item:read", "staff:read", "company:read", "legalDocument:read"}}
 *         },
 *         "put": {"security": "is_granted('edit', object)"},
 *         "patch": {"security": "is_granted('edit', object)"}
 *     },
 *     normalizationContext={"groups": {"user:read"}},
 *     denormalizationContext={"groups": {"user:write"}}
 * )
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Core\Entity\Versioned\VersionedUser")
 *
 * @ORM\Table(name="core_user", indexes={@ORM\Index(columns={"last_name"})})
 * @ORM\Entity(repositoryClass="Unilend\Core\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"email"}, message="Users.email.unique")
 *
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter", properties={"email": "exact"})
 */
class User implements UserInterface, EquatableInterface, TraceableStatusAwareInterface
{
    use TimestampableTrait;
    use RoleableTrait;
    use PublicizeIdentityTrait;

    public const ROLE_USER        = 'ROLE_USER';
    public const ROLE_ADMIN       = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const PHONE_NUMBER_DEFAULT_REGION = 'FR';

    /**
     * @var string|null
     *
     * @Groups({"user:read", "user:write"})
     *
     * @ORM\Column(name="last_name", type="string", length=191, nullable=true)
     *
     * @Assert\Length(min=2)
     * @Assert\Regex(pattern="/[^A-zÀ-ÿ\s\-\'']+/i", match=false)
     */
    private ?string $lastName = null;

    /**
     * @var string|null
     *
     * @Groups({"user:read", "user:write"})
     *
     * @ORM\Column(name="first_name", type="string", length=191, nullable=true)
     *
     * @Assert\Length(min=2)
     * @Assert\Regex(pattern="/[^A-zÀ-ÿ\s\-\'']+/i", match=false)
     */
    private ?string $firstName = null;

    /**
     * @var string|null
     *
     * @Groups({"user:read", "user:write"})
     *
     * @ORM\Column(name="phone", type="string", length=35, nullable=true)
     *
     * @AssertPhoneNumber(defaultRegion="Users::PHONE_NUMBER_DEFAULT_REGION", type="any")
     */
    private ?string $phone = null;

    /**
     * @var string
     *
     * @Groups({"user:read", "user:create"})
     *
     * @ORM\Column(name="email", type="string", length=191, unique=true)
     *
     * @Assert\NotBlank
     * @Assert\Email
     */
    private string $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="string", length=191, nullable=true)
     *
     * @Gedmo\Versioned
     */
    private ?string $password = null;

    /**
     * @Groups({"user:write"})
     *
     * @SerializedName("password")
     *
     * @AssertPassword(message="Users.password.password")
     */
    private ?string $plainPassword = null;

    /**
     * @var string|null
     *
     * @Groups({"user:read", "user:write"})
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $jobFunction = null;

    /**
     * @var Staff[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\Staff", mappedBy="user")
     *
     * @Groups({"user:item:read"})
     */
    private Collection $staff;

    /**
     * @var UserStatus|null
     *
     * @Groups({"user:read"})
     *
     * @ORM\OneToOne(targetEntity="UserStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true)
     */
    private ?UserStatus $currentStatus = null;

    /**
     * Property initialised only in UserNormalizer
     *
     * @var LegalDocument|null
     *
     * @Groups({"user:item:read"})
     */
    private ?LegalDocument $serviceTermsToSign = null;

    /**
     * @var Collection|UserStatus[]
     *
     * @ORM\OneToMany(targetEntity="UserStatus", mappedBy="user", orphanRemoval=true, cascade={"persist"})
     */
    private Collection $statuses;

    /**
     * @var Staff|null
     */
    private ?Staff $currentStaff = null;

    /**
     * @var GoogleRecaptchaResult|null
     */
    private ?GoogleRecaptchaResult $recaptchaResult = null;

    /**
     * Users constructor.
     *
     * @param string $email
     *
     * @throws Exception
     */
    public function __construct(string $email)
    {
        $this->statuses = new ArrayCollection();
        $this->setCurrentStatus(new UserStatus($this, UserStatus::STATUS_INVITED));

        $this->added   = new DateTimeImmutable();
        $this->roles[] = self::ROLE_USER;
        $this->staff   = new ArrayCollection();
        $this->email   = $email;
    }

    /**
     * For comparaison (ex. array_unique).
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getEmail();
    }

    /**
     * @param string|null $lastName
     *
     * @return User

     **@throws Exception
     *
     */
    public function setLastName(?string $lastName): User
    {
        $this->lastName = $this->normalizeName($lastName);

        $this->onProfileUpdated();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $firstName
     *
     * @return User

     **@throws Exception
     *
     */
    public function setFirstName(?string $firstName): User
    {
        $this->firstName = $this->normalizeName($firstName);

        $this->onProfileUpdated();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $phone
     *
     * @return User
     */
    public function setPhone(?string $phone): User
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function setEmail(string $email): User
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string|null $password
     *
     * @return User

     **@throws Exception
     *
     */
    public function setPassword(?string $password): User
    {
        $this->password = $password;

        $this->onProfileUpdated();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     *
     * @return $this
     */
    public function setPlainPassword(string $plainPassword): User
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getJobFunction(): ?string
    {
        return $this->jobFunction;
    }

    /**
     * @param string|null $jobFunction
     *
     * @return User
     */
    public function setJobFunction(?string $jobFunction): User
    {
        $this->jobFunction = $jobFunction;

        return $this;
    }

    /**
     * @return Staff[]|iterable
     */
    public function getStaff(): iterable
    {
        return $this->staff;
    }

    /**
     * @param Staff $staff
     */
    public function addStaff(Staff $staff)
    {
        if ($staff->getUser() !== $this) {
            throw new InvalidArgumentException('The staff should concern the user');
        }

        $this->staff->add($staff);
    }

    /**
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        return $this->getCurrentStaff() ? $this->getCurrentStaff()->getCompany() : null;
    }

    /**
     * @return string
     */
    public function getInitials(): string
    {
        return mb_substr($this->getFirstName() ?? '', 0, 1) . mb_substr($this->getLastName() ?? '', 0, 1);
    }

    /**
     * @return bool
     */
    public function isGrantedLogin(): bool
    {
        if (false === $this->isInStatus(UserStatus::GRANTED_LOGIN)) {
            return false;
        }

        foreach ($this->getStaff() as $staff) {
            if ($staff->isGrantedLogin()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isInitializationNeeded(): bool
    {
        return $this->isInStatus([UserStatus::STATUS_INVITED]) || false === $this->isProfileCompleted();
    }

    /**
     * {@inheritdoc}
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (false === $user instanceof self) {
            return false;
        }

        if ($this->getPublicId() !== $user->getPublicId()) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername() && $this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if (false === $user->isGrantedLogin()) {
            return false; // The user has been changed to a critical status. He/she is no longer the user that we known as he/she was.
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt(): string
    {
        return ''; // Since we use the BCrypt password encoder, the salt will be ignored. The auto-generated one is always the best.
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->getEmail();
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * @param StatusInterface|UserStatus $currentStatus
     *
     * @return User
     */
    public function setCurrentStatus(StatusInterface $currentStatus): User
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    /**
     * @return array|string[]
     */
    public static function getAvailableRoles(): array
    {
        return self::getConstants('ROLE_');
    }

    /**
     * @return Staff|null
     */
    public function getCurrentStaff(): ?Staff
    {
        return $this->currentStaff;
    }

    /**
     * @param Staff $currentStaff
     */
    public function setCurrentStaff(Staff $currentStaff): void
    {
        $this->currentStaff = $currentStaff;
    }

    /**
     * @return Collection|UserStatus[]
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    /**
     * @return UserStatus
     */
    public function getCurrentStatus(): UserStatus
    {
        return $this->currentStatus;
    }

    /**
     * @return LegalDocument|null
     */
    public function getServiceTermsToSign(): ?LegalDocument
    {
        return $this->serviceTermsToSign;
    }

    /**
     * @param LegalDocument $serviceTermsToSign
     *
     * @return User
     */
    public function setServiceTermsToSign(LegalDocument $serviceTermsToSign): User
    {
        $this->serviceTermsToSign = $serviceTermsToSign;

        return $this;
    }

    /**
     * @return GoogleRecaptchaResult|null
     */
    public function getRecaptchaResult(): ?GoogleRecaptchaResult
    {
        return $this->recaptchaResult;
    }

    /**
     * @param GoogleRecaptchaResult|null $recaptchaResult
     *
     * @return User
     */
    public function setRecaptchaResult(?GoogleRecaptchaResult $recaptchaResult): User
    {
        $this->recaptchaResult = $recaptchaResult;

        return $this;
    }

    /**
     * @param string|null $name
     *
     * @return string|null
     */
    private function normalizeName(?string $name): ?string
    {
        if (null === $name) {
            return null;
        }

        $name = mb_strtolower($name);
        $pos  = mb_strrpos($name, '-');

        if (false === $pos) {
            return ucwords($name);
        }

        $tabName = explode('-', $name);
        $newName = '';
        $i       = 0;
        foreach ($tabName as $token) {
            $newName .= (0 === $i ? '' : '-') . ucwords($token);
            ++$i;
        }

        return $newName;
    }

    /**
     * @param array $status
     *
     * @return bool
     */
    private function isInStatus(array $status): bool
    {
        $userStatus = $this->getCurrentStatus();

        return $userStatus && \in_array($userStatus->getStatus(), $status, true);
    }

    /**
     * @return bool
     */
    private function isProfileCompleted(): bool
    {
        return $this->getFirstName() && $this->getLastName() && $this->getPassword();
    }

    /**
     * Set user status to created when profile (firstName, lastName and password) is complete.
     *
     * @throws Exception
     */
    private function onProfileUpdated(): void
    {
        if ($this->isProfileCompleted() && $this->getCurrentStatus()->getStatus() < UserStatus::STATUS_CREATED) {
            $this->setCurrentStatus(new UserStatus($this, UserStatus::STATUS_CREATED));
        }
    }
}
