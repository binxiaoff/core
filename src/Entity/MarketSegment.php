<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\MarketSegmentRepository")
 */
class MarketSegment
{
    public const MARKET_SEGMENT_ID_PUBLIC_COLLECTIVITY     = 1;
    public const MARKET_SEGMENT_ID_ENERGY                  = 2;
    public const MARKET_SEGMENT_ID_CORPORATE               = 3;
    public const MARKET_SEGMENT_ID_LBO                     = 4;
    public const MARKET_SEGMENT_ID_REAL_ESTATE_DEVELOPMENT = 5;
    public const MARKET_SEGMENT_ID_INFRASTRUCTURE          = 6;


    public const MARKET_SEGMENT_LABEL_PUBLIC_COLLECTIVITY     = 'public_collectivity';
    public const MARKET_SEGMENT_LABEL_ENERGY                  = 'energy';
    public const MARKET_SEGMENT_LABEL_CORPORATE               = 'corporate';
    public const MARKET_SEGMENT_LABEL_LBO                     = 'lbo';
    public const MARKET_SEGMENT_LABEL_REAL_ESTATE_DEVELOPMENT = 'real_estate_development';
    public const MARKET_SEGMENT_LABEL_INFRASTRUCTURE          = 'infrastructure';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $label;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

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
