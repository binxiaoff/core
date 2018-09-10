<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GreenpointKyc
 *
 * @ORM\Table(name="greenpoint_kyc", uniqueConstraints={@ORM\UniqueConstraint(name="id_client", columns={"id_client"})}, indexes={@ORM\Index(name="index_gp_kyc_id_client", columns={"id_client"})})
 * @ORM\Entity
* @ORM\HasLifecycleCallbacks
 */
class GreenpointKyc
{
    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=3, nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_update", type="datetime", nullable=false)
     */
    private $lastUpdate;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients", mappedBy="idClient")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client")
     * })
     */
    private $idClient;

    /**
     * @var int
     *
     * @ORM\Column(name="id_greenpoint_kyc", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idGreenpointKyc;

    /**
     * @param Clients $idClient
     *
     * @return GreenpointKyc
     */
    public function setIdClient(Clients $idClient): GreenpointKyc
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getIdClient(): Clients
    {
        return $this->idClient;
    }

    /**
     * @param string $status
     *
     * @return GreenpointKyc
     */
    public function setStatus(string $status): GreenpointKyc
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param \DateTime $creationDate
     *
     * @return GreenpointKyc
     */
    public function setCreationDate(\DateTime $creationDate): GreenpointKyc
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $lastUpdate
     *
     * @return GreenpointKyc
     */
    public function setLastUpdate(\DateTime $lastUpdate): GreenpointKyc
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastUpdate(): \DateTime
    {
        return $this->lastUpdate;
    }

    /**
     * @param \DateTime $added
     *
     * @return GreenpointKyc
     */
    public function setAdded(\DateTime $added): GreenpointKyc
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * @param \DateTime|null $updated
     *
     * @return GreenpointKyc
     */
    public function setUpdated(?\DateTime $updated): GreenpointKyc
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * @return int
     */
    public function getIdGreenpointKyc(): int
    {
        return $this->idGreenpointKyc;
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
