<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Table(name="clients_history", indexes={
 *     @ORM\Index(name="idx_clients_history_ip", columns={"ip"}),
 *     @ORM\Index(name="idx_clients_history_added", columns={"added"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Repository\ClientsHistoryRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ClientsHistory
{
    use TimestampableAddedOnlyTrait;

    public const STATUS_ACTION_LOGIN            = 1;
    public const STATUS_ACTION_ACCOUNT_CREATION = 2;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
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
     * @var UserAgentHistory|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\UserAgentHistory")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user_agent_history", referencedColumnName="id")
     * })
     */
    private $userAgentHistory;

    /**
     * @var int
     *
     * @ORM\Column(name="id_history", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idHistory;

    /**
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
     * @return Clients
     */
    public function getIdClient(): Clients
    {
        return $this->idClient;
    }

    /**
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
     * @return UserAgentHistory|null
     */
    public function getUserAgentHistory(): ?UserAgentHistory
    {
        return $this->userAgentHistory;
    }

    /**
     * @param UserAgentHistory $userAgentHistory
     *
     * @return ClientsHistory
     */
    public function setUserAgentHistory(?UserAgentHistory $userAgentHistory): ClientsHistory
    {
        $this->userAgentHistory = $userAgentHistory;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdHistory(): int
    {
        return $this->idHistory;
    }
}
