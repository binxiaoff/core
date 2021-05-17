<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\User;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:participationMember:read",
 *             "agency:projectMember:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {"agency:participationMember:create",  "agency:projectMember:write", "agency:projectMember:create", "user:create", "user:write"}
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
 *
 * @ORM\Table(name="agency_participation_member", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_user", "id_participation"})
 * })
 * @ORM\Entity
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
class ParticipationMember extends AbstractProjectMember
{
    /**
     * @ORM\ManyToOne(targetEntity=Participation::class, inversedBy="members")
     * @ORM\JoinColumn(name="id_participation", onDelete="CASCADE", nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:participationMember:read", "agency:participationMember:create"})
     *
     * @ApiProperty(readableLink=false)
     */
    private Participation $participation;

    public function __construct(Participation $participation, User $user)
    {
        parent::__construct($user);
        $this->participation = $participation;
    }

    public function getProject(): Project
    {
        return $this->participation->getProject();
    }

    public function getParticipation(): Participation
    {
        return $this->participation;
    }
}
