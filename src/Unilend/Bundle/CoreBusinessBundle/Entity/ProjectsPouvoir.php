<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\UniversignEntityInterface;

/**
 * ProjectsPouvoir
 *
 * @ORM\Table(name="projects_pouvoir", indexes={@ORM\Index(name="id_project", columns={"id_project"})})
 * @ORM\Entity
 */
class ProjectsPouvoir implements UniversignEntityInterface
{
    const STATUS_PENDING   = 0;
    const STATUS_SIGNED    = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_FAILED    = 3;

    const STATUS_PENDING_VALIDATION = 0;
    const STATUS_VALIDATED          = 1;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects", inversedBy="proxy")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="id_universign", type="string", length=191, nullable=false)
     */
    private $idUniversign;

    /**
     * @var string
     *
     * @ORM\Column(name="url_universign", type="string", length=191, nullable=false)
     */
    private $urlUniversign;

    /**
     * @var string
     *
     * @ORM\Column(name="url_pdf", type="string", length=191, nullable=false)
     */
    private $urlPdf;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_remb", type="integer", nullable=false)
     */
    private $statusRemb;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_pouvoir", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idPouvoir;


    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return ProjectsPouvoir
     */
    public function setIdProject(Projects $idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return Projects
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ProjectsPouvoir
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set idUniversign
     *
     * @param string $idUniversign
     *
     * @return ProjectsPouvoir
     */
    public function setIdUniversign($idUniversign)
    {
        $this->idUniversign = $idUniversign;

        return $this;
    }

    /**
     * Get idUniversign
     *
     * @return string
     */
    public function getIdUniversign()
    {
        return $this->idUniversign;
    }

    /**
     * Set urlUniversign
     *
     * @param string $urlUniversign
     *
     * @return ProjectsPouvoir
     */
    public function setUrlUniversign($urlUniversign)
    {
        $this->urlUniversign = $urlUniversign;

        return $this;
    }

    /**
     * Get urlUniversign
     *
     * @return string
     */
    public function getUrlUniversign()
    {
        return $this->urlUniversign;
    }

    /**
     * Set urlPdf
     *
     * @param string $urlPdf
     *
     * @return ProjectsPouvoir
     */
    public function setUrlPdf($urlPdf)
    {
        $this->urlPdf = $urlPdf;

        return $this;
    }

    /**
     * Get urlPdf
     *
     * @return string
     */
    public function getUrlPdf()
    {
        return $this->urlPdf;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return ProjectsPouvoir
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set statusRemb
     *
     * @param integer $statusRemb
     *
     * @return ProjectsPouvoir
     */
    public function setStatusRemb($statusRemb)
    {
        $this->statusRemb = $statusRemb;

        return $this;
    }

    /**
     * Get statusRemb
     *
     * @return integer
     */
    public function getStatusRemb()
    {
        return $this->statusRemb;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return ProjectsPouvoir
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectsPouvoir
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

    public function getId()
    {
        return $this->idPouvoir;
    }
}
