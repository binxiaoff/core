<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ORM\Table(name="agency_covenant")
 * @ORM\Entity
 */
class Covenant
{
    use PublicizeIdentityTrait;
    use ConstantsAwareTrait;
    use TimestampableAddedOnlyTrait;

    public const NATURE_DOCUMENT           = "document";
    public const NATURE_CONTROL            = "control";
    public const NATURE_FINANCIAL_ELEMENT  = "financial_element";
    public const NATURE_FINANCIAL_RATIO    = "financial_ratio";

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
     * @Groups({"agency:covenant:read"})
     */
    private string $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"agency:covenant:read"})
     */
    private ?string $article = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"agency:covenant:read"})
     */
    private ?string $extract = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"agency:covenant:read"})
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
     * @Groups({"agency:covenant:read"})
     */
    private string $nature;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="startDate", type="datetime_immutable")
     *
     * @Groups({"agency:covenant:read"})
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
     * @Groups({"agency:covenant:read"})
     */
    private int $delay;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="endDate", type="datetime_immutable")
     *
     * @Assert\GreaterThan(propertyPath="startDate")
     *
     * @Groups({"agency:covenant:read"})
     */
    private DateTimeImmutable $endDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Choice(callback="getPeriodicities")
     *
     * @Groups({"agency:covenant:read"})
     */
    private string $periodicity;

    /**
     * @var CovenantRule[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\CovenantRule", mappedBy="covenant", indexBy="year")
     *
     * @Assert\Valid
     *
     * @Groups({"agency:covenant:read"})
     */
    private Collection $covenantRules;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:covenant:read"})
     */
    private ?DateTimeImmutable $publicationDate;

    /**
     * @var Collection|Term[]
     *
     * @ORM\OneToMany(targetEntity=Term::class, cascade={"persist", "remove"}, mappedBy="covenant")
     * @ORM\OrderBy({"start"="ASC"})
     *
     * @Assert\Count(min=1, groups={"published"})
     *
     * @Groups({"agency:covenant:read"})
     */
    private Collection $terms;

    /**
     * @var MarginRule[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\MarginRule", mappedBy="covenant", cascade={"persist"})
     *
     * @Assert\Valid
     * @Assert\AtLeastOneOf({
     *   @Assert\Expression("this.isFinancial()"),
     *   @Assert\Count(0),
     * })
     *
     * @Groups({"covenant:read"})
     */
    private Collection $marginRules;

    /**
     * @param Project           $project
     * @param string            $name
     * @param string            $nature
     * @param DateTimeImmutable $startDate
     * @param int               $delay
     * @param DateTimeImmutable $endDate
     * @param string            $periodicity
     *
     * @throws Exception
     */
    public function __construct(Project $project, string $name, string $nature, DateTimeImmutable $startDate, int $delay, DateTimeImmutable $endDate, string $periodicity)
    {
        $this->project           = $project;
        $this->name              = $name;
        $this->nature            = $nature;
        $this->startDate         = $startDate;
        $this->delay             = $delay;
        $this->endDate           = $endDate;
        $this->periodicity       = $periodicity;
        $this->added             = new DateTimeImmutable();
        $this->publicationDate   = null;
        $this->terms             = new ArrayCollection();
        $this->covenantRules = new ArrayCollection();
        $this->marginRules   = new ArrayCollection();
    }

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
     * @return Covenant
     */
    public function setProject(Project $project): Covenant
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
     * @return Covenant
     */
    public function setName(string $name): Covenant
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
     * @return Covenant
     */
    public function setArticle(?string $article): Covenant
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
     * @return Covenant
     */
    public function setExtract(?string $extract): Covenant
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
     * @return Covenant
     */
    public function setDescription(?string $description): Covenant
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
     * @return Covenant
     */
    public function setNature(string $nature): Covenant
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
     * @return Covenant
     */
    public function setStartDate(DateTimeImmutable $startDate): Covenant
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
     * @return Covenant
     */
    public function setDelay(int $delay): Covenant
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
     * @return Covenant
     */
    public function setEndDate(DateTimeImmutable $endDate): Covenant
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
     * @return Covenant
     */
    public function setPeriodicity(string $periodicity): Covenant
    {
        $this->periodicity = $periodicity;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFinancial(): bool
    {
        $financialNatures = [self::NATURE_FINANCIAL_RATIO, self::NATURE_FINANCIAL_ELEMENT];

        return in_array($this->nature, $financialNatures);
    }

    /**
     * @return CovenantRule[]|iterable
     */
    public function getCovenantRules(): iterable
    {
        return $this->covenantRules;
    }

    /**
     * @return int
     */
    public function getEndYear(): int
    {
        return (int) $this->endDate->format('Y');
    }

    /**
     * @return int
     */
    public function getStartYear(): int
    {
        return (int) $this->startDate->format('Y');
    }

    /**
     * @return int
     */
    public function getCovenantYearsDuration(): int
    {
        return $this->getEndYear() - $this->getStartYear();
    }

    /**
     * @return MarginRule[]|iterable
     */
    public function getMarginRules(): iterable
    {
        return $this->marginRules;
    }

    /**
     * @param MarginRule $rule
     *
     * @return Covenant
     */
    public function addMarginRule(MarginRule $rule): Covenant
    {
        if (false === $this->marginRules->contains($rule)) {
            $this->marginRules->add($rule);
        }

        return $this;
    }

    /**
     * @return string[]|iterable
     */
    public function getNatures(): iterable
    {
        return static::getConstants('NATURE_');
    }

    /**
     * @return string[]|iterable
     */
    public function getPeriodicities(): iterable
    {
        return static::getConstants('PERIODICITY_');
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validateCovenantRules(ExecutionContextInterface $context)
    {
        $covenantRulesCount = count($this->covenantRules);

        // non financial covenant must not have rules
        if (0 !== $covenantRulesCount && false === $this->isFinancial()) {
            $context->buildViolation('Agency.CovenantRule.inconsistentCovenant')
                ->atPath('covenantRules')
                ->addViolation();
        }

        // financial covenant must have 1 rule per year (including starting year)
        if ($this->isFinancial() && ($this->getCovenantYearsDuration() + 1) !== $covenantRulesCount) {
            $context->buildViolation('Agency.CovenantRule.inconsistentCovenant')
                ->atPath('covenantRules')
                ->addViolation();
        }
    }

    /**
     * @return bool
     *
     * @Groups({"agency:covenant:read"})
     */
    public function isPublished(): bool
    {
        return null !== $this->publicationDate;
    }

    /**
     * This method actually publish a covenant
     *
     * @throws Exception
     *
     * @return Covenant
     */
    public function publish(): Covenant
    {
        if ($this->isPublished()) {
            return $this;
        }

        $this->publicationDate = new DateTimeImmutable();

        // This create an iterable with each of the term start date
        // https://www.php.net/manual/fr/class.dateperiod.php
        $datePeriod = new DatePeriod($this->startDate, new DateInterval($this->periodicity), $this->endDate);

        foreach ($datePeriod as $termStart) {
            $termEnd = DateTimeImmutable::createFromFormat('U', (string) strtotime('+' . $this->delay . ' days', $termStart->getTimestamp()));
            $this->terms[] = new Term($this, $termStart, $termEnd);
        }

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getPublicationDate(): ?DateTimeImmutable
    {
        return $this->publicationDate;
    }

    /**
     * Must be static : https://api-platform.com/docs/core/validation/#dynamic-validation-groups
     *
     * @param Covenant $covenant
     *
     * @return array|string[]
     */
    public static function getCurrentValidationGroups(self $covenant): array
    {
        $validationGroups = ['Default', 'Covenant'];

        if ($covenant->isPublished()) {
            $validationGroups[] = ['published'];
        }

        return $validationGroups;
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    private function validateMarginRules(ExecutionContextInterface $context)
    {
        // non financial covenant must not have margin rules
        if (false === $this->isFinancial() && 0 !== $this->marginRules->count()) {
            $context->buildViolation('Agency.CovenantRule.inconsistentCovenant')
                ->atPath('marginRules')
                ->addViolation();
        }
    }
}
