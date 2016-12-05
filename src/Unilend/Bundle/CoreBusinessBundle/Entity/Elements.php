<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Elements
 *
 * @ORM\Table(name="elements")
 * @ORM\Entity
 */
class Elements
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_template", type="integer", nullable=false)
     */
    private $idTemplate;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_bloc", type="integer", nullable=false)
     */
    private $idBloc;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=191, nullable=false)
     */
    private $slug;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer", nullable=false)
     */
    private $ordre = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="type_element", type="string", length=50, nullable=false)
     */
    private $typeElement;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
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
     * @ORM\Column(name="id_element", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idElement;



    /**
     * Set idTemplate
     *
     * @param integer $idTemplate
     *
     * @return Elements
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
     * Set idBloc
     *
     * @param integer $idBloc
     *
     * @return Elements
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
     * Set name
     *
     * @param string $name
     *
     * @return Elements
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return Elements
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set ordre
     *
     * @param integer $ordre
     *
     * @return Elements
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
     * Set typeElement
     *
     * @param string $typeElement
     *
     * @return Elements
     */
    public function setTypeElement($typeElement)
    {
        $this->typeElement = $typeElement;

        return $this;
    }

    /**
     * Get typeElement
     *
     * @return string
     */
    public function getTypeElement()
    {
        return $this->typeElement;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Elements
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
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
     * @return Elements
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
     * @return Elements
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
     * Get idElement
     *
     * @return integer
     */
    public function getIdElement()
    {
        return $this->idElement;
    }
}
