<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use InvalidArgumentException;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "projectStatus:read",
 *             "timestampable:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "projectStatus:create",
 *                 },
 *                 "openapi_definition_name": "write",
 *             },
 *         },
 *     },
 * )
 *
 * @ORM\Table(
 *     name="syndication_project_status",
 *     indexes={
 *         @ORM\Index(columns={"status"}, name="idx_project_status_status"),
 *         @ORM\Index(columns={"id_project"}, name="idx_project_status_id_project"),
 *         @ORM\Index(columns={"added_by"}, name="idx_project_status_added_by")
 *     }
 * )
 *
 * @Assert\Callback(
 *     callback={"KLS\Core\Validator\Constraints\TraceableStatusValidator", "validate"},
 *     payload={ "path": "status" }
 * )
 *
 * @ORM\Entity
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

    public const DISPLAYABLE_STATUSES = [
        self::STATUS_INTEREST_EXPRESSION,
        self::STATUS_PARTICIPANT_REPLY,
        self::STATUS_ALLOCATION,
        self::STATUS_CONTRACTUALISATION,
        self::STATUS_SYNDICATION_FINISHED,
    ];

    public const NON_EDITABLE_STATUSES = [
        self::STATUS_SYNDICATION_CANCELLED,
        self::STATUS_SYNDICATION_FINISHED,
    ];

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Arrangement\Entity\Project", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"projectStatus:create"})
     */
    private Project $project;

    /**
     * @ORM\Column(type="smallint")
     *
     * @Groups({"projectStatus:read", "projectStatus:create"})
     */
    private int $status;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @throws Exception
     */
    public function __construct(Project $project, int $status, Staff $addedBy)
    {
        if (!\in_array($status, static::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                \sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }

        $this->status  = $status;
        $this->project = $project;
        $this->added   = new DateTimeImmutable();
        $this->addedBy = $addedBy;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @Groups({"projectStatus:read"})
     */
    public function getHumanLabel(): string
    {
        $constantName          = \array_flip(static::getPossibleStatuses())[$this->status];
        $lowercaseConstantName = \mb_strtolower($constantName);

        // CamelCased status label
        $tokens = \explode('_', $lowercaseConstantName);

        // Remove "status" prefix
        \array_shift($tokens);

        // Capitalize all tokens
        $capitalizedTokens = \array_map('ucfirst', $tokens);

        // Join to create  PascalCase statusLabel
        $statusLabel = \implode('', $capitalizedTokens);

        // Return camelCase statusLabel
        return \lcfirst($statusLabel);
    }

    public static function getPossibleStatuses(): array
    {
        return static::getConstants('STATUS_');
    }

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
     * @param $payload
     */
    public function validateSyndicationAndParticipationTypes(ExecutionContextInterface $context, $payload)
    {
        if ($this->getStatus() > self::STATUS_DRAFT) {
            if (null === $this->getProject()->getSyndicationType()) {
                $context->buildViolation('Syndication.ProjectStatus.project.syndicationType.required')
                    ->atPath('project.syndicationType')
                    ->addViolation()
                ;
            }
            if (null === $this->getProject()->getParticipationType()) {
                $context->buildViolation('Syndication.ProjectStatus.project.participationType.required')
                    ->atPath('project.participationType')
                    ->addViolation()
                ;
            }
        }
    }

    /**
     * @Assert\Callback
     *
     * @param $payload
     */
    public function validateOversubscription(ExecutionContextInterface $context, $payload)
    {
        if ($this->getStatus() > self::STATUS_ALLOCATION && $this->getProject()->isOversubscribed()) {
            $context->buildViolation('Syndication.ProjectStatus.project.oversubscribed')
                ->atPath('status')
                ->addViolation()
            ;
        }
    }
}
