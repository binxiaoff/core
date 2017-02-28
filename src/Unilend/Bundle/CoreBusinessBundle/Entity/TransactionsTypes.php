<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TransactionsTypes
 *
 * @ORM\Table(name="transactions_types")
 * @ORM\Entity
 */
class TransactionsTypes
{
    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=191, nullable=false)
     */
    private $nom;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_transaction_type", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTransactionType;



    /**
     * Set nom
     *
     * @param string $nom
     *
     * @return TransactionsTypes
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Get idTransactionType
     *
     * @return integer
     */
    public function getIdTransactionType()
    {
        return $this->idTransactionType;
    }
}
