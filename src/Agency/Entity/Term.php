<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="agency_term")
 */
class Term
{
    use PublicizeIdentityTrait;

    /**
     * @var Covenant
     *
     * @Assert\NotBlank
     *
     * @ORM\ManyToOne(targetEntity=Covenant::class, inversedBy="terms")
     * @ORM\JoinColumn(name="id_covenant", onDelete="CASCADE")
     */
    private Covenant $covenant;

    /**
     * @var DateTimeImmutable
     *
     * @Assert\NotBlank
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $start;

    /**
     * @var DateTimeImmutable
     *
     * @Assert\GreaterThan(propertyPath="start")
     * @Assert\NotBlank
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $end;

    /**
     * @param Covenant          $covenant
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     */
    public function __construct(Covenant $covenant, DateTimeImmutable $start, DateTimeImmutable $end)
    {
        $this->covenant = $covenant;
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @return Covenant
     */
    public function getCovenant(): Covenant
    {
        return $this->covenant;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getEnd(): DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * @param DateTimeImmutable $end
     *
     * @return Term
     */
    public function setEnd(DateTimeImmutable $end): Term
    {
        $this->end = $end;

        return $this;
    }
}
