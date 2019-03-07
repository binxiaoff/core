<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsersHistory
 *
 * @ORM\Table(name="users_history", indexes={
 *     @ORM\Index(name="idx_users_history_id_user", columns={"id_user"}),
 *     @ORM\Index(name="idx_users_history_id_form_nom_form", columns={"id_form", "nom_form"})
 * })
 * @ORM\Entity
 */
class UsersHistory
{
    const FORM_ID_LENDER_STATUS         = 1;
    const FORM_ID_LENDER                = 3;
    const FORM_ID_PROJECT_UPLOAD        = 9;
    const FORM_ID_BULK_PROJECT_CREATION = 13;

    const FORM_NAME_LENDER_STATUS         = 'Statut prêteur';
    const FORM_NAME_TAX_EXEMPTION         = 'modification exoneration fiscale';
    const FROM_NAME_BULK_PROJECT_CREATION = 'depot_dossier_en_masse';

    /**
     * @var int
     *
     * @ORM\Column(name="id_form", type="integer")
     */
    private $idForm;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_form", type="string", length=191)
     */
    private $nomForm;

    /**
     * @var int
     *
     * @ORM\Column(name="id_user", type="integer")
     */
    private $idUser;

    /**
     * @var string
     *
     * @ORM\Column(name="serialize", type="text", length=16777215)
     */
    private $serialize;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
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
