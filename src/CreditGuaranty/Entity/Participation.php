<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\{ApiProperty, ApiResource};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\{Company, Constant\CARegionalBank, Traits\PublicizeIdentityTrait, Traits\TimestampableTrait};

/**
 * @ApiResource(
 *     normalizationContext={"groups":{"creditGuaranty:participation:read", "creditGuaranty:participation:read", "timestampable:read"}},
 *     denormalizationContext={"groups": {"creditGuaranty:participation:write"}},
 *      itemOperations={
 *          "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *          },
 *          "patch",
 *          "delete"
 *      },
 *      collectionOperations={
 *         "post"
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *      name="credit_guaranty_program_participation",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"id_company", "id_program"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"participant", "program"}, message="CreditGuaranty.Participation.participant.unique")
 */
class Participation
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program", inversedBy="participations")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:participation:read", "creditGuaranty:participation:write"})
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

    /**
     * @param Program $program
     * @param Company $participant
     * @param string  $quota
     */
    public function __construct(Program $program, Company $participant, string $quota)
    {
        $this->program     = $program;
        $this->participant = $participant;
        $this->quota       = $quota;
        $this->added       = new \DateTimeImmutable();
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @return Company
     */
    public function getParticipant(): Company
    {
        return $this->participant;
    }

    /**
     * @return string
     */
    public function getQuota(): string
    {
        return $this->quota;
    }

    /**
     * @param string $quota
     *
     * @return Participation
     */
    public function setQuota(string $quota): Participation
    {
        $this->quota = $quota;

        return $this;
    }

    /**
     * @return bool
     */
    public function isParticipantValid(): bool
    {
        return in_array($this->getParticipant()->getShortCode(), CARegionalBank::REGIONAL_BANKS, true);
    }
}
