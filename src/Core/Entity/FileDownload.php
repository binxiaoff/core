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
     * @var FileVersion
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\FileVersion", inversedBy="fileVersionDownloads")
     * @ORM\JoinColumn(name="id_file_version", nullable=false)
     */
    private FileVersion $fileVersion;

    /**
     * @var string
     *
     * @ORM\Column(length=150)
     */
    private string $type;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User")
     * @ORM\JoinColumn(name="added_by", referencedColumnName="id", nullable=false)
     */
    private User $addedBy;

    /**
     * @var Company|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=true)
     */
    private ?Company $company;

    /**
     * @param FileVersion  $fileVersion
     * @param User         $addedBy
     * @param string       $type
     * @param Company|null $company
     */
    public function __construct(FileVersion $fileVersion, User $addedBy, string $type, ?Company $company = null)
    {
        $this->fileVersion = $fileVersion;
        $this->addedBy     = $addedBy;
        $this->type        = $type;
        $this->added       = new DateTimeImmutable();
        $this->company     = $company;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return FileVersion
     */
    public function getFileVersion(): FileVersion
    {
        return $this->fileVersion;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return User
     */
    public function getAddedBy(): User
    {
        return $this->addedBy;
    }

    /**
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }
}
