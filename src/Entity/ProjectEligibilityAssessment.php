<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectEligibilityAssessment
 *
 * @ORM\Table(name="project_eligibility_assessment", indexes={@ORM\Index(name="fk_project_eligibility_assessment_id_project", columns={"id_project"}), @ORM\Index(name="fk_project_eligibility_assessment_id_rule", columns={"id_rule"}), @ORM\Index(name="fk_project_eligibility_assessment_id_rule_set", columns={"id_rule_set"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectEligibilityAssessmentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectEligibilityAssessment
{
    const STATUS_CHECK_KO = 0;
    const STATUS_CHECK_OK = 1;

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
     * @var \Unilend\Entity\ProjectEligibilityRule
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectEligibilityRule")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rule", referencedColumnName="id", nullable=false)
     * })
     */
    private $idRule;

    /**
     * @var \Unilend\Entity\ProjectEligibilityRuleSet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectEligibilityRuleSet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rule_set", referencedColumnName="id", nullable=false)
     * })
     */
    private $idRuleSet;

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
     * Set status
     *
     * @param boolean $status
     *
     * @return ProjectEligibilityAssessment
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
     * @return ProjectEligibilityAssessment
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
     * Set idRule
     *
     * @param \Unilend\Entity\ProjectEligibilityRule $idRule
     *
     * @return ProjectEligibilityAssessment
     */
    public function setIdRule(\Unilend\Entity\ProjectEligibilityRule $idRule = null)
    {
        $this->idRule = $idRule;

        return $this;
    }

    /**
     * Get idRule
     *
     * @return \Unilend\Entity\ProjectEligibilityRule
     */
    public function getIdRule()
    {
        return $this->idRule;
    }

    /**
     * Set idRuleSet
     *
     * @param \Unilend\Entity\ProjectEligibilityRuleSet $idRuleSet
     *
     * @return ProjectEligibilityAssessment
     */
    public function setIdRuleSet(\Unilend\Entity\ProjectEligibilityRuleSet $idRuleSet = null)
    {
        $this->idRuleSet = $idRuleSet;

        return $this;
    }

    /**
     * Get idRuleSet
     *
     * @return \Unilend\Entity\ProjectEligibilityRuleSet
     */
    public function getIdRuleSet()
    {
        return $this->idRuleSet;
    }

    /**
     * Set idProject
     *
     * @param \Unilend\Entity\Projects $idProject
     *
     * @return ProjectEligibilityAssessment
     */
    public function setIdProject(\Unilend\Entity\Projects $idProject = null)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return \Unilend\Entity\Projects
     */
    public function getIdProject()
    {
        return $this->idProject;
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
