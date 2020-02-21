<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\Fee;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProjectParticipationFee")
 */
class ProjectParticipationFee
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const TYPE_PARTICIPATION = 'participation';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     *
     * @Groups({"projectParticipationFee:read"})
     */
    private $id;

    /**
     * @var Fee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Fee")
     *
     * @Gedmo\Versioned
     *
     * @Assert\Valid
     *
     * @Groups({
     *     "projectParticipationFee:read",
     *     "projectParticipationFee:write"
     * })
     */
    private $fee;

    /**
     * @var ProjectParticipation
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="projectParticipationFee")
     * @ORM\JoinColumn(name="id_project_participation", nullable=false, onDelete="CASCADE")
     *
     * @Assert\Valid
     *
     * @Groups({"projectParticipationFee:create"})
     */
    private $projectParticipation;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Fee                  $fee
     *
     * @throws Exception
     */
    public function __construct(ProjectParticipation $projectParticipation, Fee $fee)
    {
        $this->projectParticipation = $projectParticipation;
        $this->fee                  = $fee;
        $this->added                = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Fee
     */
    public function getFee(): Fee
    {
        return $this->fee;
    }

    /**
     * @return ProjectParticipation
     */
    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    /**
     * @return array
     */
    public static function getFeeTypes(): array
    {
        return self::getConstants('TYPE_');
    }

    /**
     * @return string
     *
     * @Assert\Choice(callback="getFeeTypes")
     */
    public function getFeeType(): string
    {
        return $this->fee->getType();
    }

    /**
     * @param Fee $fee
     *
     * @return ProjectParticipationFee
     */
    public function setFee(Fee $fee): ProjectParticipationFee
    {
        $this->fee = $fee;

        return $this;
    }
}
