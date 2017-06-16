<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectContractAssessment
 *
 * @ORM\Table(name="project_contract_assessment", indexes={@ORM\Index(name="idx_project_contract_assessment_id_project", columns={"id_project"}), @ORM\Index(name="idx_project_contract_assessment_id_contract", columns={"id_contract"}), @ORM\Index(name="idx_project_contract_assessment_id_contract_attribute_type", columns={"id_contract_attribute_type"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectContractAssessment
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_contract", referencedColumnName="id_contract")
     * })
     */
    private $idContract;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_contract_attribute_type", referencedColumnName="id_type")
     * })
     */
    private $idContractAttributeType;



    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return ProjectContractAssessment
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectContractAssessment
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

    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return ProjectContractAssessment
     */
    public function setIdProject(Projects $idProject = null)
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
     * Set idContract
     *
     * @param UnderlyingContract $idContract
     *
     * @return ProjectContractAssessment
     */
    public function setIdContract(UnderlyingContract $idContract = null)
    {
        $this->idContract = $idContract;

        return $this;
    }

    /**
     * Get idContract
     *
     * @return UnderlyingContract
     */
    public function getIdContract()
    {
        return $this->idContract;
    }

    /**
     * Set idContractAttributeType
     *
     * @param UnderlyingContractAttributeType $idContractAttributeType
     *
     * @return ProjectContractAssessment
     */
    public function setIdContractAttributeType(UnderlyingContractAttributeType $idContractAttributeType = null)
    {
        $this->idContractAttributeType = $idContractAttributeType;

        return $this;
    }

    /**
     * Get idContractAttributeType
     *
     * @return UnderlyingContractAttributeType
     */
    public function getIdContractAttributeType()
    {
        return $this->idContractAttributeType;
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
}
