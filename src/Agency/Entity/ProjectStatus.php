<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="agency_project_status", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_project", "status"})
 * })
 *
 * @UniqueEntity(fields={"project", "status"})
 */
class ProjectStatus
{
    use BlamableAddedTrait;

    public const DRAFT = 10;

    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false, unique=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Project", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     */
    private Project $project;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Groups({"projectStatus:create"})
     */
    private int $status;

    /**
     * @param Project $project
     * @param Staff   $addedBy
     * @param int     $status
     */
    public function __construct(Project $project, Staff $addedBy, int $status)
    {
        $this->project = $project;
        $this->status  = $status;
        $this->addedBy = $addedBy;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }
}
