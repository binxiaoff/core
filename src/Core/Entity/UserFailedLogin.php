<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\IdentityTrait;

/**
 * Represent an failed attempt by a person to connect to the API.
 * The reason for failure is recorded in the error field.
 * This table is likely to be populated by @see LoginLogSubscriber.
 *
 * @ORM\Table(
 *     name="core_user_failed_login",
 *     indexes={
 *         @ORM\Index(name="idx_user_failed_login_username", columns={"username"}),
 *         @ORM\Index(name="idx_user_failed_login_ip", columns={"ip"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="KLS\Core\Repository\UserFailedLoginRepository")
 */
class UserFailedLogin
{
    use IdentityTrait;

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

    public function __construct()
    {
        $this->added = new DateTimeImmutable();
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
}
