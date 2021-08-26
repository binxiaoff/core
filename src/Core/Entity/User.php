<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use KLS\Core\DTO\GoogleRecaptchaResult;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\RoleableTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Validator\Constraints\{Password as AssertPassword};
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "user:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "user:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     collectionOperations={
 *         "get",
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *             "normalization_context": {
 *                 "groups": {
 *                     "user:read",
 *                     "user:item:read",
 *                     "staff:read",
 *                     "company:read",
 *                     "legalDocument:read",
 *                 },
 *                 "openapi_definition_name": "read",
 *             },
 *         },
 *         "put": {"security": "is_granted('edit', object)"},
 *         "patch": {"security": "is_granted('edit', object)"},
 *     },
 * )
 *
 * @Gedmo\Loggable(logEntryClass="KLS\Core\Entity\Versioned\VersionedUser")
 *
 * @ORM\Table(name="core_user", indexes={@ORM\Index(columns={"last_name"})})
 * @ORM\Entity(repositoryClass="KLS\Core\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"email"}, message="Core.Users.email.unique")
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
     * @Groups({"user:read", "user:write"})
     *
     * @ORM\Column(name="last_name", type="string", length=191, nullable=true)
     *
     * @Assert\Length(min=2)
     * @Assert\Regex(pattern="/[^A-zÀ-ÿ\s\-\'']+/i", match=false)
     */
    private ?string $lastName = null;

    /**
     * @Groups({"user:read", "user:write"})
     *
     * @ORM\Column(name="first_name", type="string", length=191, nullable=true)
     *
     * @Assert\Length(min=2)
     * @Assert\Regex(pattern="/[^A-zÀ-ÿ\s\-\'']+/i", match=false)
     */
    private ?string $firstName = null;

    /**
     * @Groups({"user:read", "user:write"})
     *
     * @ORM\Column(name="phone", type="string", length=35, nullable=true)
     *
     * @AssertPhoneNumber(defaultRegion="Users::PHONE_NUMBER_DEFAULT_REGION", type="any")
     */
    private ?string $phone = null;

    /**
     * @Groups({"user:read", "user:create"})
     *
     * @ORM\Column(name="email", type="string", length=191, unique=true)
     *
     * @Assert\NotBlank
     * @Assert\Email
     */
    private string $email;

    /**
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
     * @AssertPassword(message="Core.Users.password.password")
     */
    private ?string $plainPassword = null;

    /**
     * @Groups({"user:read", "user:write"})
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $jobFunction = null;

    /**
     * @var Staff[]|Collection
     *
     * @ORM\OneToMany(targetEntity="KLS\Core\Entity\Staff", mappedBy="user")
     *
     * @Groups({"user:item:read"})
     */
    private Collection $staff;

    /**
     * @Groups({"user:read"})
     *
     * @ORM\OneToOne(targetEntity="UserStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true)
     */
    private ?UserStatus $currentStatus = null;

    /**
     * @var Collection|AcceptationsLegalDocs[]
     *
     * @ORM\OneToMany(targetEntity="KLS\Core\Entity\AcceptationsLegalDocs", mappedBy="acceptedBy")
     */
    private Collection $legalDocumentAcceptations;

    /**
     * Property initialised only in UserNormalizer.
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

    private ?Staff $currentStaff = null;

    private ?GoogleRecaptchaResult $recaptchaResult = null;

    /**
     * @throws Exception
     */
    public function __construct(string $email)
    {
        $this->statuses = new ArrayCollection();
        $this->setCurrentStatus(new UserStatus($this, UserStatus::STATUS_INVITED));

        $this->added                     = new DateTimeImmutable();
        $this->roles[]                   = self::ROLE_USER;
        $this->staff                     = new ArrayCollection();
        $this->email                     = $email;
        $this->legalDocumentAcceptations = new ArrayCollection();
    }

    /**
     * For comparaison (ex. array_unique).
     */
    public function __toString(): string
    {
        return $this->getEmail();
    }

    /**
     * @throws Exception
     */
    public function setLastName(?string $lastName): User
    {
        $this->lastName = $this->normalizeName($lastName);

        $this->onProfileUpdated();

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @throws Exception
     */
    public function setFirstName(?string $firstName): User
    {
        $this->firstName = $this->normalizeName($firstName);

        $this->onProfileUpdated();

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setPhone(?string $phone): User
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setEmail(string $email): User
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @throws Exception
     */
    public function setPassword(?string $password): User
    {
        $this->password = $password;

        $this->onProfileUpdated();

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @return $this
     */
    public function setPlainPassword(string $plainPassword): User
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getJobFunction(): ?string
    {
        return $this->jobFunction;
    }

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

    public function getCompany(): ?Company
    {
        return $this->getCurrentStaff() ? $this->getCurrentStaff()->getCompany() : null;
    }

    public function getInitials(): string
    {
        return \mb_substr($this->getFirstName() ?? '', 0, 1) . \mb_substr($this->getLastName() ?? '', 0, 1);
    }

    public function isGrantedLogin(): bool
    {
        return $this->isInStatus(UserStatus::GRANTED_LOGIN);
    }

    public function isInitializationNeeded(): bool
    {
        return $this->isInStatus([UserStatus::STATUS_INVITED]) || false === $this->isProfileCompleted();
    }

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

    public function getSalt(): string
    {
        return ''; // Since we use the BCrypt password encoder, the salt will be ignored. The auto-generated one is always the best.
    }

    public function getUsername(): string
    {
        return $this->getEmail();
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * @param StatusInterface|UserStatus $currentStatus
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

    public function getCurrentStaff(): ?Staff
    {
        return $this->currentStaff;
    }

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

    public function getCurrentStatus(): UserStatus
    {
        return $this->currentStatus;
    }

    public function getServiceTermsToSign(): ?LegalDocument
    {
        return $this->serviceTermsToSign;
    }

    public function setServiceTermsToSign(LegalDocument $serviceTermsToSign): User
    {
        $this->serviceTermsToSign = $serviceTermsToSign;

        return $this;
    }

    public function getRecaptchaResult(): ?GoogleRecaptchaResult
    {
        return $this->recaptchaResult;
    }

    public function setRecaptchaResult(?GoogleRecaptchaResult $recaptchaResult): User
    {
        $this->recaptchaResult = $recaptchaResult;

        return $this;
    }

    /**
     * @ApiProperty
     *
     * @Groups({"user:read"})
     */
    public function hasPreviousCGUAcceptations(): bool
    {
        // Works because there is only one type of legalDocument
        return 0 < \count($this->legalDocumentAcceptations);
    }

    private function normalizeName(?string $name): ?string
    {
        if (null === $name) {
            return null;
        }

        $name = \mb_strtolower($name);
        $pos  = \mb_strrpos($name, '-');

        if (false === $pos) {
            return \ucwords($name);
        }

        $tabName = \explode('-', $name);
        $newName = '';
        $i       = 0;
        foreach ($tabName as $token) {
            $newName .= (0 === $i ? '' : '-') . \ucwords($token);
            ++$i;
        }

        return $newName;
    }

    private function isInStatus(array $status): bool
    {
        $userStatus = $this->getCurrentStatus();

        return $userStatus && \in_array($userStatus->getStatus(), $status, true);
    }

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
