<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PreScoring
 *
 * @ORM\Table(name="pre_scoring", indexes={@ORM\Index(name="idx_altares_infolegale_euler_hermes_grade", columns={"altares", "infolegale", "euler_hermes_grade"})})
 * @ORM\Entity
 */
class PreScoring
{
    /**
     * @var string
     *
     * @ORM\Column(name="altares", type="string", length=2, nullable=false)
     */
    private $altares;

    /**
     * @var string
     *
     * @ORM\Column(name="infolegale", type="string", length=2, nullable=false)
     */
    private $infolegale;

    /**
     * @var string
     *
     * @ORM\Column(name="euler_hermes_grade", type="string", length=2, nullable=false)
     */
    private $eulerHermesGrade;

    /**
     * @var integer
     *
     * @ORM\Column(name="note", type="integer", nullable=false)
     */
    private $note;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set altares
     *
     * @param string $altares
     *
     * @return PreScoring
     */
    public function setAltares($altares)
    {
        $this->altares = $altares;

        return $this;
    }

    /**
     * Get altares
     *
     * @return string
     */
    public function getAltares()
    {
        return $this->altares;
    }

    /**
     * Set infolegale
     *
     * @param string $infolegale
     *
     * @return PreScoring
     */
    public function setInfolegale($infolegale)
    {
        $this->infolegale = $infolegale;

        return $this;
    }

    /**
     * Get infolegale
     *
     * @return string
     */
    public function getInfolegale()
    {
        return $this->infolegale;
    }

    /**
     * Set eulerHermesGrade
     *
     * @param string $eulerHermesGrade
     *
     * @return PreScoring
     */
    public function setEulerHermesGrade($eulerHermesGrade)
    {
        $this->eulerHermesGrade = $eulerHermesGrade;

        return $this;
    }

    /**
     * Get eulerHermesGrade
     *
     * @return string
     */
    public function getEulerHermesGrade()
    {
        return $this->eulerHermesGrade;
    }

    /**
     * Set note
     *
     * @param integer $note
     *
     * @return PreScoring
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return integer
     */
    public function getNote()
    {
        return $this->note;
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
