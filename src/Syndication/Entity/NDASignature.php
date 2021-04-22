<?php

declare(strict_types=1);

namespace Unilend\Syndication\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\{BlamableAddedTrait,
    PublicizeIdentityTrait,
    TimestampableAddedOnlyTrait};

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
 * @ApiFilter(SearchFilter::class, properties={"projectParticipation.publicId": "exact","addedBy.publicId": "exact"})
 */
class NDASignature
{
    use TimestampableAddedOnlyTrait;
    use BlamableAddedTrait;
    use PublicizeIdentityTrait;

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Syndication\Entity\ProjectParticipation", inversedBy="ndaSignatures")
     * @ORM\JoinColumn(name="id_project_participation", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"ndaSignature:read", "ndaSignature:create"})
     *
     * @Assert\NotBlank
     */
    private ProjectParticipation $projectParticipation;

    /**
     * @var FileVersion
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\FileVersion")
     * @ORM\JoinColumn(name="id_file_version")
     *
     * @Groups({"ndaSignature:read"})
     */
    private FileVersion $fileVersion;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=65535)
     *
     * @Groups({"ndaSignature:read", "ndaSignature:create"})
     */
    private string $term;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $addedBy
     * @param string               $term
     */
    public function __construct(ProjectParticipation $projectParticipation, Staff $addedBy, string $term)
    {
        $this->projectParticipation = $projectParticipation;
        $this->fileVersion          = $projectParticipation->getAcceptableNdaVersion();
        $this->addedBy              = $addedBy;
        $this->added                = new DateTimeImmutable();
        $this->term                 = $term;
    }

    /**
     * @return ProjectParticipation
     */
    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    /**
     * @return FileVersion
     */
    public function getFileVersion(): FileVersion
    {
        return $this->fileVersion;
    }

    /**
     * @return string
     */
    public function getTerm(): string
    {
        return $this->term;
    }

    /**
     * @Groups({"ndaSignature:read"})
     *
     * @return Staff
     */
    public function getSignatory(): Staff
    {
        return $this->addedBy;
    }
}
