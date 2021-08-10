<?php

declare(strict_types=1);

namespace KLS\Agency\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use KLS\Core\Entity\User;
use KLS\Core\Validator\Constraints\Siren as AssertSiren;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractProjectPartaker
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $corporateName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $legalForm;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $headOffice;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $bankInstitution;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $bankAddress;

    /**
     * @ORM\Column(type="string", length=11, nullable=true)
     *
     * @Assert\Bic
     */
    protected ?string $bic;

    /**
     * @ORM\Column(type="string", length=34, nullable=true)
     *
     * @Assert\Iban
     */
    protected ?string $iban;

    /**
     * @var AbstractProjectMember[]|Collection
     *
     * @Assert\Valid
     */
    protected Collection $members;

    /**
     * @ORM\Column(type="string", length=9)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="9")
     *
     * @AssertSiren
     */
    protected string $matriculationNumber;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     *
     * @Assert\Length(max="40")
     */
    protected ?string $rcs;

    /**
     * TODO Move this field to borrower (agent and participant do not need a capital).
     *
     * @ORM\Embedded(class=NullableMoney::class)
     *
     * @Assert\Valid
     *
     * @Groups({"agency:projectPartaker:read", "agency:projectPartaker:write"})
     */
    private NullableMoney $capital;

    public function __construct(string $matriculationNumber, ?NullableMoney $capital = null)
    {
        $this->matriculationNumber = $matriculationNumber;
        $this->capital             = $capital ?? new NullableMoney();
        $this->rcs                 = null;
        $this->added               = new DateTimeImmutable();
        $this->setPublicId();
    }

    abstract public function getProject(): Project;

    public function getMatriculationNumber(): string
    {
        return $this->matriculationNumber;
    }

    public function getCapital(): NullableMoney
    {
        return $this->capital;
    }

    public function getRcs(): ?string
    {
        return $this->rcs;
    }

    public function setMatriculationNumber(string $matriculationNumber): AbstractProjectPartaker
    {
        $this->matriculationNumber = $matriculationNumber;

        return $this;
    }

    public function setCapital(NullableMoney $capital): AbstractProjectPartaker
    {
        $this->capital = $capital;

        return $this;
    }

    public function setRcs(?string $rcs): AbstractProjectPartaker
    {
        $this->rcs = $rcs;

        return $this;
    }

    public function getLegalForm(): ?string
    {
        return $this->legalForm;
    }

    public function setLegalForm(?string $legalForm): AbstractProjectPartaker
    {
        $this->legalForm = $legalForm;

        return $this;
    }

    public function getHeadOffice(): ?string
    {
        return $this->headOffice;
    }

    public function setHeadOffice(?string $headOffice): AbstractProjectPartaker
    {
        $this->headOffice = $headOffice;

        return $this;
    }

    public function getBankInstitution(): ?string
    {
        return $this->bankInstitution;
    }

    public function setBankInstitution(?string $bankInstitution): AbstractProjectPartaker
    {
        $this->bankInstitution = $bankInstitution;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): AbstractProjectPartaker
    {
        $this->bic = $bic;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): AbstractProjectPartaker
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBankAddress(): ?string
    {
        return $this->bankAddress;
    }

    public function setBankAddress(?string $bankAddress): AbstractProjectPartaker
    {
        $this->bankAddress = $bankAddress;

        return $this;
    }

    public function getCorporateName(): ?string
    {
        return $this->corporateName;
    }

    public function setCorporateName(?string $corporateName): AbstractProjectPartaker
    {
        $this->corporateName = $corporateName;

        return $this;
    }

    /**
     * @return Collection|AbstractProjectMember[]
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(AbstractProjectMember $member): AbstractProjectPartaker
    {
        if (null === $this->findMemberByUser($member->getUser())) {
            $this->members[] = $member;
        }

        return $this;
    }

    public function findMemberByUser(User $user): ?AbstractProjectMember
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return $member;
            }
        }

        return null;
    }

    public function removeMember(AbstractProjectMember $member): AbstractProjectPartaker
    {
        $this->members->removeElement($member);

        return $this;
    }

    /**
     * @return Collection|AbstractProjectMember[]
     */
    public function getReferents(): Collection
    {
        return $this->members->filter(fn (AbstractProjectMember $member) => $member->isReferent());
    }

    /**
     * @return Collection|AbstractProjectMember[]
     */
    public function getSignatory(): Collection
    {
        return $this->members->filter(fn (AbstractProjectMember $member) => $member->isSignatory());
    }
}
