<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;

trait AddressTrait
{
    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     */
    protected ?string $addressStreet;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     */
    protected ?string $addressPostCode;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     */
    protected ?string $addressCity;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     */
    protected ?string $addressDepartment;

    /**
     * @ORM\ManyToOne(targetEntity=ProgramChoiceOption::class)
     * @ORM\JoinColumn(name="id_address_country")
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     */
    protected ?ProgramChoiceOption $addressCountry = null;

    public function getAddressStreet(): ?string
    {
        return $this->addressStreet;
    }

    public function setAddressStreet(?string $street): self
    {
        $this->addressStreet = $street;

        return $this;
    }

    public function getAddressPostCode(): ?string
    {
        return $this->addressPostCode;
    }

    public function setAddressPostCode(?string $postCode): self
    {
        $this->addressPostCode = $postCode;

        return $this;
    }

    public function getAddressCity(): ?string
    {
        return $this->addressCity;
    }

    public function setAddressCity(?string $city): self
    {
        $this->addressCity = $city;

        return $this;
    }

    public function getAddressDepartment(): ?string
    {
        return $this->addressDepartment;
    }

    public function setAddressDepartment(?string $department): self
    {
        $this->addressDepartment = $department;

        return $this;
    }

    public function getAddressCountry(): ?ProgramChoiceOption
    {
        return $this->addressCountry;
    }

    public function setAddressCountry(?ProgramChoiceOption $country): self
    {
        $this->addressCountry = $country;

        return $this;
    }
}
