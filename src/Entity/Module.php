<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\{BlamableUpdatedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_company", "name"})})
 *
 * @ApiResource(
 *     normalizationContext={"groups": {"module:read"}},
 *     denormalizationContext={"groups": {"module:write"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)"
 *         }
 *     }
 * )
 */
class Module
{
    use TimestampableTrait;
    use PublicizeIdentityTrait;
    use BlamableUpdatedTrait;
    use ConstantsAwareTrait;

    public const MODULE_ARRANGEMENT   = 'arrangement';
    public const MODULE_PARTICIPATION = 'participation';
    public const MODULE_AGENCY        = 'agency';

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     *
     * @Assert\Choice(callback="getAvailableModulesNames")
     *
     * @Groups({"module:read"})
     */
    private $name;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Company", inversedBy="modules")
     * @ORM\JoinColumn(nullable=false, name="id_company")
     *
     * @Assert\NotBlank
     *
     * @Groups({"module:read"})
     */
    private $company;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"module:read", "module:write"})
     */
    private $activated;

    /**
     * @param string  $name
     * @param Company $company
     * @param bool    $activated
     *
     * @throws Exception
     */
    public function __construct(string $name, Company $company, bool $activated = false)
    {
        $this->name      = $name;
        $this->company   = $company;
        $this->activated = $activated;
        $this->added     = new DateTimeImmutable();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
     * @return Module
     */
    public function setActivated(bool $activated): Module
    {
        $this->activated = $activated;

        return $this;
    }

    /**
     * @return array|string[]
     */
    public static function getAvailableModulesNames(): array
    {
        return static::getConstants('MODULE_');
    }
}
