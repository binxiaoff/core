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
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false, unique=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Project", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     */
    private Project $project;

    /**
     * @ORM\Column(type="integer")
     *
     * @Groups({"agency:projectStatus:create"})
     */
    private int $status;

    public function __construct(Project $project, Staff $addedBy, int $status)
    {
        $this->project = $project;
        $this->status  = $status;
        $this->addedBy = $addedBy;
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
