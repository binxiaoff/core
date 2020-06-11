<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class NullableSimplifiedFee
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\NotBlank
     *
     * @Gedmo\Versioned
     *
     * @Groups({"nullableSimplifiedFee:read", "nullableSimplifiedFee:write"})
     */
    protected $rate;

    /**
     * @param string $rate
     */
    public function __construct(?string $rate = null)
    {
        $this->rate = $rate;
    }

    /**
     * @return string|null
     */
    public function getRate(): ?string
    {
        return $this->rate;
    }
}
