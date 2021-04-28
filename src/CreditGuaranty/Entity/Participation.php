<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Constant\CARegionalBank;
use Unilend\Core\Entity\Traits\CloneableTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"creditGuaranty:participation:read", "creditGuaranty:participation:read", "timestampable:read"}},
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
 *             "denormalization_context": {"groups": {"creditGuaranty:participation:write", "creditGuaranty:participation:create"}}
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
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program", inversedBy="participations")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:participation:read", "creditGuaranty:participation:create"})
     */
    private Program $program;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company")
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

    public function isParticipantValid(): bool
    {
        return in_array($this->getParticipant()->getShortCode(), CARegionalBank::REGIONAL_BANKS, true);
    }
}
