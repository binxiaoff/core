<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\InvitationRepository")
 */
class Invitation
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
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients", inversedBy="invitations")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients", inversedBy="hasInvited")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="invited_by", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $invitedBy;

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
    public function getIdClient(): ?Clients
    {
        return $this->idClient;
    }

    /**
     * @param Clients|null $idClient
     *
     * @return Invitation
     */
    public function setIdClient(?Clients $idClient): self
    {
        $this->idClient = $idClient;

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
     * @return Invitation
     */
    public function setInvitedBy(?Clients $invitedBy): self
    {
        $this->invitedBy = $invitedBy;

        return $this;
    }

    /**
     * @param \DateTimeInterface $added
     *
     * @return Invitation
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
     * @return Invitation
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

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
