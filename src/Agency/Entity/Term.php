<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="agency_term")
 *
 * @ApiResource(
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
class Term
{
    use PublicizeIdentityTrait;

    /**
     * @var Covenant
     *
     * @Assert\NotBlank
     *
     * @ORM\ManyToOne(targetEntity=Covenant::class, inversedBy="terms")
     * @ORM\JoinColumn(name="id_covenant", onDelete="CASCADE")
     */
    private Covenant $covenant;

    /**
     * @var DateTimeImmutable
     *
     * @Assert\NotBlank
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $start;

    /**
     * @var DateTimeImmutable
     *
     * @Assert\GreaterThan(propertyPath="start")
     * @Assert\NotBlank
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $end;

    /**
     * @var DateTimeImmutable|null
     *
     * @Assert\GreaterThanOrEqual(propertyPath="start")
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $sharingDate;

    /**
     * @var TermAnswer[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\TermAnswer", mappedBy="term")
     * @ORM\OrderBy({"added": "DESC"})
     *
     * @Assert\Valid
     */
    private Collection $answers;

    /**
     * @var DateTimeImmutable|null
     *
     * @Assert\GreaterThanOrEqual(propertyPath="start")
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $archivingDate;

    /**
     * @param Covenant               $covenant
     * @param DateTimeImmutable      $start
     * @param DateTimeImmutable|null $end
     */
    public function __construct(Covenant $covenant, DateTimeImmutable $start, ?DateTimeImmutable $end = null)
    {
        $this->covenant = $covenant;
        $this->start    = $start;
        $this->end      = $end ?? $start->add(DateInterval::createFromDateString('+ ' . $covenant->getDelay() . ' days'));
        $this->answers  = new ArrayCollection();
    }

    /**
     * @return Covenant
     */
    public function getCovenant(): Covenant
    {
        return $this->covenant;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getEnd(): DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * @param DateTimeImmutable $end
     *
     * @return Term
     */
    public function setEnd(DateTimeImmutable $end): Term
    {
        $this->end = $end;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getSharingDate(): ?DateTimeImmutable
    {
        return $this->sharingDate;
    }

    /**
     * @return bool
     */
    public function isShared(): bool
    {
        return null !== $this->sharingDate;
    }

    /**
     * @return Term
     *
     * @throws Exception
     */
    public function share(): Term
    {
        if ($this->isShared()) {
            return $this;
        }

        $this->sharingDate = new DateTimeImmutable();

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getArchivingDate(): ?DateTimeImmutable
    {
        return $this->archivingDate;
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return null !== $this->archivingDate;
    }

    /**
     * @return Term
     *
     * @throws Exception
     */
    public function archive(): Term
    {
        if ($this->isArchived() || false === $this->isShared()) {
            return $this;
        }

        $this->archivingDate = new DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection|TermAnswer[]
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @return TermAnswer|null;
     */
    public function getLastAnswer(): ?TermAnswer
    {
        return $this->answers->first();
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        $lastAnswer = $this->getLastAnswer();

        if (null === $lastAnswer) {
            return false;
        }

        return $lastAnswer->isValid();
    }

    /**
     * @return bool
     */
    public function isInvalid(): bool
    {
        $lastAnswer = $this->getLastAnswer();

        if (null === $lastAnswer) {
            return false;
        }

        return $lastAnswer->isInvalid();
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        $lastAnswer = $this->getLastAnswer();

        if (null === $lastAnswer) {
            return true;
        }

        return $lastAnswer->isPending();
    }

    /**
     * @return bool
     */
    public function isFinancial(): bool
    {
        return $this->getCovenant()->isFinancial();
    }

    /**
     * @return CovenantRule|null
     */
    public function getFinancialRule(): ?CovenantRule
    {
        if (false === $this->isFinancial()) {
            return null;
        }

        $year = (int) $this->getStart()->format('Y');

        return $this->getCovenant()->getCovenantRules()[$year];
    }

    /**
     * @return string
     */
    public function getNature(): string
    {
        return $this->getCovenant()->getNature();
    }

    /**
     * @return bool
     */
    public function isFulfilled(): bool
    {
        return $this->getLastAnswer()
            && $this->getLastAnswer()->isFulfilled()
            && $this->getStart() <= $this->getLastAnswer()->getAdded()
            && $this->getLastAnswer()->getAdded() <= $this->getEnd() ;
    }
}
