<?php

namespace Unilend\DataFixtures;

use Exception;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\Embeddable\NullableMoney;
use Unilend\Entity\Embeddable\Offer;
use Unilend\Entity\Embeddable\OfferWithFee;
use Unilend\Entity\Embeddable\RangedOfferWithFee;

/**
 * Helpers to generate offer objects
 */
trait OfferFixtureTrait
{

    /**
     * @param int|null $value
     *
     * @return Offer
     *
     * @throws Exception
     */
    public function createOffer(?int $value = null): Offer
    {
        return new Offer($this->createNullableMoney($value));
    }

    /**
     * @param int   $min
     * @param int   $max
     * @param float $rate
     *
     * @return RangedOfferWithFee
     */
    public function createRangedOffer(int $min, int $max, float $rate = 0.03): RangedOfferWithFee
    {
        return new RangedOfferWithFee(
            $this->createNullableMoney($min),
            $rate,
            $this->createNullableMoney($max)
        );
    }

    /**
     * @param int   $value
     * @param float $rate
     *
     * @return OfferWithFee
     *
     * @throws Exception
     */
    public function createOfferWithFee(int $value, float $rate = 0.03): OfferWithFee
    {
        return new OfferWithFee($this->createNullableMoney($value), $rate);
    }

    /**
     * @param int|null $value
     *
     * @return NullableMoney
     */
    public function createNullableMoney(?int $value = null): NullableMoney
    {
        return new NullableMoney('EUR', $value ?: $this->faker->numberBetween(1000000, 5000000));
    }

    /**
     * @param int|null $value
     *
     * @return Money
     */
    public function createMoney(?int $value = null): Money
    {
        return new Money('EUR', $value ?: $this->faker->numberBetween(1000000, 5000000));
    }
}
