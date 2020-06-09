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
     * @Groups({"nullablefee:read", "nullablefee:write"})
     */
    private $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"nullablefee:read", "nullablefee:write"})
     */
    private $comment;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     *
     * @Assert\NotBlank
     *
     * @Groups({"fee:read", "fee:write"})
     */
    private $rate;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $recurring;

    /**
     * @param string $rate
     * @param string $type
     * @param bool   $recurring
     */
    public function __construct(?string $rate, ?string $type, ?bool $recurring)
    {
        $this->rate      = $rate;
        $this->recurring = $recurring;
        $this->type      = $type;

        if ($rate && $type && $recurring) {
            parent::__construct($rate, $type, $recurring);
        }
    }
}
