<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AcceptCookies
 *
 * @ORM\Table(name="accept_cookies", indexes={@ORM\Index(name="ip", columns={"ip"}), @ORM\Index(name="id_client", columns={"id_client"})})
 * @ORM\Entity
 */
class AcceptCookies
{
    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=50, nullable=false)
     */
    private $ip;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

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
     * @ORM\Column(name="id_accept_cookies", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAcceptCookies;



    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return AcceptCookies
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
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return AcceptCookies
     */
    public function setIdClient($idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return integer
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return AcceptCookies
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
     * @return AcceptCookies
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
     * Get idAcceptCookies
     *
     * @return integer
     */
    public function getIdAcceptCookies()
    {
        return $this->idAcceptCookies;
    }
}
