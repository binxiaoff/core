<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TreeElements
 *
 * @ORM\Table(name="tree_elements", uniqueConstraints={@ORM\UniqueConstraint(name="id_tree_2", columns={"id_tree", "id_element", "id_langue"})}, indexes={@ORM\Index(name="id_element", columns={"id_element"}), @ORM\Index(name="id_tree_3", columns={"id_tree"})})
 * @ORM\Entity
 */
class TreeElements
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_tree", type="integer")
     */
    private $idTree;

    /**
     * @var int
     *
     * @ORM\Column(name="id_element", type="integer")
     */
    private $idElement;

    /**
     * @var string
     *
     * @ORM\Column(name="id_langue", type="string", length=2)
     */
    private $idLangue;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text")
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="complement", type="text", length=16777215)
     */
    private $complement;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", nullable=false, options={"default" : 0})
     */
    private $status = 0;

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
     * Set idTree
     *
     * @param integer $idTree
     *
     * @return TreeElements
     */
    public function setIdTree($idTree)
    {
        $this->idTree = $idTree;

        return $this;
    }

    /**
     * Get idTree
     *
     * @return integer
     */
    public function getIdTree()
    {
        return $this->idTree;
    }

    /**
     * Set idElement
     *
     * @param integer $idElement
     *
     * @return TreeElements
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
     * @return TreeElements
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
     * @return TreeElements
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
     * @return TreeElements
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
     * @return TreeElements
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
     * @return TreeElements
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
     * @return TreeElements
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
