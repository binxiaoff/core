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

    public const PERIODICITY_3M = '3m';
    public const PERIODICITY_6M = '6m';
    public const PERIODICITY_12M = '12m';

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
     */
    public function setProject(Project $project): void
    {
        $this->project = $project;
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
     */
    public function setName(string $name): void
    {
        $this->name = $name;
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
     */
    public function setArticle(?string $article): void
    {
        $this->article = $article;
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
     */
    public function setExtract(?string $extract): void
    {
        $this->extract = $extract;
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
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
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
     */
    public function setNature(string $nature): void
    {
        $this->nature = $nature;
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
     */
    public function setStartDate(DateTimeImmutable $startDate): void
    {
        $this->startDate = $startDate;
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
     */
    public function setDelay(int $delay): void
    {
        $this->delay = $delay;
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
     */
    public function setEndDate(DateTimeImmutable $endDate): void
    {
        $this->endDate = $endDate;
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
     */
    public function setPeriodicity(string $periodicity): void
    {
        $this->periodicity = $periodicity;
    }

    /**
     * @return array
     */
    private function getNatures(): array
    {
        return static::getConstants('NATURE_');
    }

    /**
     * @return array
     */
    private function getPeriodicities(): array
    {
        return static::getConstants('PERIODICITY_');
    }
}
