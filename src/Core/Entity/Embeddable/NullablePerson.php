<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Embeddable;

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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): NullablePerson
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): NullablePerson
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getParentUnit(): ?string
    {
        return $this->parentUnit;
    }

    public function setParentUnit(?string $parentUnit): NullablePerson
    {
        $this->parentUnit = $parentUnit;

        return $this;
    }

    public function getOccupation(): ?string
    {
        return $this->occupation;
    }

    public function setOccupation(?string $occupation): NullablePerson
    {
        $this->occupation = $occupation;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): NullablePerson
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): NullablePerson
    {
        $this->phone = $phone;

        return $this;
    }

    public function isValid(): bool
    {
        return $this->firstName && $this->lastName && $this->parentUnit && $this->occupation && $this->email && $this->phone;
    }
}
