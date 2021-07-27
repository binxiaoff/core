<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
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
 *
 * @ApiResource(
 *     attributes={
 *         "validation_groups": {Covenant::class, "getCurrentValidationGroups"}
 *     },
 *     normalizationContext={
 *         "groups": {"agency:covenant:read", "agency:inequality:read"}
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)"
 *         },
 *         "patch": {
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:covenant:update",
 *                     "agency:covenant:write",
 *                     "agency:covenantRule:create",
 *                     "agency:marginRule:create",
 *                     "agency:marginImpact:create",
 *                     "agency:inequality:write"
 *                 }
 *             },
 *             "security_denormalize": "is_granted('edit', object)"
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:covenant:create",
 *                     "agency:covenant:write",
 *                     "agency:covenantRule:create",
 *                     "agency:marginRule:create",
 *                     "agency:marginImpact:create",
 *                     "agency:inequality:write"
 *                 }
 *             },
 *             "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     }
 * )
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "agency:covenantRule:read",
 *             "agency:marginRule:read",
 *             "agency:marginImpact:read",
 *             "file:read",
 *             "fileVersion:read",
 *         }
 *     }
 * )
 */
class Covenant
{
    use PublicizeIdentityTrait;
    use ConstantsAwareTrait;
    use TimestampableAddedOnlyTrait;

    public const NATURE_DOCUMENT          = 'document';
    public const NATURE_CONTROL           = 'control';
    public const NATURE_FINANCIAL_ELEMENT = 'financial_element';
    public const NATURE_FINANCIAL_RATIO   = 'financial_ratio';

