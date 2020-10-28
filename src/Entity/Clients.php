<?php

declare(strict_types=1);

namespace Unilend\Entity;

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
use Unilend\DTO\GoogleRecaptchaResult;
use Unilend\Entity\Interfaces\{StatusInterface, TraceableStatusAwareInterface};
use Unilend\Entity\Traits\{PublicizeIdentityTrait, RoleableTrait, TimestampableTrait};
use Unilend\Validator\Constraints\{Password as AssertPassword};
use URLify;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get"
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *             "normalization_context": {"groups": {"client:read", "client:item:read", "staff:read", "company:read", "legalDocument:read"}}
 *         },
 *         "put": {"security": "is_granted('edit', object)"},
 *         "patch": {"security": "is_granted('edit', object)"}
 *     },
 *     normalizationContext={"groups": {"client:read"}},
 *     denormalizationContext={"groups": {"client:write"}}
 * )
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedClients")
 *
 * @ORM\Table(name="clients", indexes={@ORM\Index(columns={"last_name"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\ClientsRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"email"}, message="Clients.email.unique")
 *
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter", properties={"email": "exact"})
 */
class Clients implements UserInterface, EquatableInterface, TraceableStatusAwareInterface
{
    use TimestampableTrait;
    use RoleableTrait;
    use PublicizeIdentityTrait;

    public const ROLE_USER        = 'ROLE_USER';
    public const ROLE_ADMIN       = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const PHONE_NUMBER_DEFAULT_REGION = 'FR';

    /**
     * @var string
     *
     * @ORM\Column(name="id_language", type="string", length=2)
     */
    private string $idLanguage = 'fr';

    /**
     * @var string|null
     *
     * @Groups({"client:read", "client:write"})
     *
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private ?string $title = null;

    /**
     * @var string|null
     *
     * @Groups({"client:read", "client:write"})
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
     * @Groups({"client:read", "client:write"})
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
     * @ORM\Column(name="slug", type="string", length=191, nullable=true)
     */
    private ?string $slug = null;

    /**
     * @var string|null
     *
     * @Groups({"client:read", "client:write"})
     *
     * @ORM\Column(name="phone", type="string", length=35, nullable=true)
     *
     * @AssertPhoneNumber(defaultRegion="Clients::PHONE_NUMBER_DEFAULT_REGION", type="any")
     */
    private ?string $phone = null;

    /**
     * @var string|null
     *
     * @Groups({"client:read", "client:write"})
     *
     * @ORM\Column(name="mobile", type="string", length=35, nullable=true)
     *
     * @AssertPhoneNumber(defaultRegion="Clients::PHONE_NUMBER_DEFAULT_REGION", type="mobile")
     */
    private ?string $mobile = null;

    /**
     * @var string
     *
     * @Groups({"client:read", "client:create"})
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
     * @Groups({"client:write"})
     *
     * @SerializedName("password")
     *
     * @AssertPassword(message="Clients.password.password")
     */
    private ?string $plainPassword = null;

    /**
     * @var string|null
     *
     * @Groups({"client:read", "client:write"})
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $jobFunction = null;

    /**
     * @var Staff[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Staff", mappedBy="client")
     *
     * @Groups({"client:item:read"})
     */
    private Collection $staff;

    /**
     * @var ClientStatus|null
     *
     * @Groups({"client:read"})
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ClientStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true)
     */
    private ?ClientStatus $currentStatus = null;

    /**
     * Property initialised only in ClientNormalizer
     *
     * @var LegalDocument|null
     *
     * @Groups({"client:item:read"})
     */
    private ?LegalDocument $serviceTermsToSign = null;

    /**
     * @var Collection|ClientStatus[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ClientStatus", mappedBy="client", orphanRemoval=true, cascade={"persist"})
     */
    private Collection $statuses;

    /**
     * @var Staff|null
     */
    private ?Staff $currentStaff = null;

    /**
     * @var GoogleRecaptchaResult|null
     */
    private ?GoogleRecaptchaResult $recaptchaResult;
    /**
     * Clients constructor.
     *
     * @param string $email
     *
     * @throws Exception
     */
    public function __construct(string $email)
    {
        $this->statuses = new ArrayCollection();
        $this->setCurrentStatus(new ClientStatus($this, ClientStatus::STATUS_INVITED));

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
     * @param string $idLanguage
     *
     * @return Clients
     */
    public function setIdLanguage(string $idLanguage): Clients
    {
        $this->idLanguage = $idLanguage;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdLanguage(): string
    {
        return $this->idLanguage;
    }

    /**
     * @param string|null $title
     *
     * @return Clients
     */
    public function setTitle(?string $title): Clients
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $lastName
     *
     * @throws Exception
     *
     * @return Clients
     */
    public function setLastName(?string $lastName): Clients
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
     * @throws Exception
     *
     * @return Clients
     */
    public function setFirstName(?string $firstName): Clients
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
     * @param string|null $slug
     *
     * @return Clients
     */
    public function setSlug(?string $slug): Clients
    {
        $this->slug = URLify::filter($slug);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string|null $phone
     *
     * @return Clients
     */
    public function setPhone(?string $phone): Clients
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
     * @param string|null $mobile
     *
     * @return Clients
     */
    public function setMobile(?string $mobile): Clients
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * @param string $email
     *
     * @return Clients
     */
    public function setEmail(string $email): Clients
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
     * @throws Exception
     *
     * @return Clients
     */
    public function setPassword(?string $password): Clients
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
    public function setPlainPassword(string $plainPassword): Clients
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
     * @return Clients
     */
    public function setJobFunction(?string $jobFunction): Clients
    {
        $this->jobFunction = $jobFunction;

        return $this;
    }

    /**
     * @return Staff[]|Collection
     */
    public function getStaff(): Collection
    {
        return $this->staff;
    }

    /**
     * @param Staff $staff
     */
    public function addStaff(Staff $staff)
    {
        if ($staff->getClient() !== $this) {
            throw new InvalidArgumentException('The staff should concern the client');
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
        return $this->isInStatus(ClientStatus::GRANTED_LOGIN) && $this->getStaff()->exists(
            static function (int $key, Staff $staff) {
                return $staff->isGrantedLogin();
            }
        );
    }

    /**
     * @return bool
     */
    public function isInitializationNeeded(): bool
    {
        return $this->isInStatus([ClientStatus::STATUS_INVITED]) || false === $this->isProfileCompleted();
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
            return false; // The client has been changed to a critical status. He/she is no longer the client that we known as he/she was.
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
     * @param StatusInterface|ClientStatus $currentStatus
     *
     * @return Clients
     */
    public function setCurrentStatus(StatusInterface $currentStatus): Clients
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
     * @return Collection|ClientStatus[]
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    /**
     * @return ClientStatus
     */
    public function getCurrentStatus(): ClientStatus
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
     * @return Clients
     */
    public function setServiceTermsToSign(LegalDocument $serviceTermsToSign): Clients
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
     * @return Clients
     */
    public function setRecaptchaResult(?GoogleRecaptchaResult $recaptchaResult): Clients
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
        $clientStatus = $this->getCurrentStatus();

        return $clientStatus && \in_array($clientStatus->getStatus(), $status, true);
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
        if ($this->isProfileCompleted() && $this->getCurrentStatus()->getStatus() < ClientStatus::STATUS_CREATED) {
            $this->setCurrentStatus(new ClientStatus($this, ClientStatus::STATUS_CREATED));
        }
    }
}
