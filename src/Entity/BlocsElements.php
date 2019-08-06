<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="blocs_elements", uniqueConstraints={@ORM\UniqueConstraint(name="id_bloc", columns={"id_bloc", "id_element", "id_langue"})}, indexes={@ORM\Index(name="id_bloc_2", columns={"id_bloc"}), @ORM\Index(name="id_element", columns={"id_element"})})
 * @ORM\Entity
 */
class BlocsElements
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_bloc", type="integer")
     */
    private $idBloc;

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
     * @ORM\Column(name="value", type="text", length=16777215)
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
     * @ORM\Column(name="status", type="integer")
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
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @param int $idBloc
     *
     * @return BlocsElements
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
     * @param int $idElement
     *
     * @return BlocsElements
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
     * @return BlocsElements
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
     * @return BlocsElements
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
     * @return BlocsElements
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
     * @return BlocsElements
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
     * @return BlocsElements
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
     * @return BlocsElements
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
