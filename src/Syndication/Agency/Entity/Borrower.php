<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Embeddable\NullableMoney;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:borrower:read",
 *             "nullableMoney:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:borrower:create",
 *                     "agency:borrower:write",
 *                     "agency:projectPartaker:write",
 *                     "nullableMoney:write",
 *                     "agency:borrowerMember:create",
 *                     "agency:borrowerMember:write",
 *                     "user:create",
 *                     "user:write",
 *                 },
 *                 "openapi_definition_name": "collection-post-write",
 *             },
 *         },
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)",
 *         },
 *         "patch": {
 *             "security_post_denormalize": "is_granted('edit', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:borrower:write",
 *                     "nullableMoney:write",
 *                     "agency:borrowerMember:create",
 *                     "agency:borrowerMember:write",
 *                     "money:write",
 *                     "user:create",
 *                     "user:write",
 *                 },
 *                 "openapi_definition_name": "item-patch-write",
 *             },
 *         },
 *     },
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
class Borrower extends AbstractProjectPartaker
{
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
     * @Groups({"agency:borrower:read", "agency:borrower:write"})
     */
    protected Collection $members;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Agency\Entity\Project", inversedBy="borrowers")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create"})
     */
    private Project $project;

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
        string $corporateName,
        string $legalForm,
        NullableMoney $capital,
        string $headOffice,
        string $matriculationNumber
    ) {
        parent::__construct($matriculationNumber, $capital);
        $this->project       = $project;
        $this->corporateName = $corporateName;
        $this->legalForm     = $legalForm;
        $this->headOffice    = $headOffice;
        $this->members       = new ArrayCollection();
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return Collection|BorrowerTrancheShare[]
     */
    public function getTrancheShares(): Collection
    {
        return $this->trancheShares;
    }

    /**
     * @param Collection|BorrowerTrancheShare[] $trancheShares
     *
     * @return Borrower
     */
    public function setTrancheShares($trancheShares)
    {
        $this->trancheShares = $trancheShares;

        return $this;
    }

    /**
     * @Groups({"agency:borrower:read"})
     */
    public function getBankInstitution(): ?string
    {
        return $this->bankInstitution;
    }

    /**
     * @Groups({"agency:borrower:write"})
     */
    public function setBankInstitution(?string $bankInstitution): AbstractProjectPartaker
    {
        $this->bankInstitution = $bankInstitution;

        return $this;
    }

    /**
     * @Groups({"agency:borrower:read"})
     */
    public function getBankAddress(): ?string
    {
        return $this->bankAddress;
    }

    /**
     * @Groups({"agency:borrower:write"})
     */
    public function setBankAddress(?string $bankAddress): AbstractProjectPartaker
    {
        $this->bankAddress = $bankAddress;

        return $this;
    }

    /**
     * @Groups({"agency:borrower:read"})
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    /**
     * @Groups({"agency:borrower:write"})
     */
    public function setBic(?string $bic): AbstractProjectPartaker
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * @Groups({"agency:borrower:read"})
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * @Groups({"agency:borrower:write"})
     */
    public function setIban(?string $iban): AbstractProjectPartaker
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * @Groups({"agency:borrower:read"})
     */
    public function getMatriculationNumber(): string
    {
        return $this->matriculationNumber;
    }

    /**
     * @Groups({"agency:borrower:write"})
     */
    public function setMatriculationNumber(string $matriculationNumber): AbstractProjectPartaker
    {
        $this->matriculationNumber = $matriculationNumber;

        return $this;
    }

    /**
     * @Groups({"agency:borrower:read"})
     */
    public function getCapital(): NullableMoney
    {
        return parent::getCapital();
    }

    /**
     * @Groups({"agency:borrower:write"})
     */
    public function setCapital(NullableMoney $capital): AbstractProjectPartaker
    {
        parent::setCapital($capital);

        return $this;
    }

    /**
     * @Groups({"agency:borrower:read"})
     */
    public function getRcs(): ?string
    {
        return $this->rcs;
    }

    /**
     * @Groups({"agency:borrower:write"})
     */
    public function setRcs(?string $rcs): AbstractProjectPartaker
    {
        $this->rcs = $rcs;

        return $this;
    }

    /**
     * @Groups({"agency:borrower:read"})
     */
    public function getCorporateName(): ?string
    {
        return $this->corporateName;
    }

    /**
     * @Groups({"agency:borrower:write"})
     */
    public function setCorporateName(?string $corporateName): AbstractProjectPartaker
    {
        $this->corporateName = $corporateName;

        return $this;
    }

    /**
     * @Groups({"agency:borrower:read"})
     */
    public function getHeadOffice(): ?string
    {
        return $this->headOffice;
    }

    /**
     * @Groups({"agency:borrower:write"})
     */
    public function setHeadOffice(?string $headOffice): AbstractProjectPartaker
    {
        $this->headOffice = $headOffice;

        return $this;
    }

    /**
     * @Groups({"agency:borrower:read"})
     */
    public function getLegalForm(): ?string
    {
        return $this->legalForm;
    }

    /**
     * @Groups({"agency:borrower:write"})
     */
    public function setLegalForm(?string $legalForm): AbstractProjectPartaker
    {
        $this->legalForm = $legalForm;

        return $this;
    }

    /**
     * @Groups({"agency:borrower:read"})
     */
    public function isCompleted()
    {
        return $this->getBankInstitution() && $this->getBankAddress() && $this->getIban() && $this->getBic();
    }
}
