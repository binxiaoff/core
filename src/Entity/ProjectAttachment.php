<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectAttachmentRepository")
 */
class ProjectAttachment
{
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
     * @ORM\JoinColumn(nullable=false)
     */
    private $file;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectAttachments")
     */
    private $project;

    /**
     * @param string $type
     *
     * @throws Exception
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @param Project $project
     * @param string  $type
     *
     * @return ProjectAttachment
     */
    public function setType(Project $project, string $type): ProjectAttachment
    {
        $this->project = $project;
        $this->type    = $type;

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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return File|null
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * @param File $file
     *
     * @return $this
     */
    public function setFile(File $file): self
    {
        $this->file = $file;

        return $this;
    }
}
