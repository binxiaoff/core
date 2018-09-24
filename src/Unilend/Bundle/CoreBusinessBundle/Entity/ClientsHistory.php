<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsHistory
 *
 * @ORM\Table(name="clients_history", indexes={@ORM\Index(name="id_client", columns={"id_client"}), @ORM\Index(name="idx_clients_history_ip", columns={"ip"}), @ORM\Index(name="idx_clients_history_added", columns={"added"}), @ORM\Index(name="idx_clients_history_id_user_agent", columns={"id_user_agent"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ClientsHistory
{
    const TYPE_CLIENT_LENDER          = 1;
    const TYPE_CLIENT_BORROWER        = 2;
    const TYPE_CLIENT_LENDER_BORROWER = 3;
    const TYPE_CLIENT_PARTNER         = 4;

    const STATUS_ACTION_LOGIN            = 1;
    const STATUS_ACTION_ACCOUNT_CREATION = 2;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip", type="string", length=45, nullable=true)
     */
    private $ip;

    /**
     * @var string|null
     *
     * @ORM\Column(name="country_iso_code", type="string", length=2, nullable=true)
     */
    private $countryIsoCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="city", type="string", length=64, nullable=true)
     */
    private $city;

    /**
     * @var UserAgent|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\UserAgent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_agent", referencedColumnName="id")
     * })
     */
    private $idUserAgent;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_history", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idHistory;



    /**
     * Set idClient
     *
     * @param Clients $idClient
     *
     * @return ClientsHistory
     */
    public function setIdClient(Clients $idClient): ClientsHistory
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
     * Set type
     *
     * @param int $type
     *
     * @return ClientsHistory
     */
    public function setType(int $type): ClientsHistory
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param int $status
     *
     * @return ClientsHistory
     */
    public function setStatus(int $status): ClientsHistory
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * @param string|null $ip
     *
     * @return ClientsHistory
     */
    public function setIp(?string $ip): ClientsHistory
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountryIsoCode(): ?string
    {
        return $this->countryIsoCode;
    }

    /**
     * @param string|null $countryIsoCode
     *
     * @return ClientsHistory
     */
    public function setCountryIsoCode(?string $countryIsoCode): ClientsHistory
    {
        $this->countryIsoCode = $countryIsoCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     *
     * @return ClientsHistory
     */
    public function setCity(?string $city): ClientsHistory
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return UserAgent|null
     */
    public function getIdUserAgent(): ?UserAgent
    {
        return $this->idUserAgent;
    }

    /**
     * @param UserAgent $idUserAgent
     *
     * @return ClientsHistory
     */
    public function setIdUserAgent(?UserAgent $idUserAgent): ClientsHistory
    {
        $this->idUserAgent = $idUserAgent;

        return $this;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ClientsHistory
     */
    public function setAdded(\DateTime $added): ClientsHistory
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
     * Get idHistory
     *
     * @return int
     */
    public function getIdHistory(): int
    {
        return $this->idHistory;
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
}
