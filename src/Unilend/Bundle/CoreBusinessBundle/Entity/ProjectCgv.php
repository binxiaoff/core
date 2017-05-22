<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectCgv
 *
 * @ORM\Table(name="project_cgv", uniqueConstraints={@ORM\UniqueConstraint(name="id_project", columns={"id_project"})})
 * @ORM\Entity
 */
class ProjectCgv implements UniversignEntityInterface
{
    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects", inversedBy="termOfUse")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_tree", type="integer", nullable=true)
     */
    private $idTree;

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
     * @ORM\Column(name="name", type="string", length=191, nullable=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return ProjectCgv
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
     * Set idTree
     *
     * @param integer $idTree
     *
     * @return ProjectCgv
     */
    public function setIdTree($idTree)
    {
        $this->idTree = $idTree;

        return $this;
    }

    /**
     * Get idTree
     *
     * @return integer
     */
    public function getIdTree()
    {
        return $this->idTree;
    }

    /**
     * Set idUniversign
     *
     * @param string $idUniversign
     *
     * @return ProjectCgv
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
     * @return ProjectCgv
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
     * Set name
     *
     * @param string $name
     *
     * @return ProjectCgv
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
     * Set status
     *
     * @param integer $status
     *
     * @return ProjectCgv
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
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return ProjectCgv
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
     * @return ProjectCgv
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function generateFileName()
    {
        return 'cgv-unilend-' . sha1($this->idProject->getIdProject() . '_' . $this->idTree) . '.pdf';
    }

    public function getUrlPath()
    {
        return '/pdf/cgv_emprunteurs/' . $this->idProject->getIdProject() . '/' . $this->generateFileName();
    }
}
