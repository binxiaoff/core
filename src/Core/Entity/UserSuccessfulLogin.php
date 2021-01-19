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
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user", referencedColumnName="id", nullable=false)
     * })
     */
    private User $user;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=20)
     */
    private string $action;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip", type="string", length=45, nullable=true)
     */
    private ?string $ip;

    /**
     * @var string|null
     *
     * @ORM\Column(name="country_iso_code", type="string", length=2, nullable=true)
     */
    private ?string $countryIsoCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="city", type="string", length=64, nullable=true)
     */
    private ?string $city;

    /**
     * @var UserAgent|null
     *
     * @ORM\ManyToOne(targetEntity="UserAgent", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user_agent", referencedColumnName="id")
     * })
     */
    private ?UserAgent $userAgent;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $recaptchaScore;

    /**
     * UserLoginHistory constructor.
     *
     * @param User   $user
     * @param string $action
     */
    public function __construct(User $user, string $action)
    {
        if (false === \in_array($action, self::getActions(), true)) {
            throw new InvalidArgumentException(
                sprintf('action should be one of these values (%s), %s given', implode(', ', self::getActions()), $action)
            );
        }

        $this->user = $user;
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
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param string $action
     *
     * @return UserSuccessfulLogin
     */
    public function setAction(string $action): UserSuccessfulLogin
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
     * @return UserSuccessfulLogin
     */
    public function setIp(?string $ip): UserSuccessfulLogin
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
     * @return UserSuccessfulLogin
     */
    public function setCountryIsoCode(?string $countryIsoCode): UserSuccessfulLogin
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
     * @return UserSuccessfulLogin
     */
    public function setCity(?string $city): UserSuccessfulLogin
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
     * @param UserAgent|null $userAgent
     *
     * @return UserSuccessfulLogin
     */
    public function setUserAgent(?UserAgent $userAgent): UserSuccessfulLogin
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getRecaptchaScore(): ?float
    {
        return $this->recaptchaScore;
    }

    /**
     * @param float|null $recaptchaScore
     *
     * @return UserSuccessfulLogin
     */
    public function setRecaptchaScore(?float $recaptchaScore): UserSuccessfulLogin
    {
        $this->recaptchaScore = $recaptchaScore;

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
