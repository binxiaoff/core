<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Syndication\Agency\Entity\Embeddable\BankAccount;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={
 *         "validation_groups": {AgentBankAccount::class, "getCurrentValidationGroups"},
 *     },
 *     normalizationContext={
 *         "groups": {
 *             "agency:agentBankAccount:read",
 *             "agency:bankAccount:read"
 *         },
 *         "openapi_definition_name": "read"
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)"
 *         },
 *         "patch": {
 *             "security_post_denormalize": "is_granted('edit', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:agentBankAccount:write",
 *                     "agency:bankAccount:write"
 *                 }
 *             },
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:agentBankAccount:create",
 *                     "agency:agentBankAccount:write",
 *                     "agency:bankAccount:write"
 *                 }
 *             },
 *         }
 *     }
 * )
 * @ORM\Table(name="agency_agent_bank_account")
 * @ORM\Entity
 */
class AgentBankAccount
{
    use PublicizeIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Agent::class, inversedBy="bankAccounts")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE", name="id_agent")
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Serializer\Groups({"agency:agentBankAccount:read", "agency:agentBankAccount:create"})
     */
    private Agent $agent;

    /**
     * @ORM\Embedded(class=BankAccount::class)
     *
     * @Assert\Valid
     *
     * @Serializer\Groups({"agency:agentBankAccount:read", "agency:agentBankAccount:write"})
     */
    private BankAccount $bankAccount;

    /**
     * @ORM\OneToMany(targetEntity=Participation::class, mappedBy="agentBankAccount")
     *
     * @Assert\All({
     *     @Assert\Expression("this.getProject() === value.getProject()")
     * })
     *
     * @Serializer\Groups({"agency:agentBankAccount:read", "agency:agentBankAccount:write"})
     */
    private Collection $participations;

    /**
     * @ORM\OneToMany(targetEntity=Borrower::class, mappedBy="agentBankAccount")
     *
     * @Assert\All({
     *     @Assert\Expression("this.getProject() === value.getProject()")
     * })
     *
     * @Serializer\Groups({"agency:agentBankAccount:read", "agency:agentBankAccount:write"})
     */
    private Collection $borrowers;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
        $this->agent->addBankAccount($this); // TODO Is it a good idea ?
        $this->bankAccount    = new BankAccount();
        $this->participations = new ArrayCollection();
        $this->borrowers      = new ArrayCollection();
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }

    public function getBankAccount(): BankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(BankAccount $bankAccount): AgentBankAccount
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }

    /**
     * @return Collection|Participation[]
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): AgentBankAccount
    {
        if (false === $this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setAgentBankAccount($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): AgentBankAccount
    {
        if ($this->participations->contains($participation)) {
            $this->participations->removeElement($participation);
            $participation->setAgentBankAccount(null);
        }

        return $this;
    }

    public function addBorrower(Borrower $borrower): AgentBankAccount
    {
        if (false === $this->borrowers->contains($borrower)) {
            $this->borrowers->add($borrower);
            $borrower->setAgentBankAccount($this);
        }

        return $this;
    }

    public function removeBorrower(Borrower $borrower): AgentBankAccount
    {
        if ($this->borrowers->contains($borrower)) {
            $this->borrowers->removeElement($borrower);
            $borrower->setAgentBankAccount(null);
        }

        return $this;
    }

    public static function getCurrentValidationGroups(self $agentBankAccount): array
    {
        $validationGroups = ['Default', 'AgentBankAccount'];

        if ($agentBankAccount->getProject()->isPublished()) {
            $validationGroups[] = 'bankAccount::completed';
        }

        return $validationGroups;
    }

    /**
     * @return Collection|Borrower[]
     */
    public function getBorrowers(): Collection
    {
        return $this->borrowers;
    }

    public function getProject(): Project
    {
        return $this->getAgent()->getProject();
    }
}
