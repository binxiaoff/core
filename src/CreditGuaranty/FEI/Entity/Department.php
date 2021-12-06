<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\IdentityTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={
 *         "pagination_items_per_page": 200
 *     },
 *     collectionOperations={"get"},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *     },
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="credit_guaranty_department",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"code", "name"})
 *     }
 * )
 *
 * @UniqueEntity("code", "name")
 */
class Department
{
    use IdentityTrait;

    /**
     * @ORM\Column(type="string", length=5, nullable=false, unique=true)
     *
     * @Assert\NotBlank
     * @Assert\Length(5)
     */
    private string $code;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     */
    private string $region;

    public function __construct(string $code, string $name, string $region)
    {
        $this->code   = $code;
        $this->name   = $name;
        $this->region = $region;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRegion(): string
    {
        return $this->region;
    }
}
