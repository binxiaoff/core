<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Entity\Interfaces\StatusInterface;
use Unilend\Entity\Interfaces\TraceableStatusAwareInterface;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableAddedOnlyTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"projectStatus:read", "timestampable:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"projectStatus:create"}}
 *         }
 *     }
 * )
 *
 * @ORM\Table(
 *     name="project_status",
 *     indexes={
 *         @ORM\Index(columns={"status"}, name="idx_project_status_status"),
 *         @ORM\Index(columns={"id_project"}, name="idx_project_status_id_project"),
 *         @ORM\Index(columns={"added_by"}, name="idx_project_status_added_by")
 *     }
 * )
 *
 * @Assert\Callback(
 *     callback={"Unilend\Validator\Constraints\TraceableStatusValidator", "validate"},
 *     payload={ "path": "status" }
 * )
 *
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectStatusRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;

    public const STATUS_SYNDICATION_CANCELLED = -99;
    public const STATUS_DRAFT                 = 10;
    public const STATUS_INTEREST_EXPRESSION   = 20;
    public const STATUS_PARTICIPANT_REPLY     = 30;
    public const STATUS_ALLOCATION            = 40;
    public const STATUS_CONTRACTUALISATION    = 50;
    public const STATUS_SYNDICATION_FINISHED  = 60;

    public const DISPLAYABLE_STATUS = [
        self::STATUS_INTEREST_EXPRESSION,
        self::STATUS_PARTICIPANT_REPLY,
        self::STATUS_ALLOCATION,
        self::STATUS_CONTRACTUALISATION,
        self::STATUS_SYNDICATION_FINISHED,
    ];

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"projectStatus:create"})
     */
    private $project;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Groups({"projectStatus:read", "projectStatus:create"})
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @param Project $project
     * @param int     $status
     * @param Staff   $addedBy
     *
     * @throws Exception
     */
    public function __construct(Project $project, int $status, Staff $addedBy)
    {
        if (!\in_array($status, static::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }

        $this->status  = $status;
        $this->project = $project;
        $this->added   = new DateTimeImmutable();
        $this->addedBy = $addedBy;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return string
     *
     * @Groups({"projectStatus:read"})
     */
    public function getHumanLabel(): string
    {
        return str_replace('status_', '', mb_strtolower(array_flip(static::getPossibleStatuses())[$this->status]));
    }

    /**
     * {@inheritdoc}
     */
    public static function getPossibleStatuses(): array
    {
        return static::getConstants('STATUS_');
    }

    /**
     * @param Project $project
     *
     * @return ProjectStatus
     */
    public function setProject(Project $project): ProjectStatus
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return TraceableStatusAwareInterface|Project
     */
    public function getAttachedObject()
    {
        return $this->getProject();
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     * @param                           $payload
     */
    public function validateSyndicationAndParticipationTypes(ExecutionContextInterface $context, $payload)
    {
        if ($this->getStatus() > self::STATUS_DRAFT) {
            if (null === $this->getProject()->getSyndicationType()) {
                $context->buildViolation('The syndication type of the project must be present to change its status.')
                    ->atPath('project.syndicationType')
                    ->addViolation()
                ;
            }
            if (null === $this->getProject()->getParticipationType()) {
                $context->buildViolation('The participation type of the project must be present to change its status.')
                    ->atPath('project.participationType')
                    ->addViolation()
                ;
            }
        }
    }
}
