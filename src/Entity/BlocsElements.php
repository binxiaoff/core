<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BlocsElements.
 *
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
     * Set idBloc.
     *
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
     * Get idBloc.
     *
     * @return int
     */
    public function getIdBloc()
    {
        return $this->idBloc;
    }

    /**
     * Set idElement.
     *
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
     * Get idElement.
     *
     * @return int
     */
    public function getIdElement()
    {
        return $this->idElement;
    }

    /**
     * Set idLangue.
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
     * Get idLangue.
     *
     * @return string
     */
    public function getIdLangue()
    {
        return $this->idLangue;
    }

    /**
     * Set value.
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
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set complement.
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
     * Get complement.
     *
     * @return string
     */
    public function getComplement()
    {
        return $this->complement;
    }

    /**
     * Set status.
     *
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
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set added.
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
     * Get added.
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated.
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
     * Get updated.
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
