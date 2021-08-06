<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\IdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_file_download")
 */
class FileDownload
{
    use TimestampableAddedOnlyTrait;
    use IdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\FileVersion", inversedBy="fileVersionDownloads")
     * @ORM\JoinColumn(name="id_file_version", nullable=false)
     */
    private FileVersion $fileVersion;

    /**
     * @ORM\Column(length=150)
     */
    private string $type;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User")
     * @ORM\JoinColumn(name="added_by", referencedColumnName="id", nullable=false)
     */
    private User $addedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=true)
     */
    private ?Company $company;

    public function __construct(FileVersion $fileVersion, User $addedBy, string $type, ?Company $company = null)
    {
        $this->fileVersion = $fileVersion;
        $this->addedBy     = $addedBy;
        $this->type        = $type;
        $this->added       = new DateTimeImmutable();
        $this->company     = $company;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFileVersion(): FileVersion
    {
        return $this->fileVersion;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAddedBy(): User
    {
        return $this->addedBy;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }
}
