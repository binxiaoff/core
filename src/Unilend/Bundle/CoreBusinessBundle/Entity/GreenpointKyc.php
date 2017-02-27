<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GreenpointKyc
 *
 * @ORM\Table(name="greenpoint_kyc", uniqueConstraints={@ORM\UniqueConstraint(name="id_client", columns={"id_client"})}, indexes={@ORM\Index(name="index_gp_kyc_id_client", columns={"id_client"})})
 * @ORM\Entity
 */
class GreenpointKyc
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=3, nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=191, nullable=true)
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(name="pdf_token", type="string", length=191, nullable=true)
     */
    private $pdfToken;

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
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_greenpoint_kyc", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idGreenpointKyc;



    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return GreenpointKyc
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
     * Set status
     *
     * @param string $status
     *
     * @return GreenpointKyc
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return GreenpointKyc
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
     * Set pdfToken
     *
     * @param string $pdfToken
     *
     * @return GreenpointKyc
     */
    public function setPdfToken($pdfToken)
    {
        $this->pdfToken = $pdfToken;

        return $this;
    }

    /**
     * Get pdfToken
     *
     * @return string
     */
    public function getPdfToken()
    {
        return $this->pdfToken;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return GreenpointKyc
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set lastUpdate
     *
     * @param \DateTime $lastUpdate
     *
     * @return GreenpointKyc
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    /**
     * Get lastUpdate
     *
     * @return \DateTime
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return GreenpointKyc
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
     * @return GreenpointKyc
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
     * Get idGreenpointKyc
     *
     * @return integer
     */
    public function getIdGreenpointKyc()
    {
        return $this->idGreenpointKyc;
    }
}
