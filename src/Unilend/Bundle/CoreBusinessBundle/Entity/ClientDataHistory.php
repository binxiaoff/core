<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientDataHistory
 *
 * @ORM\Table(name="client_data_history", indexes={@ORM\Index(name="idx_client_data_history_field", columns={"field"}), @ORM\Index(name="fk_client_data_history_id_client", columns={"id_client"}), @ORM\Index(name="fk_client_data_history_id_user", columns={"id_user"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ClientDataHistoryRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ClientDataHistory
{
    /**
     * @var string
     *
     * @ORM\Column(name="field", type="string", length=191, nullable=false)
     */
    private $field;

    /**
     * @var string
     *
     * @ORM\Column(name="old_value", type="string", length=191, nullable=true)
     */
    private $oldValue;

    /**
     * @var string
     *
     * @ORM\Column(name="new_value", type="string", length=191, nullable=true)
     */
    private $newValue;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_pending", type="datetime", nullable=false)
     */
    private $datePending;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_validated", type="datetime", nullable=true)
     */
    private $dateValidated;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id_user", nullable=false)
     * })
     */
    private $idUser;



    /**
     * Set field
     *
     * @param string $field
     *
     * @return ClientDataHistory
     */
    public function setField(string $field): ClientDataHistory
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Set oldValue
     *
     * @param string|null $oldValue
     *
     * @return ClientDataHistory
     */
    public function setOldValue(?string $oldValue): ClientDataHistory
    {
        $this->oldValue = $oldValue;

        return $this;
    }

    /**
     * Get oldValue
     *
     * @return string|null
     */
    public function getOldValue(): ?string
    {
        return $this->oldValue;
    }

    /**
     * Set newValue
     *
     * @param string|null $newValue
     *
     * @return ClientDataHistory
     */
    public function setNewValue(?string $newValue): ClientDataHistory
    {
        $this->newValue = $newValue;

        return $this;
    }

    /**
     * Get newValue
     *
     * @return string|null
     */
    public function getNewValue(): ?string
    {
        return $this->newValue;
    }

    /**
     * Set datePending
     *
     * @param \DateTime $datePending
     *
     * @return ClientDataHistory
     */
    public function setDatePending(\DateTime $datePending): ClientDataHistory
    {
        $this->datePending = $datePending;

        return $this;
    }

    /**
     * Get datePending
     *
     * @return \DateTime
     */
    public function getDatePending(): \DateTime
    {
        return $this->datePending;
    }

    /**
     * Set dateValidated
     *
     * @param \DateTime|null $dateValidated
     *
     * @return ClientDataHistory
     */
    public function setDateValidated(?\DateTime $dateValidated): ClientDataHistory
    {
        $this->dateValidated = $dateValidated;

        return $this;
    }

    /**
     * Get dateValidated
     *
     * @return \DateTime|null
     */
    public function getDateValidated(): ?\DateTime
    {
        return $this->dateValidated;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set idClient
     *
     * @param Clients $idClient
     *
     * @return ClientDataHistory
     */
    public function setIdClient(Clients $idClient): ClientDataHistory
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return Clients
     */
    public function getIdClient(): Clients
    {
        return $this->idClient;
    }

    /**
     * Set idUser
     *
     * @param Users $idUser
     *
     * @return ClientDataHistory
     */
    public function setIdUser(Users $idUser): ClientDataHistory
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return Users
     */
    public function getIdUser(): Users
    {
        return $this->idUser;
    }

    /**
     * @ORM\PrePersist
     */
    public function setDatePendingValue(): void
    {
        if (! $this->datePending instanceof \DateTime || 1 > $this->getDatePending()->getTimestamp()) {
            $this->datePending = new \DateTime();
        }
    }
}
