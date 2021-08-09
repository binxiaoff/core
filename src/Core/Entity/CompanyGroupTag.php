<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="core_company_group_tag",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uniq_companyGroup_code", columns={"code", "id_company_group"})
 *     }
 * )
 *
 * @ApiResource(
 *     normalizationContext={"groups": {"companyGroupTag:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     },
 *     collectionOperations={}
 * )
 */
class CompanyGroupTag
{
    use PublicizeIdentityTrait;

    /**
     * @ORM\Column(type="string", nullable=false)
     *
     * @Groups({"companyGroupTag:read"})
     */
    private string $code;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\CompanyGroup", inversedBy="tags")
     * @ORM\JoinColumn(name="id_company_group", nullable=false)
     */
    private CompanyGroup $companyGroup;

    public function __construct(CompanyGroup $companyGroup, string $code)
    {
        $this->code         = $code;
        $this->companyGroup = $companyGroup;
    }

    public function __toString(): string
    {
        return $this->getCode();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCompanyGroup(): CompanyGroup
    {
        return $this->companyGroup;
    }
}
