<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Closure;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\EquivalenceCheckerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:borrowerTrancheShare:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *     },
 *     collectionOperations={},
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
class BorrowerTrancheShare implements EquivalenceCheckerInterface
{
    use PublicizeIdentityTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="KLS\Syndication\Agency\Entity\Borrower",
     *     cascade={"persist"}, inversedBy="trancheShares"
     * )
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
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Agency\Entity\Tranche", inversedBy="borrowerShares")
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
     * @ORM\Column(type="string", length=40, nullable=true)
     *
     * @Groups({"agency:borrowerTrancheShare:read", "agency:borrowerTrancheShare:write"})
     *
     * @Assert\Length(max=40)
     */
    private ?string $guaranty;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\Money")
     *
     * @Groups({"agency:borrowerTrancheShare:read", "agency:borrowerTrancheShare:write"})
     *
     * @Assert\NotBlank
     * @Assert\Valid
     */
    private Money $share;

    public function __construct(Borrower $borrower, Tranche $tranche, Money $share, string $guaranty = null)
    {
        $this->borrower = $borrower;
        $this->tranche  = $tranche;
        $this->guaranty = $guaranty;
        $this->share    = $share;
    }

    public function getBorrower(): Borrower
    {
        return $this->borrower;
    }

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

    public function setGuaranty(?string $guaranty): BorrowerTrancheShare
    {
        $this->guaranty = $guaranty;

        return $this;
    }

    public function getShare(): Money
    {
        return $this->share;
    }

    public function setShare(Money $share): BorrowerTrancheShare
    {
        $this->share = $share;

        return $this;
    }

    public function getEquivalenceChecker(): Closure
    {
        $self = $this;

        return static function (int $key, BorrowerTrancheShare $bts) use ($self): bool {
            return $bts->getBorrower() === $self->getBorrower()
                && $bts->getTranche()  === $self->getTranche();
        };
    }
}
