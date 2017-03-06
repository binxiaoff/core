<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PartenairesClics
 *
 * @ORM\Table(name="partenaires_clics")
 * @ORM\Entity
 */
class PartenairesClics
{
    /**
     * @var string
     *
     * @ORM\Column(name="ip_adress", type="string", length=45, nullable=false)
     */
    private $ipAdress;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_clics", type="integer", nullable=false)
     */
    private $nbClics;

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
     * @ORM\Column(name="id_partenaire", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idPartenaire;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $date;



    /**
     * Set ipAdress
     *
     * @param string $ipAdress
     *
     * @return PartenairesClics
     */
    public function setIpAdress($ipAdress)
    {
        $this->ipAdress = $ipAdress;

        return $this;
    }

    /**
     * Get ipAdress
     *
     * @return string
     */
    public function getIpAdress()
    {
        return $this->ipAdress;
    }

    /**
     * Set nbClics
     *
     * @param integer $nbClics
     *
     * @return PartenairesClics
     */
    public function setNbClics($nbClics)
    {
        $this->nbClics = $nbClics;

        return $this;
    }

    /**
     * Get nbClics
     *
     * @return integer
     */
    public function getNbClics()
    {
        return $this->nbClics;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return PartenairesClics
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
     * @return PartenairesClics
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
     * Set idPartenaire
     *
     * @param integer $idPartenaire
     *
     * @return PartenairesClics
     */
    public function setIdPartenaire($idPartenaire)
    {
        $this->idPartenaire = $idPartenaire;

        return $this;
    }

    /**
     * Get idPartenaire
     *
     * @return integer
     */
    public function getIdPartenaire()
    {
        return $this->idPartenaire;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return PartenairesClics
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}
