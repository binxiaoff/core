<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_client", "invited_by", "project"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectInvitationRepository")
 */
class ProjectInvitation
{
    public const STATUS_SENT   = 0;
    public const STATUS_FINISH = 1;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
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
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients", inversedBy="hasInvited")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="invited_by", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $invitedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectInvitations")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="project", referencedColumnName="id", nullable=false)
     * })
     */
    private $project;

    /**
     * @ORM\Column(type="datetime")
     */
    private $added;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->added = new DateTime();
    }

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
     * @return Clients|null
     */
    public function getInvitedBy(): ?Clients
    {
        return $this->invitedBy;
    }

    /**
     * @param Clients|null $invitedBy
     *
     * @return ProjectInvitation
     */
    public function setInvitedBy(?Clients $invitedBy): self
    {
        $this->invitedBy = $invitedBy;

        return $this;
    }

    /**
     * @param \DateTimeInterface $added
     *
     * @return ProjectInvitation
     */
    public function setAdded(\DateTimeInterface $added): self
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return ProjectInvitation
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

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

    /**
     * @return \DateTimeInterface|null
     */
    private function getAdded(): ?\DateTimeInterface
    {
        return $this->added;
    }
}
