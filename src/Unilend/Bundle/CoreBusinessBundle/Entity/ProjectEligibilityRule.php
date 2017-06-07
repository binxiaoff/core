<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectEligibilityRule
 *
 * @ORM\Table(name="project_eligibility_rule", uniqueConstraints={@ORM\UniqueConstraint(name="unq_project_eligibility_rule_label", columns={"label"})})
 * @ORM\Entity
 */
class ProjectEligibilityRule
{
    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=100, nullable=false)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

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
     * @ORM\ManyToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet", mappedBy="idRule")
     */
    private $idRuleSet;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->idRuleSet = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Set label
     *
     * @param string $label
     *
     * @return ProjectEligibilityRule
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
     * Set description
     *
     * @param string $description
     *
     * @return ProjectEligibilityRule
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectEligibilityRule
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
     * Add idRuleSet
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet $idRuleSet
     *
     * @return ProjectEligibilityRule
     */
    public function addIdRuleSet(\Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet $idRuleSet)
    {
        $this->idRuleSet[] = $idRuleSet;

        return $this;
    }

    /**
     * Remove idRuleSet
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet $idRuleSet
     */
    public function removeIdRuleSet(\Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet $idRuleSet)
    {
        $this->idRuleSet->removeElement($idRuleSet);
    }

    /**
     * Get idRuleSet
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIdRuleSet()
    {
        return $this->idRuleSet;
    }
}
