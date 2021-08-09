<?php

declare(strict_types=1);

namespace KLS\Syndication\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"ndaSignature:read", "timestampable:read", "blameable:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     },
 *     collectionOperations={
 *         "get",
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"ndaSignature:create"}}
 *         }
 *     }
 * )
 * @ORM\Table(
 *     name="syndication_nda_signature",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"added_by", "id_project_participation"}, name="uniq_added_by_project_participation")}
 * )
 * @ORM\Entity
 *
 * @UniqueEntity({"addedBy", "projectParticipation"})
 *
 * @ApiFilter(SearchFilter::class, properties={"projectParticipation.publicId": "exact"})
 */
class NDASignature
{
    use TimestampableAddedOnlyTrait;
    use BlamableAddedTrait;
    use PublicizeIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Entity\ProjectParticipation", inversedBy="ndaSignatures")
     * @ORM\JoinColumn(name="id_project_participation", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"ndaSignature:read", "ndaSignature:create"})
     *
     * @Assert\NotBlank
     */
    private ProjectParticipation $projectParticipation;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\FileVersion")
     * @ORM\JoinColumn(name="id_file_version")
     *
     * @Groups({"ndaSignature:read"})
     */
    private FileVersion $fileVersion;

    /**
     * @ORM\Column(type="text", length=65535)
     *
     * @Groups({"ndaSignature:read", "ndaSignature:create"})
     */
    private string $term;

    public function __construct(ProjectParticipation $projectParticipation, Staff $addedBy, string $term)
    {
        $this->projectParticipation = $projectParticipation;
        $this->fileVersion          = $projectParticipation->getAcceptableNdaVersion();
        $this->addedBy              = $addedBy;
        $this->added                = new DateTimeImmutable();
        $this->term                 = $term;
    }

    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    public function getFileVersion(): FileVersion
    {
        return $this->fileVersion;
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    /**
     * @Groups({"ndaSignature:read"})
     */
    public function getSignatory(): Staff
    {
        return $this->addedBy;
    }
}
