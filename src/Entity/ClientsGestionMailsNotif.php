<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsGestionMailsNotif
 *
 * @ORM\Table(name="clients_gestion_mails_notif", indexes={@ORM\Index(name="id_client", columns={"id_client"}), @ORM\Index(name="id_notif", columns={"id_notif"}), @ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="date_notif", columns={"date_notif"}), @ORM\Index(name="status_check_quotidienne", columns={"status_check_quotidienne"}), @ORM\Index(name="status_check_hebdomadaire", columns={"status_check_hebdomadaire"}), @ORM\Index(name="status_check_mensuelle", columns={"status_check_mensuelle"}), @ORM\Index(name="idx_clients_gestion_mails_notif_id_notification", columns={"id_notification"}), @ORM\Index(name="immediatement", columns={"immediatement"}), @ORM\Index(name="quotidienne", columns={"quotidienne"}), @ORM\Index(name="hebdomadaire", columns={"hebdomadaire"}), @ORM\Index(name="mensuelle", columns={"mensuelle"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ClientsGestionMailsNotif
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_client", type="integer")
     */
    private $idClient;

    /**
     * @var int
     *
     * @ORM\Column(name="id_notif", type="integer")
     */
    private $idNotif;

    /**
     * @var int
     *
     * @ORM\Column(name="id_project", type="integer", nullable=true)
     */
    private $idProject;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_notif", type="datetime")
     */
    private $dateNotif;

    /**
     * @var int
     *
     * @ORM\Column(name="id_notification", type="integer", nullable=true)
     */
    private $idNotification;

    /**
     * @var int
     *
     * @ORM\Column(name="id_loan", type="integer", nullable=true)
     */
    private $idLoan;

    /**
     * @var bool
     *
     * @ORM\Column(name="immediatement", type="boolean", nullable=true)
     */
    private $immediatement;

    /**
     * @var bool
     *
     * @ORM\Column(name="quotidienne", type="boolean", nullable=true)
     */
    private $quotidienne;

    /**
     * @var bool
     *
     * @ORM\Column(name="status_check_quotidienne", type="boolean", nullable=true)
     */
    private $statusCheckQuotidienne;

    /**
     * @var bool
     *
     * @ORM\Column(name="hebdomadaire", type="boolean", nullable=true)
     */
    private $hebdomadaire;

    /**
     * @var bool
     *
     * @ORM\Column(name="status_check_hebdomadaire", type="boolean", nullable=true)
     */
    private $statusCheckHebdomadaire;

    /**
     * @var bool
     *
     * @ORM\Column(name="mensuelle", type="boolean", nullable=true)
     */
    private $mensuelle;

    /**
     * @var bool
     *
     * @ORM\Column(name="status_check_mensuelle", type="boolean", nullable=true)
     */
    private $statusCheckMensuelle;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_clients_gestion_mails_notif", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idClientsGestionMailsNotif;

    /**
     * @var \Unilend\Entity\WalletBalanceHistory
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\WalletBalanceHistory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet_balance_history", referencedColumnName="id")
     * })
     */
    private $idWalletBalanceHistory;

    /**
     * Set idClient
     *
     * @param int $idClient
     *
     * @return ClientsGestionMailsNotif
     */
    public function setIdClient(int $idClient): ClientsGestionMailsNotif
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return int
     */
    public function getIdClient(): int
    {
        return $this->idClient;
    }

    /**
     * Set idNotif
     *
     * @param int $idNotif
     *
     * @return ClientsGestionMailsNotif
     */
    public function setIdNotif(int $idNotif): ClientsGestionMailsNotif
    {
        $this->idNotif = $idNotif;

        return $this;
    }

    /**
     * Get idNotif
     *
     * @return int
     */
    public function getIdNotif(): int
    {
        return $this->idNotif;
    }

    /**
     * Set idProject
     *
     * @param int|null $idProject
     *
     * @return ClientsGestionMailsNotif
     */
    public function setIdProject(?int $idProject): ClientsGestionMailsNotif
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return int|null
     */
    public function getIdProject(): ?int
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
    public function setDateNotif(\DateTime $dateNotif)
    {
        $this->dateNotif = $dateNotif;

        return $this;
    }

    /**
     * Get dateNotif
     *
     * @return \DateTime
     */
    public function getDateNotif(): \DateTime
    {
        return $this->dateNotif;
    }

    /**
     * Set idNotification
     *
     * @param int|null $idNotification
     *
     * @return ClientsGestionMailsNotif
     */
    public function setIdNotification(?int $idNotification): ClientsGestionMailsNotif
    {
        $this->idNotification = $idNotification;

        return $this;
    }

    /**
     * Get idNotification
     *
     * @return int|null
     */
    public function getIdNotification(): ?int
    {
        return $this->idNotification;
    }

    /**
     * Set idLoan
     *
     * @param int|null $idLoan
     *
     * @return ClientsGestionMailsNotif
     */
    public function setIdLoan(?int $idLoan): ClientsGestionMailsNotif
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return int|null
     */
    public function getIdLoan(): ?int
    {
        return $this->idLoan;
    }

    /**
     * Set immediatement
     *
     * @param bool|null $immediatement
     *
     * @return ClientsGestionMailsNotif
     */
    public function setImmediatement(?bool $immediatement): ClientsGestionMailsNotif
    {
        $this->immediatement = $immediatement;

        return $this;
    }

    /**
     * Get immediatement
     *
     * @return bool|null
     */
    public function getImmediatement(): ?bool
    {
        return $this->immediatement;
    }

    /**
     * Set quotidienne
     *
     * @param bool|null $quotidienne
     *
     * @return ClientsGestionMailsNotif
     */
    public function setQuotidienne(?bool $quotidienne): ClientsGestionMailsNotif
    {
        $this->quotidienne = $quotidienne;

        return $this;
    }

    /**
     * Get quotidienne
     *
     * @return bool|null
     */
    public function getQuotidienne(): ?bool
    {
        return $this->quotidienne;
    }

    /**
     * Set statusCheckQuotidienne
     *
     * @param bool|null $statusCheckQuotidienne
     *
     * @return ClientsGestionMailsNotif
     */
    public function setStatusCheckQuotidienne(?bool $statusCheckQuotidienne): ClientsGestionMailsNotif
    {
        $this->statusCheckQuotidienne = $statusCheckQuotidienne;

        return $this;
    }

    /**
     * Get statusCheckQuotidienne
     *
     * @return bool|null
     */
    public function getStatusCheckQuotidienne(): ?bool
    {
        return $this->statusCheckQuotidienne;
    }

    /**
     * Set hebdomadaire
     *
     * @param bool|null $hebdomadaire
     *
     * @return ClientsGestionMailsNotif
     */
    public function setHebdomadaire(?bool $hebdomadaire): ClientsGestionMailsNotif
    {
        $this->hebdomadaire = $hebdomadaire;

        return $this;
    }

    /**
     * Get hebdomadaire
     *
     * @return bool|null
     */
    public function getHebdomadaire(): ?bool
    {
        return $this->hebdomadaire;
    }

    /**
     * Set statusCheckHebdomadaire
     *
     * @param bool|null $statusCheckHebdomadaire
     *
     * @return ClientsGestionMailsNotif
     */
    public function setStatusCheckHebdomadaire(?bool $statusCheckHebdomadaire): ClientsGestionMailsNotif
    {
        $this->statusCheckHebdomadaire = $statusCheckHebdomadaire;

        return $this;
    }

    /**
     * Get statusCheckHebdomadaire
     *
     * @return bool|null
     */
    public function getStatusCheckHebdomadaire(): ?bool
    {
        return $this->statusCheckHebdomadaire;
    }

    /**
     * Set mensuelle
     *
     * @param bool|null $mensuelle
     *
     * @return ClientsGestionMailsNotif
     */
    public function setMensuelle(?bool $mensuelle): ClientsGestionMailsNotif
    {
        $this->mensuelle = $mensuelle;

        return $this;
    }

    /**
     * Get mensuelle
     *
     * @return bool|null
     */
    public function getMensuelle(): ?bool
    {
        return $this->mensuelle;
    }

    /**
     * Set statusCheckMensuelle
     *
     * @param bool|null $statusCheckMensuelle
     *
     * @return ClientsGestionMailsNotif
     */
    public function setStatusCheckMensuelle(?bool $statusCheckMensuelle): ClientsGestionMailsNotif
    {
        $this->statusCheckMensuelle = $statusCheckMensuelle;

        return $this;
    }

    /**
     * Get statusCheckMensuelle
     *
     * @return bool|null
     */
    public function getStatusCheckMensuelle(): ?bool
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
    public function setAdded(\DateTime $added): ClientsGestionMailsNotif
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime|null $updated
     *
     * @return ClientsGestionMailsNotif
     */
    public function setUpdated(?\DateTime $updated): ClientsGestionMailsNotif
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * Get idClientsGestionMailsNotif
     *
     * @return int
     */
    public function getIdClientsGestionMailsNotif(): int
    {
        return $this->idClientsGestionMailsNotif;
    }

    /**
     * Set idWalletBalanceHistory
     *
     * @param WalletBalanceHistory $idWalletBalanceHistory
     *
     * @return ClientsGestionMailsNotif
     */
    public function setIdWalletBalanceHistory(WalletBalanceHistory $idWalletBalanceHistory)
    {
        $this->idWalletBalanceHistory = $idWalletBalanceHistory;

        return $this;
    }

    /**
     * Get idWalletBalanceHistory
     *
     * @return WalletBalanceHistory
     */
    public function getIdWalletBalanceHistory()
    {
        return $this->idWalletBalanceHistory;
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
