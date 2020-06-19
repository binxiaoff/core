<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class Fee
{
    public const RATE_SCALE = 2;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     *
     * @Assert\NotBlank
     *
     * @Groups({"fee:read", "fee:write"})
     */
    protected $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"fee:read", "fee:write"})
     */
    protected $comment;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=5, scale=4)
     *
     * @Assert\Type("numeric")
     *
     * @Assert\NotBlank
     *
     * @Groups({"fee:read", "fee:write"})
     */
    protected $rate;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $recurring;

    /**
     * @param string $rate
     * @param string $type
     * @param bool   $recurring
     */
    public function __construct(string $rate, string $type, bool $recurring = false)
    {
        $this->rate      = $rate;
        $this->recurring = $recurring;
        $this->type      = $type;
    }

    /**
     * the type hint is nullable because the child classes have this property nullable.
     *
     * @return string
     */
    public function getRate(): ?string
    {
        return $this->rate;
    }

    /**
     * the type hint is nullable because the child classes have this property nullable.
     *
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * the type hint is nullable because the child classes have this property nullable.
     *
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * the type hint is nullable because the child classes have this property nullable.
     *
     * @return bool|null
     */
    public function isRecurring(): ?bool
    {
        return $this->recurring;
    }
}
