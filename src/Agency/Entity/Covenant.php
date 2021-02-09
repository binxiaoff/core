<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ORM\Table(name="agency_covenant")
 * @ORM\Entity
 */
class Covenant
{
    use PublicizeIdentityTrait;
    use ConstantsAwareTrait;

    public const NATURE_DOCUMENT = "document";
    public const NATURE_CONTROL  = "control";

    public const PERIODICITY_3M = 'P3M';
    public const PERIODICITY_6M = 'P6M';
    public const PERIODICITY_12M = 'P12M';

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Project", inversedBy="covenants")
     * @ORM\JoinColumn(name="id_project")
     */
    private Project $project;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank
     *
     * @Groups({"covenant:read"})
     */
    private string $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"covenant:read"})
     */
    private ?string $article = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"covenant:read"})
     */
    private ?string $extract = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"covenant:read"})
     */
    private ?string $description = null;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getNatures")
     *
     * @Groups({"covenant:read"})
     */
    private string $nature;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="startDate", type="datetime_immutable")
     *
     * @Groups({"covenant:read"})
     */
    private DateTimeImmutable $startDate;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Type("integer")
     * @Assert\Positive
     *
     * @Groups({"covenant:read"})
     */
    private int $delay;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="endDate", type="datetime_immutable")
     *
     * @Assert\GreaterThan(propertyPath="startDate")
     *
     * @Groups({"covenant:read"})
     */
    private DateTimeImmutable $endDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Choice(callback="getPeriodicities")
     *
     * @Groups({"covenant:read"})
     */
    private string $periodicity;

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     *
     * @return Project
     */
    public function setProject(Project $project): Project
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Project
     */
    public function setName(string $name): Project
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getArticle(): ?string
    {
        return $this->article;
    }

    /**
     * @param string|null $article
     *
     * @return Project
     */
    public function setArticle(?string $article): Project
    {
        $this->article = $article;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExtract(): ?string
    {
        return $this->extract;
    }

    /**
     * @param string|null $extract
     *
     * @return Project
     */
    public function setExtract(?string $extract): Project
    {
        $this->extract = $extract;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return Project
     */
    public function setDescription(?string $description): Project
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getNature(): string
    {
        return $this->nature;
    }

    /**
     * @param string $nature
     *
     * @return Project
     */
    public function setNature(string $nature): Project
    {
        $this->nature = $nature;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    /**
     * @param DateTimeImmutable $startDate
     *
     * @return Project
     */
    public function setStartDate(DateTimeImmutable $startDate): Project
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * @param int $delay
     *
     * @return Project
     */
    public function setDelay(int $delay): Project
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    /**
     * @param DateTimeImmutable $endDate
     *
     * @return Project
     */
    public function setEndDate(DateTimeImmutable $endDate): Project
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getPeriodicity(): string
    {
        return $this->periodicity;
    }

    /**
     * @param string $periodicity
     *
     * @return Project
     */
    public function setPeriodicity(string $periodicity): Project
    {
        $this->periodicity = $periodicity;

        return $this;
    }

    /**
     * @return string[]|array
     */
    private function getNatures(): array
    {
        return static::getConstants('NATURE_');
    }

    /**
     * @return string[]|array
     */
    private function getPeriodicities(): array
    {
        return static::getConstants('PERIODICITY_');
    }
}
