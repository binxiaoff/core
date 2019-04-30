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
    private $indexType;

    /**
     * The margin to be added on the indexed rate.
     *
     * @var string
     *
     * @ORM\Column(type="decimal", precision=4, scale=2, nullable=true)
     *
     * @Assert\Range(min="0.01", max="99.99")
     */
    private $margin;
}
