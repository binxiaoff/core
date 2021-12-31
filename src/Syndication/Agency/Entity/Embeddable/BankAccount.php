<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class BankAccount
{
    /**
     * @ORM\Column(type="string", nullable=true, length=255)
     *
     * @Assert\Length(max=255)
     *
     * @Serializer\Groups({
     *     "agency:bankAccount:read",
     *     "agency:bankAccount:write",
     * })
     */
    protected ?string $label;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(max=255)
     * @Assert\NotBlank(groups={"bankAccount::completed"})
     *
     * @Serializer\Groups({
     *     "agency:bankAccount:read",
     *     "agency:bankAccount:write",
     * })
     */
    protected ?string $institutionName;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(max=255)
     * @Assert\NotBlank(groups={"bankAccount::completed"})
     *
     * @Serializer\Groups({
     *     "agency:bankAccount:read",
     *     "agency:bankAccount:write",
     * })
     */
    protected ?string $institutionAddress;

    /**
     * @ORM\Column(type="string", length=11, nullable=true)
     *
     * @Assert\Bic
     * @Assert\NotBlank(groups={"bankAccount::completed"})
     *
     * @Serializer\Groups({
     *     "agency:bankAccount:read",
     *     "agency:bankAccount:write",
     * })
     */
    protected ?string $bic;

    /**
     * @ORM\Column(type="string", length=34, nullable=true)
     *
     * @Assert\Iban
     * @Assert\NotBlank(groups={"bankAccount::completed"})
     *
     * @Serializer\Groups({
     *     "agency:bankAccount:read",
     *     "agency:bankAccount:write",
     * })
     */
    protected ?string $iban;

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): BankAccount
    {
        $this->label = $label;

        return $this;
    }

    public function getInstitutionName(): ?string
    {
        return $this->institutionName;
    }

    public function setInstitutionName(?string $institutionName): BankAccount
    {
        $this->institutionName = $institutionName;

        return $this;
    }

    public function getInstitutionAddress(): ?string
    {
        return $this->institutionAddress;
    }

    public function setInstitutionAddress(?string $institutionAddress): BankAccount
    {
        $this->institutionAddress = $institutionAddress;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): BankAccount
    {
        $this->bic = $bic;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): BankAccount
    {
        $this->iban = $iban;

        return $this;
    }

    public function isValid(): bool
    {
        return $this->getInstitutionName() && $this->getInstitutionAddress() && $this->getIban() && $this->getBic();
    }
}
