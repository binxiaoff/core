<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsStatusHistory
 *
 * @ORM\Table(name="clients_status_history", indexes={@ORM\Index(name="id_client", columns={"id_client"}), @ORM\Index(name="id_client_status", columns={"id_client_status"}), @ORM\Index(name="id_user", columns={"id_user"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ClientsStatusHistoryRepository")
 */
class ClientsStatusHistory
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
     * @ORM\Column(name="id_client_status", type="integer", nullable=false)
     */
    private $idClientStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", length=16777215, nullable=false)
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="numero_relance", type="integer", nullable=false)
     */
    private $numeroRelance;

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
     * @ORM\Column(name="id_client_status_history", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idClientStatusHistory;



    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return ClientsStatusHistory
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
     * Set idClientStatus
     *
     * @param integer $idClientStatus
     *
     * @return ClientsStatusHistory
     */
    public function setIdClientStatus($idClientStatus)
    {
        $this->idClientStatus = $idClientStatus;

        return $this;
    }

    /**
     * Get idClientStatus
     *
     * @return integer
     */
    public function getIdClientStatus()
    {
        return $this->idClientStatus;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return ClientsStatusHistory
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return ClientsStatusHistory
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
     * Set numeroRelance
     *
     * @param integer $numeroRelance
     *
     * @return ClientsStatusHistory
     */
    public function setNumeroRelance($numeroRelance)
    {
        $this->numeroRelance = $numeroRelance;

        return $this;
    }

    /**
     * Get numeroRelance
     *
     * @return integer
     */
    public function getNumeroRelance()
    {
        return $this->numeroRelance;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ClientsStatusHistory
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
     * @return ClientsStatusHistory
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
     * Get idClientStatusHistory
     *
     * @return integer
     */
    public function getIdClientStatusHistory()
    {
        return $this->idClientStatusHistory;
    }
}
