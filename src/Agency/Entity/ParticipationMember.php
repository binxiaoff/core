<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Entity\User;
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "timestampable:read",
 *             "agency:participationMember:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                  "groups": {"agency:participationMember:create", "user:create", "user:write"}
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
 */
class ParticipationMember
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    public const TYPE_BACK_OFFICE = 'back_office';
    public const TYPE_WAIVER = 'waiver';
    public const TYPE_LEGAL = 'legal';

    /**
     * @var Participation
     *
     * @ORM\ManyToOne(targetEntity=Participation::class, inversedBy="members")
     * @ORM\JoinColumn(name="id_participation", onDelete="CASCADE")

     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:participationMember:read", "agency:participationMember:create"})
     */
    private Participation $participation;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity=User::class, cascade={"persist"})
     * @ORM\JoinColumn(name="id_user")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:participationMember:read", "agency:participationMember:create"})
     */
    private User $user;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=40)
     *
     * @Assert\Choice({ParticipationMember::TYPE_BACK_OFFICE, ParticipationMember::TYPE_LEGAL, ParticipationMember::TYPE_WAIVER})
     */
    private ?string $type;

    /**
     * @param Participation $participation
     * @param User          $user
     */
    public function __construct(Participation $participation, User $user)
    {
        $this->participation = $participation;
        $this->added       = new DateTimeImmutable();
        $this->user        = $user;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return ParticipationMember
     */
    public function setUser(User $user): ParticipationMember
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->participation->getProject();
    }

    /**
     * @return Participation
     */
    public function getParticipation(): Participation
    {
        return $this->participation;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     *
     * @return ParticipationMember
     */
    public function setType(?string $type): ParticipationMember
    {
        $this->type = $type;

        return $this;
    }
}
