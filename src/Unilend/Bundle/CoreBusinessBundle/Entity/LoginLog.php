<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LoginLog
 *
 * @ORM\Table(name="login_log", indexes={@ORM\Index(name="pseudo", columns={"pseudo"})})
 * @ORM\Entity
 */
class LoginLog
{
    /**
     * @var string
     *
     * @ORM\Column(name="pseudo", type="string", length=191, nullable=false)
     */
    private $pseudo;

    /**
     * @var string
     *
     * @ORM\Column(name="IP", type="string", length=191, nullable=false)
     */
    private $ip;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_action", type="datetime", nullable=false)
     */
    private $dateAction;

    /**
     * @var boolean
     *
     * @ORM\Column(name="statut", type="boolean", nullable=false)
     */
    private $statut;

    /**
     * @var string
     *
     * @ORM\Column(name="retour", type="string", length=191, nullable=false)
     */
    private $retour;

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
     * @var integer
     *
     * @ORM\Column(name="id_log_login", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLogLogin;



    /**
     * Set pseudo
     *
     * @param string $pseudo
     *
     * @return LoginLog
     */
    public function setPseudo($pseudo)
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    /**
     * Get pseudo
     *
     * @return string
     */
    public function getPseudo()
    {
        return $this->pseudo;
    }

    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return LoginLog
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
     * Set dateAction
     *
     * @param \DateTime $dateAction
     *
     * @return LoginLog
     */
    public function setDateAction($dateAction)
    {
        $this->dateAction = $dateAction;

        return $this;
    }

    /**
     * Get dateAction
     *
     * @return \DateTime
     */
    public function getDateAction()
    {
        return $this->dateAction;
    }

    /**
     * Set statut
     *
     * @param boolean $statut
     *
     * @return LoginLog
     */
    public function setStatut($statut)
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get statut
     *
     * @return boolean
     */
    public function getStatut()
    {
        return $this->statut;
    }

    /**
     * Set retour
     *
     * @param string $retour
     *
     * @return LoginLog
     */
    public function setRetour($retour)
    {
        $this->retour = $retour;

        return $this;
    }

    /**
     * Get retour
     *
     * @return string
     */
    public function getRetour()
    {
        return $this->retour;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LoginLog
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
     * @return LoginLog
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
     * Get idLogLogin
     *
     * @return integer
     */
    public function getIdLogLogin()
    {
        return $this->idLogLogin;
    }
}
