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
     * @Groups({
     *     "project:view",
     *     "projectParticipation:create",
     *     "projectParticipation:list",
     *     "tranche:view",
     *     "tranche:create",
     *     "tranche:update",
     *     "projectParticipationFee:create",
     *     "projectParticipationFee:update"
     * })
     */
    private $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({
     *     "project:view",
     *     "projectParticipation:create",
     *     "projectParticipation:list",
     *     "tranche:view",
     *     "tranche:create",
     *     "tranche:update",
     *     "projectParticipationFee:create",
     *     "projectParticipationFee:update"
     * })
     */
    private $comment;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=4, scale=4)
     *
     * @Assert\Type("numeric")
     *
     * @Assert\NotBlank
     *
     * @Groups({
     *     "project:view",
     *     "projectParticipation:create",
     *     "projectParticipation:list",
     *     "tranche:view",
     *     "tranche:create",
     *     "tranche:update",
     *     "projectParticipationFee:create",
     *     "projectParticipationFee:update"
     * })
     */
    private $rate;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({
     *     "project:view",
     *     "projectParticipation:create",
     *     "projectParticipation:list",
     *     "tranche:view",
     *     "tranche:create",
     *     "tranche:update",
     *     "projectParticipationFee:create",
     *     "projectParticipationFee:update"
     * })
     */
    private $recurring;

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
     * @return string|null
     */
    public function getRate(): string
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
    public function isRecurring(): bool
    {
        return $this->recurring;
    }

    /**
     * @param bool $recurring
     *
     * @return self
     */
    public function setRecurring(bool $recurring): self
    {
        $this->recurring = $recurring;

        return $this;
    }
}
