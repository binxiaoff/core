<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\{BlamableAddedTrait, BlamableUpdatedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "blameable:read",
 *             "timestampable:read",
 *             "contact:read"
 *         }
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "contact:write"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"contact:create", "contact:write"}}
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)",
 *         },
 *     }
 * )
 * @ORM\Table(name="agency_contact")
 * @ORM\Entity
 */
class Contact
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;
    use BlamableUpdatedTrait;
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
     * @Groups({"contact:create"})
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
    private string $office;

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
     * @Assert\Type("bool")
     */
    private bool $referent;

    /**
     * @param Project $project
     * @param string  $type
     * @param Staff   $addedBy
     * @param string  $firstName
     * @param string  $lastName
     * @param string  $direction
     * @param string  $office
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
        string $office,
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
        $this->office    = $office;
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
     *
     * @return Contact
     */
    public function setFirstName(string $firstName): Contact
    {
        $this->firstName = $firstName;

        return $this;
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
     *
     * @return Contact
     */
    public function setLastName(string $lastName): Contact
    {
        $this->lastName = $lastName;

        return $this;
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
     *
     * @return Contact
     */
    public function setDirection(string $direction): Contact
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * @return string
     */
    public function getOffice(): string
    {
        return $this->office;
    }

    /**
     * @param string $office
     *
     * @return Contact
     */
    public function setOffice(string $office): Contact
    {
        $this->office = $office;

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
     * @param string $email
     *
     * @return Contact
     */
    public function setEmail(string $email): Contact
    {
        $this->email = $email;

        return $this;
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
     *
     * @return Contact
     */
    public function setPhone(string $phone): Contact
    {
        $this->phone = $phone;

        return $this;
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
     *
     * @return Contact
     */
    public function setReferent(bool $referent): Contact
    {
        $this->referent = $referent;

        return $this;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public static function getTypes(): array
    {
        return self::getConstants('TYPE_');
    }
}
