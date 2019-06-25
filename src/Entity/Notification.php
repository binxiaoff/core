<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Notification
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const TYPE_ACCOUNT_CREATED       = 1;
    public const TYPE_PROJECT_REQUEST       = 2;
    public const TYPE_PROJECT_PUBLICATION   = 3;
    public const TYPE_BID_SUBMITTED_BIDDER  = 4;
    public const TYPE_BID_SUBMITTED_LENDERS = 5;
    public const TYPE_PROJECT_COMMENT_ADDED = 6;

    public const STATUS_READ   = 1;
    public const STATUS_UNREAD = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint")
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $client;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Project")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", referencedColumnName="id")
     * })
     */
    private $project;

    /**
     * @var Bids
     *
     * @ORM\ManyToOne(targetEntity="Bids")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_bid", referencedColumnName="id_bid")
     * })
     */
    private $bid;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $type
     *
     * @return Notification
     */
    public function setType(int $type): Notification
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $status
     *
     * @return Notification
     */
    public function setStatus(int $status): Notification
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param Clients $client
     *
     * @return Notification
     */
    public function setClient(Clients $client): Notification
    {
        $this->client = $client;

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
     * @param Project|null $project
     *
     * @return Notification
     */
    public function setProject(?Project $project): Notification
    {
        $this->project = $project;

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
     * @param Bids|null $bid
     *
     * @return Notification
     */
    public function setBid(?Bids $bid): Notification
    {
        $this->bid = $bid;

        return $this;
    }

    /**
     * @return Bids|null
     */
    public function getBid(): ?Bids
    {
        return $this->bid;
    }
}
