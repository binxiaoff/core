<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class CompanyIdentityDetail
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
     * @JMS\SerializedName("rue")
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
     * @JMS\SerializedName("greffe")
     * @JMS\Type("string")
     */
    private $commercialCourt;

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
     * @JMS\SerializedName("formeJuridiqueCode")
     * @JMS\Type("string")
     */
    private $legalFormCode;

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
     * @return mixed
     */
    public function getCommercialCourt()
    {
        return $this->commercialCourt;
    }

    /**
     * @return string
     */
    public function getCompanyStatus()
    {
        return $this->companyStatus;
    }

    /**
     * return boolean
     */
    public function getCollectiveProcedure()
    {
        return 'OUI' === $this->collectiveProcedure;
    }

    /**
     * @return string
     */
    public function getLegalFormCode()
    {
        return $this->legalFormCode;
    }
}
