<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\{BlamableAddedTrait, BlamableArchivedTrait, PublicizeIdentityTrait, TimestampableTrait, TraceableBlamableUpdatedTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     attributes={"pagination_client_enabled": true},
 *     normalizationContext={"groups": {"attachment:read", "blameable:read"}},
 *     denormalizationContext={"groups": "attachment:write"},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "delete": {"security": "is_granted('edit', object.getProject())"},
 *         "download": {
 *             "security": "is_granted('download', object)",
 *             "method": "GET",
 *             "controller": "Unilend\Controller\Attachment\Download",
 *             "path": "/attachments/{id}/download"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "method": "POST",
 *             "controller": "Unilend\Controller\Attachment\Upload",
 *             "deserialize": false,
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
 *                         "description": "The attachment type"
 *                     },
 *                     {
 *                         "in": "formData",
 *                         "name": "project",
 *                         "type": "string",
 *                         "description": "The project as an IRI"
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
 * @Gedmo\SoftDeleteable(fieldName="archived")
 *
 * @ORM\Entity(repositoryClass="Unilend\Repository\AttachmentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Attachment
{
    use PublicizeIdentityTrait;
    use ConstantsAwareTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;
    use BlamableArchivedTrait;
    use TraceableBlamableUpdatedTrait;

    public const TYPE_PROJECT_CONFIDENTIALITY_DISCLAIMER = 'project_confidentiality_disclaimer';
    public const TYPE_PROJECT_DESCRIPTION                = 'project_description';

    private const TYPE_GENERAL              = 'general';
    private const TYPE_ACCOUNTING_FINANCIAL = 'accounting_financial';
    private const TYPE_LEGAL                = 'legal';
    private const TYPE_KYC                  = 'kyc';

    /**
     * @var string
     *
     * @ORM\Column(length=191)
     *
     * @Gedmo\Versioned
     */
    private $path;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"attachment:read"})
     */
    private $archived;

    /**
     * @var string
     *
     * @ORM\Column(length=60)
     *
     * @Assert\Choice(callback="getAttachmentTypes")
     *
     * @Groups({"attachment:read", "attachment:write"})
     */
    private $type;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="attachments")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"attachment:write"})
     *
     * @Assert\NotBlank
     */
    private $project;

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
     * @ORM\OneToMany(targetEntity="Unilend\Entity\AttachmentSignature", mappedBy="attachment")
     *
     * @Groups({"attachment:read"})
     */
    private $signatures;

    /**
     * The size of the file in bytes.
     *
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Groups({"attachment:read"})
     */
    private $size;

    /**
     * @var Collection|AttachmentDownload[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\AttachmentDownload", fetch="EXTRA_LAZY", mappedBy="attachment", cascade={"persist", "remove"})
     */
    private $attachmentDownloads;

    /**
     * Attachment constructor.
     *
     * @param string  $path
     * @param string  $type
     * @param Staff   $addedBy
     * @param Project $project
     *
     * @throws Exception
     */
    public function __construct(string $path, string $type, Staff $addedBy, Project $project)
    {
        $this->signatures          = new ArrayCollection();
        $this->attachmentDownloads = new ArrayCollection();
        $this->path                = $path;
        $this->type                = $type;
        $this->addedBy             = $addedBy;
        $this->added               = new DateTimeImmutable();
        $this->project             = $project;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

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
    public function getArchived(): ?DateTimeImmutable
    {
        return $this->archived;
    }

    /**
     * @param string $type
     *
     * @return Attachment
     */
    public function setType(string $type): Attachment
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
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
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     *
     * @return Attachment
     */
    public function setProject(Project $project): Attachment
    {
        $this->project = $project;

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
     * @return array
     */
    public function getAttachmentTypes(): array
    {
        return self::getConstants('TYPE_');
    }

    /**
     * @return int
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @param int $size
     *
     * @return Attachment
     */
    public function setSize(?int $size): Attachment
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return Collection|AttachmentDownload[]
     */
    public function getAttachmentDownloads()
    {
        return $this->attachmentDownloads;
    }
}
