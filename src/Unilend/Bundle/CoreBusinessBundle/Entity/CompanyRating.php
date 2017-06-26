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
    const TYPE_EULER_HERMES_GRADE         = 'grade_euler_hermes';

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company_rating_history", referencedColumnName="id_company_rating_history")
     * })
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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory $idCompanyRatingHistory
     *
     * @return CompanyRating
     */
    public function setIdCompanyRatingHistory(\Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory $idCompanyRatingHistory)
    {
        $this->idCompanyRatingHistory = $idCompanyRatingHistory;

        return $this;
    }

    /**
     * Get idCompanyRatingHistory
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory
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
