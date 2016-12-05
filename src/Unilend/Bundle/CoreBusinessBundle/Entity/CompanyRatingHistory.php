<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyRatingHistory
 *
 * @ORM\Table(name="company_rating_history", indexes={@ORM\Index(name="id_company", columns={"id_company"})})
 * @ORM\Entity
 */
class CompanyRatingHistory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_company", type="integer", nullable=false)
     */
    private $idCompany;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=50, nullable=false)
     */
    private $action;

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
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_company_rating_history", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idCompanyRatingHistory;



    /**
     * Set idCompany
     *
     * @param integer $idCompany
     *
     * @return CompanyRatingHistory
     */
    public function setIdCompany($idCompany)
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * Get idCompany
     *
     * @return integer
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }

    /**
     * Set action
     *
     * @param string $action
     *
     * @return CompanyRatingHistory
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return CompanyRatingHistory
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return CompanyRatingHistory
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
     * @return CompanyRatingHistory
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
     * Get idCompanyRatingHistory
     *
     * @return integer
     */
    public function getIdCompanyRatingHistory()
    {
        return $this->idCompanyRatingHistory;
    }
}
