<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogginConnectionAdmin
 *
 * @ORM\Table(name="loggin_connection_admin", indexes={@ORM\Index(name="id_user", columns={"id_user"})})
 * @ORM\Entity
 */
class LogginConnectionAdmin
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_user", type="string", length=191, nullable=false)
     */
    private $nomUser;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=191, nullable=false)
     */
    private $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_connexion", type="datetime", nullable=false)
     */
    private $dateConnexion;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=50, nullable=false)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="pays", type="string", length=191, nullable=false)
     */
    private $pays;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_loggin_connection_admin", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLogginConnectionAdmin;



    /**
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return LogginConnectionAdmin
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
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

    /**
     * Set nomUser
     *
     * @param string $nomUser
     *
     * @return LogginConnectionAdmin
     */
    public function setNomUser($nomUser)
    {
        $this->nomUser = $nomUser;

        return $this;
    }

    /**
     * Get nomUser
     *
     * @return string
     */
    public function getNomUser()
    {
        return $this->nomUser;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return LogginConnectionAdmin
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
     * Set dateConnexion
     *
     * @param \DateTime $dateConnexion
     *
     * @return LogginConnectionAdmin
     */
    public function setDateConnexion($dateConnexion)
    {
        $this->dateConnexion = $dateConnexion;

        return $this;
    }

    /**
     * Get dateConnexion
     *
     * @return \DateTime
     */
    public function getDateConnexion()
    {
        return $this->dateConnexion;
    }

    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return LogginConnectionAdmin
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
     * Set pays
     *
     * @param string $pays
     *
     * @return LogginConnectionAdmin
     */
    public function setPays($pays)
    {
        $this->pays = $pays;

        return $this;
    }

    /**
     * Get pays
     *
     * @return string
     */
    public function getPays()
    {
        return $this->pays;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return LogginConnectionAdmin
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LogginConnectionAdmin
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
     * Get idLogginConnectionAdmin
     *
     * @return integer
     */
    public function getIdLogginConnectionAdmin()
    {
        return $this->idLogginConnectionAdmin;
    }
}
