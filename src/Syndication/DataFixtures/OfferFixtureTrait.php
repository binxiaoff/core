<?php

declare(strict_types=1);

namespace Unilend\Syndication\DataFixtures;

use Exception;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Syndication\Entity\Embeddable\Offer;
use Unilend\Syndication\Entity\Embeddable\OfferWithFee;
use Unilend\Syndication\Entity\Embeddable\RangedOfferWithFee;

/**
 * Helpers to generate offer objects.
 */
trait OfferFixtureTrait
{
    /**
     * @throws Exception
     */
    public function createOffer(?int $value = null): Offer
    {
        return new Offer($this->createNullableMoney($value));
    }

    /**
     * @throws Exception
     */
    public function createRangedOffer(int $min, int $max, float $rate = 0.03): RangedOfferWithFee
    {
        return new RangedOfferWithFee(
            $this->createNullableMoney($min),
            (string) $rate,
            $this->createNullableMoney($max)
        );
    }

    /**
     * @throws Exception
     */
    public function createOfferWithFee(int $value, float $rate = 0.03): OfferWithFee
    {
        return new OfferWithFee($this->createNullableMoney($value), (string) $rate);
    }

    public function createNullableMoney(?int $value = null): NullableMoney
    {
        return new NullableMoney('EUR', (string) $value ?: $this->faker->numberBetween(1000000, 5000000));
    }

    public function createMoney(?int $value = null): Money
    {
        return new Money('EUR', (string) $value ?: $this->faker->numberBetween(1000000, 5000000));
    }
}
