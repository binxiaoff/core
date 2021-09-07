<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use KLS\Core\Entity\File;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="syndication_project_file")
 *
 * @ApiResource(
 *     normalizationContext={"groups": {"projectFile:read", "file:read", "fileVersion:read", "timestampable:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *         "delete": {"security_post_denormalize": "is_granted('delete', previous_object)"}
 *     },
 *     collectionOperations={}
 * )
 */
class ProjectFile
{
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;
    use ConstantsAwareTrait;
    use PublicizeIdentityTrait;

    public const PROJECT_FILE_TYPE_GENERAL              = 'project_file_general';
    public const PROJECT_FILE_TYPE_ACCOUNTING_FINANCIAL = 'project_file_accounting_financial';
    public const PROJECT_FILE_TYPE_LEGAL                = 'project_file_legal';
    public const PROJECT_FILE_TYPE_KYC                  = 'project_file_kyc';

    /**
     * @var string
     *
     * @ORM\Column(length=60)
     *
     * @Assert\Choice(callback="getTypes")
     *
     * @Groups({"projectFile:read"})
     */
    private $type;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Core\Entity\File", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_file", nullable=false, unique=true)
     *
     * @Groups({"projectFile:read"})
     */
    private $file;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Arrangement\Entity\Project", inversedBy="projectFiles")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     */
    private $project;

    /**
     * @throws Exception
     */
    public function __construct(string $type, File $file, Project $project, Staff $addedBy)
    {
        $this->type    = $type;
        $this->file    = $file;
        $this->project = $project;
        $this->addedBy = $addedBy;
        $this->added   = new \DateTimeImmutable();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public static function getTypes(): array
    {
        return self::getConstants('TYPE_');
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public static function getProjectFileTypes(): array
    {
        return self::getConstants('PROJECT_FILE_TYPE_');
    }
}
