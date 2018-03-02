<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Users
 *
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="email", columns={"email"})})
 * @ORM\Entity
 */
class Users
{
    const USER_ID_CRON       = -1;
    const USER_ID_FRONT      = -2;
    const USER_ID_WEBSERVICE = -3;
    // @todo useful because users can't belong to several groups for the moment
    const USER_ID_ALAIN_ELKAIM    = 28;
    const USER_ID_ARNAUD_SCHWARTZ = 23;
    const USER_ID_NICOLAS_LESUR   = 3;

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
     * @ORM\Column(name="phone", type="string", length=50, nullable=false)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=50, nullable=false)
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=191, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="slack", type="string", length=191, nullable=false)
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
     * @ORM\Column(name="password_edited", type="datetime", nullable=false)
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
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=false)
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
    public function setIdUserType(UsersTypes $idUserType)
    {
        $this->idUserType = $idUserType;

        return $this;
    }

    /**
     * Get idUserType
     *
     * @return UsersTypes
     */
    public function getIdUserType()
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
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
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
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return Users
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return Users
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
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
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set slack
     *
     * @param string $slack
     *
     * @return Users
     */
    public function setSlack($slack)
    {
        $this->slack = $slack;

        return $this;
    }

    /**
     * Get slack
     *
     * @return string
     */
    public function getSlack()
    {
        return $this->slack;
    }

    /**
     * Set IP range
     *
     * @param string $ip
     *
     * @return Users
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
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
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set passwordEdited
     *
     * @param \DateTime $passwordEdited
     *
     * @return Users
     */
    public function setPasswordEdited($passwordEdited)
    {
        $this->passwordEdited = $passwordEdited;

        return $this;
    }

    /**
     * Get passwordEdited
     *
     * @return \DateTime
     */
    public function getPasswordEdited()
    {
        return $this->passwordEdited;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Users
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
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
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Users
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set lastlogin
     *
     * @param \DateTime $lastlogin
     *
     * @return Users
     */
    public function setLastlogin($lastlogin)
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Get lastlogin
     *
     * @return \DateTime
     */
    public function getLastlogin()
    {
        return $this->lastlogin;
    }

    /**
     * Get idUser
     *
     * @return integer
     */
    public function getIdUser()
    {
        return $this->idUser;
    }
}
