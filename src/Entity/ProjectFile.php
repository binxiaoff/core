<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\BlamableAddedTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
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
     */
    public function __construct(string $type, File $file, Project $project)
    {
        $this->type    = $type;
        $this->file    = $file;
        $this->project = $project;
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
    public function getAttachmentTypes(): array
    {
        return self::getConstants('TYPE_');
    }
}
