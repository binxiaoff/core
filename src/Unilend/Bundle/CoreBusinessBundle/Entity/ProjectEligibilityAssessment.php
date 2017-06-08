<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectEligibilityAssessment
 *
 * @ORM\Table(name="project_eligibility_assessment", indexes={@ORM\Index(name="fk_project_eligibility_assessment_id_project", columns={"id_project"}), @ORM\Index(name="fk_project_eligibility_assessment_id_rule_set_member", columns={"id_rule_set_member"})})
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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSetMember
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSetMember")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rule_set_member", referencedColumnName="id")
     * })
     */
    private $idRuleSetMember;

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
     * Set idRuleSetMember
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSetMember $idRuleSetMember
     *
     * @return ProjectEligibilityAssessment
     */
    public function setIdRuleSetMember(\Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSetMember $idRuleSetMember = null)
    {
        $this->idRuleSetMember = $idRuleSetMember;

        return $this;
    }

    /**
     * Get idRuleSetMember
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSetMember
     */
    public function getIdRuleSetMember()
    {
        return $this->idRuleSetMember;
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
