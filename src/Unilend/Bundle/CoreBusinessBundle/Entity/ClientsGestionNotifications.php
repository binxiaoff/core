<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsGestionNotifications
 *
 * @ORM\Table(name="clients_gestion_notifications", indexes={@ORM\Index(name="immediatement", columns={"immediatement"}), @ORM\Index(name="quotidienne", columns={"quotidienne"}), @ORM\Index(name="hebdomadaire", columns={"hebdomadaire"}), @ORM\Index(name="mensuelle", columns={"mensuelle"})})
 * @ORM\Entity
 */
class ClientsGestionNotifications
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="immediatement", type="boolean", nullable=false)
     */
    private $immediatement;

    /**
     * @var boolean
     *
     * @ORM\Column(name="quotidienne", type="boolean", nullable=false)
     */
    private $quotidienne;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hebdomadaire", type="boolean", nullable=false)
     */
    private $hebdomadaire;

    /**
     * @var boolean
     *
     * @ORM\Column(name="mensuelle", type="boolean", nullable=false)
     */
    private $mensuelle;

    /**
     * @var boolean
     *
     * @ORM\Column(name="uniquement_notif", type="boolean", nullable=false)
     */
    private $uniquementNotif;

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
     * @ORM\Column(name="id_client", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idClient;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_notif", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idNotif;



    /**
     * Set immediatement
     *
     * @param boolean $immediatement
     *
     * @return ClientsGestionNotifications
     */
    public function setImmediatement($immediatement)
    {
        $this->immediatement = $immediatement;

        return $this;
    }

    /**
     * Get immediatement
     *
     * @return boolean
     */
    public function getImmediatement()
    {
        return $this->immediatement;
    }

    /**
     * Set quotidienne
     *
     * @param boolean $quotidienne
     *
     * @return ClientsGestionNotifications
     */
    public function setQuotidienne($quotidienne)
    {
        $this->quotidienne = $quotidienne;

        return $this;
    }

    /**
     * Get quotidienne
     *
     * @return boolean
     */
    public function getQuotidienne()
    {
        return $this->quotidienne;
    }

    /**
     * Set hebdomadaire
     *
     * @param boolean $hebdomadaire
     *
     * @return ClientsGestionNotifications
     */
    public function setHebdomadaire($hebdomadaire)
    {
        $this->hebdomadaire = $hebdomadaire;

        return $this;
    }

    /**
     * Get hebdomadaire
     *
     * @return boolean
     */
    public function getHebdomadaire()
    {
        return $this->hebdomadaire;
    }

    /**
     * Set mensuelle
     *
     * @param boolean $mensuelle
     *
     * @return ClientsGestionNotifications
     */
    public function setMensuelle($mensuelle)
    {
        $this->mensuelle = $mensuelle;

        return $this;
    }

    /**
     * Get mensuelle
     *
     * @return boolean
     */
    public function getMensuelle()
    {
        return $this->mensuelle;
    }

    /**
     * Set uniquementNotif
     *
     * @param boolean $uniquementNotif
     *
     * @return ClientsGestionNotifications
     */
    public function setUniquementNotif($uniquementNotif)
    {
        $this->uniquementNotif = $uniquementNotif;

        return $this;
    }

    /**
     * Get uniquementNotif
     *
     * @return boolean
     */
    public function getUniquementNotif()
    {
        return $this->uniquementNotif;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ClientsGestionNotifications
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
     * @return ClientsGestionNotifications
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
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return ClientsGestionNotifications
     */
    public function setIdClient($idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return integer
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set idNotif
     *
     * @param integer $idNotif
     *
     * @return ClientsGestionNotifications
     */
    public function setIdNotif($idNotif)
    {
        $this->idNotif = $idNotif;

        return $this;
    }

    /**
     * Get idNotif
     *
     * @return integer
     */
    public function getIdNotif()
    {
        return $this->idNotif;
    }
}
