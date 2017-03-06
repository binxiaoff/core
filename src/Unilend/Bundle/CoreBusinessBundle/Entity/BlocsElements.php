<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BlocsElements
 *
 * @ORM\Table(name="blocs_elements", uniqueConstraints={@ORM\UniqueConstraint(name="id_bloc", columns={"id_bloc", "id_element", "id_langue"})}, indexes={@ORM\Index(name="id_bloc_2", columns={"id_bloc"}), @ORM\Index(name="id_element", columns={"id_element"})})
 * @ORM\Entity
 */
class BlocsElements
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
     * @ORM\Column(name="id_element", type="integer", nullable=false)
     */
    private $idElement;

    /**
     * @var string
     *
     * @ORM\Column(name="id_langue", type="string", length=2, nullable=false)
     */
    private $idLangue;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", length=16777215, nullable=false)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="complement", type="text", length=16777215, nullable=false)
     */
    private $complement;

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
     * @return BlocsElements
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
     * Set idElement
     *
     * @param integer $idElement
     *
     * @return BlocsElements
     */
    public function setIdElement($idElement)
    {
        $this->idElement = $idElement;

        return $this;
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

    /**
     * Set idLangue
     *
     * @param string $idLangue
     *
     * @return BlocsElements
     */
    public function setIdLangue($idLangue)
    {
        $this->idLangue = $idLangue;

        return $this;
    }

    /**
     * Get idLangue
     *
     * @return string
     */
    public function getIdLangue()
    {
        return $this->idLangue;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return BlocsElements
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set complement
     *
     * @param string $complement
     *
     * @return BlocsElements
     */
    public function setComplement($complement)
    {
        $this->complement = $complement;

        return $this;
    }

    /**
     * Get complement
     *
     * @return string
     */
    public function getComplement()
    {
        return $this->complement;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return BlocsElements
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
     * @return BlocsElements
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
     * @return BlocsElements
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
