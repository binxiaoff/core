<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\User;
use Unilend\Core\Validator\Constraints\Rcs as AssertRcs;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:borrower:read",
 *             "money:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:borrower:create",
 *                     "agency:borrower:write",
 *                     "money:write",
 *                     "agency:borrowerMember:create",
 *                     "agency:borrowerMember:write",
 *                     "user:create",
 *                     "user:write"
 *                 }
 *             },
 *             "security_post_denormalize": "is_granted('create', object)",
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {"agency:borrower:read", "money:read"}
 *             },
 *             "security": "is_granted('view', object)",
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)",
 *         },
 *         "patch": {
 *             "denormalization_context": {
 *                 "groups": {"agency:borrower:update", "agency:borrower:write", "money:write"}
 *             },
 *             "security_post_denormalize": "is_granted('edit', object)",
 *         }
 *     }
 * )
 *
 * @ORM\Table(name="agency_borrower")
 * @ORM\Entity
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "agency:borrowerMember:read",
 *             "user:read"
 *         }
 *     }
 * )
 */
class Borrower
{
    use PublicizeIdentityTrait;
    use BlamableAddedTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Project", inversedBy="borrowers")
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create"})
     *
     * @ApiProperty(readableLink=false)
     */
    private Project $project;

    /**
     * @ORM\Column(type="string", length=100)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="100")
     *
     * @Groups({"agency:borrower:read", "agency:borrower:write"})
     */
    private string $corporateName;

    /**
     * @ORM\Column(type="string", length=100)
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:write"})
     */
    private string $legalForm;

    /**
     * @Assert\Valid
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:write"})
     *
     * @ORM\Embedded(class=Money::class)
     */
    private Money $capital;

    /**
     * @ORM\Column(type="string", length=100)
     *
     * @Assert\Length(max="100")
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:write"})
     */
    private string $headquarterAddress;

    /**
     * @ORM\Column(type="string", length=100)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="100")
     *
     * @AssertRcs
     *
     * @Groups({"agency:borrower:read", "agency:borrower:write"})
     */
    private string $matriculationNumber;

    /**
     * @ORM\OneToOne(targetEntity=BorrowerMember::class)
     * @ORM\JoinColumn(name="id_signatory", onDelete="SET NULL")
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getMembers")
     * @Assert\Valid
     *
     * @Groups({"agency:borrower:read", "agency:borrower:write"})
     */
    private ?BorrowerMember $signatory;

    /**
     * @ORM\OneToOne(targetEntity=BorrowerMember::class)
     * @ORM\JoinColumn(name="id_referent", onDelete="SET NULL")
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getMembers")
     * @Assert\Valid
     *
     * @Groups({"agency:borrower:read", "agency:borrower:write"})
     */
    private ?BorrowerMember $referent;

    /**
     * @var Collection|BorrowerMember[]
     *
     * @ORM\OneToMany(targetEntity=BorrowerMember::class, mappedBy="borrower", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getBorrower() === this")
     * })
     *
     * @Groups({"agency:borrower:read"})
     */
    private Collection $members;

    /**
     * @var Collection|BorrowerTrancheShare[]
     *
     * @ORM\OneToMany(targetEntity="BorrowerTrancheShare", mappedBy="borrower", orphanRemoval=true)
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getBorrower() === this")
     * })
     */
    private Collection $trancheShares;

    public function __construct(
        Project $project,
        Staff $addedBy,
        string $corporateName,
        string $legalForm,
        Money $capital,
        string $headquarterAddress,
        string $matriculationNumber
    ) {
        $this->project             = $project;
        $this->addedBy             = $addedBy;
        $this->corporateName       = $corporateName;
        $this->legalForm           = $legalForm;
        $this->capital             = $capital;
        $this->headquarterAddress  = $headquarterAddress;
        $this->matriculationNumber = $matriculationNumber;
        $this->members             = new ArrayCollection();
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getCorporateName(): string
    {
        return $this->corporateName;
    }

    public function setCorporateName(string $corporateName): Borrower
    {
        $this->corporateName = $corporateName;

        return $this;
    }

    public function getLegalForm(): string
    {
        return $this->legalForm;
    }

    public function setLegalForm(string $legalForm): Borrower
    {
        $this->legalForm = $legalForm;

        return $this;
    }

    public function getCapital(): Money
    {
        return $this->capital;
    }

    public function setCapital(Money $capital): Borrower
    {
        $this->capital = $capital;

        return $this;
    }

    public function getHeadquarterAddress(): string
    {
        return $this->headquarterAddress;
    }

    public function setHeadquarterAddress(string $headquarterAddress): Borrower
    {
        $this->headquarterAddress = $headquarterAddress;

        return $this;
    }

    public function getMatriculationNumber(): string
    {
        return $this->matriculationNumber;
    }

    public function setMatriculationNumber(string $matriculationNumber): Borrower
    {
        $this->matriculationNumber = $matriculationNumber;

        return $this;
    }

    public function getSignatory(): ?BorrowerMember
    {
        return $this->signatory;
    }

    public function setSignatory(BorrowerMember $signatory): Borrower
    {
        $this->signatory = $this->findMemberByUser($signatory->getUser()) ?? $signatory;
        $this->addMember($this->signatory);

        return $this;
    }

    public function getReferent(): ?BorrowerMember
    {
        return $this->referent;
    }

    public function setReferent(BorrowerMember $referent): Borrower
    {
        $this->referent = $this->findMemberByUser($referent->getUser()) ?? $referent;
        $this->addMember($this->referent);

        return $this;
    }

    /**
     * @return array|BorrowerMember[]
     */
    public function getMembers(): array
    {
        return $this->members->toArray();
    }

    public function addMember(BorrowerMember $member): Borrower
    {
        if (null === $this->findMemberByUser($member->getUser())) {
            $this->members[] = $member;
        }

        return $this;
    }

    public function removeMember(BorrowerMember $member): Borrower
    {
        if ($this->members->removeElement($member)) {
            if ($this->referent === $member) {
                $this->referent = null;
            }

            if ($this->signatory === $member) {
                $this->signatory = null;
            }
        }

        return $this;
    }

    public function findMemberByUser(User $user): ?BorrowerMember
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return $member;
            }
        }

        return null;
    }
}
