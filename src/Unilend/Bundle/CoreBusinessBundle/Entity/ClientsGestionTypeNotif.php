<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsGestionTypeNotif
 *
 * @ORM\Table(name="clients_gestion_type_notif")
 * @ORM\Entity
 */
class ClientsGestionTypeNotif
{
    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=191, nullable=false)
     */
    private $nom;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_client_gestion_type_notif", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idClientGestionTypeNotif;



    /**
     * Set nom
     *
     * @param string $nom
     *
     * @return ClientsGestionTypeNotif
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set ordre
     *
     * @param integer $ordre
     *
     * @return ClientsGestionTypeNotif
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return integer
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * Get idClientGestionTypeNotif
     *
     * @return integer
     */
    public function getIdClientGestionTypeNotif()
    {
        return $this->idClientGestionTypeNotif;
    }
}
