<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
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
 *              "denormalization_context": {
 *                  "groups": {
 *                      "agency:borrower:create",
 *                      "money:write",
 *                      "agency:borrowerMember:create",
 *                      "user:create",
 *                      "user:write"
 *                  }
 *              },
 *             "security_post_denormalize": "is_granted('create', object)",
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)",
 *          },
 *         "patch": {
 *              "denormalization_context": {
 *                  "groups": {"agency:borrower:update", "money:write"}
 *              },
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
     * @var Project
     *
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
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="100")
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:update"})
     */
    private string $corporateName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:update"})
     */
    private string $legalForm;

    /**
     * @var Money
     *
     * @Assert\Valid
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:update"})
     *
     * @ORM\Embedded(class=Money::class)
     */
    private Money $capital;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     *
     * @Assert\Length(max="100")
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:update"})
     */
    private string $headquarterAddress;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="100")
     *
     * @AssertRcs
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:update"})
     */
    private string $matriculationNumber;

    /**
     * @var BorrowerMember|null
     *
     * @ORM\ManyToOne(targetEntity=BorrowerMember::class)
     * @ORM\JoinColumn(name="id_signatory", onDelete="SET NULL")
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getMembers")
     * @Assert\Valid
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create"})
     */
    private ?BorrowerMember $signatory;

    /**
     * @var BorrowerMember|null
     *
     * @ORM\ManyToOne(targetEntity=BorrowerMember::class)
     * @ORM\JoinColumn(name="id_referent", onDelete="SET NULL")
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getMembers")
     * @Assert\Valid
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create"})
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
     *    @Assert\Expression("value.getBorrower() === this")
     * })
     */
    private Collection $trancheShares;

    /**
     * @param Project $project
     * @param Staff   $addedBy
     * @param string  $corporateName
     * @param string  $legalForm
     * @param Money   $capital
     * @param string  $headquarterAddress
     * @param string  $matriculationNumber
     */
    public function __construct(
        Project $project,
        Staff $addedBy,
        string $corporateName,
        string $legalForm,
        Money $capital,
        string $headquarterAddress,
        string $matriculationNumber
    ) {
        $this->project = $project;
        $this->addedBy = $addedBy;
        $this->corporateName = $corporateName;
        $this->legalForm = $legalForm;
        $this->capital = $capital;
        $this->headquarterAddress = $headquarterAddress;
        $this->matriculationNumber = $matriculationNumber;
        $this->members = new ArrayCollection();
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getCorporateName(): string
    {
        return $this->corporateName;
    }

    /**
     * @param string $corporateName
     *
     * @return Borrower
     */
    public function setCorporateName(string $corporateName): Borrower
    {
        $this->corporateName = $corporateName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLegalForm(): string
    {
        return $this->legalForm;
    }

    /**
     * @param string $legalForm
     *
     * @return Borrower
     */
    public function setLegalForm(string $legalForm): Borrower
    {
        $this->legalForm = $legalForm;

        return $this;
    }

    /**
     * @return Money
     */
    public function getCapital(): Money
    {
        return $this->capital;
    }

    /**
     * @param Money $capital
     *
     * @return Borrower
     */
    public function setCapital(Money $capital): Borrower
    {
        $this->capital = $capital;

        return $this;
    }

    /**
     * @return string
     */
    public function getHeadquarterAddress(): string
    {
        return $this->headquarterAddress;
    }

    /**
     * @param string $headquarterAddress
     *
     * @return Borrower
     */
    public function setHeadquarterAddress(string $headquarterAddress): Borrower
    {
        $this->headquarterAddress = $headquarterAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getMatriculationNumber(): string
    {
        return $this->matriculationNumber;
    }

    /**
     * @param string $matriculationNumber
     *
     * @return Borrower
     */
    public function setMatriculationNumber(string $matriculationNumber): Borrower
    {
        $this->matriculationNumber = $matriculationNumber;

        return $this;
    }

    /**
     * @return BorrowerMember
     */
    public function getSignatory(): BorrowerMember
    {
        return $this->signatory;
    }

    /**
     * @param BorrowerMember $signatory
     *
     * @return Borrower
     */
    public function setSignatory(BorrowerMember $signatory): Borrower
    {
        $this->signatory = $this->findMemberByUser($signatory->getUser()) ?? $signatory;
        $this->addMember($this->signatory);

        return $this;
    }

    /**
     * @return BorrowerMember
     */
    public function getReferent(): BorrowerMember
    {
        return $this->referent;
    }

    /**
     * @param BorrowerMember $referent
     *
     * @return Borrower
     */
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

    /**
     * @param BorrowerMember $member
     *
     * @return Borrower
     */
    public function addMember(BorrowerMember $member): Borrower
    {
        if (null === $this->findMemberByUser($member->getUser())) {
            $this->members[] = $member;
        }

        return $this;
    }

    /**
     * @param BorrowerMember $member
     *
     * @return Borrower
     */
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

    /**
     * @param User $user
     *
     * @return BorrowerMember|null
     */
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
