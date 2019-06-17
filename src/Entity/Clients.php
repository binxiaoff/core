<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\{Exception\UnsatisfiedDependencyException, Uuid};
use Symfony\Component\Security\Core\User\{EquatableInterface, UserInterface};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\{RoleableTrait, TimestampableTrait};
use URLify;

/**
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

    public const TYPE_PERSON                 = 1;
    public const TYPE_LEGAL_ENTITY           = 2;
    public const TYPE_PERSON_FOREIGNER       = 3;
    public const TYPE_LEGAL_ENTITY_FOREIGNER = 4;

    public const TITLE_MISS      = 'Mme';
    public const TITLE_MISTER    = 'M.';
    public const TITLE_UNDEFINED = '';

    public const ROLE_USER           = 'ROLE_USER';
    public const ROLE_LENDER         = 'ROLE_LENDER';
    public const ROLE_BORROWER       = 'ROLE_BORROWER';
    public const ROLE_PARTNER        = 'ROLE_PARTNER';
    public const ROLE_DEBT_COLLECTOR = 'ROLE_DEBT_COLLECTOR';

    public const ROLE_DEFAULT = self::ROLE_USER;

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
     * @ORM\Column(name="preferred_name", type="string", length=191, nullable=true)
     *
     * @Assert\Regex(pattern="/[^A-zÀ-ÿ\s\-\'']+/i", match=false, groups={"lender_person"})
     */
    private $preferredName;

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
     * @var DateTime
     *
     * @ORM\Column(name="date_of_birth", type="date", nullable=true)
     *
     * @Assert\Date(groups={"lender_person"})
     */
    private $dateOfBirth;

    /**
     * @var int
     *
     * @ORM\Column(name="id_birth_country", type="integer", nullable=true)
     *
     * @Assert\NotBlank(groups={"lender_person"})
     * @Assert\Length(min=1, max=3, groups={"lender_person"})
     */
    private $idBirthCountry;

    /**
     * @var string
     *
     * @ORM\Column(name="birth_city", type="string", length=191, nullable=true)
     */
    private $birthCity;

    /**
     * @var string
     *
     * @ORM\Column(name="insee_birth", type="string", length=16, nullable=true)
     *
     * @Assert\Length(min=5, groups={"lender_person"})
     */
    private $inseeBirth;

    /**
     * @var int
     *
     * @ORM\Column(name="id_nationaliy", type="integer", nullable=true)
     *
     * @Assert\NotBlank(groups={"lender_person"})
     * @Assert\Length(min=1, max=3, groups={"lender_person"})
     */
    private $idNationality;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=191, nullable=true)
     *
     * @Assert\Regex(pattern="/[^0-9\s\-\+]/", match=false)
     * @Assert\Length(min=10)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=191, nullable=true)
     *
     * @Assert\Regex(pattern="/[^0-9\s\-\+]/", match=false)
     * @Assert\Length(min=10)
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
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="security_question", type="string", length=191, nullable=true)
     */
    private $securityQuestion;

    /**
     * @var string
     *
     * @ORM\Column(name="security_answer", type="string", length=191, nullable=true)
     */
    private $securityAnswer;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", nullable=true)
     */
    private $type;

    /**
     * @var ClientsStatusHistory
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ClientsStatusHistory")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client_status_history", referencedColumnName="id")
     * })
     */
    private $idClientStatusHistory;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="personal_data_updated", type="datetime", nullable=true)
     */
    private $personalDataUpdated;

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
     * @var Wallet[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Wallet", mappedBy="idClient")
     */
    private $wallets;

    /**
     * @var ClientsAdresses[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ClientsAdresses", mappedBy="idClient")
     */
    private $clientsAddresses;

    /**
     * @var ClientAddress|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ClientAddress")
     * @ORM\JoinColumn(name="id_address", referencedColumnName="id")
     */
    private $idAddress;

    /**
     * @var ClientAddress|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ClientAddress")
     * @ORM\JoinColumn(name="id_postal_address", referencedColumnName="id")
     */
    private $idPostalAddress;

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
     * Clients constructor.
     */
    public function __construct()
    {
        $this->attachments      = new ArrayCollection();
        $this->wallets          = new ArrayCollection();
        $this->clientsAddresses = new ArrayCollection();
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
     * @param string|null $preferredName
     *
     * @return Clients
     */
    public function setPreferredName(?string $preferredName): Clients
    {
        $this->preferredName = '';

        if (false === empty($preferredName)) {
            $this->preferredName = $this->normalizeName($preferredName);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPreferredName(): ?string
    {
        return $this->preferredName;
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
     * @param DateTime|null $dateOfBirth
     *
     * @return Clients
     */
    public function setDateOfBirth(?DateTime $dateOfBirth): Clients
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDateOfBirth(): ?DateTime
    {
        return $this->dateOfBirth;
    }

    /**
     * @param int|null $idBirthCountry
     *
     * @return Clients
     */
    public function setIdBirthCountry(?int $idBirthCountry): Clients
    {
        $this->idBirthCountry = $idBirthCountry;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getIdBirthCountry(): ?int
    {
        return $this->idBirthCountry;
    }

    /**
     * @param string|null $birthCity
     *
     * @return Clients
     */
    public function setBirthCity(?string $birthCity): Clients
    {
        $this->birthCity = $birthCity;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBirthCity(): ?string
    {
        return $this->birthCity;
    }

    /**
     * @param string|null $inseeBirth
     *
     * @return Clients
     */
    public function setInseeBirth(?string $inseeBirth): Clients
    {
        $this->inseeBirth = $inseeBirth;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInseeBirth(): ?string
    {
        return $this->inseeBirth;
    }

    /**
     * @param int|null $idNationality
     *
     * @return Clients
     */
    public function setIdNationality(?int $idNationality): Clients
    {
        $this->idNationality = $idNationality;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getIdNationality(): ?int
    {
        return $this->idNationality;
    }

    /**
     * @param string|null $phone
     *
     * @return Clients
     */
    public function setPhone(?string $phone): Clients
    {
        $this->phone = $this->cleanPhoneNumber($phone);

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
        $this->mobile = $this->cleanPhoneNumber($mobile);

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
     * @param int|null $type
     *
     * @return Clients
     */
    public function setType(?int $type): Clients
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @param ClientsStatusHistory|null $idClientStatusHistory
     *
     * @return Clients
     */
    public function setIdClientStatusHistory(?ClientsStatusHistory $idClientStatusHistory): Clients
    {
        $this->idClientStatusHistory = $idClientStatusHistory;

        return $this;
    }

    /**
     * @return ClientsStatusHistory|null
     */
    public function getIdClientStatusHistory(): ?ClientsStatusHistory
    {
        return $this->idClientStatusHistory;
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
                $this->hash = md5(uniqid());
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
     * @return Wallet[]
     */
    public function getWallets(): iterable
    {
        return $this->wallets;
    }

    /**
     * @return bool
     */
    public function isLender(): bool
    {
        return $this->hasRole(self::ROLE_LENDER);
    }

    /**
     * @return bool
     */
    public function isBorrower(): bool
    {
        return $this->hasRole(self::ROLE_BORROWER);
    }

    /**
     * @return bool
     */
    public function isPartner(): bool
    {
        return $this->hasRole(self::ROLE_PARTNER);
    }

    /**
     * @return bool
     */
    public function isDebtCollector(): bool
    {
        return $this->hasRole(self::ROLE_DEBT_COLLECTOR);
    }

    /**
     * @return bool
     */
    public function isNaturalPerson(): bool
    {
        return in_array($this->type, [self::TYPE_PERSON, self::TYPE_PERSON_FOREIGNER]);
    }

    /**
     * @return ClientsAdresses[]
     */
    public function getClientsAddresses(): iterable
    {
        return $this->clientsAddresses;
    }

    /**
     * @param ClientsAdresses[] $clientsAddresses
     *
     * @return Clients
     */
    public function setClientsAddresses(iterable $clientsAddresses): Clients
    {
        $this->clientsAddresses = $clientsAddresses;

        return $this;
    }

    /**
     * @return ClientAddress|null
     */
    public function getIdAddress(): ?ClientAddress
    {
        if (null !== $this->idAddress && empty($this->idAddress->getId())) {
            $this->idAddress = null;
        }

        return $this->idAddress;
    }

    /**
     * @param ClientAddress|null $idAddress
     *
     * @return Clients
     */
    public function setIdAddress(?ClientAddress $idAddress): Clients
    {
        $this->idAddress = $idAddress;

        return $this;
    }

    /**
     * @return ClientAddress|null
     */
    public function getIdPostalAddress(): ?ClientAddress
    {
        if (null !== $this->idPostalAddress && empty($this->idPostalAddress->getId())) {
            $this->idPostalAddress = null;
        }

        return $this->idPostalAddress;
    }

    /**
     * @param ClientAddress|null $idPostalAddress
     *
     * @return Clients
     */
    public function setIdPostalAddress(?ClientAddress $idPostalAddress): Clients
    {
        $this->idPostalAddress = $idPostalAddress;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getPersonalDataUpdated(): ?DateTime
    {
        return $this->personalDataUpdated;
    }

    /**
     * @param DateTime|null $personalDataUpdated
     *
     * @throws Exception
     *
     * @return Clients
     */
    public function setPersonalDataUpdated(?DateTime $personalDataUpdated = null): Clients
    {
        if (null === $personalDataUpdated) {
            $personalDataUpdated = new DateTime();
        }

        $this->personalDataUpdated = $personalDataUpdated;

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
     * @return bool
     */
    public function isSuspended(): bool
    {
        return $this->isInStatus([ClientsStatus::STATUS_SUSPENDED]);
    }

    /**
     * {@inheritdoc}
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (false === $user instanceof Clients) {
            return false;
        }

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
     * @param string $name
     *
     * @return string
     */
    private function normalizeName(string $name): string
    {
        $name = mb_strtolower($name);

        $pos = mb_strrpos($name, '-');
        if (false === $pos) {
            return ucwords($name);
        }
        $tabName = explode('-', $name);
        $newName = '';
        $i       = 0;
        foreach ($tabName as $name) {
            $newName .= (0 === $i ? '' : '-') . ucwords($name);
            ++$i;
        }

        return $newName;
    }

    /**
     * @param string $number
     *
     * @return string
     */
    private function cleanPhoneNumber(string $number): string
    {
        return str_replace([' ', '.'], '', $number);
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
        return $this->getIdClientStatusHistory() && in_array($this->getIdClientStatusHistory()->getIdStatus()->getId(), $status);
    }
}
