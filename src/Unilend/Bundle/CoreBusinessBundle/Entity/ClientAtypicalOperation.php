<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientAtypicalOperation
 *
 * @ORM\Table(name="client_atypical_operation", indexes={@ORM\Index(name="idx_client_atypical_operation_id_client", columns={"id_client"}), @ORM\Index(name="idx_client_atypical_operation_status", columns={"status"}), @ORM\Index(name="fk_vigilance_rule_client_atypical_operation_id_rule", columns={"id_rule"})})
 * @ORM\Entity
 */
class ClientAtypicalOperation
{
    /**
     * @var int
     *
     * @ORM\Column(name="status", type="int", nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="atypical_value", type="string", length=191, nullable=true)
     */
    private $atypicalValue;

    /**
     * @var string
     *
     * @ORM\Column(name="operation_log", type="string", length=191, nullable=true)
     */
    private $operationLog;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rule", referencedColumnName="id")
     * })
     */
    private $rule;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client")
     * })
     */
    private $client;

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return ClientAtypicalOperation
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getAtypicalValue()
    {
        return $this->atypicalValue;
    }

    /**
     * @param string $atypicalValue
     *
     * @return ClientAtypicalOperation
     */
    public function setAtypicalValue($atypicalValue)
    {
        $this->atypicalValue = $atypicalValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getOperationLog()
    {
        return $this->operationLog;
    }

    /**
     * @param string $operationLog
     *
     * @return ClientAtypicalOperation
     */
    public function setOperationLog($operationLog)
    {
        $this->operationLog = $operationLog;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * @param int $idUser
     *
     * @return ClientAtypicalOperation
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @param \DateTime $added
     *
     * @return ClientAtypicalOperation
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     *
     * @return ClientAtypicalOperation
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     *
     * @return ClientAtypicalOperation
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return VigilanceRule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param VigilanceRule $rule
     *
     * @return ClientAtypicalOperation
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Clients $client
     *
     * @return ClientAtypicalOperation
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }
}
