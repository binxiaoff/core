<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Unilend\Entity\Embeddable\Fee;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProjectFee")
 */
class ProjectFee
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const TYPE_PARTICIPATION  = 'participation';
    public const TYPE_ADMINISTRATIVE = 'administrative';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Fee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Fee")
     *
     * @Gedmo\Versioned
     */
    private $fee;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectFees")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * Initialise some object-value.
     */
    public function __construct()
    {
        $this->fee = new Fee();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     *
     * @return ProjectFee
     */
    public function setProject(Project $project): ProjectFee
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Fee|null
     */
    public function getFee(): Fee
    {
        return $this->fee;
    }

    /**
     * @param Fee $fee
     *
     * @return ProjectFee
     */
    public function setFee(Fee $fee): ProjectFee
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * @return array
     */
    public static function getFeeTypes(): array
    {
        return self::getConstants('TYPE_');
    }

    /**
     * @param int $value
     *
     * @return false|string
     */
    public static function getFeeTypeConstantKey(int $value)
    {
        return self::getConstantKey($value, 'TYPE_');
    }
}
