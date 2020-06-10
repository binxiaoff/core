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
class NullablePerson
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255)
     *
     * @Groups({
     *     "nullablePerson:read",
     *     "nullablePerson:write"
     * })
     */
    private $firstName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255)
     *
     * @Groups({
     *     "nullablePerson:read",
     *     "nullablePerson:write"
     * })
     */
    private $lastName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255)
     *
     * @Groups({
     *     "nullablePerson:read",
     *     "nullablePerson:write"
     * })
     */
    private $parentUnit;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255)
     *
     * @Groups({
     *     "nullablePerson:read",
     *     "nullablePerson:write"
     * })
     */
    private $occupation;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Email
     * @Assert\Length(max=255)
     *
     * @Groups({
     *     "nullablePerson:read",
     *     "nullablePerson:write"
     * })
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=35, nullable=true)
     *
     * @Assert\Length(max=35)
     *
     * @AssertPhoneNumber
     *
     * @Groups({
     *     "nullablePerson:read",
     *     "nullablePerson:write"
     * })
     */
    private $phone;

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     *
     * @return NullablePerson
     */
    public function setFirstName(?string $firstName): NullablePerson
    {
        $this->firstName = $firstName;

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
     * @param string|null $lastName
     *
     * @return NullablePerson
     */
    public function setLastName(?string $lastName): NullablePerson
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getParentUnit(): ?string
    {
        return $this->parentUnit;
    }

    /**
     * @param string|null $parentUnit
     *
     * @return NullablePerson
     */
    public function setParentUnit(?string $parentUnit): NullablePerson
    {
        $this->parentUnit = $parentUnit;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOccupation(): ?string
    {
        return $this->occupation;
    }

    /**
     * @param string|null $occupation
     *
     * @return NullablePerson
     */
    public function setOccupation(?string $occupation): NullablePerson
    {
        $this->occupation = $occupation;

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
     * @param string|null $email
     *
     * @return NullablePerson
     */
    public function setEmail(?string $email): NullablePerson
    {
        $this->email = $email;

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
     * @param string|null $phone
     *
     * @return NullablePerson
     */
    public function setPhone(?string $phone): NullablePerson
    {
        $this->phone = $phone;

        return $this;
    }
}
