<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyRating
 *
 * @ORM\Table(name="company_rating", indexes={@ORM\Index(name="id_company_rating_history", columns={"id_company_rating_history"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\CompanyRatingRepository")
 */
class CompanyRating
{
    const TYPE_ALTARES_VALUE_DATE         = 'date_valeur_altares';
    const TYPE_ALTARES_SCORE_20           = 'score_altares';
    const TYPE_ALTARES_SECTORAL_SCORE_20  = 'score_sectoriel_altares';
    const TYPE_INFOLEGALE_SCORE           = 'note_infolegale';
    const TYPE_XERFI_RISK_SCORE           = 'xerfi';
    const TYPE_UNILEND_XERFI_RISK         = 'xerfi_unilend';
    const TYPE_EULER_HERMES_GRADE         = 'grade_euler_hermes';
    const TYPE_EULER_HERMES_TRAFFIC_LIGHT = 'traffic_light_euler_hermes';
    const TYPE_INFOGREFFE_RETURN_CODE     = 'infogreffe_code';

    const AUTOMATIC_RATING_TYPES = [
        self::TYPE_ALTARES_VALUE_DATE,
        self::TYPE_ALTARES_SCORE_20,
        self::TYPE_ALTARES_SECTORAL_SCORE_20,
        self::TYPE_INFOLEGALE_SCORE,
        self::TYPE_XERFI_RISK_SCORE,
        self::TYPE_UNILEND_XERFI_RISK,
        self::TYPE_EULER_HERMES_GRADE,
        self::TYPE_EULER_HERMES_TRAFFIC_LIGHT
    ];

    const ALTARES_RATING_TYPES = [
        self::TYPE_ALTARES_SCORE_20,
        self::TYPE_ALTARES_SECTORAL_SCORE_20,
        self::TYPE_ALTARES_VALUE_DATE
    ];

    /**
     * @var \Unilend\Entity\CompanyRatingHistory
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\CompanyRatingHistory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company_rating_history", referencedColumnName="id_company_rating_history", nullable=false)
     * })
     */
    private $idCompanyRatingHistory;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=191)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=191)
     */
    private $value;

    /**
     * @var int
     *
     * @ORM\Column(name="id_company_rating", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idCompanyRating;



    /**
     * Set idCompanyRatingHistory
     *
     * @param \Unilend\Entity\CompanyRatingHistory $idCompanyRatingHistory
     *
     * @return CompanyRating
     */
    public function setIdCompanyRatingHistory(\Unilend\Entity\CompanyRatingHistory $idCompanyRatingHistory)
    {
        $this->idCompanyRatingHistory = $idCompanyRatingHistory;

        return $this;
    }

    /**
     * Get idCompanyRatingHistory
     *
     * @return \Unilend\Entity\CompanyRatingHistory
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
