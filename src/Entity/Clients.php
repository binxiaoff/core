<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use libphonenumber\PhoneNumber;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\{EquatableInterface, UserInterface};
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\{PublicizeIdentityTrait, RoleableTrait, TimestampableTrait, TraceableStatusTrait};
use Unilend\Validator\Constraints\Password as AssertPassword;
use URLify;

/**
 * @ApiResource(
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object)"},
 *         "put": {"security": "is_granted('edit', object)"},
 *         "patch": {"security": "is_granted('edit', object)"}
 *     },
 *     normalizationContext={"groups": {"client:read"}},
 *     denormalizationContext={"groups": {"client:write"}}
 * )
 *
 * @method ClientStatus getCurrentStatus()
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedClients")
 *
 * @ORM\Table(name="clients", indexes={
 *     @ORM\Index(columns={"public_id"}),
 *     @ORM\Index(columns={"email"}),
 *     @ORM\Index(columns={"last_name"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Repository\ClientsRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"email"}, message="Clients.email.unique")
 */
class Clients implements UserInterface, EquatableInterface
{
    use TimestampableTrait;
    use RoleableTrait;
    use TraceableStatusTrait {
        setCurrentStatus as baseStatusSetter;
    }
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
    private $idLanguage = 'fr';

    /**
     * @var string
     *
     * @Groups({"client:read", "client:write"})
     *
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @Groups({"client:read", "client:write"})
     *
     * @ORM\Column(name="last_name", type="string", length=191, nullable=true)
     *
     * @Assert\Length(min=2)
     * @Assert\Regex(pattern="/[^A-zÀ-ÿ\s\-\'']+/i", match=false)
     */
    private $lastName;

    /**
     * @var string
     *
     * @Groups({"client:read", "client:write"})
     *
     * @ORM\Column(name="first_name", type="string", length=191, nullable=true)
     *
     * @Assert\Length(min=2)
     * @Assert\Regex(pattern="/[^A-zÀ-ÿ\s\-\'']+/i", match=false)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=191, nullable=true)
     */
    private $slug;

    /**
     * @var PhoneNumber
     *
     * @Groups({"client:read", "client:write"})
     *
     * @ORM\Column(name="phone", type="phone_number", nullable=true)
     *
     * @AssertPhoneNumber(defaultRegion="Clients::PHONE_NUMBER_DEFAULT_REGION", type="any")
     */
    private $phone;

    /**
     * @var PhoneNumber
     *
     * @Groups({"client:read", "client:write"})
     *
     * @ORM\Column(name="mobile", type="phone_number", nullable=true)
     *
     * @AssertPhoneNumber(defaultRegion="Clients::PHONE_NUMBER_DEFAULT_REGION", type="mobile")
     */
    private $mobile;

    /**
     * @var string
     *
     * @Groups({"client:read", "client:create"})
     *
     * @ORM\Column(name="email", type="string", length=191, nullable=false, unique=true)
     *
     * @Assert\NotBlank
     * @Assert\Email
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=191, nullable=true)
     *
     * @Gedmo\Versioned
     */
    private $password;

    /**
     * @Groups({"client:write"})
     *
     * @SerializedName("password")
     *
     * @AssertPassword
     */
    private $plainPassword;

    /**
     * @var string
     *
     * @Groups({"client:read", "client:write"})
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $jobFunction;

    /**
     * @var Staff|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\Staff", mappedBy="client")
     */
    private $staff;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var ClientStatus
     *
     * @Groups({"client:read"})
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ClientStatus")
     * @ORM\JoinColumn(name="id_current_status", unique=true)
     */
    private $currentStatus;

    /**
     * @var ArrayCollection|ClientStatus[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ClientStatus", mappedBy="client", orphanRemoval=true, cascade={"persist"})
     */
    private $statuses;

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
        $this->setCurrentStatus(ClientStatus::STATUS_INVITED);

        $this->added   = new DateTimeImmutable();
        $this->roles[] = self::ROLE_USER;
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
     * @param PhoneNumber|null $phone
     *
     * @return Clients
     */
    public function setPhone(?PhoneNumber $phone): Clients
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return PhoneNumber|null
     */
    public function getPhone(): ?PhoneNumber
    {
        return $this->phone;
    }

    /**
     * @param PhoneNumber|null $mobile
     *
     * @return Clients
     */
    public function setMobile(?PhoneNumber $mobile): Clients
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * @return PhoneNumber|null
     */
    public function getMobile(): ?PhoneNumber
    {
        return $this->mobile;
    }

    /**
     * @param string|null $email
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
     * @return Staff|null
     */
    public function getStaff(): ?Staff
    {
        return $this->staff;
    }

    /**
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        $company = null;

        if ($this->getStaff()) {
            $company = $this->getStaff()->getCompany();
        }

        return $company;
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
        return $this->isInStatus(ClientStatus::GRANTED_LOGIN) && null !== $this->getCompany() && $this->getCompany()->hasSigned();
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
     * @param int         $status
     * @param string|null $content
     *
     * @throws Exception
     *
     * @return Clients
     */
    public function setCurrentStatus(int $status, ?string $content = null): self
    {
        $clientStatus = new ClientStatus($this, $status, $content);

        return $this->baseStatusSetter($clientStatus);
    }

    /**
     * @return array|string[]
     */
    public static function getAvailableRoles(): array
    {
        return self::getConstants('ROLE_');
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

        return $clientStatus && in_array($clientStatus->getStatus(), $status, true);
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
     */
    private function onProfileUpdated(): void
    {
        if ($this->isProfileCompleted() && $this->getCurrentStatus()->getStatus() < ClientStatus::STATUS_CREATED) {
            $this->setCurrentStatus(ClientStatus::STATUS_CREATED);
        }
    }
}
