<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
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
     * @var DateTimeImmutable|null
     *
     * @Assert\GreaterThan(propertyPath="start")
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $sharingDate;

    /**
     * @param Covenant               $covenant
     * @param DateTimeImmutable      $start
     * @param DateTimeImmutable|null $end
     */
    public function __construct(Covenant $covenant, DateTimeImmutable $start, ?DateTimeImmutable $end = null)
    {
        $this->covenant = $covenant;
        $this->start = $start;
        $this->end = $end ?? $start->add(DateInterval::createFromDateString('+ ' . $covenant->getDelay() . ' days'));
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

    /**
     * @return DateTimeImmutable|null
     */
    public function getSharingDate(): ?DateTimeImmutable
    {
        return $this->sharingDate;
    }

    /**
     * @return bool
     */
    public function isShared(): bool
    {
        return null !== $this->sharingDate;
    }

    /**
     * @return Term
     *
     * @throws Exception
     */
    public function share(): Term
    {
        if ($this->isShared()) {
            return $this;
        }

        $this->sharingDate = new DateTimeImmutable();

        return $this;
    }
}
