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
use Unilend\Core\Entity\User;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:agentMember:read",
 *             "agency:projectMember:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {"agency:agentMember:create", "agency:projectMember:write", "agency:projectMember:write", "user:create", "user:write"}
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
class AgentMember extends AbstractProjectMember
{
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

    public function __construct(Agent $agent, User $user)
    {
        parent::__construct($user);
        $this->agent = $agent;
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }

    public function getProject(): Project
    {
        return $this->getAgent()->getProject();
    }
}
