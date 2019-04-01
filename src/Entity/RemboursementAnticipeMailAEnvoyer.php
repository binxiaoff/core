<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RemboursementAnticipeMailAEnvoyer
 *
 * @ORM\Table(name="remboursement_anticipe_mail_a_envoyer", indexes={@ORM\Index(name="id_reception", columns={"id_reception"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 */
class RemboursementAnticipeMailAEnvoyer
{
    const STATUS_PENDING = 0;
    const STATUS_SENT    = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id_reception", type="integer")
     */
    private $idReception;

    /**
     * @var int
     *
     * @ORM\Column(name="statut", type="smallint")
     */
    private $statut;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_envoi", type="datetime", nullable=true)
     */
    private $dateEnvoi;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_remboursement_anticipe_mail_a_envoyer", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idRemboursementAnticipeMailAEnvoyer;

    /**
     * Set idReception
     *
     * @param integer $idReception
     *
     * @return RemboursementAnticipeMailAEnvoyer
     */
    public function setIdReception($idReception)
    {
        $this->idReception = $idReception;

        return $this;
    }

    /**
     * Get idReception
     *
     * @return integer
     */
    public function getIdReception()
    {
        return $this->idReception;
    }

    /**
     * Set statut
     *
     * @param integer $statut
     *
     * @return RemboursementAnticipeMailAEnvoyer
     */
    public function setStatut($statut)
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get statut
     *
     * @return integer
     */
    public function getStatut()
    {
        return $this->statut;
    }

    /**
     * Set dateEnvoi
     *
     * @param \DateTime $dateEnvoi
     *
     * @return RemboursementAnticipeMailAEnvoyer
     */
    public function setDateEnvoi($dateEnvoi)
    {
        $this->dateEnvoi = $dateEnvoi;

        return $this;
    }

    /**
     * Get dateEnvoi
     *
     * @return \DateTime
     */
    public function getDateEnvoi()
    {
        return $this->dateEnvoi;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return RemboursementAnticipeMailAEnvoyer
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
     * @return RemboursementAnticipeMailAEnvoyer
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
     * Get idRemboursementAnticipeMailAEnvoyer
     *
     * @return integer
     */
    public function getIdRemboursementAnticipeMailAEnvoyer()
    {
        return $this->idRemboursementAnticipeMailAEnvoyer;
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
