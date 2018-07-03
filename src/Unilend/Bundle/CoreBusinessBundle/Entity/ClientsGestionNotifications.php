<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsGestionNotifications
 *
 * @ORM\Table(name="clients_gestion_notifications", indexes={@ORM\Index(name="immediatement", columns={"immediatement"}), @ORM\Index(name="quotidienne", columns={"quotidienne"}), @ORM\Index(name="hebdomadaire", columns={"hebdomadaire"}), @ORM\Index(name="mensuelle", columns={"mensuelle"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ClientsGestionNotifications
{
    const TYPE_NOTIFICATION_IMMEDIATE = 'immediatement';
    const TYPE_NOTIFICATION_DAILY     = 'quotidienne';
    const TYPE_NOTIFICATION_WEEKLY    = 'hebdomadaire';
    const TYPE_NOTIFICATION_MONTHLY   = 'mensuelle';
    const TYPE_NOTIFICATION_NO_MAIL   = 'uniquement_notif';

    const ALL_PERIOD = [
        self::TYPE_NOTIFICATION_IMMEDIATE,
        self::TYPE_NOTIFICATION_DAILY,
        self::TYPE_NOTIFICATION_WEEKLY,
        self::TYPE_NOTIFICATION_MONTHLY,
        self::TYPE_NOTIFICATION_NO_MAIL
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="immediatement", type="integer", nullable=false)
     */
    private $immediatement;

    /**
     * @var int
     *
     * @ORM\Column(name="quotidienne", type="integer", nullable=false)
     */
    private $quotidienne;

    /**
     * @var int
     *
     * @ORM\Column(name="hebdomadaire", type="integer", nullable=false)
     */
    private $hebdomadaire;

    /**
     * @var int
     *
     * @ORM\Column(name="mensuelle", type="integer", nullable=false)
     */
    private $mensuelle;

    /**
     * @var int
     *
     * @ORM\Column(name="uniquement_notif", type="integer", nullable=false)
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
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_client", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idClient;

    /**
     * @var int
     *
     * @ORM\Column(name="id_notif", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idNotif;



    /**
     * Set immediatement
     *
     * @param int $immediatement
     *
     * @return ClientsGestionNotifications
     */
    public function setImmediatement(int $immediatement): ClientsGestionNotifications
    {
        $this->immediatement = $immediatement;

        return $this;
    }

    /**
     * Get immediatement
     *
     * @return int
     */
    public function getImmediatement(): int
    {
        return $this->immediatement;
    }

    /**
     * Set quotidienne
     *
     * @param int $quotidienne
     *
     * @return ClientsGestionNotifications
     */
    public function setQuotidienne(int $quotidienne): ClientsGestionNotifications
    {
        $this->quotidienne = $quotidienne;

        return $this;
    }

    /**
     * Get quotidienne
     *
     * @return int
     */
    public function getQuotidienne(): int
    {
        return $this->quotidienne;
    }

    /**
     * Set hebdomadaire
     *
     * @param int $hebdomadaire
     *
     * @return ClientsGestionNotifications
     */
    public function setHebdomadaire(int $hebdomadaire): ClientsGestionNotifications
    {
        $this->hebdomadaire = $hebdomadaire;

        return $this;
    }

    /**
     * Get hebdomadaire
     *
     * @return int
     */
    public function getHebdomadaire(): int
    {
        return $this->hebdomadaire;
    }

    /**
     * Set mensuelle
     *
     * @param int $mensuelle
     *
     * @return ClientsGestionNotifications
     */
    public function setMensuelle(int $mensuelle): ClientsGestionNotifications
    {
        $this->mensuelle = $mensuelle;

        return $this;
    }

    /**
     * Get mensuelle
     *
     * @return int
     */
    public function getMensuelle(): int
    {
        return $this->mensuelle;
    }

    /**
     * Set uniquementNotif
     *
     * @param int $uniquementNotif
     *
     * @return ClientsGestionNotifications
     */
    public function setUniquementNotif(int $uniquementNotif): ClientsGestionNotifications
    {
        $this->uniquementNotif = $uniquementNotif;

        return $this;
    }

    /**
     * Get uniquementNotif
     *
     * @return int
     */
    public function getUniquementNotif(): int
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
    public function setAdded(\DateTime $added): ClientsGestionNotifications
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
     * @return ClientsGestionNotifications
     */
    public function setUpdated(?\DateTime $updated): ClientsGestionNotifications
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
     * Set idClient
     *
     * @param int $idClient
     *
     * @return ClientsGestionNotifications
     */
    public function setIdClient(int $idClient): ClientsGestionNotifications
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
     * @return ClientsGestionNotifications
     */
    public function setIdNotif(int $idNotif): ClientsGestionNotifications
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
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new \DateTime();
    }
}
