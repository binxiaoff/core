<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class CompanyIdentity
{
    /**
     * @JMS\SerializedName("raisonSociale")
     * @JMS\Type("string")
     */
    private $corporateName;

    /**
     * @JMS\SerializedName("formeJuridique")
     * @JMS\Type("string")
     */
    private $companyForm;

    /**
     * @JMS\Type("float")
     */
    private $capital;

    /**
     * @JMS\SerializedName("naf5EntreCode")
     * @JMS\Type("string")
     */
    private $NAFCode;

    /**
     * @JMS\SerializedName("naf5EntreLibelle")
     * @JMS\Type("string")
     */
    private $NAFLabel;

    /**
     * @JMS\SerializedName("adresse")
     * @JMS\Type("string")
     */
    private $address;

    /**
     * @JMS\SerializedName("codePostal")
     * @JMS\Type("string")
     */
    private $postCode;

    /**
     * @JMS\SerializedName("ville")
     * @JMS\Type("string")
     */
    private $city;

    /**
     * @JMS\Type("string")
     */
    private $siret;

    /**
     * @JMS\SerializedName("dateCreation")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $creationDate;

    /**
     * @JMS\Type("string")
     */
    private $rcs;

    /**
     $ @JMS\SerializedName("etat")
     * @JMS\Type("integer")
     */
    private $companyStatus;
    /**
     * @JMS\SerializedName("procedureCollective")
     * @JMS\Type("string")
     */
    private $collectiveProcedure;

    /**
     * CompanyIdentity constructor.
     * @param $corporateName
     * @param $companyForm
     * @param $capital
     * @param $NAFCode
     * @param $NAFLabel
     * @param $address
     * @param $postCode
     * @param $city
     * @param $siret
     * @param $creationDate
     * @param $rcs
     * @param $companyStatus
     * @param $collectiveProcedure
     */
    public function __construct($corporateName, $companyForm, $capital, $NAFCode, $NAFLabel, $address, $postCode, $city, $siret, $creationDate, $rcs, $companyStatus, $collectiveProcedure)
    {
        $this->corporateName = $corporateName;
        $this->companyForm   = $companyForm;
        $this->capital       = $capital;
        $this->NAFCode       = $NAFCode;
        $this->NAFLabel      = $NAFLabel;
        $this->address       = $address;
        $this->postCode      = $postCode;
        $this->city          = $city;
        $this->siret         = $siret;
        $this->creationDate  = $creationDate;
        $this->rcs           = $rcs;
        $this->companyStatus = $companyStatus;
        $this->collectiveProcedure = $collectiveProcedure;
    }

    /**
     * @return mixed
     */
    public function getCorporateName()
    {
        return $this->corporateName;
    }

    /**
     * @return mixed
     */
    public function getCompanyForm()
    {
        return $this->companyForm;
    }

    /**
     * @return mixed
     */
    public function getCapital()
    {
        return $this->capital;
    }

    /**
     * @return mixed
     */
    public function getNAFCode()
    {
        return $this->NAFCode;
    }

    /**
     * @return mixed
     */
    public function getNAFLabel()
    {
        return $this->NAFLabel;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getPostCode()
    {
        return $this->postCode;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return mixed
     */
    public function getSiret()
    {
        return $this->siret;
    }

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return mixed
     */
    public function getRcs()
    {
        return $this->rcs;
    }

    /**
     * @return string
     */
    public function getCompanyStatus()
    {
        return $this->companyStatus;
    }
}
