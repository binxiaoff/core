<?php

namespace Unilend\Entity;

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
    const STATUS_CHECK_KO      = 0;
    const STATUS_CHECK_OK      = 1;
    const STATUS_CHECK_SKIPPED = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="status", type="boolean", nullable=true)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $idProject;

    /**
     * @var \Unilend\Entity\UnderlyingContract
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\UnderlyingContract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_contract", referencedColumnName="id_contract", nullable=false)
     * })
     */
    private $idContract;

    /**
     * @var \Unilend\Entity\UnderlyingContractAttributeType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\UnderlyingContractAttributeType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_contract_attribute_type", referencedColumnName="id_type", nullable=false)
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
     * Set idContract
     *
     * @param UnderlyingContract $idContract
     *
     * @return ProjectContractAssessment
     */
    public function setIdContract(UnderlyingContract $idContract)
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
    public function setIdContractAttributeType(UnderlyingContractAttributeType $idContractAttributeType)
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
