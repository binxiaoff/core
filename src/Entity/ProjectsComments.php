<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectsComments
 *
 * @ORM\Table(name="projects_comments", indexes={@ORM\Index(name="projects_comments_id_project", columns={"id_project"}), @ORM\Index(name="projects_comments_id_user", columns={"id_user"}), @ORM\Index(name="idx_projects_comments_public", columns={"public"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectsComments
{
    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", length=16777215)
     */
    private $content;

    /**
     * @var bool
     *
     * @ORM\Column(name="public", type="boolean", nullable=false, options={"default" : 0})
     */
    private $public = false;

    /**
     * @var Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Projects", inversedBy="memos")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $idProject;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id_user")
     * })
     */
    private $idUser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_project_comment", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProjectComment;



    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return ProjectsComments
     */
    public function setIdProject(Projects $idProject): ProjectsComments
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return Projects
     */
    public function getIdProject(): Projects
    {
        return $this->idProject;
    }

    /**
     * Set idClient
     *
     * @param Clients $idClient
     *
     * @return ProjectsComments
     */
    public function setIdClient(Clients $idClient): ProjectsComments
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return Clients
     */
    public function getIdClient(): Clients
    {
        return $this->idClient;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return ProjectsComments
     */
    public function setContent(string $content): ProjectsComments
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set public
     *
     * @param bool $public
     *
     * @return ProjectsComments
     */
    public function setPublic(bool $public): ProjectsComments
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public
     *
     * @return bool
     */
    public function getPublic(): bool
    {
        return $this->public;
    }

    /**
     * Set idUser
     *
     * @param Users|null $idUser
     *
     * @return ProjectsComments
     */
    public function setIdUser(?Users $idUser): ProjectsComments
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return Users|null
     */
    public function getIdUser(): ?Users
    {
        return $this->idUser;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectsComments
     */
    public function setAdded(\DateTime $added): ProjectsComments
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime|null $updated
     *
     * @return ProjectsComments
     */
    public function setUpdated(?\DateTime $updated): ProjectsComments
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * Get idProjectComment
     *
     * @return int
     */
    public function getIdProjectComment(): int
    {
        return $this->idProjectComment;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new \DateTime();
    }
}
