<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Table(name="elements")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Elements
{
    use TimestampableTrait;

    public const TYPE_PDF_SERVICE_TERMS = 143;

    /**
     * @var int
     *
     * @ORM\Column(name="id_template", type="integer")
     */
    private $idTemplate;

    /**
     * @var int
     *
     * @ORM\Column(name="id_bloc", type="integer")
     */
    private $idBloc;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=191)
     */
    private $slug;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre", type="integer", nullable=false, options={"default": 0})
     */
    private $ordre = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="type_element", type="string", length=50)
     */
    private $typeElement;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="id_element", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idElement;

    /**
     * @param int $idTemplate
     *
     * @return Elements
     */
    public function setIdTemplate($idTemplate)
    {
        $this->idTemplate = $idTemplate;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdTemplate()
    {
        return $this->idTemplate;
    }

    /**
     * @param int $idBloc
     *
     * @return Elements
     */
    public function setIdBloc($idBloc)
    {
        $this->idBloc = $idBloc;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdBloc()
    {
        return $this->idBloc;
    }

    /**
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
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
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param int $ordre
     *
     * @return Elements
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
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
     * @return string
     */
    public function getTypeElement()
    {
        return $this->typeElement;
    }

    /**
     * @param int $status
     *
     * @return Elements
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getIdElement()
    {
        return $this->idElement;
    }
}
