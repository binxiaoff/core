<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {"agency:borrowerTrancheShare:read"}
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *         }
 *     },
 *     collectionOperations={}
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="agency_borrower_tranche_share", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_borrower", "id_tranche"})
 * })
 *
 * @UniqueEntity(fields={"borrower", "tranche"})
 *
 * @Assert\Expression("this.getBorrower().getProject() === this.getTranche().getProject()")
 */
class BorrowerTrancheShare
{
    use PublicizeIdentityTrait;

    /**
     * @var Borrower
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Borrower", inversedBy="trancheShares")
     * @ORM\JoinColumn(name="id_borrower", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"agency:borrowerTrancheShare:read", "agency:borrowerTrancheShare:write"})
     *
     * @Assert\NotBlank
     *
     * @ApiProperty(readableLink=false)
     */
    private Borrower $borrower;

    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Tranche", inversedBy="borrowerShares")
     * @ORM\JoinColumn(name="id_tranche", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"agency:borrowerTrancheShare:read", "agency:borrowerTrancheShare:write"})
     *
     * @Assert\NotBlank
     *
     * @ApiProperty(readableLink=false)
     */
    private Tranche $tranche;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=40, nullable=true)
     *
     * @Groups({"agency:borrowerTrancheShare:read", "agency:borrowerTrancheShare:write"})
     *
     * @Assert\Length(max=40)
     */
    private ?string $guaranty;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Groups({"agency:borrowerTrancheShare:read", "agency:borrowerTrancheShare:write"})
     *
     * @Assert\NotBlank
     * @Assert\Valid
     */
    private Money $share;

    /**
     * @param Borrower    $borrower
     * @param Tranche     $tranche
     * @param Money       $share
     * @param string|null $guaranty
     */
    public function __construct(Borrower $borrower, Tranche $tranche, Money $share, string $guaranty = null)
    {
        $this->borrower = $borrower;
        $this->tranche  = $tranche;
        $this->guaranty = $guaranty;
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
    public function getGuaranty(): ?string
    {
        return $this->guaranty;
    }

    /**
     * @param string|null $guaranty
     *
     * @return BorrowerTrancheShare
     */
    public function setGuaranty(?string $guaranty): BorrowerTrancheShare
    {
        $this->guaranty = $guaranty;

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
