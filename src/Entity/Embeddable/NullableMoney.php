<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class NullableMoney extends Money
{
    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=15, scale=2, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\Positive
     *
     * @Groups({
     *     "project:create",
     *     "project:list",
     *     "project:view",
     *     "projectParticipation:list",
     *     "projectParticipation:create",
     *     "projectParticipation:update",
     *     "projectParticipation:view"
     * })
     */
    protected $amount;

    /**
     * 3 letter ISO 4217 code (Currency code).
     *
     * @var string
     *
     * @ORM\Column(type="string", length=3, nullable=true)
     *
     * @Assert\Currency
     *
     * @Groups({
     *     "project:create",
     *     "project:list",
     *     "project:view",
     *     "projectParticipation:list",
     *     "projectParticipation:create",
     *     "projectParticipation:update",
     *     "projectParticipation:view"
     * })
     */
    protected $currency;

    /**
     * @param string|null $currency
     * @param string|null $amount
     */
    public function __construct(?string $currency = null, ?string $amount = null)
    {
        $this->amount   = $amount;
        $this->currency = $currency;

        if ($amount && $currency) {
            parent::__construct($currency, $amount);
        }
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->currency && $this->amount;
    }
}
