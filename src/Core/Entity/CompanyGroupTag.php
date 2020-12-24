<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="core_company_group_tag",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="uniq_companyGroup_code", columns={"code", "id_company_group"})
 *    }
 * )
 *
 * @ApiResource(
 *     attributes={
 *         "route_prefix"="/core"
 *     },
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
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     *
     * @Groups({"companyGroupTag:read"})
     */
    private string $code;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\CompanyGroup", inversedBy="tags")
     * @ORM\JoinColumn(name="id_company_group", nullable=false)
     *
     * @var CompanyGroup
     */
    private CompanyGroup $companyGroup;

    /**
     * @param CompanyGroup $companyGroup
     * @param string       $code
     */
    public function __construct(CompanyGroup $companyGroup, string $code)
    {
        $this->code = $code;
        $this->companyGroup = $companyGroup;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return CompanyGroup
     */
    public function getCompanyGroup(): CompanyGroup
    {
        return $this->companyGroup;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getCode();
    }
}
