<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsersHistory
 *
 * @ORM\Table(name="users_history")
 * @ORM\Entity
 */
class UsersHistory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_form", type="integer", nullable=false)
     */
    private $idForm;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_form", type="string", length=191, nullable=false)
     */
    private $nomForm;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

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
     * @ORM\Column(name="id_user_history", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUserHistory;



    /**
     * Set idForm
     *
     * @param integer $idForm
     *
     * @return UsersHistory
     */
    public function setIdForm($idForm)
    {
        $this->idForm = $idForm;

        return $this;
    }

    /**
     * Get idForm
     *
     * @return integer
     */
    public function getIdForm()
    {
        return $this->idForm;
    }

    /**
     * Set nomForm
     *
     * @param string $nomForm
     *
     * @return UsersHistory
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
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return UsersHistory
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return integer
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set serialize
     *
     * @param string $serialize
     *
     * @return UsersHistory
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
     * @return UsersHistory
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
     * @return UsersHistory
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
     * Get idUserHistory
     *
     * @return integer
     */
    public function getIdUserHistory()
    {
        return $this->idUserHistory;
    }
}
