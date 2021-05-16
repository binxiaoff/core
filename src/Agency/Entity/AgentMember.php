<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\BlamableUserAddedTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\User;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:agentMember:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {"agency:agentMember:create", "user:create", "user:write"}
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *         }
 *     }
 * )
 * @ORM\Table(name="agency_agent_member", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_agent", "id_user"})
 * })
 * @ORM\Entity
 *
 * @UniqueEntity(fields={"agent", "user"})
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "user:read"
 *         }
 *     }
 * )
 */
class AgentMember
{
    use PublicizeIdentityTrait;
    use BlamableUserAddedTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Agent", inversedBy="members")
     * @ORM\JoinColumn(name="id_agent", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:agentMember:read", "agency:agentMember:create"})
     *
     * @ApiProperty(readableLink=false)
     */
    private Agent $agent;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User")
     * @ORM\JoinColumn(name="id_user", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:agentMember:read", "agency:agentMember:create"})
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
