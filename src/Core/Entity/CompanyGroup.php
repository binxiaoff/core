<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_company_group")
 */
class CompanyGroup
{
    public const GROUPNAME_CA = 'Crédit Agricole';

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    private string $name;

    /**
     * @var iterable
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\CompanyGroupTag", mappedBy="companyGroup")
     */
    private iterable $tags;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->id = null;
        $this->name = $name;
        $this->tags = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return CompanyGroupTag[]|array
     */
    public function getTags(): array
    {
        return $this->tags->toArray();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
