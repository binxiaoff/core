<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Constant\CARegionalBank;
use KLS\Core\Entity\Traits\CloneableTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"creditGuaranty:participation:read"}},
 *     denormalizationContext={"groups": {"creditGuaranty:participation:write"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {"security": "is_granted('edit', object)"},
 *         "delete": {"security": "is_granted('delete', object)"}
 *     },
 *     collectionOperations={
 *         "post": {
 *             "post": {"security_post_denormalize": "is_granted('create', object)"},
 *             "denormalization_context": {"groups": {
 *                 "creditGuaranty:participation:write",
 *                 "creditGuaranty:participation:create"
 *             }}
 *         },
 *         "get": {
 *             "normalization_context": {"groups": {
 *                 "creditGuaranty:participation:list",
 *                 "creditGuaranty:programStatus:read",
 *                 "money:read"
 *             }}
 *         }
 *     }
 * )
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "creditGuaranty:program:read",
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="credit_guaranty_program_participation",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"id_company", "id_program"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"participant", "program"}, message="CreditGuaranty.Participation.participant.unique")
 */
class Participation
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use CloneableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\Entity\Program", inversedBy="participations")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(writableLink=false)
     *
     * @Groups({"creditGuaranty:participation:list", "creditGuaranty:participation:read", "creditGuaranty:participation:create"})
     */
    private Program $program;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_company", nullable=false)
     *
     * @Assert\Expression("this.isParticipantValid()")
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:participation:read", "creditGuaranty:participation:write"})
     */
    private Company $participant;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="1")
     *
     * @Groups({"creditGuaranty:participation:read", "creditGuaranty:participation:write"})
     */
    private string $quota;

    public function __construct(Program $program, Company $participant, string $quota)
    {
        $this->program     = $program;
        $this->participant = $participant;
        $this->quota       = $quota;
        $this->added       = new \DateTimeImmutable();
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function setProgram(Program $program): Participation
    {
        $this->program = $program;

        return $this;
    }

    public function getParticipant(): Company
    {
        return $this->participant;
    }

    public function getQuota(): string
    {
        return $this->quota;
    }

    public function setQuota(string $quota): Participation
    {
        $this->quota = $quota;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:participation:read"})
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    /**
     * @Groups({"creditGuaranty:participation:read"})
     */
    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    /**
     * @Groups({"creditGuaranty:participation:read", "creditGuaranty:participation:list"})
     */
    public function getReservationCount(): int
    {
        return $this->countParticipantReservationByStatus(ReservationStatus::STATUS_SENT);
    }

    /**
     * @Groups({"creditGuaranty:participation:list"})
     */
    public function getAcceptedReservationCount(): int
    {
        return $this->countParticipantReservationByStatus(ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY);
    }

    /**
     * @Groups({"creditGuaranty:participation:list"})
     */
    public function getFormalizedReservationCount(): int
    {
        return $this->countParticipantReservationByStatus(ReservationStatus::STATUS_CONTRACT_FORMALIZED);
    }

    /**
     * Used in an expression assert.
     */
    public function isParticipantValid(): bool
    {
        return \in_array($this->getParticipant()->getShortCode(), CARegionalBank::REGIONAL_BANKS, true);
    }

    private function countParticipantReservationByStatus(int $status): int
    {
        $count = 0;

        foreach ($this->getProgram()->getReservations() as $reservation) {
            if ($reservation->getManagingCompany() !== $this->getParticipant()) {
                continue;
            }

            if (ReservationStatus::STATUS_SENT === $status && $reservation->isSent()) {
                ++$count;
            }

            if (ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY === $status && $reservation->isAcceptedByManagingCompany()) {
                ++$count;
            }

            if (ReservationStatus::STATUS_CONTRACT_FORMALIZED === $status && $reservation->isFormalized()) {
                ++$count;
            }
        }

        return $count;
    }
}
