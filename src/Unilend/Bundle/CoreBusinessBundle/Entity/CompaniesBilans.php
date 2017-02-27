<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompaniesBilans
 *
 * @ORM\Table(name="companies_bilans", indexes={@ORM\Index(name="id_company_2", columns={"id_company"}), @ORM\Index(name="cloture_exercice_fiscal", columns={"cloture_exercice_fiscal"}), @ORM\Index(name="idx_company_bilan_company_tax_form_type", columns={"id_company_tax_form_type"})})
 * @ORM\Entity
 */
class CompaniesBilans
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_company", type="integer", nullable=false)
     */
    private $idCompany;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="cloture_exercice_fiscal", type="date", nullable=false)
     */
    private $clotureExerciceFiscal;

    /**
     * @var integer
     *
     * @ORM\Column(name="duree_exercice_fiscal", type="integer", nullable=false)
     */
    private $dureeExerciceFiscal;

    /**
     * @var float
     *
     * @ORM\Column(name="ca", type="float", precision=10, scale=0, nullable=false)
     */
    private $ca;

    /**
     * @var float
     *
     * @ORM\Column(name="resultat_brute_exploitation", type="float", precision=10, scale=0, nullable=false)
     */
    private $resultatBruteExploitation;

    /**
     * @var float
     *
     * @ORM\Column(name="resultat_exploitation", type="float", precision=10, scale=0, nullable=false)
     */
    private $resultatExploitation;

    /**
     * @var float
     *
     * @ORM\Column(name="resultat_financier", type="float", precision=10, scale=0, nullable=false)
     */
    private $resultatFinancier;

    /**
     * @var float
     *
     * @ORM\Column(name="produit_exceptionnel", type="float", precision=10, scale=0, nullable=false)
     */
    private $produitExceptionnel;

    /**
     * @var float
     *
     * @ORM\Column(name="charges_exceptionnelles", type="float", precision=10, scale=0, nullable=false)
     */
    private $chargesExceptionnelles;

    /**
     * @var float
     *
     * @ORM\Column(name="resultat_exceptionnel", type="float", precision=10, scale=0, nullable=false)
     */
    private $resultatExceptionnel;

    /**
     * @var float
     *
     * @ORM\Column(name="resultat_net", type="float", precision=10, scale=0, nullable=false)
     */
    private $resultatNet;

    /**
     * @var float
     *
     * @ORM\Column(name="investissements", type="float", precision=10, scale=0, nullable=false)
     */
    private $investissements;

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
     * @ORM\Column(name="id_bilan", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBilan;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyTaxFormType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\CompanyTaxFormType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company_tax_form_type", referencedColumnName="id_type")
     * })
     */
    private $idCompanyTaxFormType;



    /**
     * Set idCompany
     *
     * @param integer $idCompany
     *
     * @return CompaniesBilans
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
     * Set clotureExerciceFiscal
     *
     * @param \DateTime $clotureExerciceFiscal
     *
     * @return CompaniesBilans
     */
    public function setClotureExerciceFiscal($clotureExerciceFiscal)
    {
        $this->clotureExerciceFiscal = $clotureExerciceFiscal;

        return $this;
    }

    /**
     * Get clotureExerciceFiscal
     *
     * @return \DateTime
     */
    public function getClotureExerciceFiscal()
    {
        return $this->clotureExerciceFiscal;
    }

    /**
     * Set dureeExerciceFiscal
     *
     * @param integer $dureeExerciceFiscal
     *
     * @return CompaniesBilans
     */
    public function setDureeExerciceFiscal($dureeExerciceFiscal)
    {
        $this->dureeExerciceFiscal = $dureeExerciceFiscal;

        return $this;
    }

    /**
     * Get dureeExerciceFiscal
     *
     * @return integer
     */
    public function getDureeExerciceFiscal()
    {
        return $this->dureeExerciceFiscal;
    }

    /**
     * Set ca
     *
     * @param float $ca
     *
     * @return CompaniesBilans
     */
    public function setCa($ca)
    {
        $this->ca = $ca;

        return $this;
    }

    /**
     * Get ca
     *
     * @return float
     */
    public function getCa()
    {
        return $this->ca;
    }

    /**
     * Set resultatBruteExploitation
     *
     * @param float $resultatBruteExploitation
     *
     * @return CompaniesBilans
     */
    public function setResultatBruteExploitation($resultatBruteExploitation)
    {
        $this->resultatBruteExploitation = $resultatBruteExploitation;

        return $this;
    }

    /**
     * Get resultatBruteExploitation
     *
     * @return float
     */
    public function getResultatBruteExploitation()
    {
        return $this->resultatBruteExploitation;
    }

    /**
     * Set resultatExploitation
     *
     * @param float $resultatExploitation
     *
     * @return CompaniesBilans
     */
    public function setResultatExploitation($resultatExploitation)
    {
        $this->resultatExploitation = $resultatExploitation;

        return $this;
    }

    /**
     * Get resultatExploitation
     *
     * @return float
     */
    public function getResultatExploitation()
    {
        return $this->resultatExploitation;
    }

    /**
     * Set resultatFinancier
     *
     * @param float $resultatFinancier
     *
     * @return CompaniesBilans
     */
    public function setResultatFinancier($resultatFinancier)
    {
        $this->resultatFinancier = $resultatFinancier;

        return $this;
    }

    /**
     * Get resultatFinancier
     *
     * @return float
     */
    public function getResultatFinancier()
    {
        return $this->resultatFinancier;
    }

    /**
     * Set produitExceptionnel
     *
     * @param float $produitExceptionnel
     *
     * @return CompaniesBilans
     */
    public function setProduitExceptionnel($produitExceptionnel)
    {
        $this->produitExceptionnel = $produitExceptionnel;

        return $this;
    }

    /**
     * Get produitExceptionnel
     *
     * @return float
     */
    public function getProduitExceptionnel()
    {
        return $this->produitExceptionnel;
    }

    /**
     * Set chargesExceptionnelles
     *
     * @param float $chargesExceptionnelles
     *
     * @return CompaniesBilans
     */
    public function setChargesExceptionnelles($chargesExceptionnelles)
    {
        $this->chargesExceptionnelles = $chargesExceptionnelles;

        return $this;
    }

    /**
     * Get chargesExceptionnelles
     *
     * @return float
     */
    public function getChargesExceptionnelles()
    {
        return $this->chargesExceptionnelles;
    }

    /**
     * Set resultatExceptionnel
     *
     * @param float $resultatExceptionnel
     *
     * @return CompaniesBilans
     */
    public function setResultatExceptionnel($resultatExceptionnel)
    {
        $this->resultatExceptionnel = $resultatExceptionnel;

        return $this;
    }

    /**
     * Get resultatExceptionnel
     *
     * @return float
     */
    public function getResultatExceptionnel()
    {
        return $this->resultatExceptionnel;
    }

    /**
     * Set resultatNet
     *
     * @param float $resultatNet
     *
     * @return CompaniesBilans
     */
    public function setResultatNet($resultatNet)
    {
        $this->resultatNet = $resultatNet;

        return $this;
    }

    /**
     * Get resultatNet
     *
     * @return float
     */
    public function getResultatNet()
    {
        return $this->resultatNet;
    }

    /**
     * Set investissements
     *
     * @param float $investissements
     *
     * @return CompaniesBilans
     */
    public function setInvestissements($investissements)
    {
        $this->investissements = $investissements;

        return $this;
    }

    /**
     * Get investissements
     *
     * @return float
     */
    public function getInvestissements()
    {
        return $this->investissements;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return CompaniesBilans
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
     * @return CompaniesBilans
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
     * Get idBilan
     *
     * @return integer
     */
    public function getIdBilan()
    {
        return $this->idBilan;
    }

    /**
     * Set idCompanyTaxFormType
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyTaxFormType $idCompanyTaxFormType
     *
     * @return CompaniesBilans
     */
    public function setIdCompanyTaxFormType(\Unilend\Bundle\CoreBusinessBundle\Entity\CompanyTaxFormType $idCompanyTaxFormType = null)
    {
        $this->idCompanyTaxFormType = $idCompanyTaxFormType;

        return $this;
    }

    /**
     * Get idCompanyTaxFormType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyTaxFormType
     */
    public function getIdCompanyTaxFormType()
    {
        return $this->idCompanyTaxFormType;
    }
}
