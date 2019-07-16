<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class Attribute
{
    /**
     * @var string
     *
     * @ORM\Column(length=191)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(length=191)
     */
    private $value;

    /**
     * @param string|null $name
     * @param string|null $value
     */
    public function __construct(?string $name = null, ?string $value = null)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Attribute
     */
    public function setName(string $name): Attribute
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return Attribute
     */
    public function setValue(string $value): Attribute
    {
        $this->value = $value;

        return $this;
    }
}
