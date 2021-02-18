<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\IdentityTrait;

/**
 * @ORM\Entity
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get"
 *     },
 *     itemOperations={
 *         "get"
 *     }
 * )
 *
 * @ORM\Table(name="core_market_segment")
 */
class MarketSegment
{
    use IdentityTrait;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private string $label;

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return MarketSegment
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
}
