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

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:borrower:read",
 *             "money:read",
 *             "agency:projectPartaker:read",
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:borrower:create",
 *                     "agency:borrower:write",
 *                     "agency:projectPartaker:write",
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
 *             "security": "is_granted('view', object)",
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)",
 *         },
 *         "patch": {
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:borrower:update",
 *                     "agency:projectPartaker:write",
 *                     "agency:borrower:write",
 *                     "money:write"
 *                 }
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
 *             "user:read",
 *             "agency:projectMember:read"
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
     * @Groups({"agency:borrower:read"})
     */
    protected Collection $members;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Project", inversedBy="borrowers")
     * @ORM\JoinColumn(name="id_project", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create"})
     *
     * @ApiProperty(readableLink=false)
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
        Money $capital,
        string $headquarterAddress,
        string $matriculationNumber
    ) {
        parent::__construct($matriculationNumber, $capital);
        $this->project       = $project;
        $this->corporateName = $corporateName;
        $this->legalForm     = $legalForm;
        $this->headOffice    = $headquarterAddress;
        $this->members       = new ArrayCollection();
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getTrancheShares()
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
}
