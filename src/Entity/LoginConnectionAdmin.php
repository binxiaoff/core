<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="login_connection_admin", indexes={@ORM\Index(name="id_user", columns={"id_user"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\LoginConnectionAdminRepository")
 * @ORM\HasLifecycleCallbacks
 */
class LoginConnectionAdmin
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_user", type="integer")
     */
    private $idUser;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_user", type="string", length=191, nullable=true)
     */
    private $nomUser;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=191)
     */
    private $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_connexion", type="datetime")
     */
    private $dateConnexion;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=50)
     */
    private $ip;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id_login_connection_admin", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLoginConnectionAdmin;

    /**
     * @param int $idUser
     *
     * @return LoginConnectionAdmin
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * @param string $nomUser
     *
     * @return LoginConnectionAdmin
     */
    public function setNomUser($nomUser)
    {
        $this->nomUser = $nomUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getNomUser()
    {
        return $this->nomUser;
    }

    /**
     * @param string $email
     *
     * @return LoginConnectionAdmin
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param \DateTime $dateConnexion
     *
     * @return LoginConnectionAdmin
     */
    public function setDateConnexion($dateConnexion)
    {
        $this->dateConnexion = $dateConnexion;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateConnexion()
    {
        return $this->dateConnexion;
    }

    /**
     * @param string $ip
     *
     * @return LoginConnectionAdmin
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param \DateTime $updated
     *
     * @return LoginConnectionAdmin
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $added
     *
     * @return LoginConnectionAdmin
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @return int
     */
    public function getIdLoginConnectionAdmin()
    {
        return $this->idLoginConnectionAdmin;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (!$this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
