<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TemporaryLinksLogin
 *
 * @ORM\Table(name="temporary_links_login", uniqueConstraints={@ORM\UniqueConstraint(name="id_link", columns={"id_link"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class TemporaryLinksLogin
{
    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=150, nullable=false)
     */
    private $token;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires", type="datetime", nullable=false)
     */
    private $expires;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="accessed", type="datetime", nullable=false)
     */
    private $accessed;

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
     * @ORM\Column(name="id_link", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLink;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client")
     * })
     */
    private $idClient;


    /**
     * Set idClient
     *
     * @param Clients $idClient
     *
     * @return TemporaryLinksLogin
     */
    public function setIdClient(Clients $idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return Clients
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return TemporaryLinksLogin
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set expires
     *
     * @param \DateTime $expires
     *
     * @return TemporaryLinksLogin
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * Get expires
     *
     * @return \DateTime
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * Set accessed
     *
     * @param \DateTime $accessed
     *
     * @return TemporaryLinksLogin
     */
    public function setAccessed($accessed)
    {
        $this->accessed = $accessed;

        return $this;
    }

    /**
     * Get accessed
     *
     * @return \DateTime
     */
    public function getAccessed()
    {
        return $this->accessed;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return TemporaryLinksLogin
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
     * @return TemporaryLinksLogin
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
     * Get idLink
     *
     * @return integer
     */
    public function getIdLink()
    {
        return $this->idLink;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
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
