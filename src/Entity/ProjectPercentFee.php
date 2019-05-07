<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ProjectPercentFee
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var PercentFee
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\PercentFee", cascade={"persist", "remove"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_percent_fee", nullable=false)
     * })
     */
    private $percentFee;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectPercentFees")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     *
     * @return ProjectPercentFee
     */
    public function setProject(Project $project): ProjectPercentFee
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return PercentFee|null
     */
    public function getPercentFee(): ?PercentFee
    {
        return $this->percentFee;
    }

    /**
     * @param PercentFee $percentFee
     *
     * @return ProjectPercentFee
     */
    public function setPercentFee(PercentFee $percentFee): ProjectPercentFee
    {
        $this->percentFee = $percentFee;

        return $this;
    }
}
