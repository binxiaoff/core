<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="tree_menu", uniqueConstraints={@ORM\UniqueConstraint(name="id_langue", columns={"id_langue", "id_menu", "nom", "value", "complement"})})
 * @ORM\Entity
 */
class TreeMenu
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_menu", type="integer")
     */
    private $idMenu;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=191)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=191)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="complement", type="string", length=191)
     */
    private $complement;

    /**
     * @var string
     *
     * @ORM\Column(name="target", type="string")
     */
    private $target;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre", type="integer", nullable=false, options={"default": 0})
     */
    private $ordre = 0;

    /**
     * 0 : Hors ligne 1: En ligne.
     *
     * @var bool
     *
     * @ORM\Column(name="status", type="boolean")
     */
    private $status;

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
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="id_langue", type="string", length=2)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idLangue;

    /**
     * @param int $idMenu
     *
     * @return TreeMenu
     */
    public function setIdMenu($idMenu)
    {
        $this->idMenu = $idMenu;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdMenu()
    {
        return $this->idMenu;
    }

    /**
     * @param string $nom
     *
     * @return TreeMenu
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * @param string $value
     *
     * @return TreeMenu
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
     * @return TreeMenu
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
     * @param string $target
     *
     * @return TreeMenu
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param int $ordre
     *
     * @return TreeMenu
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
     * @param bool $status
     *
     * @return TreeMenu
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param \DateTime $added
     *
     * @return TreeMenu
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
     * @return TreeMenu
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
     * @param int $id
     *
     * @return TreeMenu
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $idLangue
     *
     * @return TreeMenu
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
}
