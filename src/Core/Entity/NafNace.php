<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\IdentityTrait;

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
 *         },
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="core_naf_nace")
 *
 * @UniqueEntity("nafCode")
 */
class NafNace
{
    use IdentityTrait;

    /**
     * @ORM\Column(type="string", length=5, nullable=false, unique=true)
     *
     * @Assert\NotBlank
     * @Assert\Length(5)
     */
    private string $nafCode;

    /**
     * @ORM\Column(type="string", length=7, nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Length(7)
     */
    private string $naceCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     */
    private string $nafTitle;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     */
    private string $naceTitle;

    public function __construct(string $nafCode, string $naceCode, string $nafTitle, string $naceTitle)
    {
        $this->nafCode   = $nafCode;
        $this->naceCode  = $naceCode;
        $this->nafTitle  = $nafTitle;
        $this->naceTitle = $naceTitle;
    }

    public function getNafCode(): string
    {
        return $this->nafCode;
    }

    public function getNaceCode(): string
    {
        return $this->naceCode;
    }

    public function getNafTitle(): string
    {
        return $this->nafTitle;
    }

    public function getNaceTitle(): string
    {
        return $this->naceTitle;
    }
}
