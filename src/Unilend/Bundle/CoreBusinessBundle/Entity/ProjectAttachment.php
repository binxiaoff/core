<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectAttachment
 *
 * @ORM\Table(name="project_attachment", indexes={@ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="id_attachment", columns={"id_attachment"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectAttachment
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects", inversedBy="attachments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Attachment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_attachment", referencedColumnName="id")
     * })
     */
    private $idAttachment;



    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectAttachment
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return ProjectAttachment
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idProject
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject
     *
     * @return ProjectAttachment
     */
    public function setProject(Projects $idProject = null)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     */
    public function getProject()
    {
        return $this->idProject;
    }

    /**
     * Set idAttachment
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment $idAttachment
     *
     * @return ProjectAttachment
     */
    public function setAttachment(Attachment $idAttachment = null)
    {
        $this->idAttachment = $idAttachment;

        return $this;
    }

    /**
     * Get idAttachment
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment
     */
    public function getAttachment()
    {
        return $this->idAttachment;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
