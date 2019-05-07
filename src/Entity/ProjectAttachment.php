<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectAttachmentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectAttachment
{
    use TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectAttachments")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * @var Attachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Attachment")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_attachment", nullable=false)
     * })
     */
    private $attachment;

    /**
     * @var ProjectAttachmentSignature[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectAttachmentSignature", mappedBy="projectAttachment")
     */
    private $signatures;

    /**
     * ProjectAttachment constructor.
     */
    public function __construct()
    {
        $this->signatures = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param Project $project
     *
     * @return ProjectAttachment
     */
    public function setProject(Project $project): ProjectAttachment
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Project|null
     */
    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * @param Attachment $attachment
     *
     * @return ProjectAttachment
     */
    public function setAttachment(Attachment $attachment): ProjectAttachment
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return Attachment|null
     */
    public function getAttachment(): ?Attachment
    {
        return $this->attachment;
    }

    /**
     * @return ProjectAttachmentSignature[]
     */
    public function getSignatures(): iterable
    {
        return $this->signatures;
    }
}
