<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectComment
{
    use TimestampableTrait;

    public const VISIBILITY_ALL = 1;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var ProjectComment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectComment", inversedBy="children")
     * @ORM\JoinColumn(name="id_parent", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectComment", mappedBy="parent")
     */
    private $children;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="comments")
     * @ORM\JoinColumn(name="id_project", referencedColumnName="id", nullable=false)
     */
    private $project;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     */
    private $client;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", length=16777215)
     */
    private $content;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $visibility;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return ProjectComment|null
     */
    public function getParent(): ?ProjectComment
    {
        return $this->parent;
    }

    /**
     * @param ProjectComment|null $parent
     *
     * @return ProjectComment
     */
    public function setParent(?ProjectComment $parent): ProjectComment
    {
        $this->parent = $parent;

        return $this;
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
     * @return ProjectComment
     */
    public function setProject(Project $project): ProjectComment
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getClient(): Clients
    {
        return $this->client;
    }

    /**
     * @param Clients $client
     *
     * @return ProjectComment
     */
    public function setClient(Clients $client): ProjectComment
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return ProjectComment
     */
    public function setContent(string $content): ProjectComment
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return int
     */
    public function getVisibility(): int
    {
        return $this->visibility;
    }

    /**
     * @param int $visibility
     *
     * @return ProjectComment
     */
    public function setVisibility(int $visibility): ProjectComment
    {
        $this->visibility = $visibility;

        return $this;
    }
}
