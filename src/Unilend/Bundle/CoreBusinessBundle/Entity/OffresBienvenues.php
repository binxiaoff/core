<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OffresBienvenues
 *
 * @ORM\Table(name="offres_bienvenues", indexes={@ORM\Index(name="id_user", columns={"id_user"})})
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\OffresBienvenuesRepository")
 * @ORM\HasLifecycleCallbacks
 */
class OffresBienvenues
{
    const STATUS_OFFLINE = 0;
    const STATUS_ONLINE  = 1;

    const DISPLAY_HOME = 'home_page';
    const DISPLAY_LANDING_PAGE = 'landing_page';

    /**
     * @var integer
     *
     * @ORM\Column(name="montant", type="integer", nullable=false)
     */
    private $montant;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant_limit", type="integer", nullable=true)
     */
    private $montantLimit;

    /**
     * @var string
     *
     * @ORM\Column(name="display", type="string", length=45, nullable=false)
     */
    private $display;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="debut", type="date", nullable=false)
     */
    private $debut;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="fin", type="date", nullable=true)
     */
    private $fin;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_offre_bienvenue", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idOffreBienvenue;



    /**
     * Set montant
     *
     * @param integer $montant
     *
     * @return OffresBienvenues
     */
    public function setMontant($montant)
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Get montant
     *
     * @return integer
     */
    public function getMontant()
    {
        return $this->montant;
    }

    /**
     * Set montantLimit
     *
     * @param integer $montantLimit
     *
     * @return OffresBienvenues
     */
    public function setMontantLimit($montantLimit)
    {
        $this->montantLimit = $montantLimit;

        return $this;
    }

    /**
     * Get montantLimit
     *
     * @return integer
     */
    public function getMontantLimit()
    {
        return $this->montantLimit;
    }

    /**
     * Set display
     *
     * @param string $display
     *
     * @return OffresBienvenues
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display
     *
     * @return string
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set debut
     *
     * @param \DateTime $debut
     *
     * @return OffresBienvenues
     */
    public function setDebut($debut)
    {
        $this->debut = $debut;

        return $this;
    }

    /**
     * Get debut
     *
     * @return \DateTime
     */
    public function getDebut()
    {
        return $this->debut;
    }

    /**
     * Set fin
     *
     * @param \DateTime $fin
     *
     * @return OffresBienvenues
     */
    public function setFin($fin)
    {
        $this->fin = $fin;

        return $this;
    }

    /**
     * Get fin
     *
     * @return \DateTime
     */
    public function getFin()
    {
        return $this->fin;
    }

    /**
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return OffresBienvenues
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return integer
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return OffresBienvenues
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return OffresBienvenues
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
     * @return OffresBienvenues
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
     * Get idOffreBienvenue
     *
     * @return integer
     */
    public function getIdOffreBienvenue()
    {
        return $this->idOffreBienvenue;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
