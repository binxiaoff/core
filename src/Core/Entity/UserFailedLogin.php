<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="core_user_failed_login",
 *     indexes={
 *         @ORM\Index(name="idx_user_failed_login_username", columns={"username"}),
 *         @ORM\Index(name="idx_user_failed_login_ip", columns={"ip"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Unilend\Core\Repository\UserFailedLoginRepository")
 */
class UserFailedLogin
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="username", type="string", length=191, nullable=true)
     */
    private ?string $username;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip", type="string", length=191, nullable=true)
     */
    private ?string $ip;

    /**
     * @var string|null
     *
     * @ORM\Column(name="error", type="string", length=191, nullable=true)
     */
    private ?string $error;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="added", type="datetime_immutable")
     */
    private DateTimeImmutable $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $recaptchaScore;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->added = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     *
     * @return UserFailedLogin
     */
    public function setUsername(?string $username): UserFailedLogin
    {
        $this->username = $username;

        return $this;
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
     * @return UserFailedLogin
     */
    public function setIp(?string $ip): UserFailedLogin
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string|null $error
     *
     * @return UserFailedLogin
     */
    public function setError(?string $error): UserFailedLogin
    {
        $this->error = $error;

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
     * @return UserFailedLogin
     */
    public function setRecaptchaScore(?float $recaptchaScore): UserFailedLogin
    {
        $this->recaptchaScore = $recaptchaScore;

        return $this;
    }
}
