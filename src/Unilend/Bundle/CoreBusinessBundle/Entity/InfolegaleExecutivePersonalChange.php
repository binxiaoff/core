<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InfolegaleExecutivePersonalChange
 *
 * @ORM\Table(name="infolegale_executive_personal_change", uniqueConstraints={@ORM\UniqueConstraint(name="unq_id_executive_infolegale_siren", columns={"id_executive", "siren", "code_position"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\InfolegaleExecutivePersonalChangeRepository")
 * @ORM\HasLifecycleCallbacks
 */
class InfolegaleExecutivePersonalChange
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_executive", type="integer")
     */
    private $idExecutive;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=10, nullable=true)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="first_name", type="string", length=50, nullable=true)
     */
    private $firstName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="last_name", type="string", length=50, nullable=true)
     */
    private $lastName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siren", type="string", length=9, nullable=true)
     */
    private $siren;

    /**
     * @var string
     * @ORM\Column(name="siren_if_company", type="string", length=9, nullable=true)
     */
    private $sirenIfCompany;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code_position", type="string", length=5, nullable=true)
     */
    private $codePosition;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", length=50, nullable=true)
     */
    private $position;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="nominated", type="date", nullable=true)
     */
    private $nominated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ended", type="date", nullable=true)
     */
    private $ended;

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Set idExecutive
     *
     * @param integer $idExecutive
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setIdExecutive($idExecutive)
    {
        $this->idExecutive = $idExecutive;

        return $this;
    }

    /**
     * Get idExecutive
     *
     * @return integer
     */
    public function getIdExecutive()
    {
        return $this->idExecutive;
    }

    /**
     * Set firstName
     *
     * @param string|null $firstName
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setFirstName(?string $firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string|null $lastName
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setLastName(?string $lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Set siren
     *
     * @param string|null $siren
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setSiren($siren)
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * Get siren
     *
     * @return string|null
     */
    public function getSiren()
    {
        return $this->siren;
    }

    /**
     * @return string|null
     */
    public function getSirenIfCompany(): ?string
    {
        return $this->sirenIfCompany;
    }

    /**
     * @param string|null $sirenIfCompany
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setSirenIfCompany(?string $sirenIfCompany): InfolegaleExecutivePersonalChange
    {
        if (false === empty((int) $sirenIfCompany)) {
            $this->sirenIfCompany = $sirenIfCompany;
        }

        return $this;
    }

    /**
     * Set codePosition
     *
     * @param string|null $codePosition
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setCodePosition($codePosition)
    {
        $this->codePosition = $codePosition;

        return $this;
    }

    /**
     * Get codePosition
     *
     * @return string|null
     */
    public function getCodePosition()
    {
        return $this->codePosition;
    }

    /**
     * Set position
     *
     * @param string $position
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set nominated
     *
     * @param \DateTime $nominated
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setNominated($nominated)
    {
        $this->nominated = $nominated;

        return $this;
    }

    /**
     * Get nominated
     *
     * @return \DateTime
     */
    public function getNominated()
    {
        return $this->nominated;
    }

    /**
     * Set ended
     *
     * @param \DateTime $ended
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setEnded($ended)
    {
        $this->ended = $ended;

        return $this;
    }

    /**
     * Get ended
     *
     * @return \DateTime
     */
    public function getEnded()
    {
        return $this->ended;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     *
     * @return InfolegaleExecutivePersonalChange
     */
    public function setTitle(?string $title): InfolegaleExecutivePersonalChange
    {
        $this->title = $title;

        return $this;
    }
}
