<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The annotation "AttributeOverride" cannot be used for an embeddable class, because of the bug mentioned here:
 * https://github.com/doctrine/orm/pull/6151#issuecomment-271432401 .
 *
 * @ORM\Embeddable
 */
class NullableLendingRate extends LendingRate
{
    /**
     * @var string
     *
     * @ORM\Column(length=20, nullable=true)
     */
    protected $indexType;

    /**
     * The margin to be added on the indexed rate.
     *
     * @var string
     *
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\Range(min="0", max="1")
     */
    protected $margin;

    /**
     * @param string|null $indexType
     *
     * @return self
     */
    public function setIndexType(?string $indexType)
    {
        $this->indexType = $indexType;

        return $this;
    }

    /**
     * @param string|null $margin
     *
     * @return self
     */
    public function setMargin(?string $margin)
    {
        $this->margin = $margin;

        return $this;
    }
}
