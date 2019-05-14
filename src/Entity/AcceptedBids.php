<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Table(name="accepted_bids", uniqueConstraints={@ORM\UniqueConstraint(columns={"id_bid", "id_loan"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\AcceptedBidsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AcceptedBids
{
    use TimestampableTrait;

    /**
     * @var Bids
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Bids")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_bid", referencedColumnName="id_bid", nullable=false)
     * })
     */
    private $bid;

    /**
     * @var Loans
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Loans")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_loan", referencedColumnName="id_loan")
     * })
     */
    private $loan;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Money")
     */
    private $money;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    public function __construct()
    {
        $this->money = new Money();
    }

    /**
     * @param Bids $bid
     *
     * @return AcceptedBids
     */
    public function setBid(Bids $bid): AcceptedBids
    {
        $this->bid = $bid;

        return $this;
    }

    /**
     * @return Bids|null
     */
    public function getBid(): ?Bids
    {
        return $this->bid;
    }

    /**
     * @param Loans|null $loan
     *
     * @return AcceptedBids
     */
    public function setLoan(?Loans $loan): AcceptedBids
    {
        $this->loan = $loan;

        return $this;
    }

    /**
     * @return Loans|null
     */
    public function getLoan(): ?Loans
    {
        return $this->loan;
    }

    /**
     * @return Money
     */
    public function getMoney(): Money
    {
        return $this->money;
    }

    /**
     * @param Money $money
     *
     * @return AcceptedBids
     */
    public function setMoney(Money $money): AcceptedBids
    {
        $this->money = $money;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
