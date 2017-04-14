<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsHistoryActions
 *
 * @ORM\Table(name="clients_history_actions", indexes={@ORM\Index(name="idx_clients_history_actions_id_client_nom_form", columns={"id_client", "nom_form"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ClientsHistoryActionsRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ClientsHistoryActions
{
    const LENDER_PROVISION_BY_CREDIT_CARD                       = 'alim cb';
    const LENDER_WITHDRAWAL                                     = 'retrait argent';
    const LENDER_PROFILE_PERSONAL_INFORMATION                   = 'info perso profile';
    const LENDER_PROFILE_BANK_INFORMATION                       = 'info bank file profile';
    const CHANGE_PASSWORD                                       = 'change mdp';
    const LENDER_BID                                            = 'bid';
    const LENDER_UPLOAD_FILES                                   = 'upload doc profile';
    const LENDER_PERSON_SUBSCRIPTION_PERSONAL_INFORMATION       = 'inscription etape 1 particulier';
    const LENDER_PERSON_SUBSCRIPTION_BANK_DOCUMENTS             = 'inscription etape 2 particulier';
    const LENDER_LEGAL_ENTITY_SUBSCRIPTION_PERSONAL_INFORMATION = 'inscription etape 1 entreprise';
    const LENDER_LEGAL_ENTITY_SUBSCRIPTION_BANK_DOCUMENTS       = 'inscription etape 2 entreprise';
    const LENDER_PROFILE_SECURITY_QUESTION                      = 'change secret question';
    const AUTOBID_SWITCH                                        = 'autobid_on_off';

    /**
     * @var string
     *
     * @ORM\Column(name="nom_form", type="string", length=191, nullable=false)
     */
    private $nomForm;

    /**
     * @var Clients
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients", inversedBy="clientsHistoryActions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client")
     * })
     */
    private $idClient;

    /**
     * @var string
     *
     * @ORM\Column(name="serialize", type="text", length=16777215, nullable=false)
     */
    private $serialize;

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
     * @ORM\Column(name="id_client_history_action", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idClientHistoryAction;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=191, nullable=false)
     */
    private $ip;

    /**
     * Set nomForm
     *
     * @param string $nomForm
     *
     * @return ClientsHistoryActions
     */
    public function setNomForm($nomForm)
    {
        $this->nomForm = $nomForm;

        return $this;
    }

    /**
     * Get nomForm
     *
     * @return string
     */
    public function getNomForm()
    {
        return $this->nomForm;
    }

    /**
     * Set idClient
     *
     * @param Clients $idClient
     *
     * @return ClientsHistoryActions
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
     * Set serialize
     *
     * @param string $serialize
     *
     * @return ClientsHistoryActions
     */
    public function setSerialize($serialize)
    {
        $this->serialize = $serialize;

        return $this;
    }

    /**
     * Get serialize
     *
     * @return string
     */
    public function getSerialize()
    {
        return $this->serialize;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ClientsHistoryActions
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
     * @return ClientsHistoryActions
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
     * Get idClientHistoryAction
     *
     * @return integer
     */
    public function getIdClientHistoryAction()
    {
        return $this->idClientHistoryAction;
    }

    /**
     * @param string $ip
     */
    public function setIP($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
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
