<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Table(name="client_login", indexes={
 *     @ORM\Index(name="idx_clients_login_ip", columns={"ip"}),
 *     @ORM\Index(name="idx_clients_login_added", columns={"added"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ClientLogin
{
    use TimestampableAddedOnlyTrait;

    public const ACTION_LOGIN   = 'login';
    public const ACTION_REFRESH = 'refresh';

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
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $client;

    /**
     * @var int
     *
     * @ORM\Column(name="action", type="string", length=10)
     */
    private $action;

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
     * @ORM\ManyToOne(targetEntity="UserAgent", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user_agent", referencedColumnName="id")
     * })
     */
    private $userAgent;

    /**
     * ClientLoginHistory constructor.
     *
     * @param Clients $client
     * @param string  $action
     */
    public function __construct(
        Clients $client,
        string $action
    ) {
        $this->client = $client;
        $this->action = $action;
    }

    /**
     * @return Clients
     */
    public function getClient(): Clients
    {
        return $this->client;
    }

    /**
     * @param string $action
     *
     * @return ClientLogin
     */
    public function setAction(string $action): ClientLogin
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return int
     */
    public function getAction(): int
    {
        return $this->action;
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
     * @return ClientLogin
     */
    public function setIp(?string $ip): ClientLogin
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
     * @return ClientLogin
     */
    public function setCountryIsoCode(?string $countryIsoCode): ClientLogin
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
     * @return ClientLogin
     */
    public function setCity(?string $city): ClientLogin
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return UserAgent|null
     */
    public function getUserAgent(): ?UserAgent
    {
        return $this->userAgent;
    }

    /**
     * @param UserAgent $userAgent
     *
     * @return ClientLogin
     */
    public function setUserAgent(?UserAgent $userAgent): ClientLogin
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
