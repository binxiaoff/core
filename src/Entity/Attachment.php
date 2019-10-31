<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Traits\{BlamableAddedTrait, BlamableArchivedTrait, BlamableUpdatedTrait, TimestampableTrait};

/**
 * @ApiResource(
 *     normalizationContext={"groups": "attachment:read"},
 *     denormalizationContext={"attachment:read"},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "method": "POST",
 *             "controller": "Unilend\Controller\Attachment\Upload",
 *             "deserialize": false,
 *             "input": false,
 *             "swagger_context": {
 *                 "consumes": {"multipart/form-data"},
 *                 "parameters": {
 *                     {
 *                         "in": "formData",
 *                         "name": "file",
 *                         "type": "file",
 *                         "description": "The uploaded file",
 *                         "required": true
 *                     },
 *                     {
 *                         "in": "formData",
 *                         "name": "type",
 *                         "type": "string",
 *                         "description": "The attachmentType as an IRI"
 *                     },
 *                     {
 *                         "in": "formData",
 *                         "name": "company",
 *                         "type": "string",
 *                         "description": "The companyOwner as an IRI"
 *                     },
 *                     {
 *                         "in": "formData",
 *                         "name": "description",
 *                         "type": "string",
 *                         "description": "The description"
 *                     },
 *                     {
 *                         "in": "formData",
 *                         "name": "user",
 *                         "type": "string",
 *                         "description": "The uploader as an IRI (available as an admin)"
 *                     }
 *                 }
 *             }
 *         }
 *     }
 * )
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedAttachment")
 * @Gedmo\SoftDeleteable(fieldName="archivedAt")
 *
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
     *
     * @Gedmo\Versioned
     *
     * @Groups({"attachment:read"})
     */
    private $archivedAt;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"attachment:read"})
     */
    private $downloaded;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Groups({"attachment:read"})
     */
    private $id;

    /**
     * @var AttachmentType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\AttachmentType")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_type", nullable=true)
     * })
     *
     * @Groups({"attachment:read"})
     */
    private $type;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company_owner", referencedColumnName="id_company")
     * })
     *
     * @Groups({"attachment:read"})
     */
    private $companyOwner;

    /**
     * @var string
     *
     * @ORM\Column(length=191, nullable=true)
     *
     * @Groups({"attachment:read"})
     */
    private $originalName;

    /**
     * @var string
     *
     * @ORM\Column(length=191, nullable=true)
     *
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var AttachmentSignature[]
     *
     * @ORM\OneToMany(targetEntity="AttachmentSignature", mappedBy="attachment")
     *
     * @Groups({"attachment:read"})
     */
    private $signatures;

    /**
     * Attachment constructor.
     *
     * @param string  $path
     * @param Clients $addedBy
     */
    public function __construct(string $path, Clients $addedBy)
    {
        $this->signatures = new ArrayCollection();
        $this->path       = $path;
        $this->addedBy    = $addedBy;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param DateTimeImmutable $archivedAt
     *
     * @return Attachment
     */
    public function setArchivedAt(DateTimeImmutable $archivedAt): Attachment
    {
        $this->archivedAt = $archivedAt;

        return $this;
    }

    /**
     * @param Clients $clients
     *
     * @throws Exception
     *
     * @return Attachment
     */
    public function archive(Clients $clients): Attachment
    {
        $this->setArchivedAt(new DateTimeImmutable())
            ->setArchivedBy($clients)
        ;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getArchivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
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
    public function setType(?AttachmentType $type): Attachment
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
     * @param string $originalName
     *
     * @return Attachment
     */
    public function setOriginalName(string $originalName): Attachment
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

    /**
     * @return AttachmentSignature[]
     */
    public function getSignatures(): iterable
    {
        return $this->signatures;
    }

    /**
     * @param DateTimeImmutable $downloaded
     *
     * @return Attachment
     */
    public function setDownloaded(DateTimeImmutable $downloaded): Attachment
    {
        $this->downloaded = $downloaded;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getDownloaded(): ?DateTimeImmutable
    {
        return $this->downloaded;
    }
}
