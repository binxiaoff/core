<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * Users.
 *
 * @ORM\Table(name="users")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Users
{
    use TimestampableTrait;

    public const USER_ID_CRON       = -1;
    public const USER_ID_FRONT      = -2;
    public const USER_ID_WEBSERVICE = -3;

    public const STATUS_ONLINE  = 1;
    public const STATUS_OFFLINE = 0;

    /**
     * @var UsersTypes
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\UsersTypes")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user_type", referencedColumnName="id_user_type")
     * })
     */
    private $idUserType;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=191, unique=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=191)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=50)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=50)
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="slack", type="string", length=191, nullable=true)
     */
    private $slack;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=191, nullable=true)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=191)
     */
    private $password;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="password_edited", type="datetime", nullable=true)
     */
    private $passwordEdited;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private $lastlogin;

    /**
     * @var int
     *
     * @ORM\Column(name="id_user", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUser;

    /**
     * @param UsersTypes $idUserType
     *
     * @return Users
     */
    public function setIdUserType(UsersTypes $idUserType): Users
    {
        $this->idUserType = $idUserType;

        return $this;
    }

    /**
     * @return UsersTypes
     */
    public function getIdUserType(): UsersTypes
    {
        return $this->idUserType;
    }

    /**
     * @param string $firstname
     *
     * @return Users
     */
    public function setFirstname(string $firstname): Users
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @param string $name
     *
     * @return Users
     */
    public function setName(string $name): Users
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string|null $phone
     *
     * @return Users
     */
    public function setPhone(?string $phone): Users
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $mobile
     *
     * @return Users
     */
    public function setMobile(?string $mobile): Users
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * @param string $email
     *
     * @return Users
     */
    public function setEmail(string $email): Users
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string|null $slack
     *
     * @return Users
     */
    public function setSlack(?string $slack): Users
    {
        $this->slack = $slack;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSlack(): ?string
    {
        return $this->slack;
    }

    /**
     * @param string|null $ip
     *
     * @return Users
     */
    public function setIp(?string $ip): Users
    {
        $this->ip = $ip;

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
     * @param string $password
     *
     * @return Users
     */
    public function setPassword(string $password): Users
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param DateTime|null $passwordEdited
     *
     * @return Users
     */
    public function setPasswordEdited(?DateTime $passwordEdited): Users
    {
        $this->passwordEdited = $passwordEdited;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getPasswordEdited(): ?DateTime
    {
        return $this->passwordEdited;
    }

    /**
     * @param int $status
     *
     * @return Users
     */
    public function setStatus(int $status): Users
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
     * @param DateTime|null $lastlogin
     *
     * @return Users
     */
    public function setLastlogin(?DateTime $lastlogin): Users
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastlogin(): ?DateTime
    {
        return $this->lastlogin;
    }

    /**
     * @return int
     */
    public function getIdUser(): int
    {
        return $this->idUser;
    }
}
