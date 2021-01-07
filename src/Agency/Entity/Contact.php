<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ORM\Table(name="agency_contact")
 * @ORM\Entity
 */
class Contact
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;
    use ConstantsAwareTrait;

    public const TYPE_BACK_OFFICE = 'back_office';
    public const TYPE_LEGAL       = 'legal';
    public const TYPE_VOTER       = 'voter';

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Project", inversedBy="contacts")
     * @ORM\JoinColumn(name="id_project", nullable=false)
     *
     * @Groups({"contact:create"})
     *
     * @Assert\NotBlank
     */
    private Project $project;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"contact:read", "contact:write"})
     *
     * @Assert\Choice(callback="getTypes")
     */
    private string $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"contact:read", "contact:write"})
     *
     * @Assert\NotBlank
     */
    private string $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"contact:read", "contact:write"})
     *
     * @Assert\NotBlank
     */
    private string $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"contact:read", "contact:write"})
     *
     * @Assert\NotBlank
     */
    private string $direction;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"contact:read", "contact:write"})
     *
     * @Assert\NotBlank
     */
    private string $function;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"contact:read", "contact:write"})
     *
     * @Assert\NotBlank
     * @Assert\Email
     */
    private string $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"contact:read", "contact:write"})
     *
     * @Assert\NotBlank
     *
     * @AssertPhoneNumber
     */
    private string $phone;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"contact:read", "contact:write"})
     *
     * @Assert\NotBlank
     */
    private bool $referent;

    /**
     * @param Project $project
     * @param string  $type
     * @param Staff   $addedBy
     * @param string  $firstName
     * @param string  $lastName
     * @param string  $direction
     * @param string  $function
     * @param string  $email
     * @param string  $phone
     * @param bool    $referent
     *
     * @throws Exception
     */
    public function __construct(
        Project $project,
        string $type,
        Staff $addedBy,
        string $firstName,
        string $lastName,
        string $direction,
        string $function,
        string $email,
        string $phone,
        bool $referent = false
    ) {
        $this->added     = new DateTimeImmutable();
        $this->addedBy   = $addedBy;
        $this->project   = $project;
        $this->type      = $type;
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->direction = $direction;
        $this->function  = $function;
        $this->email     = $email;
        $this->phone     = $phone;
        $this->referent  = $referent;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @param string $direction
     */
    public function setDirection(string $direction): void
    {
        $this->direction = $direction;
    }

    /**
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * @param string $function
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return bool
     */
    public function isReferent(): bool
    {
        return $this->referent;
    }

    /**
     * @param bool $referent
     */
    public function setReferent(bool $referent): void
    {
        $this->referent = $referent;
    }

    /**
     * @return array
     */
    public static function getTypes(): array
    {
        return self::getConstants('TYPE_');
    }
}
