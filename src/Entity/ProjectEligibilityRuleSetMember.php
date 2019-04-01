<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectEligibilityRuleSetMember
 *
 * @ORM\Table(name="project_eligibility_rule_set_member", indexes={@ORM\Index(name="fk_project_eligibility_rule_set_member_id_rule", columns={"id_rule"}), @ORM\Index(name="fk_project_eligibility_rule_set_member_id_rule_set", columns={"id_rule_set"})})
 * @ORM\Entity
 */
class ProjectEligibilityRuleSetMember
{
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
     * @var \Unilend\Entity\ProjectEligibilityRuleSet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectEligibilityRuleSet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rule_set", referencedColumnName="id", nullable=false)
     * })
     */
    private $idRuleSet;

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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectEligibilityRuleSetMember
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
     * Set idRuleSet
     *
     * @param \Unilend\Entity\ProjectEligibilityRuleSet $idRuleSet
     *
     * @return ProjectEligibilityRuleSetMember
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
     * Set idRule
     *
     * @param \Unilend\Entity\ProjectEligibilityRule $idRule
     *
     * @return ProjectEligibilityRuleSetMember
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
}
