<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\Entity\Traits\TimestampableAddedOnly;

/**
 * @ORM\Table
 * @ORM\Entity
 */
class InterestRateIndexType
{
    use TimestampableAddedOnly;
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
     * @ORM\Column(type="string", length=30)
     */
    private $name;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return InterestRateIndexType
     */
    public function setId(int $id): InterestRateIndexType
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return InterestRateIndexType
     */
    public function setName(string $name): InterestRateIndexType
    {
        $this->name = $name;

        return $this;
    }
}
