<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\{ApiProperty, ApiResource};
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\{Groups};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ApiResource(
 *     normalizationContext={"groups":{"creditGuaranty:programContact:read", "creditGuaranty:program:read", "timestampable:read"}},
 *     denormalizationContext={"groups": {"creditGuaranty:programContact:write"}},
 *      itemOperations={
 *          "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *          },
 *          "patch": {"security": "is_granted('edit', object)"},
 *          "delete": {"security": "is_granted('delete', object)"}
 *      },
 *      collectionOperations={
 *         "post": {"security_post_denormalize": "is_granted('create', object)"}
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *      name="credit_guaranty_program_contact",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"email", "id_program"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"email", "program"}, message="CreditGuaranty.ProgramContact.email.unique")
 */
class ProgramContact
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program", inversedBy="programContacts")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programContact:read", "creditGuaranty:programContact:write"})
     */
    private Program $program;

    /**
     * @ORM\Column(length=100)
     *
     * @Groups({"creditGuaranty:programContact:read", "creditGuaranty:programContact:write"})
     */
    private string $firstName;

    /**
     * @ORM\Column(length=100)
     *
     * @Groups({"creditGuaranty:programContact:read", "creditGuaranty:programContact:write"})
     */
    private string $lastName;

    /**
     * @ORM\Column(length=100)
     *
     * @Groups({"creditGuaranty:programContact:read", "creditGuaranty:programContact:write"})
     */
    private string $workingScope;

    /**
     * @ORM\Column(length=100)
     *
     * @Assert\Email
     *
     * @Groups({"creditGuaranty:programContact:read", "creditGuaranty:programContact:write"})
     */
    private string $email;

    /**
     * @ORM\Column(length=35)
     *
     * @AssertPhoneNumber(defaultRegion="Users::PHONE_NUMBER_DEFAULT_REGION", type="any")
     *
     * @Groups({"creditGuaranty:programContact:read", "creditGuaranty:programContact:write"})
     */
    private string $phone;

    /**
     * @param Program $program
     * @param string  $firstName
     * @param string  $lastName
     * @param string  $workingScope
     * @param string  $email
     * @param string  $phone
     */
    public function __construct(Program $program, string $firstName, string $lastName, string $workingScope, string $email, string $phone)
    {
        $this->program      = $program;
        $this->firstName    = $firstName;
        $this->lastName     = $lastName;
        $this->workingScope = $workingScope;
        $this->email        = $email;
        $this->phone        = $phone;
        $this->added        = new DateTimeImmutable();
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
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
     * @return ProgramContact
     */
    public function setFirstName(string $firstName): ProgramContact
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
     * @return ProgramContact
     */
    public function setLastName(string $lastName): ProgramContact
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getWorkingScope(): string
    {
        return $this->workingScope;
    }

    /**
     * @param string $workingScope
     *
     * @return ProgramContact
     */
    public function setWorkingScope(string $workingScope): ProgramContact
    {
        $this->workingScope = $workingScope;

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
     * @return ProgramContact
     */
    public function setEmail(string $email): ProgramContact
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
     * @return ProgramContact
     */
    public function setPhone(string $phone): ProgramContact
    {
        $this->phone = $phone;

        return $this;
    }
}
