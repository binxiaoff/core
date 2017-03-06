<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompaniesActifPassif
 *
 * @ORM\Table(name="companies_actif_passif", indexes={@ORM\Index(name="id_bilan", columns={"id_bilan"})})
 * @ORM\Entity
 */
class CompaniesActifPassif
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_bilan", type="integer", nullable=false)
     */
    private $idBilan;

    /**
     * @var float
     *
     * @ORM\Column(name="immobilisations_corporelles", type="float", precision=10, scale=0, nullable=false)
     */
    private $immobilisationsCorporelles;

    /**
     * @var float
     *
     * @ORM\Column(name="immobilisations_incorporelles", type="float", precision=10, scale=0, nullable=false)
     */
    private $immobilisationsIncorporelles;

    /**
     * @var float
     *
     * @ORM\Column(name="immobilisations_financieres", type="float", precision=10, scale=0, nullable=false)
     */
    private $immobilisationsFinancieres;

    /**
     * @var integer
     *
     * @ORM\Column(name="stocks", type="integer", nullable=false)
     */
    private $stocks;

    /**
     * @var float
     *
     * @ORM\Column(name="creances_clients", type="float", precision=10, scale=0, nullable=false)
     */
    private $creancesClients;

    /**
     * @var float
     *
     * @ORM\Column(name="disponibilites", type="float", precision=10, scale=0, nullable=false)
     */
    private $disponibilites;

    /**
     * @var float
     *
     * @ORM\Column(name="valeurs_mobilieres_de_placement", type="float", precision=10, scale=0, nullable=false)
     */
    private $valeursMobilieresDePlacement;

    /**
     * @var float
     *
     * @ORM\Column(name="comptes_regularisation_actif", type="float", precision=10, scale=0, nullable=false)
     */
    private $comptesRegularisationActif;

    /**
     * @var float
     *
     * @ORM\Column(name="comptes_regularisation_passif", type="float", precision=10, scale=0, nullable=false)
     */
    private $comptesRegularisationPassif;

    /**
     * @var float
     *
     * @ORM\Column(name="capitaux_propres", type="float", precision=10, scale=0, nullable=false)
     */
    private $capitauxPropres;

    /**
     * @var float
     *
     * @ORM\Column(name="provisions_pour_risques_et_charges", type="float", precision=10, scale=0, nullable=false)
     */
    private $provisionsPourRisquesEtCharges;

    /**
     * @var float
     *
     * @ORM\Column(name="amortissement_sur_immo", type="float", precision=10, scale=0, nullable=false)
     */
    private $amortissementSurImmo;

    /**
     * @var float
     *
     * @ORM\Column(name="dettes_financieres", type="float", precision=10, scale=0, nullable=false)
     */
    private $dettesFinancieres;

    /**
     * @var float
     *
     * @ORM\Column(name="dettes_fournisseurs", type="float", precision=10, scale=0, nullable=false)
     */
    private $dettesFournisseurs;

    /**
     * @var float
     *
     * @ORM\Column(name="autres_dettes", type="float", precision=10, scale=0, nullable=false)
     */
    private $autresDettes;

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
     * @ORM\Column(name="id_actif_passif", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idActifPassif;



    /**
     * Set idBilan
     *
     * @param integer $idBilan
     *
     * @return CompaniesActifPassif
     */
    public function setIdBilan($idBilan)
    {
        $this->idBilan = $idBilan;

        return $this;
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
     * Set immobilisationsCorporelles
     *
     * @param float $immobilisationsCorporelles
     *
     * @return CompaniesActifPassif
     */
    public function setImmobilisationsCorporelles($immobilisationsCorporelles)
    {
        $this->immobilisationsCorporelles = $immobilisationsCorporelles;

        return $this;
    }

    /**
     * Get immobilisationsCorporelles
     *
     * @return float
     */
    public function getImmobilisationsCorporelles()
    {
        return $this->immobilisationsCorporelles;
    }

    /**
     * Set immobilisationsIncorporelles
     *
     * @param float $immobilisationsIncorporelles
     *
     * @return CompaniesActifPassif
     */
    public function setImmobilisationsIncorporelles($immobilisationsIncorporelles)
    {
        $this->immobilisationsIncorporelles = $immobilisationsIncorporelles;

        return $this;
    }

    /**
     * Get immobilisationsIncorporelles
     *
     * @return float
     */
    public function getImmobilisationsIncorporelles()
    {
        return $this->immobilisationsIncorporelles;
    }

    /**
     * Set immobilisationsFinancieres
     *
     * @param float $immobilisationsFinancieres
     *
     * @return CompaniesActifPassif
     */
    public function setImmobilisationsFinancieres($immobilisationsFinancieres)
    {
        $this->immobilisationsFinancieres = $immobilisationsFinancieres;

        return $this;
    }

    /**
     * Get immobilisationsFinancieres
     *
     * @return float
     */
    public function getImmobilisationsFinancieres()
    {
        return $this->immobilisationsFinancieres;
    }

    /**
     * Set stocks
     *
     * @param integer $stocks
     *
     * @return CompaniesActifPassif
     */
    public function setStocks($stocks)
    {
        $this->stocks = $stocks;

        return $this;
    }

    /**
     * Get stocks
     *
     * @return integer
     */
    public function getStocks()
    {
        return $this->stocks;
    }

    /**
     * Set creancesClients
     *
     * @param float $creancesClients
     *
     * @return CompaniesActifPassif
     */
    public function setCreancesClients($creancesClients)
    {
        $this->creancesClients = $creancesClients;

        return $this;
    }

    /**
     * Get creancesClients
     *
     * @return float
     */
    public function getCreancesClients()
    {
        return $this->creancesClients;
    }

    /**
     * Set disponibilites
     *
     * @param float $disponibilites
     *
     * @return CompaniesActifPassif
     */
    public function setDisponibilites($disponibilites)
    {
        $this->disponibilites = $disponibilites;

        return $this;
    }

    /**
     * Get disponibilites
     *
     * @return float
     */
    public function getDisponibilites()
    {
        return $this->disponibilites;
    }

    /**
     * Set valeursMobilieresDePlacement
     *
     * @param float $valeursMobilieresDePlacement
     *
     * @return CompaniesActifPassif
     */
    public function setValeursMobilieresDePlacement($valeursMobilieresDePlacement)
    {
        $this->valeursMobilieresDePlacement = $valeursMobilieresDePlacement;

        return $this;
    }

    /**
     * Get valeursMobilieresDePlacement
     *
     * @return float
     */
    public function getValeursMobilieresDePlacement()
    {
        return $this->valeursMobilieresDePlacement;
    }

    /**
     * Set comptesRegularisationActif
     *
     * @param float $comptesRegularisationActif
     *
     * @return CompaniesActifPassif
     */
    public function setComptesRegularisationActif($comptesRegularisationActif)
    {
        $this->comptesRegularisationActif = $comptesRegularisationActif;

        return $this;
    }

    /**
     * Get comptesRegularisationActif
     *
     * @return float
     */
    public function getComptesRegularisationActif()
    {
        return $this->comptesRegularisationActif;
    }

    /**
     * Set comptesRegularisationPassif
     *
     * @param float $comptesRegularisationPassif
     *
     * @return CompaniesActifPassif
     */
    public function setComptesRegularisationPassif($comptesRegularisationPassif)
    {
        $this->comptesRegularisationPassif = $comptesRegularisationPassif;

        return $this;
    }

    /**
     * Get comptesRegularisationPassif
     *
     * @return float
     */
    public function getComptesRegularisationPassif()
    {
        return $this->comptesRegularisationPassif;
    }

    /**
     * Set capitauxPropres
     *
     * @param float $capitauxPropres
     *
     * @return CompaniesActifPassif
     */
    public function setCapitauxPropres($capitauxPropres)
    {
        $this->capitauxPropres = $capitauxPropres;

        return $this;
    }

    /**
     * Get capitauxPropres
     *
     * @return float
     */
    public function getCapitauxPropres()
    {
        return $this->capitauxPropres;
    }

    /**
     * Set provisionsPourRisquesEtCharges
     *
     * @param float $provisionsPourRisquesEtCharges
     *
     * @return CompaniesActifPassif
     */
    public function setProvisionsPourRisquesEtCharges($provisionsPourRisquesEtCharges)
    {
        $this->provisionsPourRisquesEtCharges = $provisionsPourRisquesEtCharges;

        return $this;
    }

    /**
     * Get provisionsPourRisquesEtCharges
     *
     * @return float
     */
    public function getProvisionsPourRisquesEtCharges()
    {
        return $this->provisionsPourRisquesEtCharges;
    }

    /**
     * Set amortissementSurImmo
     *
     * @param float $amortissementSurImmo
     *
     * @return CompaniesActifPassif
     */
    public function setAmortissementSurImmo($amortissementSurImmo)
    {
        $this->amortissementSurImmo = $amortissementSurImmo;

        return $this;
    }

    /**
     * Get amortissementSurImmo
     *
     * @return float
     */
    public function getAmortissementSurImmo()
    {
        return $this->amortissementSurImmo;
    }

    /**
     * Set dettesFinancieres
     *
     * @param float $dettesFinancieres
     *
     * @return CompaniesActifPassif
     */
    public function setDettesFinancieres($dettesFinancieres)
    {
        $this->dettesFinancieres = $dettesFinancieres;

        return $this;
    }

    /**
     * Get dettesFinancieres
     *
     * @return float
     */
    public function getDettesFinancieres()
    {
        return $this->dettesFinancieres;
    }

    /**
     * Set dettesFournisseurs
     *
     * @param float $dettesFournisseurs
     *
     * @return CompaniesActifPassif
     */
    public function setDettesFournisseurs($dettesFournisseurs)
    {
        $this->dettesFournisseurs = $dettesFournisseurs;

        return $this;
    }

    /**
     * Get dettesFournisseurs
     *
     * @return float
     */
    public function getDettesFournisseurs()
    {
        return $this->dettesFournisseurs;
    }

    /**
     * Set autresDettes
     *
     * @param float $autresDettes
     *
     * @return CompaniesActifPassif
     */
    public function setAutresDettes($autresDettes)
    {
        $this->autresDettes = $autresDettes;

        return $this;
    }

    /**
     * Get autresDettes
     *
     * @return float
     */
    public function getAutresDettes()
    {
        return $this->autresDettes;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return CompaniesActifPassif
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
     * @return CompaniesActifPassif
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
     * Get idActifPassif
     *
     * @return integer
     */
    public function getIdActifPassif()
    {
        return $this->idActifPassif;
    }
}
