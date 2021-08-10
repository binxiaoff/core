<?php

declare(strict_types=1);

namespace KLS\Syndication\Entity\Embeddable;

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
class Offer
{
    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({"offer:read", "offer:write"})
     */
    protected NullableMoney $money;

    /**
     * @ORM\Column(name="added", type="datetime_immutable", nullable=true)
     *
     * @Groups({"offer:read"})
     */
    protected ?DateTimeImmutable $added = null;

    /**
     * @throws Exception
     */
    public function __construct(?NullableMoney $money = null)
    {
        $this->money = new NullableMoney();

        if ($money && $money->isValid()) {
            $this->money = $money;
            $this->added = new DateTimeImmutable();
        }
    }

    public function getMoney(): NullableMoney
    {
        return $this->money;
    }

    public function getAdded(): ?DateTimeImmutable
    {
        return $this->added;
    }

    public function isValid(): bool
    {
        return $this->money->isValid();
    }
}
