<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientVigilanceStatusHistory
 *
 * @ORM\Table(name="client_vigilance_status_history", indexes={@ORM\Index(name="idx_client_atypical_operation_id_client", columns={"id_client"}), @ORM\Index(name="fk_client_atypical_operation_client_vigilance_status_history", columns={"id_atypical_operation"})})
 * @ORM\Entity
 */
class ClientVigilanceStatusHistory
{
    /**
     * @var int
     *
     * @ORM\Column(name="vigilance_status", type="int", nullable=false)
     */
    private $vigilanceStatus = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="user_comment", type="text", length=65535, nullable=false)
     */
    private $userComment;

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
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ClientAtypicalOperation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ClientAtypicalOperation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_atypical_operation", referencedColumnName="id")
     * })
     */
    private $atypicalOperation;

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
    public function getVigilanceStatus()
    {
        return $this->vigilanceStatus;
    }

    /**
     * @param int $vigilanceStatus
     *
     * @return ClientVigilanceStatusHistory
     */
    public function setVigilanceStatus($vigilanceStatus)
    {
        $this->vigilanceStatus = $vigilanceStatus;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserComment()
    {
        return $this->userComment;
    }

    /**
     * @param string $userComment
     *
     * @return ClientVigilanceStatusHistory
     */
    public function setUserComment($userComment)
    {
        $this->userComment = $userComment;

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
     * @return ClientVigilanceStatusHistory
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
     * @return ClientVigilanceStatusHistory
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

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
     * @return ClientVigilanceStatusHistory
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

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
     * @param int $id
     *
     * @return ClientVigilanceStatusHistory
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return ClientAtypicalOperation
     */
    public function getAtypicalOperation()
    {
        return $this->atypicalOperation;
    }

    /**
     * @param ClientAtypicalOperation $atypicalOperation
     *
     * @return ClientVigilanceStatusHistory
     */
    public function setAtypicalOperation($atypicalOperation)
    {
        $this->atypicalOperation = $atypicalOperation;

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
     * @return ClientVigilanceStatusHistory
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }
}
