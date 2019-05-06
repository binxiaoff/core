<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\Timestampable;

/**
 * @ORM\Table(name="project_attachment")
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectAttachmentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectAttachment
{
    use Timestampable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Projects", inversedBy="attachments")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $idProject;

    /**
     * @var Attachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Attachment")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_attachment", referencedColumnName="id", nullable=false)
     * })
     */
    private $idAttachment;

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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param Projects $idProject
     *
     * @return ProjectAttachment
     */
    public function setProject(Projects $idProject): ProjectAttachment
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * @return Projects
     */
    public function getProject(): Projects
    {
        return $this->idProject;
    }

    /**
     * @param Attachment $idAttachment
     *
     * @return ProjectAttachment
     */
    public function setAttachment(Attachment $idAttachment): ProjectAttachment
    {
        $this->idAttachment = $idAttachment;

        return $this;
    }

    /**
     * @return Attachment
     */
    public function getAttachment(): Attachment
    {
        return $this->idAttachment;
    }

    /**
     * @return ProjectAttachmentSignature[]
     */
    public function getSignatures(): iterable
    {
        return $this->signatures;
    }
}
