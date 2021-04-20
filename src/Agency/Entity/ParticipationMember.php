<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Core\Entity\User;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:participationMember:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {"agency:participationMember:create", "agency:participationMember:write", "user:create", "user:write"}
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
class ParticipationMember
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    public const TYPE_BACK_OFFICE = 'back_office';
    public const TYPE_WAIVER      = 'waiver';
    public const TYPE_LEGAL       = 'legal';

    /**
     * @Groups({"agency:participationMember:read", "agency:participationMember:write"})
     *
     * @Assert\Length(max=200)
     *
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    protected ?string $projectFunction;

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

    /**
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
     * @ORM\Column(type="string", nullable=true, length=40)
     *
     * @Assert\Choice({ParticipationMember::TYPE_BACK_OFFICE, ParticipationMember::TYPE_LEGAL, ParticipationMember::TYPE_WAIVER})
     *
     * @Groups({"agency:participationMember:read", "agency:participationMember:create"})
     */
    private ?string $type;

    public function __construct(Participation $participation, User $user)
    {
        $this->participation = $participation;
        $this->added         = new DateTimeImmutable();
        $this->user          = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getProject(): Project
    {
        return $this->participation->getProject();
    }

    public function getParticipation(): Participation
    {
        return $this->participation;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): ParticipationMember
    {
        $this->type = $type;

        return $this;
    }

    public function getProjectFunction(): ?string
    {
        return $this->projectFunction;
    }

    public function setProjectFunction(?string $projectFunction): ParticipationMember
    {
        $this->projectFunction = $projectFunction;

        return $this;
    }
}
