<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyRating
 *
 * @ORM\Table(name="company_rating", indexes={@ORM\Index(name="id_company_rating_history", columns={"id_company_rating_history"})})
 * @ORM\Entity
 */
class CompanyRating
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_company_rating_history", type="integer", nullable=false)
     */
    private $idCompanyRatingHistory;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=191, nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=191, nullable=false)
     */
    private $value;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_company_rating", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idCompanyRating;



    /**
     * Set idCompanyRatingHistory
     *
     * @param integer $idCompanyRatingHistory
     *
     * @return CompanyRating
     */
    public function setIdCompanyRatingHistory($idCompanyRatingHistory)
    {
        $this->idCompanyRatingHistory = $idCompanyRatingHistory;

        return $this;
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

    /**
     * Set type
     *
     * @param string $type
     *
     * @return CompanyRating
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return CompanyRating
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get idCompanyRating
     *
     * @return integer
     */
    public function getIdCompanyRating()
    {
        return $this->idCompanyRating;
    }
}
