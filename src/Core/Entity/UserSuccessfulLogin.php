<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ORM\Table(name="core_user_successful_login", indexes={
 *     @ORM\Index(name="idx_user_successful_login_ip", columns={"ip"}),
 *     @ORM\Index(name="idx_user_successful_login_added", columns={"added"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Core\Repository\UserSuccessfulLoginRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserSuccessfulLogin
{
    use TimestampableAddedOnlyTrait;
    use ConstantsAwareTrait;

    public const ACTION_JWT_LOGIN       = 'jwt';
    public const ACTION_JWT_REFRESH     = 'jwt_refresh';
    public const ACTION_TEMPORARY_TOKEN = 'temporary_token';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user", referencedColumnName="id", nullable=false)
     * })
     */
    private User $user;

    /**
     * @ORM\Column(name="action", type="string", length=20)
     */
    private string $action;

    /**
     * @ORM\Column(name="ip", type="string", length=45, nullable=true)
     */
    private ?string $ip;

    /**
     * @ORM\Column(name="country_iso_code", type="string", length=2, nullable=true)
     */
    private ?string $countryIsoCode;

    /**
     * @ORM\Column(name="city", type="string", length=64, nullable=true)
     */
    private ?string $city;

    /**
     * @ORM\ManyToOne(targetEntity="UserAgent", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user_agent", referencedColumnName="id")
     * })
     */
    private ?UserAgent $userAgent;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $recaptchaScore;

    public function __construct(User $user, string $action)
    {
        if (false === \in_array($action, self::getActions(), true)) {
            throw new InvalidArgumentException(
                \sprintf('action should be one of these values (%s), %s given', \implode(', ', self::getActions()), $action)
            );
        }

        $this->user   = $user;
        $this->action = $action;
        $this->added  = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setAction(string $action): UserSuccessfulLogin
    {
        $this->action = $action;

        return $this;
    }

    public function getAction(): int
    {
        return $this->action;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): UserSuccessfulLogin
    {
        $this->ip = $ip;

        return $this;
    }

    public function getCountryIsoCode(): ?string
    {
        return $this->countryIsoCode;
    }

    public function setCountryIsoCode(?string $countryIsoCode): UserSuccessfulLogin
    {
        $this->countryIsoCode = $countryIsoCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): UserSuccessfulLogin
    {
        $this->city = $city;

        return $this;
    }

    public function getUserAgent(): ?UserAgent
    {
        return $this->userAgent;
    }

    public function setUserAgent(?UserAgent $userAgent): UserSuccessfulLogin
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getRecaptchaScore(): ?float
    {
        return $this->recaptchaScore;
    }

    public function setRecaptchaScore(?float $recaptchaScore): UserSuccessfulLogin
    {
        $this->recaptchaScore = $recaptchaScore;

        return $this;
    }

    private static function getActions(): array
    {
        return self::getConstants('ACTION_');
    }
}
