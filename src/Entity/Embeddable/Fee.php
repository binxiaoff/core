<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class Fee
{
    public const RATE_SCALE = 2;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=4, scale=4)
     */
    private $rate;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $isRecurring;

    /**
     * @return string|null
     */
    public function getRate(): ?string
    {
        return $this->rate;
    }

    /**
     * @param string $rate
     *
     * @return self
     */
    public function setRate(string $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     *
     * @return self
     */
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isRecurring(): ?bool
    {
        return $this->isRecurring;
    }

    /**
     * @param bool $isRecurring
     *
     * @return self
     */
    public function setIsRecurring(bool $isRecurring): self
    {
        $this->isRecurring = $isRecurring;

        return $this;
    }
}
