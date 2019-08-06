<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
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
     * @ORM\Column(name="status", type="smallint", nullable=false, options={"default": 0})
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
     * @param int $idTree
     *
     * @return TreeElements
     */
    public function setIdTree($idTree)
    {
        $this->idTree = $idTree;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdTree()
    {
        return $this->idTree;
    }

    /**
     * @param int $idElement
     *
     * @return TreeElements
     */
    public function setIdElement($idElement)
    {
        $this->idElement = $idElement;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdElement()
    {
        return $this->idElement;
    }

    /**
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
     * @return string
     */
    public function getIdLangue()
    {
        return $this->idLangue;
    }

    /**
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
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
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
     * @return string
     */
    public function getComplement()
    {
        return $this->complement;
    }

    /**
     * @param int $status
     *
     * @return TreeElements
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
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
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
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
