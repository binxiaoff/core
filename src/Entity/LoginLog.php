<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LoginLog
 *
 * @ORM\Table(name="login_log", indexes={@ORM\Index(name="pseudo", columns={"pseudo"}), @ORM\Index(name="idx_login_log_IP", columns={"IP"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\LoginLogRepository")
 * @ORM\HasLifecycleCallbacks
 */
class LoginLog
{
    /**
     * @var string
     *
     * @ORM\Column(name="pseudo", type="string", length=191)
     */
    private $pseudo;

    /**
     * @var string
     *
     * @ORM\Column(name="IP", type="string", length=191)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="retour", type="string", length=191)
     */
    private $retour;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
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
    public function setPseudo(string $pseudo): LoginLog
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    /**
     * Get pseudo
     *
     * @return string
     */
    public function getPseudo(): string
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
    public function setIp(string $ip): LoginLog
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Set retour
     *
     * @param string $retour
     *
     * @return LoginLog
     */
    public function setRetour(string $retour): LoginLog
    {
        $this->retour = $retour;

        return $this;
    }

    /**
     * Get retour
     *
     * @return string
     */
    public function getRetour(): string
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
    public function setAdded(\DateTime $added): LoginLog
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
     * Get idLogLogin
     *
     * @return int
     */
    public function getIdLogLogin(): int
    {
        return $this->idLogLogin;
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
}
