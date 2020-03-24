<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\BlamableAddedTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 *
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "projectFile:read",
 *             "file:read",
 *             "fileVersion:read",
 *             "blameable:read"
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "download": {
 *             "security": "is_granted('download', object)",
 *             "method": "GET",
 *             "controller": "Unilend\Controller\ProjectFile\Download",
 *             "path": "/project_file/{id}/download"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "method": "POST",
 *             "controller": "Unilend\Controller\ProjectFile\Upload",
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
 *                         "description": "The file type"
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
 */
class ProjectFile
{
    use BlamableAddedTrait;
    // @todo uncomment when add ApiResource annotation
//    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;
    use ConstantsAwareTrait;

    private const TYPE_GENERAL              = 'general';
    private const TYPE_ACCOUNTING_FINANCIAL = 'accounting_financial';
    private const TYPE_LEGAL                = 'legal';
    private const TYPE_KYC                  = 'kyc';

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
     */
    private $type;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Entity\File", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_file", nullable=false)
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
     * @param Clients $addedBy
     *
     * @throws \Exception
     */
    public function __construct(string $type, File $file, Project $project, Clients $addedBy)
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
}
