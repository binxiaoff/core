<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class Address
{
    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     */
    private ?string $roadName;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     */
    private ?string $roadNumber;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     */
    private ?string $city;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     */
    private ?string $postCode;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     */
    private ?string $country;

    public function getRoadName(): ?string
    {
        return $this->roadName;
    }

    public function setRoadName(?string $roadName): Address
    {
        $this->roadName = $roadName;

        return $this;
    }

    public function getRoadNumber(): ?string
    {
        return $this->roadNumber;
    }

    public function setRoadNumber(?string $roadNumber): Address
    {
        $this->roadNumber = $roadNumber;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): Address
    {
        $this->city = $city;

        return $this;
    }

    public function getPostCode(): ?string
    {
        return $this->postCode;
    }

    public function setPostCode(?string $postCode): Address
    {
        $this->postCode = $postCode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): Address
    {
        $this->country = $country;

        return $this;
    }
}
