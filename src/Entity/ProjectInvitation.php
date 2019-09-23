<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\BlamableAddedTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_client", "project"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectInvitationRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectInvitation
{
    use TimestampableAddedOnlyTrait;
    use BlamableAddedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients", inversedBy="projectInvitations")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectInvitations")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="project", referencedColumnName="id", nullable=false)
     * })
     */
    private $project;

    /**
     * @ORM\Column(type="boolean")
     */
    private $finished = false;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Clients|null
     */
    public function getClient(): ?Clients
    {
        return $this->client;
    }

    /**
     * @param Clients|null $Client
     *
     * @return ProjectInvitation
     */
    public function setClient(?Clients $Client): self
    {
        $this->client = $Client;

        return $this;
    }

    /**
     * @return int
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * @return ProjectInvitation
     */
    public function setFinished(): self
    {
        $this->finished = true;

        return $this;
    }

    /**
     * @return Project|null
     */
    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * @param Project|null $project
     *
     * @return ProjectInvitation
     */
    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }
}
