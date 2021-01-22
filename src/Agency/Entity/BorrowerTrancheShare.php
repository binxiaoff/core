<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "warenty:read"
 *         }
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "warranty:write"
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="agency_borrower_tranche_share", uniqueConstraints={
 *    @ORM\UniqueConstraint(columns={"id_borrower", "id_tranche"})
 * })
 *
 * @UniqueEntity(fields={"borrower", "tranche"})
 */
class BorrowerTrancheShare
{
    use PublicizeIdentityTrait;

    /**
     * @var Borrower
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Borrower", inversedBy="trancheShares")
     * @ORM\JoinColumn(name="id_borrower", onDelete="CASCADE")
     *
     * @Assert\NotBlank
     */
    private Borrower $borrower;

    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Tranche", inversedBy="borrowerShares")
     * @ORM\JoinColumn(name="id_tranche", onDelete="CASCADE")
     *
     * @Assert\NotBlank
     */
    private Tranche $tranche;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=40)
     *
     * @Assert\Length(max=40)
     * @Assert\NotBlank
     */
    private string $warranty;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     */
    private Money $share;

    /**
     * @param Borrower $borrower
     * @param Tranche  $tranche
     * @param string   $warranty
     * @param Money    $share
     */
    public function __construct(Borrower $borrower, Tranche $tranche, string $warranty, Money $share)
    {
        $this->borrower = $borrower;
        $this->tranche  = $tranche;
        $this->warranty = $warranty;
        $this->share    = $share;
    }

    /**
     * @return Borrower
     */
    public function getBorrower(): Borrower
    {
        return $this->borrower;
    }

    /**
     * @return Tranche
     */
    public function getTranche(): Tranche
    {
        return $this->tranche;
    }

    /**
     * @return string
     */
    public function getWarranty(): string
    {
        return $this->warranty;
    }

    /**
     * @param string $warranty
     *
     * @return BorrowerTrancheShare
     */
    public function setWarranty(string $warranty): BorrowerTrancheShare
    {
        $this->warranty = $warranty;

        return $this;
    }

    /**
     * @return Money
     */
    public function getShare(): Money
    {
        return $this->share;
    }

    /**
     * @param Money $share
     *
     * @return BorrowerTrancheShare
     */
    public function setShare(Money $share): BorrowerTrancheShare
    {
        $this->share = $share;

        return $this;
    }
}
