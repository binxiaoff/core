<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Entity\Embeddable;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use KLS\Core\Entity\Embeddable\NullableMoney;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class OfferWithFee extends Offer
{
    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\NotBlank(allowNull=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"offerWithFee:read", "offerWithFee:write"})
     */
    protected ?string $feeRate = null;

    /**
     * @throws Exception
     */
    public function __construct(?NullableMoney $money = null, ?string $feeRate = null)
    {
        $this->feeRate = $feeRate;

        if ($feeRate) {
            $this->added = new DateTimeImmutable();
        }

        parent::__construct($money);
    }

    public function getFeeRate(): ?string
    {
        return $this->feeRate;
    }

    public function isValid(): bool
    {
        return parent::isValid() && null !== $this->feeRate;
    }
}
