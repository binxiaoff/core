<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Embeddable
 */
class Offer
{
    use TimestampableAddedOnlyTrait;

    /**
     * @var NullableMoney
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"offer:read", "offer:write"})
     */
    protected $money;
    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="added", type="datetime_immutable", nullable=true)
     *
     * @Groups({"offer:read"})
     */
    protected $added;

    /**
     * @param NullableMoney $money
     */
    public function __construct(NullableMoney $money)
    {
        $this->money = $money;
        $this->added = new DateTimeImmutable();
    }

    /**
     * @return Money
     */
    public function getMoney(): Money
    {
        return $this->money;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }
}
