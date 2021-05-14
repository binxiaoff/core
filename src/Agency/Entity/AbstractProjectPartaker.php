<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Validator\Constraints\Siren as AssertSiren;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractProjectPartaker
{
    /**
     * @ORM\Column(type="string", length=9)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="9")
     *
     * @AssertSiren
     *
     * @Groups({"agency:projectPartaker:read", "agency:projectPartaker:write"})
     */
    private string $matriculationNumber;

    /**
     * @Assert\Valid
     * @Assert\NotBlank
     *
     * @Groups({"agency:projectPartaker:read", "agency:projectPartaker:write"})
     *
     * @ORM\Embedded(class=Money::class)
     */
    private Money $capital;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     *
     * @Assert\Length(max="9")
     *
     * @Groups({"agency:projectPartaker:read", "agency:projectPartaker:write"})
     */
    private ?string $rcs;

    public function __construct(string $matriculationNumber, Money $capital)
    {
        $this->matriculationNumber = $matriculationNumber;
        $this->capital             = $capital;
        $this->rcs                 = null;
    }

    public function getMatriculationNumber(): string
    {
        return $this->matriculationNumber;
    }

    public function getCapital(): Money
    {
        return $this->capital;
    }

    public function getRcs(): ?string
    {
        return $this->rcs;
    }

    public function setMatriculationNumber(string $matriculationNumber): AbstractProjectPartaker
    {
        $this->matriculationNumber = $matriculationNumber;

        return $this;
    }

    public function setCapital(Money $capital): AbstractProjectPartaker
    {
        $this->capital = $capital;

        return $this;
    }

    public function setRcs(?string $rcs): AbstractProjectPartaker
    {
        $this->rcs = $rcs;

        return $this;
    }

    abstract public function getProject(): Project;
}
