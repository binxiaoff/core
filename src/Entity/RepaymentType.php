<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\Timestampable;

/**
 * @ORM\Table(name="repayment_type", uniqueConstraints={@ORM\UniqueConstraint(columns={"label", "periodicity"})})
 * @ORM\Entity
 */
class RepaymentType
{
    use Timestampable;

    public const REPAYMENT_TYPE_FIXED_CAPITAL = 'fixed_capital';
    public const REPAYMENT_TYPE_FIXED_PAYMENT = 'fixed_payment';
    public const REPAYMENT_TYPE_DEFERRED      = 'deferred';
    public const REPAYMENT_TYPE_IN_FINE       = 'in_fine';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191)
     */
    private $label;

    /**
     * @var int
     *
     * @ORM\Column(name="periodicity", type="integer")
     */
    private $periodicity;

    /**
     * @var int
     *
     * @ORM\Column(name="id_repayment_type", type="smallint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idRepaymentType;

    /**
     * @param string $label
     *
     * @return RepaymentType
     */
    public function setLabel(string $label): RepaymentType
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param int $periodicity
     *
     * @return RepaymentType
     */
    public function setPeriodicity(int $periodicity): RepaymentType
    {
        $this->periodicity = $periodicity;

        return $this;
    }

    /**
     * @return int
     */
    public function getPeriodicity(): int
    {
        return $this->periodicity;
    }

    /**
     * @return int
     */
    public function getIdRepaymentType(): int
    {
        return $this->idRepaymentType;
    }
}
