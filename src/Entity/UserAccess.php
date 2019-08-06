<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_access", indexes={@ORM\Index(name="user_access_id_user", columns={"id_user"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class UserAccess
{
    /**
     * @var string
     *
     * @ORM\Column(name="controller", type="string", length=191)
     */
    private $controller;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=191)
     */
    private $action;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=15)
     */
    private $ip;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user", referencedColumnName="id_user", nullable=false)
     * })
     */
    private $idUser;

    /**
     * @param string $controller
     *
     * @return UserAccess
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param string $action
     *
     * @return UserAccess
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $ip
     *
     * @return UserAccess
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param \DateTime $added
     *
     * @return UserAccess
     */
    public function setAdded($added)
    {
        $this->added = $added;

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \Unilend\Entity\Users $idUser
     *
     * @return UserAccess
     */
    public function setIdUser(Users $idUser = null)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * @return \Unilend\Entity\Users
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (!$this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }
}
