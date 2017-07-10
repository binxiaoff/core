<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectsRemb
 *
 * @ORM\Table(name="projects_remb", indexes={@ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="ordre", columns={"ordre"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ProjectsRembRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectsRemb
{
    const STATUS_ERROR                        = -1;
    const STATUS_PENDING                      = 0;
    const STATUS_REPAID                       = 1;
    const STATUS_REJECTED                     = 2;
    const STATUS_IN_PROGRESS                  = 3;
    const STATUS_AUTOMATIC_REPAYMENT_DISABLED = 4;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_remb_emprunteur_reel", type="datetime", nullable=false)
     */
    private $dateRembEmprunteurReel;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_remb_preteurs", type="datetime", nullable=false)
     */
    private $dateRembPreteurs;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_remb_preteurs_reel", type="datetime", nullable=true)
     */
    private $dateRembPreteursReel;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

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
     * @ORM\Column(name="id_project_remb", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProjectRemb;


    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return ProjectsRemb
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
     * Set ordre
     *
     * @param integer $ordre
     *
     * @return ProjectsRemb
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return integer
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * Set dateRembEmprunteurReel
     *
     * @param \DateTime $dateRembEmprunteurReel
     *
     * @return ProjectsRemb
     */
    public function setDateRembEmprunteurReel($dateRembEmprunteurReel)
    {
        $this->dateRembEmprunteurReel = $dateRembEmprunteurReel;

        return $this;
    }

    /**
     * Get dateRembEmprunteurReel
     *
     * @return \DateTime
     */
    public function getDateRembEmprunteurReel()
    {
        return $this->dateRembEmprunteurReel;
    }

    /**
     * Set dateRembPreteurs
     *
     * @param \DateTime $dateRembPreteurs
     *
     * @return ProjectsRemb
     */
    public function setDateRembPreteurs($dateRembPreteurs)
    {
        $this->dateRembPreteurs = $dateRembPreteurs;

        return $this;
    }

    /**
     * Get dateRembPreteurs
     *
     * @return \DateTime
     */
    public function getDateRembPreteurs()
    {
        return $this->dateRembPreteurs;
    }

    /**
     * Set dateRembPreteursReel
     *
     * @param \DateTime $dateRembPreteursReel
     *
     * @return ProjectsRemb
     */
    public function setDateRembPreteursReel($dateRembPreteursReel)
    {
        $this->dateRembPreteursReel = $dateRembPreteursReel;

        return $this;
    }

    /**
     * Get dateRembPreteursReel
     *
     * @return \DateTime
     */
    public function getDateRembPreteursReel()
    {
        return $this->dateRembPreteursReel;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return ProjectsRemb
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectsRemb
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
     * @return ProjectsRemb
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
     * Get idProjectRemb
     *
     * @return integer
     */
    public function getIdProjectRemb()
    {
        return $this->idProjectRemb;
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
