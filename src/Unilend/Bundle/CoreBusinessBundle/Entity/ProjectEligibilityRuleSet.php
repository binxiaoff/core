<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectEligibilityRuleSet
 *
 * @ORM\Table(name="project_eligibility_rule_set", uniqueConstraints={@ORM\UniqueConstraint(name="unq_project_eligibility_rule_set_label", columns={"label"})}, indexes={@ORM\Index(name="idx_project_eligibility_rule_set_status", columns={"status"})})
 * @ORM\Entity
 */
class ProjectEligibilityRuleSet
{
    const STATUS_PENDING  = 0;
    const STATUS_ACTIVE   = 1;
    const STATUS_ARCHIVED = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=100, nullable=false)
     */
    private $label;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="int", length=4, nullable=false)
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
     * Set status
     *
     * @param int $status
     *
     * @return ProjectEligibilityRuleSet
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
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
}
