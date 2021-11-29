<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
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
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:participation:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:participation:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *         "patch": {"security": "is_granted('edit', object)"},
 *         "delete": {"security": "is_granted('delete', object)"},
 *     },
 *     collectionOperations={
 *         "post": {
 *             "post": {"security_post_denormalize": "is_granted('create', object)"},
 *             "denormalization_context": {
 *                 "groups": {
 *                     "creditGuaranty:participation:write",
 *                     "creditGuaranty:participation:create",
 *                 },
 *                 "openapi_definition_name": "collection-post-write",
 *             },
 *         },
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {
 *                     "creditGuaranty:participation:list",
 *                     "creditGuaranty:programStatus:read",
 *                     "money:read",
 *                 },
 *                 "openapi_definition_name": "collection-get-read",
 *             },
 *         },
 *     },
 * )
 *
 * @ApiFilter(NumericFilter::class, properties={"program.currentStatus.status"})
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "creditGuaranty:program:list",
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
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Program", inversedBy="participations")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(writableLink=false)
     *
     * @Groups({
     *     "creditGuaranty:participation:list",
     *     "creditGuaranty:participation:read",
     *     "creditGuaranty:participation:create",
     * })
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
     * Used in an expression assert.
     */
    public function isParticipantValid(): bool
    {
        return \in_array($this->getParticipant()->getShortCode(), CARegionalBank::REGIONAL_BANKS, true);
    }
}
