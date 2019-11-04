<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Table(name="client_successful_login", indexes={
 *     @ORM\Index(name="idx_client_successful_login_ip", columns={"ip"}),
 *     @ORM\Index(name="idx_client_successful_login_added", columns={"added"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Repository\ClientSuccessfulLoginRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ClientSuccessfulLogin
{
    use TimestampableAddedOnlyTrait;
    use ConstantsAwareTrait;

    public const ACTION_JWT_LOGIN       = 'jwt';
    public const ACTION_JWT_REFRESH     = 'jwt_refresh';
    public const ACTION_TEMPORARY_TOKEN = 'temporary_token';

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
     * @ORM\Column(name="action", type="string", length=20)
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
     *
     * @throws \Exception
     */
    public function __construct(Clients $client, string $action = self::ACTION_JWT_LOGIN)
    {
        if (false === in_array($action, self::getActions(), true)) {
            throw new InvalidArgumentException(
                sprintf('action should be one of these values (%s), %s given', implode(', ', self::getActions()), $action)
            );
        }

        $this->client = $client;
        $this->action = $action;
        $this->added  = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * @return ClientSuccessfulLogin
     */
    public function setAction(string $action): ClientSuccessfulLogin
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
     * @return ClientSuccessfulLogin
     */
    public function setIp(?string $ip): ClientSuccessfulLogin
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
     * @return ClientSuccessfulLogin
     */
    public function setCountryIsoCode(?string $countryIsoCode): ClientSuccessfulLogin
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
     * @return ClientSuccessfulLogin
     */
    public function setCity(?string $city): ClientSuccessfulLogin
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
     * @return ClientSuccessfulLogin
     */
    public function setUserAgent(?UserAgent $userAgent): ClientSuccessfulLogin
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return array
     */
    private static function getActions(): array
    {
        return self::getConstants('ACTION_');
    }
}
