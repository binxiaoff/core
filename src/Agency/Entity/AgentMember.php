<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\BlamableUserAddedTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\User;

/**
 * @ORM\Table(name="agency_agent_member")
 * @ORM\Entity
 */
class AgentMember
{
    use PublicizeIdentityTrait;
    use BlamableUserAddedTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Agent", inversedBy="members")
     * @ORM\JoinColumn(name="id_agent", nullable=false, onDelete="CASCADE")
     */
    private Agent $agent;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User")
     * @ORM\JoinColumn(name="id_user", nullable=false, onDelete="CASCADE")
     */
    private User $user;

    public function __construct(Agent $agent, User $user, User $addedBy)
    {
        $this->agent   = $agent;
        $this->user    = $user;
        $this->addedBy = $addedBy;
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
