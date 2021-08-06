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
     * @ORM\Column(name="username", type="string", length=191, nullable=true)
     */
    private ?string $username;

    /**
     * @ORM\Column(name="ip", type="string", length=191, nullable=true)
     */
    private ?string $ip;

    /**
     * @ORM\Column(name="error", type="string", length=191, nullable=true)
     */
    private ?string $error;

    /**
     * @ORM\Column(name="added", type="datetime_immutable")
     */
    private DateTimeImmutable $added;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $recaptchaScore;

    public function __construct()
    {
        $this->added = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): UserFailedLogin
    {
        $this->username = $username;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): UserFailedLogin
    {
        $this->ip = $ip;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): UserFailedLogin
    {
        $this->error = $error;

        return $this;
    }

    public function getRecaptchaScore(): ?float
    {
        return $this->recaptchaScore;
    }

    public function setRecaptchaScore(?float $recaptchaScore): UserFailedLogin
    {
        $this->recaptchaScore = $recaptchaScore;

        return $this;
    }
}
