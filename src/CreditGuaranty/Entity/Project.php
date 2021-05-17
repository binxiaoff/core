<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\NafNace;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_project")
 * @ORM\HasLifecycleCallbacks
 */
class Project
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private Money $fundingMoney;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_program_choice_option", nullable=false)
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private ProgramChoiceOption $investmentThematic;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\NafNace")
     * @ORM\JoinColumn(name="id_naf_nace", nullable=false)
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NafNace $nafNace;

    public function __construct(Money $fundingMoney, ProgramChoiceOption $investmentThematic, NafNace $nafNace)
    {
        $this->fundingMoney       = $fundingMoney;
        $this->investmentThematic = $investmentThematic;
        $this->nafNace            = $nafNace;
        $this->added              = new DateTimeImmutable();
    }

    public function getFundingMoney(): Money
    {
        return $this->fundingMoney;
    }

    public function setFundingMoney(Money $fundingMoney): Project
    {
        $this->fundingMoney = $fundingMoney;

        return $this;
    }

    public function getInvestmentThematic(): ProgramChoiceOption
    {
        return $this->investmentThematic;
    }

    public function setInvestmentThematic(ProgramChoiceOption $investmentThematic): Project
    {
        $this->investmentThematic = $investmentThematic;

        return $this;
    }

    public function getNafNace(): NafNace
    {
        return $this->nafNace;
    }

    public function setNafNace(NafNace $nafNace): Project
    {
        $this->nafNace = $nafNace;

        return $this;
    }
}
