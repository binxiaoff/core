<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectBeneficialOwnerUniversign
 *
 * @ORM\Table(name="project_beneficial_owner_universign", indexes={@ORM\Index(name="idx_project_beneficial_owner_universign_id_declaration", columns={"id_declaration"}), @ORM\Index(name="idx_project_beneficial_owner_universign_id_project", columns={"id_project"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ProjectBeneficialOwnerRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectBeneficialOwnerUniversign implements UniversignEntityInterface
{
    const DOCUMENT_TYPE = 'beneficiaires-effectifs';
    const DOCUMENT_NAME = 'declaration-beneficiaires-effectifs';

    /**
     * @var string
     *
     * @ORM\Column(name="id_universign", type="string", length=191, nullable=true)
     */
    private $idUniversign;

    /**
     * @var string
     *
     * @ORM\Column(name="url_universign", type="string", length=191, nullable=true)
     */
    private $urlUniversign;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191, nullable=true)
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
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwnerDeclaration
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwnerDeclaration")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_declaration", referencedColumnName="id")
     * })
     */
    private $idDeclaration;



    /**
     * Set idUniversign
     *
     * @param string $idUniversign
     *
     * @return ProjectBeneficialOwnerUniversign
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
     * @return ProjectBeneficialOwnerUniversign
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
     * @return ProjectBeneficialOwnerUniversign
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
     * @return ProjectBeneficialOwnerUniversign
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
     * @return ProjectBeneficialOwnerUniversign
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
     * @return ProjectBeneficialOwnerUniversign
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
     * @return ProjectBeneficialOwnerUniversign
     */
    public function setIdProject(Projects $idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set idDeclaration
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwnerDeclaration $idDeclaration
     *
     * @return ProjectBeneficialOwnerUniversign
     */
    public function setIdDeclaration(CompanyBeneficialOwnerDeclaration $idDeclaration)
    {
        $this->idDeclaration = $idDeclaration;

        return $this;
    }

    /**
     * Get idDeclaration
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwnerDeclaration
     */
    public function getIdDeclaration()
    {
        return $this->idDeclaration;
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

    /**
     * @ORM\PrePersist
     */
    public function setNameValue()
    {
        if (null === $this->name && null !== $this->idProject) {
            $this->name = $this->idProject->getIdProject() . self::DOCUMENT_NAME . '.pdf';
        }
    }
}
