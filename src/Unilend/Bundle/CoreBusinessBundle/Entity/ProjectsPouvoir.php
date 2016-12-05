<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectsPouvoir
 *
 * @ORM\Table(name="projects_pouvoir", indexes={@ORM\Index(name="id_project", columns={"id_project"})})
 * @ORM\Entity
 */
class ProjectsPouvoir
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
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
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status_remb", type="boolean", nullable=false)
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
     * @param integer $idProject
     *
     * @return ProjectsPouvoir
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return integer
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
     * @param boolean $status
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
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set statusRemb
     *
     * @param boolean $statusRemb
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
     * @return boolean
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

    /**
     * Get idPouvoir
     *
     * @return integer
     */
    public function getIdPouvoir()
    {
        return $this->idPouvoir;
    }
}
