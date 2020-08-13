<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class OfferWithFee extends Offer
{
    /**
     * @var string|null
     *
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
     * @param NullableMoney|null $money
     * @param string|null        $feeRate
     *
     * @throws Exception
     */
    public function __construct(?NullableMoney $money = null, ?string $feeRate = null)
    {
        $this->feeRate = $feeRate;
        parent::__construct($money);
    }

    /**
     * @return string|null
     */
    public function getFeeRate(): ?string
    {
        return $this->feeRate;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return parent::isValid() && $this->feeRate !== null;
    }
}
