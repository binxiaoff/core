<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Unilend\Core\Entity\Traits\IdentityTrait;
use Unilend\Core\Repository\NafNaceRepository;

/**
 * @ORM\Entity(repositoryClass=NafNaceRepository::class)
 * @ORM\Table(
 *     name="core_naf_nace",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"naf_code", "nace_code"})
 *     }
 * )
 *
 * @UniqueEntity(fields={"nafCode", "naceCode"})
 */
class NafNace
{
    use IdentityTrait;

    /**
     * @ORM\Column(type="string", length=5, nullable=false)
     */
    private string $nafCode;

    /**
     * @ORM\Column(type="string", length=7, nullable=false)
     */
    private string $naceCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private string $nafTitle;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private string $naceTitle;

    public function __construct(string $nafCode, string $naceCode, string $nafTitle, string $naceTitle)
    {
        $this->nafCode  = $nafCode;
        $this->naceCode = $naceCode;
        $this->nafTitle  = $nafTitle;
        $this->naceTitle = $naceTitle;
    }

    public function getNafCode(): string
    {
        return $this->nafCode;
    }

    public function setNafCode(string $nafCode): NafNace
    {
        $this->nafCode = $nafCode;

        return $this;
    }

    public function getNaceCode(): string
    {
        return $this->naceCode;
    }

    public function setNaceCode(string $naceCode): NafNace
    {
        $this->naceCode = $naceCode;

        return $this;
    }

    public function getNafTitle(): string
    {
        return $this->nafTitle;
    }

    public function setNafTitle(string $nafTitle): NafNace
    {
        $this->nafTitle = $nafTitle;

        return $this;
    }

    public function getNaceTitle(): string
    {
        return $this->naceTitle;
    }

    public function setNaceTitle(string $naceTitle): NafNace
    {
        $this->naceTitle = $naceTitle;

        return $this;
    }
}
