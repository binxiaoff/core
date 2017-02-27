<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SystemLogs
 *
 * @ORM\Table(name="system_logs")
 * @ORM\Entity
 */
class SystemLogs
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_individual", type="integer", nullable=false)
     */
    private $idIndividual;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender_account", type="integer", nullable=false)
     */
    private $idLenderAccount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=false)
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=45, nullable=false)
     */
    private $ip;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_action", type="integer", nullable=false)
     */
    private $idAction;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_system_log", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idSystemLog;



    /**
     * Set idIndividual
     *
     * @param integer $idIndividual
     *
     * @return SystemLogs
     */
    public function setIdIndividual($idIndividual)
    {
        $this->idIndividual = $idIndividual;

        return $this;
    }

    /**
     * Get idIndividual
     *
     * @return integer
     */
    public function getIdIndividual()
    {
        return $this->idIndividual;
    }

    /**
     * Set idLenderAccount
     *
     * @param integer $idLenderAccount
     *
     * @return SystemLogs
     */
    public function setIdLenderAccount($idLenderAccount)
    {
        $this->idLenderAccount = $idLenderAccount;

        return $this;
    }

    /**
     * Get idLenderAccount
     *
     * @return integer
     */
    public function getIdLenderAccount()
    {
        return $this->idLenderAccount;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return SystemLogs
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return SystemLogs
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set idAction
     *
     * @param integer $idAction
     *
     * @return SystemLogs
     */
    public function setIdAction($idAction)
    {
        $this->idAction = $idAction;

        return $this;
    }

    /**
     * Get idAction
     *
     * @return integer
     */
    public function getIdAction()
    {
        return $this->idAction;
    }

    /**
     * Get idSystemLog
     *
     * @return integer
     */
    public function getIdSystemLog()
    {
        return $this->idSystemLog;
    }
}
