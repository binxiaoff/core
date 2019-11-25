<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Interfaces\StatusInterface;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableAddedOnlyTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get": {"security": "is_granted('ROLE_ADMIN')"},
 *         "post": {"security_post_denormalize": "is_granted('edit', object.getProject())"}
 *     },
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object.getProject())"}
 *     }
 * )
 * @ORM\Table(
 *     name="project_status",
 *     indexes={
 *         @ORM\Index(columns={"status"}, name="idx_project_status_status"),
 *         @ORM\Index(columns={"id_project"}, name="idx_project_status_id_project"),
 *         @ORM\Index(columns={"added_by"}, name="idx_project_status_added_by")
 *     }
 * )
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectStatusRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;

    public const STATUS_REQUESTED           = 10;
    public const STATUS_PUBLISHED           = 20;
    public const STATUS_INTERESTS_COLLECTED = 30;
    public const STATUS_OFFERS_COLLECTED    = 40;
    public const STATUS_CONTRACTS_SIGNED    = 50;
    public const STATUS_REPAID              = 60;

    public const DISPLAYABLE_STATUS = [
        self::STATUS_PUBLISHED,
        self::STATUS_INTERESTS_COLLECTED,
        self::STATUS_OFFERS_COLLECTED,
        self::STATUS_CONTRACTS_SIGNED,
        self::STATUS_REPAID,
    ];

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_project", nullable=false)
     */
    private $project;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Groups({"projectParticipation:list", "project:view"})
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
     * @param Clients $addedBy
     *
     * @throws Exception
     */
    public function __construct(Project $project, int $status, Clients $addedBy)
    {
        if (!in_array($status, static::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }
        $this->status  = $status;
        $this->project = $project;
        $this->addedBy = $addedBy;
        $this->added   = new DateTimeImmutable();
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return int
     */
    public function getId(): int
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
     * @Groups({"projectParticipation:list"})
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
}
