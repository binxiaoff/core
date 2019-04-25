<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 */
class InterestRateIndexType
{
    use TimestampableAddedOnlyTrait;

    const TYPE_FIXED_RATE = 1;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(length=30, unique=true)
     */
    private $label;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return InterestRateIndexType
     */
    public function setLabel(string $label): InterestRateIndexType
    {
        $this->label = $label;

        return $this;
    }
}
