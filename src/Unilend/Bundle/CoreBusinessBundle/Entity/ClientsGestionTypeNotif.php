<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsGestionTypeNotif
 *
 * @ORM\Table(name="clients_gestion_type_notif")
 * @ORM\Entity
 */
class ClientsGestionTypeNotif
{
    const TYPE_NEW_PROJECT                   = 1;
    const TYPE_BID_PLACED                    = 2;
    const TYPE_BID_REJECTED                  = 3;
    const TYPE_LOAN_ACCEPTED                 = 4;
    const TYPE_REPAYMENT                     = 5;
    const TYPE_BANK_TRANSFER_CREDIT          = 6;
    const TYPE_CREDIT_CARD_CREDIT            = 7;
    const TYPE_DEBIT                         = 8;
    const TYPE_PROJECT_PROBLEM               = 9;
    const TYPE_AUTOBID_ACCEPTED_REJECTED_BID = 13;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=191, nullable=false)
     */
    private $nom;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_client_gestion_type_notif", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idClientGestionTypeNotif;

    /**
     * Set nom
     *
     * @param string $nom
     *
     * @return ClientsGestionTypeNotif
     */
    public function setNom(string $nom): ClientsGestionTypeNotif
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * Set ordre
     *
     * @param integer $ordre
     *
     * @return ClientsGestionTypeNotif
     */
    public function setOrdre(int $ordre): ClientsGestionTypeNotif
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return integer
     */
    public function getOrdre(): int
    {
        return $this->ordre;
    }

    /**
     * Get idClientGestionTypeNotif
     *
     * @return integer
     */
    public function getIdClientGestionTypeNotif(): int
    {
        return $this->idClientGestionTypeNotif;
    }
}
