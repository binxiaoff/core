<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectEligibilityRuleSet
 *
 * @ORM\Table(name="project_eligibility_rule_set", uniqueConstraints={@ORM\UniqueConstraint(name="unq_project_eligibility_rule_set_label", columns={"label"})})
 * @ORM\Entity
 */
class ProjectEligibilityRuleSet
{
    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=100, nullable=false)
     */
    private $label;

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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule", inversedBy="idRuleSet")
     * @ORM\JoinTable(name="project_eligibility_rule_set_member",
     *   joinColumns={
     *     @ORM\JoinColumn(name="id_rule_set", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="id_rule", referencedColumnName="id")
     *   }
     * )
     */
    private $idRule;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->idRule = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Set label
     *
     * @param string $label
     *
     * @return ProjectEligibilityRuleSet
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectEligibilityRuleSet
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
     * Add idRule
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule $idRule
     *
     * @return ProjectEligibilityRuleSet
     */
    public function addIdRule(\Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule $idRule)
    {
        $this->idRule[] = $idRule;

        return $this;
    }

    /**
     * Remove idRule
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule $idRule
     */
    public function removeIdRule(\Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule $idRule)
    {
        $this->idRule->removeElement($idRule);
    }

    /**
     * Get idRule
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIdRule()
    {
        return $this->idRule;
    }
}
