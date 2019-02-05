<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Users
 *
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="email", columns={"email"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Users
{
    const USER_ID_CRON       = -1;
    const USER_ID_FRONT      = -2;
    const USER_ID_WEBSERVICE = -3;

    const STATUS_ONLINE  = 1;
    const STATUS_OFFLINE = 0;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\UsersTypes
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\UsersTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_type", referencedColumnName="id_user_type")
     * })
     */
    private $idUserType;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=191, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=191, nullable=false)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=50, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=50, nullable=true)
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
     * @ORM\Column(name="password", type="string", length=191, nullable=false)
     */
    private $password;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="password_edited", type="datetime", nullable=true)
     */
    private $passwordEdited;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private $lastlogin;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUser;



    /**
     * Set idUserType
     *
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
     * Get idUserType
     *
     * @return UsersTypes
     */
    public function getIdUserType(): UsersTypes
    {
        return $this->idUserType;
    }

    /**
     * Set firstname
     *
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
     * Get firstname
     *
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * Set name
     *
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
     * Get name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set phone
     *
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
     * Get phone
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Set mobile
     *
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
     * Get mobile
     *
     * @return string|null
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * Set email
     *
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
     * Get email
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set slack
     *
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
     * Get slack
     *
     * @return string|null
     */
    public function getSlack(): ?string
    {
        return $this->slack;
    }

    /**
     * Set IP range
     *
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
     * Get ip
     *
     * @return string|null
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * Set password
     *
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
     * Get password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set passwordEdited
     *
     * @param \DateTime|null $passwordEdited
     *
     * @return Users
     */
    public function setPasswordEdited(?\DateTime $passwordEdited): Users
    {
        $this->passwordEdited = $passwordEdited;

        return $this;
    }

    /**
     * Get passwordEdited
     *
     * @return \DateTime|null
     */
    public function getPasswordEdited(): ?\DateTime
    {
        return $this->passwordEdited;
    }

    /**
     * Set status
     *
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
     * Get status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Users
     */
    public function setAdded(\DateTime $added): Users
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime|null $updated
     *
     * @return Users
     */
    public function setUpdated(?\DateTime $updated): Users
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * Set lastlogin
     *
     * @param \DateTime|null $lastlogin
     *
     * @return Users
     */
    public function setLastlogin(?\DateTime $lastlogin): Users
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Get lastlogin
     *
     * @return \DateTime|null
     */
    public function getLastlogin(): ?\DateTime
    {
        return $this->lastlogin;
    }

    /**
     * Get idUser
     *
     * @return int
     */
    public function getIdUser(): int
    {
        return $this->idUser;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new \DateTime();
    }
}
