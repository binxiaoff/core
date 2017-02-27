<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Prescripteurs
 *
 * @ORM\Table(name="prescripteurs", indexes={@ORM\Index(name="id_client", columns={"id_client", "id_entite"}), @ORM\Index(name="id_enseigne", columns={"id_enseigne"})})
 * @ORM\Entity
 */
class Prescripteurs
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_enseigne", type="integer", nullable=false)
     */
    private $idEnseigne;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_entite", type="integer", nullable=false)
     */
    private $idEntite;

    /**
     * @var integer
     *
     * @ORM\Column(name="type_depot_dossier", type="integer", nullable=false)
     */
    private $typeDepotDossier;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_prescripteur", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idPrescripteur;



    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return Prescripteurs
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
     * Set idEnseigne
     *
     * @param integer $idEnseigne
     *
     * @return Prescripteurs
     */
    public function setIdEnseigne($idEnseigne)
    {
        $this->idEnseigne = $idEnseigne;

        return $this;
    }

    /**
     * Get idEnseigne
     *
     * @return integer
     */
    public function getIdEnseigne()
    {
        return $this->idEnseigne;
    }

    /**
     * Set idEntite
     *
     * @param integer $idEntite
     *
     * @return Prescripteurs
     */
    public function setIdEntite($idEntite)
    {
        $this->idEntite = $idEntite;

        return $this;
    }

    /**
     * Get idEntite
     *
     * @return integer
     */
    public function getIdEntite()
    {
        return $this->idEntite;
    }

    /**
     * Set typeDepotDossier
     *
     * @param integer $typeDepotDossier
     *
     * @return Prescripteurs
     */
    public function setTypeDepotDossier($typeDepotDossier)
    {
        $this->typeDepotDossier = $typeDepotDossier;

        return $this;
    }

    /**
     * Get typeDepotDossier
     *
     * @return integer
     */
    public function getTypeDepotDossier()
    {
        return $this->typeDepotDossier;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Prescripteurs
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Prescripteurs
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
     * Get idPrescripteur
     *
     * @return integer
     */
    public function getIdPrescripteur()
    {
        return $this->idPrescripteur;
    }
}
