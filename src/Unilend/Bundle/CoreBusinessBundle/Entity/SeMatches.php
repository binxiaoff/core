<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SeMatches
 *
 * @ORM\Table(name="se_matches")
 * @ORM\Entity
 */
class SeMatches
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_word", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idWord;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_object", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idObject;

    /**
     * @var boolean
     *
     * @ORM\Column(name="object_type", type="boolean")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $objectType;



    /**
     * Set idWord
     *
     * @param integer $idWord
     *
     * @return SeMatches
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
     * Set idObject
     *
     * @param integer $idObject
     *
     * @return SeMatches
     */
    public function setIdObject($idObject)
    {
        $this->idObject = $idObject;

        return $this;
    }

    /**
     * Get idObject
     *
     * @return integer
     */
    public function getIdObject()
    {
        return $this->idObject;
    }

    /**
     * Set objectType
     *
     * @param boolean $objectType
     *
     * @return SeMatches
     */
    public function setObjectType($objectType)
    {
        $this->objectType = $objectType;

        return $this;
    }

    /**
     * Get objectType
     *
     * @return boolean
     */
    public function getObjectType()
    {
        return $this->objectType;
    }
}
