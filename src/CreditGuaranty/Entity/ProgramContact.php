<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

class ProgramContact
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_program", nullable=false)
     * })
     */
    private Program $program;

    /**
     * @ORM\Column(length=100)
     */
    private string $firstName;

    /**
     * @ORM\Column(length=100)
     */
    private string $lastName;

    /**
     * @ORM\Column(length=100)
     */
    private string $workingScope;

    /**
     * @ORM\Column(length=100)
     *
     * @Assert\Email
     */
    private string $email;

    /**
     * @ORM\Column(length=35)
     *
     * @AssertPhoneNumber(defaultRegion="Users::PHONE_NUMBER_DEFAULT_REGION", type="any")
     */
    private string $telephone;

    /**
     * @param Program $program
     * @param string  $firstName
     * @param string  $lastName
     * @param string  $workingScope
     * @param string  $email
     * @param string  $telephone
     */
    public function __construct(Program $program, string $firstName, string $lastName, string $workingScope, string $email, string $telephone)
    {
        $this->program      = $program;
        $this->firstName    = $firstName;
        $this->lastName     = $lastName;
        $this->workingScope = $workingScope;
        $this->email        = $email;
        $this->telephone    = $telephone;
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
    public function getTelephone(): string
    {
        return $this->telephone;
    }

    /**
     * @param string $telephone
     *
     * @return ProgramContact
     */
    public function setTelephone(string $telephone): ProgramContact
    {
        $this->telephone = $telephone;

        return $this;
    }
}
