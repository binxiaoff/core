<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AcceptationsLegalDocsRelances
 *
 * @ORM\Table(name="acceptations_legal_docs_relances", indexes={@ORM\Index(name="id_client", columns={"id_client"}), @ORM\Index(name="id_cgv", columns={"id_cgv"})})
 * @ORM\Entity
 */
class AcceptationsLegalDocsRelances
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_cgv", type="integer", nullable=false)
     */
    private $idCgv;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_relance", type="datetime", nullable=false)
     */
    private $dateRelance;

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
     * @ORM\Column(name="id_relance", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idRelance;



    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return AcceptationsLegalDocsRelances
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
     * Set idCgv
     *
     * @param integer $idCgv
     *
     * @return AcceptationsLegalDocsRelances
     */
    public function setIdCgv($idCgv)
    {
        $this->idCgv = $idCgv;

        return $this;
    }

    /**
     * Get idCgv
     *
     * @return integer
     */
    public function getIdCgv()
    {
        return $this->idCgv;
    }

    /**
     * Set dateRelance
     *
     * @param \DateTime $dateRelance
     *
     * @return AcceptationsLegalDocsRelances
     */
    public function setDateRelance($dateRelance)
    {
        $this->dateRelance = $dateRelance;

        return $this;
    }

    /**
     * Get dateRelance
     *
     * @return \DateTime
     */
    public function getDateRelance()
    {
        return $this->dateRelance;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return AcceptationsLegalDocsRelances
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
     * @return AcceptationsLegalDocsRelances
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
     * Get idRelance
     *
     * @return integer
     */
    public function getIdRelance()
    {
        return $this->idRelance;
    }
}
