<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\IdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="agency_project_status_history", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_project", "status"})
 * })
 *
 * @UniqueEntity(fields={"project", "status"})
 */
class ProjectStatusHistory
{
    use BlamableAddedTrait;
    use IdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Agency\Entity\Project", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     */
    private Project $project;

    /**
     * @ORM\Column(type="integer")
     *
     * @Groups({"agency:projectStatus:create"})
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback={Project::class, "getAvailableStatuses"})
     */
    private int $status;

    public function __construct(Project $project, Staff $addedBy)
    {
        $this->project = $project;
        $this->status  = $project->getCurrentStatus();
        $this->addedBy = $addedBy;
        $this->added   = new DateTimeImmutable();
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
