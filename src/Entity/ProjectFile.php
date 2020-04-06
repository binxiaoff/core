<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableAddedOnlyTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 *
 * @ApiResource(
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     }
 * )
 */
class ProjectFile
{
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;
    use ConstantsAwareTrait;

    private const PROJECT_FILE_TYPE_GENERAL              = 'project_file_general';
    private const PROJECT_FILE_TYPE_ACCOUNTING_FINANCIAL = 'project_file_accounting_financial';
    private const PROJECT_FILE_TYPE_LEGAL                = 'project_file_legal';
    private const PROJECT_FILE_TYPE_KYC                  = 'project_file_kyc';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

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
     * @ORM\OneToOne(targetEntity="Unilend\Entity\File", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_file", nullable=false, unique=true)
     *
     * @Groups({"projectFile:read"})
     */
    private $file;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectFiles")
     * @ORM\JoinColumn(name="id_project", nullable=false)
     */
    private $project;

    /**
     * @param string  $type
     * @param File    $file
     * @param Project $project
     * @param Staff   $addedBy
     *
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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return array
     */
    public static function getTypes(): array
    {
        return self::getConstants('TYPE_');
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return array
     */
    public static function getProjectFileTypes(): array
    {
        return self::getConstants('PROJECT_FILE_TYPE_');
    }
}
