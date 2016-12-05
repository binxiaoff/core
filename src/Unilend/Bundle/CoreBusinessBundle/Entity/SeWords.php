<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SeWords
 *
 * @ORM\Table(name="se_words", indexes={@ORM\Index(name="id_word", columns={"id_word"})})
 * @ORM\Entity
 */
class SeWords
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_word", type="integer", nullable=false)
     */
    private $idWord;

    /**
     * @var string
     *
     * @ORM\Column(name="id_langue", type="string", length=2, nullable=false)
     */
    private $idLangue;

    /**
     * @var string
     *
     * @ORM\Column(name="word", type="string", length=191)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $word;



    /**
     * Set idWord
     *
     * @param integer $idWord
     *
     * @return SeWords
     */
    public function setIdWord($idWord)
    {
        $this->idWord = $idWord;

        return $this;
    }

    /**
     * Get idWord
     *
     * @return integer
     */
    public function getIdWord()
    {
        return $this->idWord;
    }

    /**
     * Set idLangue
     *
     * @param string $idLangue
     *
     * @return SeWords
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
     * Get word
     *
     * @return string
     */
    public function getWord()
    {
        return $this->word;
    }
}
