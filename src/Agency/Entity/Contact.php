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
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\{BlamableAddedTrait, BlamableUpdatedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "blameable:read",
 *             "timestampable:read",
 *             "agency:contact:read"
 *         }
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "agency:contact:write"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"agency:contact:create", "agency:contact:write"}}
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

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Project", inversedBy="contacts")
     * @ORM\JoinColumn(name="id_project", nullable=false)
     *
     * @Groups({"agency:contact:create"})
     *
     * @Assert\NotBlank
     */
    private Project $project;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"agency:contact:create"})
     *
     * @Assert\Choice(callback="getTypes")
     */
    private string $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"agency:contact:read", "agency:contact:write"})
     *
     * @Assert\NotBlank
     */
    private string $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"agency:contact:read", "agency:contact:write"})
     *
     * @Assert\NotBlank
     */
    private string $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"agency:contact:read", "agency:contact:write"})
     *
     * @Assert\NotBlank
     */
    private string $department;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"agency:contact:read", "agency:contact:write"})
     *
     * @Assert\NotBlank
     */
    private string $occupation;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"agency:contact:read", "agency:contact:write"})
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
     * @Groups({"agency:contact:read", "agency:contact:write"})
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
     * @Groups({"agency:contact:read", "agency:contact:write"})
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
     * @param string  $department
     * @param string  $occupation
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
        string $department,
        string $occupation,
        string $email,
        string $phone,
        bool $referent = false
    ) {
        $this->added       = new DateTimeImmutable();
        $this->addedBy     = $addedBy;
        $this->project     = $project;
        $this->type        = $type;
        $this->firstName   = $firstName;
        $this->lastName    = $lastName;
        $this->department  = $department;
        $this->occupation  = $occupation;
        $this->email       = $email;
        $this->phone       = $phone;
        $this->referent    = $referent;
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
    public function getDepartment(): string
    {
        return $this->department;
    }

    /**
     * @param string $department
     *
     * @return Contact
     */
    public function setDepartment(string $department): Contact
    {
        $this->department = $department;

        return $this;
    }

    /**
     * @return string
     */
    public function getOccupation(): string
    {
        return $this->occupation;
    }

    /**
     * @param string $occupation
     *
     * @return Contact
     */
    public function setOccupation(string $occupation): Contact
    {
        $this->occupation = $occupation;

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
