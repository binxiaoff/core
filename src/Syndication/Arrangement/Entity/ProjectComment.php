<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Entity\User;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get": {"security": "is_granted('view', object.getProject())"},
 *         "post": {"security_post_denormalize": "is_granted('view', object.getProject())"},
 *     },
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object.getProject())"},
 *         "put": {"security_post_denormalize": "is_granted('edit', previous_object)"},
 *     },
 * )
 *
 * @Gedmo\Loggable(logEntryClass="KLS\Syndication\Arrangement\Entity\Versioned\VersionedProjectComment")
 *
 * @ORM\Entity
 * @ORM\Table(name="syndication_project_comment")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectComment
{
    use TimestampableTrait;

    public const VISIBILITY_ALL = 1;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var ProjectComment
     *
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Arrangement\Entity\ProjectComment", inversedBy="children")
     * @ORM\JoinColumn(name="id_parent", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="KLS\Syndication\Arrangement\Entity\ProjectComment", mappedBy="parent")
     */
    private $children;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Arrangement\Entity\Project", inversedBy="projectComments")
     * @ORM\JoinColumn(name="id_project", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $project;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\User")
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", length=16777215)
     *
     * @Gedmo\Versioned
     */
    private $content;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Gedmo\Versioned
     */
    private $visibility;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->added = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParent(): ?ProjectComment
    {
        return $this->parent;
    }

    public function setParent(?ProjectComment $parent): ProjectComment
    {
        $this->parent = $parent;

        return $this;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): ProjectComment
    {
        $this->project = $project;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): ProjectComment
    {
        $this->user = $user;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): ProjectComment
    {
        $this->content = $content;

        return $this;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): ProjectComment
    {
        $this->visibility = $visibility;

        return $this;
    }
}