    public const RECURRENCE_1M  = 'P1M';
    public const RECURRENCE_3M  = 'P3M';
    public const RECURRENCE_6M  = 'P6M';
    public const RECURRENCE_12M = 'P12M';

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="covenants")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"agency:covenant:read", "agency:covenant:create"})
     *
     * @ApiProperty(writableLink=false, readableLink=false)
     */
    private Project $project;

    /**
     * @ORM\Column(type="string", length=150)
     *
     * @Assert\Length(max="150")
     * @Assert\NotBlank
     *
     * @Groups({"agency:covenant:read", "agency:covenant:write"})
     */
    private string $name;

    /**
     * @ORM\Column(type="string", nullable=true, length=500)
     *
     * @Assert\Length(max="500")
     *
     * @Groups({"agency:covenant:read", "agency:covenant:write"})
     */
    private ?string $contractArticle = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"agency:covenant:read", "agency:covenant:write"})
     */
    private ?string $contractExtract = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"agency:covenant:read", "agency:covenant:write"})
     */
    private ?string $description = null;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getNatures")
     *
     * @Groups({"agency:covenant:read", "agency:covenant:create"})
     */
    private string $nature;

    /**
     * @ORM\Column(name="startDate", type="date_immutable")
     *
     * @Groups({"agency:covenant:read", "agency:covenant:write"})
     */
    private DateTimeImmutable $startDate;

    /**
     * @ORM\Column(type="smallint")
     *
     * @Assert\Type("integer")
     * @Assert\Positive
     *
     * @Groups({"agency:covenant:read", "agency:covenant:write"})
     */
    private int $delay;

    /**
     * @ORM\Column(name="endDate", type="date_immutable")
     *
     * @Assert\GreaterThanOrEqual(propertyPath="startDate")
     *
     * @Groups({"agency:covenant:read", "agency:covenant:write"})
     */
    private DateTimeImmutable $endDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Choice(callback="getRecurrences")
     *
     * @Groups({"agency:covenant:read", "agency:covenant:write"})
     */
    private ?string $recurrence;

    /**
     * @var CovenantRule[]|Collection
     *
     * @ORM\OneToMany(targetEntity=CovenantRule::class, mappedBy="covenant", indexBy="year", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EAGER")
     * @ORM\OrderBy({"year": "ASC"})
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getCovenant() === this")
     * })
     *
     * @Groups({"agency:covenant:read", "agency:covenant:write"})
     */
    private Collection $covenantRules;

    /**
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\NotBlank(groups={"published"})
     *
     * @Groups({"agency:covenant:read"})
     */
    private ?DateTimeImmutable $publicationDate;

    /**
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Groups({"agency:covenant:read"})
     */
    private ?DateTimeImmutable $archivingDate;

    /**
     * @var Collection|Term[]
     *
     * @ORM\OneToMany(targetEntity=Term::class, cascade={"persist", "remove"}, mappedBy="covenant")
     * @ORM\OrderBy({"startDate": "ASC"})
     *
     * @Assert\Count(min=1, groups={"published"})
     *
     * @Groups({"agency:covenant:read", "agency:covenant:update"})
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getCovenant() === this")
     * })
     */
    private Collection $terms;

    /**
     * @var MarginRule[]|Collection
     *
     * @ORM\OneToMany(targetEntity=MarginRule::class, mappedBy="covenant", cascade={"persist", "remove"}, fetch="EAGER", orphanRemoval=true)
     *
     * @Assert\Valid
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("this.isFinancial()"),
     *     @Assert\Count(0),
     * })
     * @Assert\All({
     *     @Assert\Expression("value.getCovenant() === this")
     * })
     *
     * @Groups({"agency:covenant:read", "agency:covenant:write"})
     */
    private Collection $marginRules;

    /**
     * @throws Exception
     */
    public function __construct(Project $project, string $name, string $nature, DateTimeImmutable $startDate, int $delay, DateTimeImmutable $endDate)
    {
        $this->project         = $project;
        $this->name            = $name;
        $this->nature          = $nature;
        $this->startDate       = $startDate;
        $this->delay           = $delay;
        $this->endDate         = $endDate;
        $this->recurrence      = null;
        $this->added           = new DateTimeImmutable();
        $this->publicationDate = null;
        $this->terms           = new ArrayCollection();
        $this->covenantRules   = new ArrayCollection();
        $this->marginRules     = new ArrayCollection();
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): Covenant
    {
        $this->project = $project;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Covenant
    {
        $this->name = $name;

        return $this;
    }

    public function getContractArticle(): ?string
    {
        return $this->contractArticle;
    }

    public function setContractArticle(?string $contractArticle): Covenant
    {
        $this->contractArticle = $contractArticle;

        return $this;
    }

    public function getContractExtract(): ?string
    {
        return $this->contractExtract;
    }

    public function setContractExtract(?string $contractExtract): Covenant
    {
        $this->contractExtract = $contractExtract;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Covenant
    {
        $this->description = $description;

        return $this;
    }

    public function getNature(): string
    {
        return $this->nature;
    }

    public function setNature(string $nature): Covenant
    {
        $this->nature = $nature;

        return $this;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeImmutable $startDate): Covenant
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $delay): Covenant
    {
        $this->delay = $delay;

        return $this;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(DateTimeImmutable $endDate): Covenant
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getRecurrence(): ?string
    {
        return $this->recurrence;
    }

    public function setRecurrence(?string $recurrence): Covenant
    {
        $this->recurrence = $recurrence;

        return $this;
    }

    public function isFinancial(): bool
    {
        $financialNatures = [self::NATURE_FINANCIAL_RATIO, self::NATURE_FINANCIAL_ELEMENT];

        return \in_array($this->nature, $financialNatures);
    }

    /**
     * @return CovenantRule[]|Collection
     */
    public function getCovenantRules(): Collection
    {
        return $this->covenantRules;
    }

    public function addCovenantRule(CovenantRule $covenantRule): Covenant
    {
        $this->covenantRules[$covenantRule->getYear()] = $this->covenantRules[$covenantRule->getYear()] ?? $covenantRule;

        $this->covenantRules[$covenantRule->getYear()]->setInequality($covenantRule->getInequality());

        return $this;
    }

    public function removeCovenantRule(CovenantRule $covenantRule): Covenant
    {
        $this->covenantRules->removeElement($covenantRule);

        return $this;
    }

    public function getEndYear(): int
    {
        return (int) $this->endDate->format('Y');
    }

    public function getStartYear(): int
    {
        return (int) $this->startDate->format('Y');
    }

    /**
     * @return MarginRule[]|iterable
     */
    public function getMarginRules(): iterable
    {
        return $this->marginRules;
    }

    public function addMarginRule(MarginRule $rule): Covenant
    {
        if (false === $this->marginRules->contains($rule)) {
            $this->marginRules->add($rule);
        }

        return $this;
    }

    public function removeMarginRule(MarginRule $marginRule): Covenant
    {
        $this->marginRules->removeElement($marginRule);

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
    public function getRecurrences(): iterable
    {
        return static::getConstants('RECURRENCE_');
    }

    /**
     * @Assert\Callback
     */
    public function validateCovenantRules(ExecutionContextInterface $context)
    {
        $covenantRulesCount = \count($this->covenantRules);

        // non financial covenant must not have rules
        if (0 !== $covenantRulesCount && (false === $this->isFinancial())) {
            $context->buildViolation('Agency.Covenant.covenantRules.otherNature')
                ->atPath('covenantRules')
                ->addViolation()
            ;
        }

        // financial covenant must have 1 rule per year (including starting year)
        if ($this->isFinancial()) {
            foreach (\range($this->getStartYear(), $this->getEndYear()) as $year) {
                if (false === isset($this->covenantRules[$year])) {
                    $context->buildViolation('Agency.Covenant.covenantRules.missingYear')
                        ->atPath('covenantRules')
                        ->setParameter('{{ missingYear }}', (string) $year)
                        ->addViolation()
                    ;
                }
            }
        }
    }

    public function addTerm(Term $term)
    {
        $this->terms[] = $term;
    }

    public function removeTerm(Term $term)
    {
        $this->terms->removeElement($term);
    }

    /**
     * @Groups({"agency:covenant:read"})
     */
    public function isPublished(): bool
    {
        return null !== $this->publicationDate;
    }

    /**
     * This method actually publish a covenant.
     *
     * @throws Exception
     */
    public function publish(): Covenant
    {
        if ($this->isPublished()) {
            return $this;
        }

        $this->publicationDate = new DateTimeImmutable();

        if (null === $this->recurrence) {
            $this->terms[] = new Term($this, $this->startDate, $this->endDate);

            return $this;
        }

        // This create an iterable with each of the term start date
        // https://www.php.net/manual/fr/class.dateperiod.php
        $datePeriod = new DatePeriod($this->startDate, new DateInterval($this->recurrence), $this->endDate);

        foreach ($datePeriod as $termStart) {
            $termEnd       = DateTimeImmutable::createFromFormat('U', (string) \strtotime('+' . $this->delay . ' days', $termStart->getTimestamp()));
            $this->terms[] = new Term($this, $termStart, $termEnd);
        }

        return $this;
    }

    /**
     * @throws Exception
     *
     * @return $this
     */
    public function archive(): Covenant
    {
        $this->archivingDate = new DateTimeImmutable();

        /** @var Term $term */
        foreach ($this->terms->getValues() as $term) {
            $term->archive();

            if ($term->getStartDate() > $this->archivingDate) {
                $this->terms->removeElement($term);
            }
        }

        return $this;
    }

    public function getArchivingDate(): ?DateTimeImmutable
    {
        return $this->publicationDate;
    }

    /**
     * @return Collection|Term[]
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * Must be static : https://api-platform.com/docs/core/validation/#dynamic-validation-groups.
     *
     * @param Covenant $covenant
     *
     * @return array|string[]
     */
    public static function getCurrentValidationGroups(self $covenant): array
    {
        $validationGroups = ['Default', 'Covenant'];

        if ($covenant->isPublished()) {
            $validationGroups[] = 'published';
        }

        return $validationGroups;
    }

    public function isArchived(): bool
    {
        return null !== $this->archivingDate;
    }
}
