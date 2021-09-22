<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity\Traits;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait ReportingDatesTrait
{
    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"reportingDate:read", "reportingDate:write"})
     */
    protected ?DateTimeImmutable $reportingFirstDate;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"reportingDate:read", "reportingDate:write"})
     */
    protected ?DateTimeImmutable $reportingLastDate;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"reportingDate:read", "reportingDate:write"})
     */
    protected ?DateTimeImmutable $reportingValidationDate;

    public function getReportingFirstDate(): ?DateTimeImmutable
    {
        return $this->reportingFirstDate;
    }

    public function setReportingFirstDate(?DateTimeImmutable $reportingFirstDate): self
    {
        $this->reportingFirstDate = $reportingFirstDate;

        return $this;
    }

    public function getReportingLastDate(): ?DateTimeImmutable
    {
        return $this->reportingLastDate;
    }

    public function setReportingLastDate(?DateTimeImmutable $reportingLastDate): self
    {
        $this->reportingLastDate = $reportingLastDate;

        return $this;
    }

    public function getReportingValidationDate(): ?DateTimeImmutable
    {
        return $this->reportingValidationDate;
    }

    public function setReportingValidationDate(?DateTimeImmutable $reportingValidationDate): self
    {
        $this->reportingValidationDate = $reportingValidationDate;

        return $this;
    }
}
