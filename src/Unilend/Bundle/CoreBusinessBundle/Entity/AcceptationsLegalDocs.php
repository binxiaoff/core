<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AcceptationsLegalDocs
 *
 * @ORM\Table(name="acceptations_legal_docs", indexes={@ORM\Index(name="id_client", columns={"id_client"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class AcceptationsLegalDocs
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_legal_doc", type="integer", nullable=false)
     */
    private $idLegalDoc;

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
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_acceptation", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAcceptation;



    /**
     * Set idLegalDoc
     *
     * @param integer $idLegalDoc
     *
     * @return AcceptationsLegalDocs
     */
    public function setIdLegalDoc($idLegalDoc)
    {
        $this->idLegalDoc = $idLegalDoc;

        return $this;
    }

    /**
     * Get idLegalDoc
     *
     * @return integer
     */
    public function getIdLegalDoc()
    {
        return $this->idLegalDoc;
    }

    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return AcceptationsLegalDocs
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
     * @return AcceptationsLegalDocs
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
     * @return AcceptationsLegalDocs
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
     * Get idAcceptation
     *
     * @return integer
     */
    public function getIdAcceptation()
    {
        return $this->idAcceptation;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if(! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
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
