<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ConvertApiCompteur
 *
 * @ORM\Table(name="convert_api_compteur")
 * @ORM\Entity
 */
class ConvertApiCompteur
{
    /**
     * @var integer
     *
     * @ORM\Column(name="compteur", type="integer", nullable=false)
     */
    private $compteur;

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
     * @ORM\Column(name="id_convert_api_compteur", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idConvertApiCompteur;



    /**
     * Set compteur
     *
     * @param integer $compteur
     *
     * @return ConvertApiCompteur
     */
    public function setCompteur($compteur)
    {
        $this->compteur = $compteur;

        return $this;
    }

    /**
     * Get compteur
     *
     * @return integer
     */
    public function getCompteur()
    {
        return $this->compteur;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ConvertApiCompteur
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
     * @return ConvertApiCompteur
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
     * Get idConvertApiCompteur
     *
     * @return integer
     */
    public function getIdConvertApiCompteur()
    {
        return $this->idConvertApiCompteur;
    }
}
