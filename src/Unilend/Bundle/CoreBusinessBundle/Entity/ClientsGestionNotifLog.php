<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsGestionNotifLog
 *
 * @ORM\Table(name="clients_gestion_notif_log")
 * @ORM\Entity
 */
class ClientsGestionNotifLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_notif", type="integer", nullable=false)
     */
    private $idNotif;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=50, nullable=false)
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="debut", type="datetime", nullable=false)
     */
    private $debut;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="fin", type="datetime", nullable=false)
     */
    private $fin;

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
     * @ORM\Column(name="id_client_gestion_notif_log", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idClientGestionNotifLog;



    /**
     * Set idNotif
     *
     * @param integer $idNotif
     *
     * @return ClientsGestionNotifLog
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

    /**
     * Set type
     *
     * @param string $type
     *
     * @return ClientsGestionNotifLog
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set debut
     *
     * @param \DateTime $debut
     *
     * @return ClientsGestionNotifLog
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
     * @return ClientsGestionNotifLog
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ClientsGestionNotifLog
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
     * @return ClientsGestionNotifLog
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
     * Get idClientGestionNotifLog
     *
     * @return integer
     */
    public function getIdClientGestionNotifLog()
    {
        return $this->idClientGestionNotifLog;
    }
}
