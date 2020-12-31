<?php

declare(strict_types=1);

namespace Unilend\Syndication\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Interfaces\{StatusInterface, TraceableStatusAwareInterface};
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableAddedOnlyTrait};
use Unilend\Core\Entity\User;
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"projectParticipationStatus:read", "timestampable:read"}},
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {"groups": {"projectParticipationStatus:create"}},
 *             "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="syndication_project_participation_status",
 *     indexes={@ORM\Index(columns={"status", "id_project_participation"})}
 * )
 *
 * @Assert\Callback(
 *     callback={"Unilend\Core\Validator\Constraints\TraceableStatusValidator", "validate"},
 *     payload={ "path": "status" }
 * )
 */
class ProjectParticipationStatus implements StatusInterface
{
    use PublicizeIdentityTrait;
    use ConstantsAwareTrait;
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;

    public const STATUS_CREATED            = 10;
    public const STATUS_COMMITTEE_PENDED   = 20;
    public const STATUS_COMMITTEE_ACCEPTED = 30;

    public const STATUS_ARCHIVED_BY_ARRANGER    = -10;
    public const STATUS_ARCHIVED_BY_PARTICIPANT = -20;
    public const STATUS_COMMITTEE_REJECTED      = -30;

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Syndication\Entity\ProjectParticipation", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_project_participation", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"projectParticipationStatus:create"})
     */
    private ProjectParticipation $projectParticipation;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Choice(callback="getPossibleStatuses")
     * @Assert\Expression(
     *     "this.isStatusValid()",
     *     message="ProjectParticipationStatus.status.invalid"
     * )
     *
     * @Groups({"projectParticipationStatus:read", "projectParticipationStatus:create"})
     */
    private int $status;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param int                  $status
     * @param Staff                $addedBy
     *
     * @throws Exception
     */
    public function __construct(ProjectParticipation $projectParticipation, int $status, Staff $addedBy)
    {
        $this->projectParticipation = $projectParticipation;
        $this->status               = $status;
        $this->addedBy              = $addedBy;
        $this->added                = new DateTimeImmutable();
    }

    /**
     * @return ProjectParticipation
     */
    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return TraceableStatusAwareInterface|ProjectParticipation
     */
    public function getAttachedObject()
    {
        return $this->getProjectParticipation();
    }

    /**
     * @return array|string[]
     */
    public static function getPossibleStatuses(): array
    {
        return static::getConstants('STATUS_');
    }

    /**
     * Used in an expression constraints:
     *  - only arrangeur can create a participation;
     *  - arranger can put the status "ARCHIVED_BY_ARRANGER";
     *  - arranger can put the status "COMMITTEE_*" only for non-user participant;
     *  - participant can put the status "COMMITTEE_*" and "ARCHIVED_BY_PARTICIPANT"
     * And after all, the participation of arranger cannot be archived.
     *
     * @return bool
     */
    public function isStatusValid(): bool
    {
        if ($this->getAddedBy()->getUser()->hasRole(User::ROLE_ADMIN)) {
            return true;
        }

        // Arranger participation is not archivable
        if (in_array($this->status, [self::STATUS_ARCHIVED_BY_ARRANGER, self::STATUS_ARCHIVED_BY_PARTICIPANT]) && $this->isArrangerParticipation()) {
            return false;
        }

        switch ($this->status) {
            case self::STATUS_CREATED:
            case self::STATUS_ARCHIVED_BY_ARRANGER:
                return $this->isArranger();
            case self::STATUS_COMMITTEE_PENDED:
            case self::STATUS_COMMITTEE_ACCEPTED:
            case self::STATUS_COMMITTEE_REJECTED:
                // see ProjectParticipationVoter::canParticipationOwnerEdit()
                return $this->isParticipationMember()
                    || (
                        $this->isArranger()
                        && (
                            (
                                $this->getProjectParticipation()->getParticipant()->isProspectAt($this->getAdded() ?? new DateTimeImmutable())
                                && $this->getProjectParticipation()->getParticipant()->isSameGroup($this->getAddedBy()->getCompany())
                            )
                            ||  $this->isArrangerParticipation()
                        )
                    );
            case self::STATUS_ARCHIVED_BY_PARTICIPANT:
                return $this->isParticipationMember();
            default:
                return false;
        }
    }

    /**
     * @return bool
     */
    private function isParticipationMember(): bool
    {
        // Include the archived member in the check, because the member can be archived after he/she had made the status change.
        // Checking if a member is archived or not is the job of the voter.
        foreach ($this->getProjectParticipation()->getProjectParticipationMembers() as $projectParticipationMember) {
            if ($this->getAddedBy() === $projectParticipationMember->getStaff()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isArranger(): bool
    {
        return $this->getAddedBy()->getCompany() === $this->projectParticipation->getProject()->getSubmitterCompany();
    }

    /**
     * @return bool
     */
    private function isArrangerParticipation(): bool
    {
        return $this->getProjectParticipation()->getParticipant() === $this->getProjectParticipation()->getProject()->getSubmitterCompany();
    }
}
