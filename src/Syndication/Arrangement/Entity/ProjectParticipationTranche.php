<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Service\MoneyCalculator;
use KLS\Core\Traits\ConstantsAwareTrait;
use KLS\Syndication\Arrangement\Entity\Embeddable\Offer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "projectParticipationTranche:read",
 *         "offer:read",
 *         "nullableMoney:read",
 *         "invitationReplyVersion:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "offer:write",
 *         "nullableMoney:write"
 *     }},
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {"groups": {"projectParticipationTranche:create"}},
 *             "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {"security_post_denormalize": "is_granted('edit', previous_object)"}
 *     }
 * )
 *
 * @Gedmo\Loggable(logEntryClass="KLS\Syndication\Arrangement\Entity\Versioned\VersionedProjectParticipationTranche")
 *
 * @ORM\Table(
 *     name="syndication_project_participation_tranche",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"id_tranche", "id_project_participation"})}
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"tranche", "projectParticipation"})
 */
class ProjectParticipationTranche
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use BlamableAddedTrait;

    // Additional normalizer group that is available for public visibility project. It's also available for the participation owner and arranger
    public const SERIALIZER_GROUP_SENSITIVE_READ = 'projectParticipationTranche:sensitive:read';

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Arrangement\Entity\Tranche", inversedBy="projectParticipationTranches")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_tranche", nullable=false, onDelete="CASCADE")
     * })
     *
     * @Assert\Expression(
     *     "this.isOwnTranche(value)",
     *     message="Syndication.ProjectParticipationTranche.tranche.notOwn"
     * )
     *
     * @Groups({"projectParticipationTranche:read", "projectParticipationTranche:create"})
     */
    private Tranche $tranche;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Arrangement\Entity\ProjectParticipation", inversedBy="projectParticipationTranches")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_participation", nullable=false, onDelete="CASCADE")
     * })
     *
     * @Groups({"projectParticipationTranche:create"})
     */
    private ProjectParticipation $projectParticipation;

    /**
     * Réponse ferme : Répartition donnée par le participant à l'arrangeur.
     *
     * @ORM\Embedded(class="KLS\Syndication\Arrangement\Entity\Embeddable\Offer")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({
     *     ProjectParticipationTranche::SERIALIZER_GROUP_SENSITIVE_READ,
     *     "projectParticipationTranche:owner:participantReply:write",
     *     "projectParticipationTranche:arrangerOwner:allocation:write"
     * })
     */
    private Offer $invitationReply;

    /**
     * @ORM\Embedded(class="KLS\Syndication\Arrangement\Entity\Embeddable\Offer")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({
     *     ProjectParticipationTranche::SERIALIZER_GROUP_SENSITIVE_READ,
     *     "projectParticipationTranche:arranger:allocation:write"
     * })
     */
    private Offer $allocation;

    /**
     * @var Collection|InvitationReplyVersion[]
     *
     * @ORM\OneToMany(targetEntity="KLS\Syndication\Arrangement\Entity\InvitationReplyVersion", mappedBy="projectParticipationTranche", orphanRemoval=true)
     *
     * @Groups({ProjectParticipationTranche::SERIALIZER_GROUP_SENSITIVE_READ})
     */
    private Collection $invitationReplyVersions;

    /**
     * @throws Exception
     */
    public function __construct(ProjectParticipation $projectParticipation, Tranche $tranche, Staff $addedBy)
    {
        $this->projectParticipation    = $projectParticipation;
        $this->tranche                 = $tranche;
        $this->addedBy                 = $addedBy;
        $this->added                   = new DateTimeImmutable();
        $this->invitationReply         = new Offer();
        $this->allocation              = new Offer();
        $this->invitationReplyVersions = new ArrayCollection();
    }

    public function getTranche(): Tranche
    {
        return $this->tranche;
    }

    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    public function getInvitationReply(): Offer
    {
        return $this->invitationReply;
    }

    public function setInvitationReply(Offer $invitationReply): ProjectParticipationTranche
    {
        $this->invitationReply = $invitationReply;

        return $this;
    }

    public function getAllocation(): Offer
    {
        return $this->allocation;
    }

    public function setAllocation(Offer $allocation): ProjectParticipationTranche
    {
        $this->allocation = $allocation;

        return $this;
    }

    /**
     * @return Collection|InvitationReplyVersion[]
     */
    public function getInvitationReplyVersions(): Collection
    {
        return $this->invitationReplyVersions;
    }

    /**
     * Used in an expression constraints: we can only add the tranche of the project.
     */
    public function isOwnTranche(Tranche $tranche): bool
    {
        return $this->getProjectParticipation()->getProject()->getTranches()->contains($tranche);
    }

    /**
     * @Assert\Callback
     */
    public function validateAllocation(ExecutionContextInterface $context): void
    {
        $arrangerParticipation = $this->getProjectParticipation()->getProject()->getArrangerProjectParticipation();

        if (
            $this->getProjectParticipation() !== $arrangerParticipation
            && $this->getAllocation()->isValid()
            && $this->getInvitationReply()->isValid()
            && MoneyCalculator::compare($this->getInvitationReply()->getMoney(), $this->getAllocation()->getMoney()) < 0
        ) {
            $context->buildViolation('Syndication.ProjectParticipationTranche.allocation.aboveInvitationReply')
                ->atPath('allocation')
                ->setParameters([
                    'invitationReplyAmount' => $this->getInvitationReply()->getMoney()->getAmount(),
                    'allocationAmount'      => $this->getAllocation()->getMoney()->getAmount(),
                ])
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateCurrencyConsistency(ExecutionContextInterface $context): void
    {
        $globalFundingMoney = $this->getProjectParticipation()->getProject()->getGlobalFundingMoney();

        if (MoneyCalculator::isDifferentCurrency($this->invitationReply->getMoney(), $globalFundingMoney)) {
            $context->buildViolation('Core.Money.currency.inconsistent')
                ->atPath('invitationReply')
                ->addViolation()
            ;
        }

        if (MoneyCalculator::isDifferentCurrency($this->allocation->getMoney(), $globalFundingMoney)) {
            $context->buildViolation('Core.Money.currency.inconsistent')
                ->atPath('allocation')
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateSyndicationStatus(ExecutionContextInterface $context): void
    {
        $tranche = $this->getTranche();

        if ($tranche->isSyndicated()) {
            return;
        }

        if (Tranche::UNSYNDICATED_FUNDER_TYPE_ARRANGER === $tranche->getUnsyndicatedFunderType() && $this->getProjectParticipation()->isArrangerParticipation()) {
            return;
        }

        $context->buildViolation('Syndication.ProjectParticipationTranche.tranche.unsyndicated')
            ->atPath('tranche')
            ->addViolation()
        ;
    }
}
