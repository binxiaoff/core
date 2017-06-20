<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Xerfi
 *
 * @ORM\Table(name="xerfi", uniqueConstraints={@ORM\UniqueConstraint(name="naf", columns={"naf"})})
 * @ORM\Entity
 */
class Xerfi
{
    const UNILEND_NO_DATA           = 'PAS DE DONNEES';
    const UNILEND_ELIMINATION_SCORE = 'ELIMINATOIRE';
    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="score", type="integer", nullable=false)
     */
    private $score;

    /**
     * @var string
     *
     * @ORM\Column(name="unilend_rating", type="string", length=191, nullable=false)
     */
    private $unilendRating;

    /**
     * @var string
     *
     * @ORM\Column(name="naf", type="string", length=5)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $naf;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return Xerfi
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set score
     *
     * @param integer $score
     *
     * @return Xerfi
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return integer
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set unilendRating
     *
     * @param string $unilendRating
     *
     * @return Xerfi
     */
    public function setUnilendRating($unilendRating)
    {
        $this->unilendRating = $unilendRating;

        return $this;
    }

    /**
     * Get unilendRating
     *
     * @return string
     */
    public function getUnilendRating()
    {
        return $this->unilendRating;
    }

    /**
     * Get naf
     *
     * @return string
     */
    public function getNaf()
    {
        return $this->naf;
    }
}
