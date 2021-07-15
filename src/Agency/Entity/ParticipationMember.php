<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\SwiftMailer\MailjetMessage;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:participationMember:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:participationMember:create",
 *                     "agency:participationMember:write",
 *                     "user:create",
 *                     "user:write"
 *                 }
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:participationMember:write"
 *                 }
 *             }
 *         }
 *     }
 * )
 *
 * @ORM\Table(name="agency_participation_member", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_user", "id_participation"})
 * })
 * @ORM\Entity
 *
 * @UniqueEntity(fields={"user", "participation"})
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "user:read"
 *         }
 *     }
 * )
 */
class ParticipationMember extends AbstractProjectMember
{
    /**
     * @ORM\ManyToOne(targetEntity=Participation::class, inversedBy="members")
     * @ORM\JoinColumn(name="id_participation", onDelete="CASCADE", nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:participationMember:read", "agency:participationMember:create"})
     *
     * @ApiProperty(readableLink=false)
     */
    private Participation $participation;

    public function __construct(Participation $participation, User $user)
    {
        parent::__construct($user);
        $this->participation = $participation;
    }

    public function getProject(): Project
    {
        return $this->participation->getProject();
    }

    public function getParticipation(): Participation
    {
        return $this->participation;
    }

    /**
     * @Groups({"agency:participationMember:read"})
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @Groups({"agency:participationMember:create"})
     */
    public function setUser(User $user): AbstractProjectMember
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @Groups({"agency:borrowerMember:read"})
     */
    public function getProjectFunction(): ?string
    {
        return $this->projectFunction;
    }

    /**
     * @Groups({"agency:borrowerMember:write"})
     */
    public function setProjectFunction(?string $projectFunction): AbstractProjectMember
    {
        $this->projectFunction = $projectFunction;

        return $this;
    }

    /**
     * @Groups({"agency:participationMember:read"})
     */
    public function isReferent(): bool
    {
        return $this->referent;
    }

    /**
     * @Groups({"agency:participationMember:write"})
     */
    public function setReferent(bool $referent): AbstractProjectMember
    {
        $this->referent = $referent;

        return $this;
    }

    /**
     * @Groups({"agency:participationMember:read"})
     */
    public function isSignatory(): bool
    {
        return $this->signatory;
    }

    /**
     * @Groups({"agency:participationMember:write"})
     */
    public function setSignatory(bool $signatory): AbstractProjectMember
    {
        $this->signatory = $signatory;

        return $this;
    }

    /**
     * @Groups({"agency:participationMember:read"})
     */
    public function isArchived(): bool
    {
        return parent::isArchived();
    }

    /**
     * @Assert\Callback
     */
    public function validateUser(ExecutionContextInterface $context)
    {
        $company = $this->participation->getParticipant();

        // Exception for external bank
        if (false === $company->hasSigned()) {
            return;
        }

        $staff = $company->findStaffByUser($this->getUser());

        if (null === $staff || $staff->isArchived()) {
            $context->buildViolation('Agency.ParticipationMember.user.missingStaff')
                ->setParameter('email', $this->getUser()->getEmail())
                ->setParameter('company', $company->getDisplayName())
                ->setInvalidValue($this->getUser())
                ->atPath('user')
                ->addViolation()
            ;
        }
    }

    public static function getProjectPublicationNotificationMailjetTemplateId(): int
    {
        return MailjetMessage::TEMPLATE_AGENCY_PARTICIPATION_MEMBER_PROJECT_PUBLISHED;
    }
}
