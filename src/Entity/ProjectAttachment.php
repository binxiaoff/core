<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource(
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object..getProject())"},
 *         "delete": {"security_post_denormalize": "is_granted('edit', object.getProject())"}
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('edit', object.getProject())",
 *             "denormalizationContext": "projectAttachment:create"
 *         }
 *     }
 * )
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectAttachmentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectAttachment
{
    use TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
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
     *
     * @Assert\NotBlank
     *
     * @Groups({"projectAttachment:create"})
     */
    private $project;

    /**
     * @var Attachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Attachment")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_attachment", nullable=false)
     * })
     *
     * @Assert\NotBlank
     *
     * @Groups({"projectAttachment:create"})
     */
    private $attachment;

    /**
     * @param Project    $project
     * @param Attachment $attachment
     */
    public function __construct(Project $project, Attachment $attachment)
    {
        $this->project    = $project;
        $this->attachment = $attachment;
    }

    /**
     * @return int|null
     */
    public function getId(): int
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
