<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TypeFinancialOperations
 *
 * @ORM\Table(name="type_financial_operations")
 * @ORM\Entity
 */
class TypeFinancialOperations
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45, nullable=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_type_financial_operation", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTypeFinancialOperation;



    /**
     * Set name
     *
     * @param string $name
     *
     * @return TypeFinancialOperations
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return TypeFinancialOperations
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get idTypeFinancialOperation
     *
     * @return integer
     */
    public function getIdTypeFinancialOperation()
    {
        return $this->idTypeFinancialOperation;
    }
}
