<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectEligibilityAssessment
 *
 * @ORM\Table(name="project_eligibility_assessment", indexes={@ORM\Index(name="fk_project_eligibility_assessment_id_project", columns={"id_project"}), @ORM\Index(name="fk_project_eligibility_assessment_id_rule", columns={"id_rule"}), @ORM\Index(name="fk_project_eligibility_assessment_id_rule_set", columns={"id_rule_set"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectEligibilityAssessment
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true)
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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rule", referencedColumnName="id")
     * })
     */
    private $idRule;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rule_set", referencedColumnName="id")
     * })
     */
    private $idRuleSet;

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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule $idRule
     *
     * @return ProjectEligibilityAssessment
     */
    public function setIdRule(\Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule $idRule = null)
    {
        $this->idRule = $idRule;

        return $this;
    }

    /**
     * Get idRule
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule
     */
    public function getIdRule()
    {
        return $this->idRule;
    }

    /**
     * Set idRuleSet
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet $idRuleSet
     *
     * @return ProjectEligibilityAssessment
     */
    public function setIdRuleSet(\Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet $idRuleSet = null)
    {
        $this->idRuleSet = $idRuleSet;

        return $this;
    }

    /**
     * Get idRuleSet
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet
     */
    public function getIdRuleSet()
    {
        return $this->idRuleSet;
    }

    /**
     * Set idProject
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject
     *
     * @return ProjectEligibilityAssessment
     */
    public function setIdProject(\Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject = null)
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
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }
}
