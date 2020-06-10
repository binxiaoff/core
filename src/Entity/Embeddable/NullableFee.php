<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class NullableFee extends Fee
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     *
     * @Assert\NotBlank
     *
     * @Groups({"nullableFee:read", "nullableFee:write"})
     */
    protected $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"nullableFee:read", "nullableFee:write"})
     */
    protected $comment;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     *
     * @Assert\NotBlank
     *
     * @Groups({"nullableFee:read", "nullableFee:write"})
     */
    protected $rate;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $recurring;

    /**
     * @param string $rate
     * @param string $type
     */
    public function __construct(?string $rate, ?string $type)
    {
        $this->rate = $rate;
        $this->type = $type;

        if ($rate && $type) {
            parent::__construct($rate, $type);
        }
    }
}
