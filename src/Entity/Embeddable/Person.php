<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class Person
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     *
     * @Groups({
     *     "person:read",
     *     "person:write"
     * })
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     *
     * @Groups({
     *     "person:read",
     *     "person:write"
     * })
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     *
     * @Groups({
     *     "person:read",
     *     "person:write"
     * })
     */
    private $parentUnit;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     *
     * @Groups({
     *     "person:read",
     *     "person:write"
     * })
     */
    private $occupation;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank
     * @Assert\Email
     * @Assert\Length(max=255)
     *
     * @Groups({
     *     "person:read",
     *     "person:write"
     * })
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=35)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=35)
     *
     * @AssertPhoneNumber
     *
     * @Groups({
     *     "person:read",
     *     "person:write"
     * })
     */
    private $phone;

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
     * @return Person
     */
    public function setFirstName(string $firstName): Person
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
     * @return Person
     */
    public function setLastName(string $lastName): Person
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getParentUnit(): string
    {
        return $this->parentUnit;
    }

    /**
     * @param string $parentUnit
     *
     * @return Person
     */
    public function setParentUnit(string $parentUnit): Person
    {
        $this->parentUnit = $parentUnit;

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
     * @return Person
     */
    public function setOccupation(string $occupation): Person
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
     * @return Person
     */
    public function setEmail(string $email): Person
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
     * @return Person
     */
    public function setPhone(string $phone): Person
    {
        $this->phone = $phone;

        return $this;
    }
}
