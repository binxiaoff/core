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
    protected ?string $name;

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
    protected ?string $institution;

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
    protected ?string $bankAddress;

    /**
     * @ORM\Column(type="string", length=11, nullable=true)
     *
     * @Assert\Length(min=11, max=11)
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
     * @Assert\Length(min=34, max=34)
     * @Assert\Iban
     * @Assert\NotBlank(groups={"bankAccount::completed"})
     *
     * @Serializer\Groups({
     *     "agency:bankAccount:read",
     *     "agency:bankAccount:write",
     * })
     */
    protected ?string $iban;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): BankAccount
    {
        $this->name = $name;

        return $this;
    }

    public function getInstitution(): ?string
    {
        return $this->institution;
    }

    public function setInstitution(?string $institution): BankAccount
    {
        $this->institution = $institution;

        return $this;
    }

    public function getBankAddress(): ?string
    {
        return $this->bankAddress;
    }

    public function setBankAddress(?string $bankAddress): BankAccount
    {
        $this->bankAddress = $bankAddress;

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
        return $this->getInstitution() && $this->getBankAddress() && $this->getIban() && $this->getBic();
    }
}
