<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rule_set", referencedColumnName="id")
     * })
     */
    private $idRuleSet;

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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet $idRuleSet
     *
     * @return ProjectEligibilityRuleSetMember
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
     * Set idRule
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule $idRule
     *
     * @return ProjectEligibilityRuleSetMember
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
}
