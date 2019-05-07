<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\{BlamableAddedTrait, BlamableArchivedTrait, BlamableUpdatedTrait, TimestampableTrait};

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\AttachmentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Attachment
{
    use TimestampableTrait;
    use BlamableAddedTrait;
    use BlamableArchivedTrait;
    use BlamableUpdatedTrait;

    /**
     * @var string
     *
     * @ORM\Column(length=191)
     */
    private $path;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $archived;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var AttachmentType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\AttachmentType")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_type", nullable=false)
     * })
     */
    private $type;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients", inversedBy="attachments")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client_owner", referencedColumnName="id_client")
     * })
     */
    private $clientOwner;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company_owner", referencedColumnName="id_company")
     * })
     */
    private $companyOwner;

    /**
     * @var GreenpointAttachment
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\GreenpointAttachment", mappedBy="idAttachment")
     */
    private $greenpointAttachment;

    /**
     * @var BankAccount
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\BankAccount", mappedBy="idAttachment")
     */
    private $bankAccount;

    /**
     * @var string
     *
     * @ORM\Column(length=191)
     */
    private $originalName;

    /**
     * @var string
     *
     * @ORM\Column(length=191, nullable=true)
     */
    private $description;

    /**
     * @param string $path
     *
     * @return Attachment
     */
    public function setPath(string $path): Attachment
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param DateTimeImmutable $archived
     *
     * @return Attachment
     */
    public function setArchived(DateTimeImmutable $archived): Attachment
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getArchived(): DateTimeImmutable
    {
        return $this->archived;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param AttachmentType $type
     *
     * @return Attachment
     */
    public function setType(AttachmentType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return AttachmentType|null
     */
    public function getType(): ?AttachmentType
    {
        return $this->type;
    }

    /**
     * @param Clients|null $clientOwner
     *
     * @return Attachment
     */
    public function setClientOwner(?Clients $clientOwner)
    {
        $this->clientOwner = $clientOwner;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getClientOwner(): Clients
    {
        return $this->clientOwner;
    }

    /**
     * @return GreenpointAttachment
     */
    public function getGreenpointAttachment(): GreenpointAttachment
    {
        return $this->greenpointAttachment;
    }

    /**
     * @return BankAccount
     */
    public function getBankAccount(): BankAccount
    {
        return $this->bankAccount;
    }

    /**
     * @param string $originalName
     *
     * @return Attachment
     */
    public function setOriginalName(string $originalName)
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return Attachment
     */
    public function setDescription(?string $description): Attachment
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Companies|null
     */
    public function getCompanyOwner(): ?Companies
    {
        return $this->companyOwner;
    }

    /**
     * @param Companies|null $companyOwner
     *
     * @return Attachment
     */
    public function setCompanyOwner(?Companies $companyOwner): Attachment
    {
        $this->companyOwner = $companyOwner;

        return $this;
    }
}
