<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsGestionMailsNotif
 *
 * @ORM\Table(name="clients_gestion_mails_notif", indexes={@ORM\Index(name=" id_client", columns={"id_client"}), @ORM\Index(name="id_notif", columns={"id_notif"}), @ORM\Index(name="id_transaction", columns={"id_transaction"}), @ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="date_notif", columns={"date_notif"}), @ORM\Index(name="status_check_quotidienne", columns={"status_check_quotidienne"}), @ORM\Index(name="status_check_hebdomadaire", columns={"status_check_hebdomadaire"}), @ORM\Index(name="status_check_mensuelle", columns={"status_check_mensuelle"}), @ORM\Index(name="idx_clients_gestion_mails_notif_id_notification", columns={"id_notification"}), @ORM\Index(name="immediatement", columns={"immediatement"}), @ORM\Index(name="quotidienne", columns={"quotidienne"}), @ORM\Index(name="hebdomadaire", columns={"hebdomadaire"}), @ORM\Index(name="mensuelle", columns={"mensuelle"})})
 * @ORM\Entity
 */
class ClientsGestionMailsNotif
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
     * @ORM\Column(name="id_notif", type="integer", nullable=false)
     */
    private $idNotif;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_notif", type="datetime", nullable=false)
     */
    private $dateNotif;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_notification", type="integer", nullable=false)
     */
    private $idNotification;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_transaction", type="integer", nullable=false)
     */
    private $idTransaction;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_loan", type="integer", nullable=false)
     */
    private $idLoan;

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
     * @ORM\Column(name="status_check_quotidienne", type="boolean", nullable=false)
     */
    private $statusCheckQuotidienne;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hebdomadaire", type="boolean", nullable=false)
     */
    private $hebdomadaire;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status_check_hebdomadaire", type="boolean", nullable=false)
     */
    private $statusCheckHebdomadaire;

    /**
     * @var boolean
     *
     * @ORM\Column(name="mensuelle", type="boolean", nullable=false)
     */
    private $mensuelle;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status_check_mensuelle", type="boolean", nullable=false)
     */
    private $statusCheckMensuelle;

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
     * @ORM\Column(name="id_clients_gestion_mails_notif", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idClientsGestionMailsNotif;



    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return ClientsGestionMailsNotif
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
     * @return ClientsGestionMailsNotif
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
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return ClientsGestionMailsNotif
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return integer
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set dateNotif
     *
     * @param \DateTime $dateNotif
     *
     * @return ClientsGestionMailsNotif
     */
    public function setDateNotif($dateNotif)
    {
        $this->dateNotif = $dateNotif;

        return $this;
    }

    /**
     * Get dateNotif
     *
     * @return \DateTime
     */
    public function getDateNotif()
    {
        return $this->dateNotif;
    }

    /**
     * Set idNotification
     *
     * @param integer $idNotification
     *
     * @return ClientsGestionMailsNotif
     */
    public function setIdNotification($idNotification)
    {
        $this->idNotification = $idNotification;

        return $this;
    }

    /**
     * Get idNotification
     *
     * @return integer
     */
    public function getIdNotification()
    {
        return $this->idNotification;
    }

    /**
     * Set idTransaction
     *
     * @param integer $idTransaction
     *
     * @return ClientsGestionMailsNotif
     */
    public function setIdTransaction($idTransaction)
    {
        $this->idTransaction = $idTransaction;

        return $this;
    }

    /**
     * Get idTransaction
     *
     * @return integer
     */
    public function getIdTransaction()
    {
        return $this->idTransaction;
    }

    /**
     * Set idLoan
     *
     * @param integer $idLoan
     *
     * @return ClientsGestionMailsNotif
     */
    public function setIdLoan($idLoan)
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return integer
     */
    public function getIdLoan()
    {
        return $this->idLoan;
    }

    /**
     * Set immediatement
     *
     * @param boolean $immediatement
     *
     * @return ClientsGestionMailsNotif
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
     * @return ClientsGestionMailsNotif
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
     * Set statusCheckQuotidienne
     *
     * @param boolean $statusCheckQuotidienne
     *
     * @return ClientsGestionMailsNotif
     */
    public function setStatusCheckQuotidienne($statusCheckQuotidienne)
    {
        $this->statusCheckQuotidienne = $statusCheckQuotidienne;

        return $this;
    }

    /**
     * Get statusCheckQuotidienne
     *
     * @return boolean
     */
    public function getStatusCheckQuotidienne()
    {
        return $this->statusCheckQuotidienne;
    }

    /**
     * Set hebdomadaire
     *
     * @param boolean $hebdomadaire
     *
     * @return ClientsGestionMailsNotif
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
     * Set statusCheckHebdomadaire
     *
     * @param boolean $statusCheckHebdomadaire
     *
     * @return ClientsGestionMailsNotif
     */
    public function setStatusCheckHebdomadaire($statusCheckHebdomadaire)
    {
        $this->statusCheckHebdomadaire = $statusCheckHebdomadaire;

        return $this;
    }

    /**
     * Get statusCheckHebdomadaire
     *
     * @return boolean
     */
    public function getStatusCheckHebdomadaire()
    {
        return $this->statusCheckHebdomadaire;
    }

    /**
     * Set mensuelle
     *
     * @param boolean $mensuelle
     *
     * @return ClientsGestionMailsNotif
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
     * Set statusCheckMensuelle
     *
     * @param boolean $statusCheckMensuelle
     *
     * @return ClientsGestionMailsNotif
     */
    public function setStatusCheckMensuelle($statusCheckMensuelle)
    {
        $this->statusCheckMensuelle = $statusCheckMensuelle;

        return $this;
    }

    /**
     * Get statusCheckMensuelle
     *
     * @return boolean
     */
    public function getStatusCheckMensuelle()
    {
        return $this->statusCheckMensuelle;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ClientsGestionMailsNotif
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
     * @return ClientsGestionMailsNotif
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
     * Get idClientsGestionMailsNotif
     *
     * @return integer
     */
    public function getIdClientsGestionMailsNotif()
    {
        return $this->idClientsGestionMailsNotif;
    }
}
