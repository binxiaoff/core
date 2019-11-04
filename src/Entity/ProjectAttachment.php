<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @UniqueEntity({"project", "attachment"})
 *
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"id_project", "id_attachment"})
 *     }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @ApiResource(
 *     collectionOperations={
 *         "post"
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *         },
 *         "delete",
 *     }
 * )
 */
class ProjectAttachment
{
    use TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectAttachments")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * @var Attachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Attachment")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_attachment", nullable=false)
     * })
     */
    private $attachment;

    /**
     * @param Project    $project
     * @param Attachment $attachment
     *
     * @throws Exception
     */
    public function __construct(Project $project, Attachment $attachment)
    {
        $this->project    = $project;
        $this->attachment = $attachment;
        $this->added      = new DateTimeImmutable();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param Project $project
     *
     * @return ProjectAttachment
     */
    public function setProject(Project $project): ProjectAttachment
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Attachment $attachment
     *
     * @return ProjectAttachment
     */
    public function setAttachment(Attachment $attachment): ProjectAttachment
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return Attachment
     */
    public function getAttachment(): ?Attachment
    {
        return $this->attachment;
    }
}
