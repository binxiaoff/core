<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsHistoryActions
 *
 * @ORM\Table(name="clients_history_actions", indexes={@ORM\Index(name="idx_clients_history_actions_id_client_nom_form", columns={"id_client", "nom_form"})})
 * @ORM\Entity
 */
class ClientsHistoryActions
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_form", type="integer", nullable=false)
     */
    private $idForm;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_form", type="string", length=191, nullable=false)
     */
    private $nomForm;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var string
     *
     * @ORM\Column(name="serialize", type="text", length=16777215, nullable=false)
     */
    private $serialize;

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
     * @ORM\Column(name="id_client_history_action", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idClientHistoryAction;



    /**
     * Set idForm
     *
     * @param integer $idForm
     *
     * @return ClientsHistoryActions
     */
    public function setIdForm($idForm)
    {
        $this->idForm = $idForm;

        return $this;
    }

    /**
     * Get idForm
     *
     * @return integer
     */
    public function getIdForm()
    {
        return $this->idForm;
    }

    /**
     * Set nomForm
     *
     * @param string $nomForm
     *
     * @return ClientsHistoryActions
     */
    public function setNomForm($nomForm)
    {
        $this->nomForm = $nomForm;

        return $this;
    }

    /**
     * Get nomForm
     *
     * @return string
     */
    public function getNomForm()
    {
        return $this->nomForm;
    }

    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return ClientsHistoryActions
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
     * Set serialize
     *
     * @param string $serialize
     *
     * @return ClientsHistoryActions
     */
    public function setSerialize($serialize)
    {
        $this->serialize = $serialize;

        return $this;
    }

    /**
     * Get serialize
     *
     * @return string
     */
    public function getSerialize()
    {
        return $this->serialize;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ClientsHistoryActions
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
     * @return ClientsHistoryActions
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
     * Get idClientHistoryAction
     *
     * @return integer
     */
    public function getIdClientHistoryAction()
    {
        return $this->idClientHistoryAction;
    }
}
