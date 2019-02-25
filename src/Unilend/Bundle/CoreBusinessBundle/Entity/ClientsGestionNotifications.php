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
     * @var bool
     *
     * @ORM\Column(name="immediatement", type="boolean")
     */
    private $immediatement;

    /**
     * @var bool
     *
     * @ORM\Column(name="quotidienne", type="boolean")
     */
    private $quotidienne;

    /**
     * @var bool
     *
     * @ORM\Column(name="hebdomadaire", type="boolean")
     */
    private $hebdomadaire;

    /**
     * @var bool
     *
     * @ORM\Column(name="mensuelle", type="boolean")
     */
    private $mensuelle;

    /**
     * @var bool
     *
     * @ORM\Column(name="uniquement_notif", type="boolean")
     */
    private $uniquementNotif;

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
     * @param bool $immediatement
     *
     * @return ClientsGestionNotifications
     */
    public function setImmediatement(bool $immediatement): ClientsGestionNotifications
    {
        $this->immediatement = $immediatement;

        return $this;
    }

    /**
     * Get immediatement
     *
     * @return bool
     */
    public function getImmediatement(): bool
    {
        return $this->immediatement;
    }

    /**
     * Set quotidienne
     *
     * @param bool $quotidienne
     *
     * @return ClientsGestionNotifications
     */
    public function setQuotidienne(bool $quotidienne): ClientsGestionNotifications
    {
        $this->quotidienne = $quotidienne;

        return $this;
    }

    /**
     * Get quotidienne
     *
     * @return bool
     */
    public function getQuotidienne(): bool
    {
        return $this->quotidienne;
    }

    /**
     * Set hebdomadaire
     *
     * @param bool $hebdomadaire
     *
     * @return ClientsGestionNotifications
     */
    public function setHebdomadaire(bool $hebdomadaire): ClientsGestionNotifications
    {
        $this->hebdomadaire = $hebdomadaire;

        return $this;
    }

    /**
     * Get hebdomadaire
     *
     * @return bool
     */
    public function getHebdomadaire(): bool
    {
        return $this->hebdomadaire;
    }

    /**
     * Set mensuelle
     *
     * @param bool $mensuelle
     *
     * @return ClientsGestionNotifications
     */
    public function setMensuelle(bool $mensuelle): ClientsGestionNotifications
    {
        $this->mensuelle = $mensuelle;

        return $this;
    }

    /**
     * Get mensuelle
     *
     * @return bool
     */
    public function getMensuelle(): bool
    {
        return $this->mensuelle;
    }

    /**
     * Set uniquementNotif
     *
     * @param bool $uniquementNotif
     *
     * @return ClientsGestionNotifications
     */
    public function setUniquementNotif(bool $uniquementNotif): ClientsGestionNotifications
    {
        $this->uniquementNotif = $uniquementNotif;

        return $this;
    }

    /**
     * Get uniquementNotif
     *
     * @return bool
     */
    public function getUniquementNotif(): bool
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
