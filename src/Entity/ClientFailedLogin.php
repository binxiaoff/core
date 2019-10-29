<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * @ORM\Table(
 *     name="client_failed_login",
 *     indexes={
 *         @ORM\Index(name="idx_client_failed_login_username", columns={"username"}),
 *         @ORM\Index(name="idx_client_failed_login_ip", columns={"ip"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Unilend\Repository\ClientFailedLoginRepository")
 */
class ClientFailedLogin
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="username", type="string", length=191, nullable=true)
     */
    private $username;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip", type="string", length=191, nullable=true)
     */
    private $ip;

    /**
     * @var string|null
     *
     * @ORM\Column(name="retour", type="string", length=191, nullable=true)
     */
    private $error;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="added", type="datetime_immutable")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @throws Exception
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
     * @return ClientFailedLogin
     */
    public function setUsername(?string $username): ClientFailedLogin
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
     * @return ClientFailedLogin
     */
    public function setIp(?string $ip): ClientFailedLogin
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
     * @return ClientFailedLogin
     */
    public function setError(?string $error): ClientFailedLogin
    {
        $this->error = $error;

        return $this;
    }
}
