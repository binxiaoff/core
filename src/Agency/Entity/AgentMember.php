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

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:agentMember:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:agentMember:create",
 *                     "agency:agentMember:write",
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
 *                     "agency:agentMember:write"
 *                 }
 *             }
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)"
 *         }
 *     }
 * )
 * @ORM\Table(name="agency_agent_member", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_agent", "id_user"})
 * })
 * @ORM\Entity
 *
 * @UniqueEntity(fields={"agent", "user"})
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
class AgentMember extends AbstractProjectMember
{
    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Agent", inversedBy="members")
     * @ORM\JoinColumn(name="id_agent", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:agentMember:read", "agency:agentMember:create"})
     *
     * @ApiProperty(readableLink=false)
     */
    private Agent $agent;

    public function __construct(Agent $agent, User $user)
    {
        parent::__construct($user);
        $this->agent = $agent;
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }

    public function getProject(): Project
    {
        return $this->getAgent()->getProject();
    }

    /**
     * @Groups({"agency:agentMember:read"})
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @Groups({"agency:agentMember:create"})
     */
    public function setUser(User $user): AbstractProjectMember
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @Groups({"agency:agentMember:read"})
     */
    public function getProjectFunction(): ?string
    {
        return $this->projectFunction;
    }

    /**
     * @Groups({"agency:agentMember:write"})
     */
    public function setProjectFunction(?string $projectFunction): AbstractProjectMember
    {
        $this->projectFunction = $projectFunction;

        return $this;
    }

    /**
     * @Groups({"agency:agentMember:read"})
     */
    public function isReferent(): bool
    {
        return $this->referent;
    }

    /**
     * @Groups({"agency:agentMember:write"})
     */
    public function setReferent(bool $referent): AbstractProjectMember
    {
        $this->referent = $referent;

        return $this;
    }

    /**
     * @Groups({"agency:agentMember:read"})
     */
    public function isSignatory(): bool
    {
        return $this->signatory;
    }

    /**
     * @Groups({"agency:agentMember:write"})
     */
    public function setSignatory(bool $signatory): AbstractProjectMember
    {
        $this->signatory = $signatory;

        return $this;
    }

    /**
     * @Groups({"agency:agentMember:read"})
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
        $company = $this->agent->getCompany();

        $staff = $company->findStaffByUser($this->getUser());

        if (null === $staff || $staff->isArchived()) {
            $context->buildViolation('Agency.AgentMember.user.missingStaff')
                ->setParameter('email', $this->getUser()->getEmail())
                ->setParameter('company', $company->getDisplayName())
                ->setInvalidValue($this->getUser())
                ->atPath('user')
                ->addViolation()
            ;
        }
    }
}
