<?php
declare(strict_types=1);

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\Entity\Traits\Timestampable;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class FeeType
{
    use Timestampable;

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
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $isRecurring;

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
     * @return FeeType
     */
    public function setLabel(string $label): FeeType
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

    /**
     * @param bool $isRecurring
     *
     * @return FeeType
     */
    public function setIsRecurring(bool $isRecurring): FeeType
    {
        $this->isRecurring = $isRecurring;

        return $this;
    }

    public function __toString()
    {
        return $this->getLabel();
    }
}
