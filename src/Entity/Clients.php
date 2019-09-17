<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use libphonenumber\PhoneNumber;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Ramsey\Uuid\{Exception\UnsatisfiedDependencyException, Uuid};
use Symfony\Component\Security\Core\User\{EquatableInterface, UserInterface};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\{RoleableTrait, StatusTraceableTrait, TimestampableTrait};
use URLify;

/**
 * @method ClientsStatus getCurrentStatus()
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedClients")
 *
 * @ORM\Table(name="clients", indexes={
 *     @ORM\Index(columns={"hash"}),
 *     @ORM\Index(columns={"email"}),
 *     @ORM\Index(columns={"last_name"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Repository\ClientsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Clients implements UserInterface, EquatableInterface
{
    use TimestampableTrait;
    use RoleableTrait;
    use StatusTraceableTrait {
        setCurrentStatus as baseStatusSetter;
    }

    public const ROLE_USER        = 'ROLE_USER';
    public const ROLE_ADMIN       = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    private const DEFAULT_ROLE = self::ROLE_USER;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=191)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="id_language", type="string", length=2)
     */
    private $idLanguage = 'fr';

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=191, nullable=true)
     *
     * @Assert\NotBlank
     * @Assert\Length(min=2)
     * @Assert\Regex(pattern="/[^A-zÀ-ÿ\s\-\'']+/i", match=false)
     */
    private $lastName;

    /**
     * @var string
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
     * @ORM\Column(name="phone", type="phone_number", nullable=true)
     *
     * @AssertPhoneNumber(defaultRegion="FR", type="any")
     */
    private $phone;

    /**
     * @var PhoneNumber
     *
     * @ORM\Column(name="mobile", type="phone_number", nullable=true)
     *
     * @AssertPhoneNumber(defaultRegion="FR", type="mobile")
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=191, nullable=true, unique=true)
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
     * @var string
     *
     * @ORM\Column(name="security_question", type="string", length=191, nullable=true)
     *
     * @Gedmo\Versioned
     */
    private $securityQuestion;

    /**
     * @var string
     *
     * @ORM\Column(name="security_answer", type="string", length=191, nullable=true)
     *
     * @Gedmo\Versioned
     */
    private $securityAnswer;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $jobFunction;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    private $lastLogin;

    /**
     * @var int
     *
     * @ORM\Column(name="id_client", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idClient;

    /**
     * @var Attachment[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Attachment", mappedBy="clientOwner")
     */
    private $attachments;

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
     * @var ClientsStatus
     *
     * @ORM\OneToOne(targetEntity="ClientsStatus")
     * @ORM\JoinColumn(name="id_current_status", unique=true)
     */
    private $currentStatus;

    /**
     * @var ArrayCollection|ClientsStatus
     *
     * @ORM\OneToMany(targetEntity="ClientsStatus", mappedBy="clients", orphanRemoval=true, cascade={"persist"})
     */
    private $statuses;

    /**
     * Clients constructor.
     */
    public function __construct()
    {
        $this->attachments = new ArrayCollection();
        $this->statuses    = new ArrayCollection();
        $this->setCurrentStatus(ClientsStatus::STATUS_CREATED);
    }

    /**
     * @param string $hash
     *
     * @return Clients
     */
    public function setHash(string $hash): Clients
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
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
    public function setEmail(?string $email): Clients
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
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
     * @param string|null $securityQuestion
     *
     * @return Clients
     */
    public function setSecurityQuestion(?string $securityQuestion): Clients
    {
        $this->securityQuestion = $securityQuestion;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSecurityQuestion(): ?string
    {
        return $this->securityQuestion;
    }

    /**
     * @param string|null $securityAnswer
     *
     * @return Clients
     */
    public function setSecurityAnswer(?string $securityAnswer): Clients
    {
        $this->securityAnswer = md5($securityAnswer);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSecurityAnswer(): ?string
    {
        return $this->securityAnswer;
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
     * @param DateTimeInterface|null $lastLogin
     *
     * @return Clients
     */
    public function setLastLogin(?DateTimeInterface $lastLogin): Clients
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastLogin(): ?DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @return int
     */
    public function getIdClient(): int
    {
        return $this->idClient;
    }

    /**
     * @ORM\PrePersist
     */
    public function setHashValue(): void
    {
        if (null === $this->hash) {
            try {
                $this->hash = $this->generateHash();
            } catch (UnsatisfiedDependencyException $exception) {
                $this->hash = md5(uniqid('', false));
            }
        }
    }

    /**
     * @param bool $includeArchived
     *
     * @return Attachment[]
     */
    public function getAttachments($includeArchived = false): iterable
    {
        if (false === $includeArchived) {
            $attachments = [];
            foreach ($this->attachments as $attachment) {
                if (null === $attachment->getArchived()) {
                    $attachments[] = $attachment;
                }
            }

            return $attachments;
        }

        return $this->attachments;
    }

    /**
     * @return Staff|null
     */
    public function getStaff(): ?Staff
    {
        return $this->staff;
    }

    /**
     * @return Companies|null
     */
    public function getCompany(): ?Companies
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
        return $this->isInStatus(ClientsStatus::GRANTED_LOGIN);
    }

    /**
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->isInStatus([ClientsStatus::STATUS_VALIDATED]);
    }

    /**
     * {@inheritdoc}
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (false === $user instanceof Clients) {
            return false;
        }

        /** @var Clients $user */
        if ($this->getHash() !== $user->getHash()) {
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
        // Not yet Implemented
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
        $clientStatus = new ClientsStatus($this, $status, $content);

        return $this->baseStatusSetter($clientStatus);
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
     * @throws Exception
     *
     * @return string
     */
    private function generateHash(): string
    {
        $uuid4 = Uuid::uuid4();

        return $uuid4->toString();
    }

    /**
     * @param array $status
     *
     * @return bool
     */
    private function isInStatus(array $status): bool
    {
        $clientsStatus = $this->getCurrentStatus();

        return $clientsStatus && in_array($clientsStatus->getStatus(), $status, true);
    }
}
