<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Traits\{BlamableUpdatedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_company", "code"})})
 * @ORM\HasLifecycleCallbacks
 *
 * @ApiResource(
 *     normalizationContext={"groups": {"companyModule:read", "nullableMoney:read"}},
 *     denormalizationContext={"groups": {"companyModule:write"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "security_post_denormalize": "is_granted('edit', object) && object.isActivated() === true"
 *         }
 *     }
 * )
 */
class CompanyModule
{
    use TimestampableTrait;
    use PublicizeIdentityTrait;
    use BlamableUpdatedTrait;
    use ConstantsAwareTrait;

    public const MODULE_ARRANGEMENT    = 'arrangement';
    public const MODULE_PARTICIPATION  = 'participation';
    public const MODULE_AGENCY         = 'agency';
    public const MODULE_ARRANGEMENT_EXTERNAL_BANK = 'arrangement_external_bank';

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     *
     * @Assert\Choice(callback="getAvailableModuleCodes")
     *
     * @Groups({"companyModule:read"})
     */
    private string $code;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company", inversedBy="modules")
     * @ORM\JoinColumn(nullable=false, name="id_company")
     *
     * @Assert\NotBlank
     */
    private Company $company;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"companyModule:read", "companyModule:write"})
     */
    private bool $activated;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\CompanyModuleLog", mappedBy="companyModule")
     * @ORM\OrderBy({"added": "ASC"})
     */
    private Collection $logs;

    /**
     * TODO This is temporary. Remove when facturation is handled by the platform
     *
     * @var NullableMoney|null
     *
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"companyModule:read"})
     */
    private ?NullableMoney $arrangementAnnualLicenseMoney;

    /**
     * @param string  $code
     * @param Company $company
     * @param bool    $activated
     *
     * @throws Exception
     */
    public function __construct(string $code, Company $company, bool $activated = false)
    {
        $this->code      = $code;
        $this->company   = $company;
        $this->activated = $activated;
        $this->added     = new DateTimeImmutable();
        $this->logs      = new ArrayCollection();
        $this->arrangementAnnualLicenseMoney = new NullableMoney();
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->activated;
    }

    /**
     * @param bool $activated
     *
     * @return CompanyModule
     */
    public function setActivated(bool $activated): CompanyModule
    {
        $this->activated = $activated;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    /**
     * @return DateTimeImmutable|null
     *
     * @Groups({"companyModule:read"})
     */
    public function getLastActivationDate(): ?DateTimeImmutable
    {
        // Work for now because there is no user module deactivation
        return $this->getUpdated();
    }

    /**
     * @return array|string[]
     */
    public static function getAvailableModuleCodes(): array
    {
        return static::getConstants('MODULE_');
    }

    /**
     * @return NullableMoney|null
     */
    public function getArrangementAnnualLicenseMoney(): ?NullableMoney
    {
        return $this->arrangementAnnualLicenseMoney->isValid() ? $this->arrangementAnnualLicenseMoney : null;
    }

    /**
     * @param NullableMoney|null $arrangementAnnualLicenseMoney
     *
     * @return CompanyModule
     */
    public function setArrangementAnnualLicenseMoney(?NullableMoney $arrangementAnnualLicenseMoney): CompanyModule
    {
        $this->arrangementAnnualLicenseMoney = $arrangementAnnualLicenseMoney;

        return $this;
    }
}
