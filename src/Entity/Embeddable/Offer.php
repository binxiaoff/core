<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class Offer
{
    /**
     * @var NullableMoney
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableMoney")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({"offer:read", "offer:write"})
     */
    protected $money;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(name="added", type="datetime_immutable", nullable=true)
     *
     * @Groups({"offer:read"})
     */
    protected $added;

    /**
     * @param NullableMoney|null $money
     */
    public function __construct(?NullableMoney $money = null)
    {
        $this->money = $money ?? new NullableMoney();
        if ($this->money->getAmount()) {
            $this->added = new DateTimeImmutable();
        }
    }

    /**
     * @return NullableMoney
     */
    public function getMoney(): NullableMoney
    {
        return $this->money;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getAdded(): ?DateTimeImmutable
    {
        return $this->added;
    }
}
