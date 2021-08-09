<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Traits\BlamableUpdatedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="core_company_module",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"id_company", "code"})
 *     }
 * )
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

    public const MODULE_ARRANGEMENT               = 'arrangement';
    public const MODULE_PARTICIPATION             = 'participation';
    public const MODULE_AGENCY                    = 'agency';
    public const MODULE_ARRANGEMENT_EXTERNAL_BANK = 'arrangement_external_bank';

    public const PROPERTY_ACTIVATED = 'activated';

    /**
     * @ORM\Column(type="string", nullable=false)
     *
     * @Assert\Choice(callback="getAvailableModuleCodes")
     *
     * @Groups({"companyModule:read"})
     */
    private string $code;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company", inversedBy="modules")
     * @ORM\JoinColumn(nullable=false, name="id_company")
     *
     * @Assert\NotBlank
     */
    private Company $company;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"companyModule:read", "companyModule:write"})
     */
    private bool $activated;

    /**
     * @ORM\OneToMany(targetEntity="KLS\Core\Entity\CompanyModuleLog", mappedBy="companyModule")
     * @ORM\OrderBy({"added": "ASC"})
     */
    private Collection $logs;

    /**
     * TODO This is temporary. Remove when facturation is handled by the platform.
     *
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"companyModule:read"})
     */
    private ?NullableMoney $arrangementAnnualLicenseMoney;

    /**
     * @throws Exception
     */
    public function __construct(string $code, Company $company, bool $activated = false)
    {
        $this->code                          = $code;
        $this->company                       = $company;
        $this->activated                     = $activated;
        $this->added                         = new DateTimeImmutable();
        $this->logs                          = new ArrayCollection();
        $this->arrangementAnnualLicenseMoney = new NullableMoney();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }

    public function setActivated(bool $activated): CompanyModule
    {
        $this->activated = $activated;

        return $this;
    }

    public function getLogs(): Collection
    {
        return $this->logs;
    }

    /**
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

    public function getArrangementAnnualLicenseMoney(): ?NullableMoney
    {
        return $this->arrangementAnnualLicenseMoney->isValid() ? $this->arrangementAnnualLicenseMoney : null;
    }

    public function setArrangementAnnualLicenseMoney(?NullableMoney $arrangementAnnualLicenseMoney): CompanyModule
    {
        $this->arrangementAnnualLicenseMoney = $arrangementAnnualLicenseMoney;

        return $this;
    }
}
