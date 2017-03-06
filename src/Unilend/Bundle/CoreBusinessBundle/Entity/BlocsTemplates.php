<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BlocsTemplates
 *
 * @ORM\Table(name="blocs_templates", uniqueConstraints={@ORM\UniqueConstraint(name="id_bloc", columns={"id_bloc", "id_template"})})
 * @ORM\Entity
 */
class BlocsTemplates
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_bloc", type="integer", nullable=false)
     */
    private $idBloc;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_template", type="integer", nullable=false)
     */
    private $idTemplate;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", nullable=false)
     */
    private $position;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set idBloc
     *
     * @param integer $idBloc
     *
     * @return BlocsTemplates
     */
    public function setIdBloc($idBloc)
    {
        $this->idBloc = $idBloc;

        return $this;
    }

    /**
     * Get idBloc
     *
     * @return integer
     */
    public function getIdBloc()
    {
        return $this->idBloc;
    }

    /**
     * Set idTemplate
     *
     * @param integer $idTemplate
     *
     * @return BlocsTemplates
     */
    public function setIdTemplate($idTemplate)
    {
        $this->idTemplate = $idTemplate;

        return $this;
    }

    /**
     * Get idTemplate
     *
     * @return integer
     */
    public function getIdTemplate()
    {
        return $this->idTemplate;
    }

    /**
     * Set position
     *
     * @param string $position
     *
     * @return BlocsTemplates
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
     * Set ordre
     *
     * @param integer $ordre
     *
     * @return BlocsTemplates
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return integer
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return BlocsTemplates
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return BlocsTemplates
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
     * @return BlocsTemplates
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
}
