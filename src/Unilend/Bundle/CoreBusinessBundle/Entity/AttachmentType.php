<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AttachmentType
 *
 * @ORM\Table(name="attachment_type")
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\AttachmentTypeRepository")
 */
class AttachmentType
{
    const CNI_PASSPORTE                        = 1;
    const JUSTIFICATIF_DOMICILE                = 2;
    const RIB                                  = 3;
    const ATTESTATION_HEBERGEMENT_TIERS        = 4;
    const CNI_PASSPORT_TIERS_HEBERGEANT        = 5;
    const JUSTIFICATIF_FISCAL                  = 6;
    const CNI_PASSPORTE_DIRIGEANT              = 7;
    const KBIS                                 = 8;
    const DELEGATION_POUVOIR                   = 9;
    const STATUTS                              = 10;
    const CNI_PASSPORTE_VERSO                  = 11;
    const DERNIERE_LIASSE_FISCAL               = 15;
    const AUTRE1                               = 22;
    const AUTRE2                               = 23;
    const AUTRE3                               = 24;
    const DISPENSE_PRELEVEMENT_2014            = 25;
    const DISPENSE_PRELEVEMENT_2015            = 26;
    const DISPENSE_PRELEVEMENT_2016            = 27;
    const DISPENSE_PRELEVEMENT_2017            = 28;
    const DELEGATION_POUVOIR_PERSONNES_MORALES = 29;
    const RELEVE_BANCAIRE_MOIS_N               = 30;
    const RELEVE_BANCAIRE_MOIS_N_1             = 31;
    const RELEVE_BANCAIRE_MOIS_N_2             = 32;
    const PRESENTATION_ENTRERPISE              = 33;
    const ETAT_ENDETTEMENT                     = 34;
    const LIASSE_FISCAL_N_1                    = 35;
    const LIASSE_FISCAL_N_2                    = 36;
    const RAPPORT_CAC                          = 37;
    const PREVISIONNEL                         = 38;
    const BALANCE_CLIENT                       = 39;
    const BALANCE_FOURNISSEUR                  = 40;
    const ETAT_PRIVILEGES_NANTISSEMENTS        = 41;
    const AUTRE4                               = 42;
    const CGV                                  = 43;
    const CNI_BENEFICIAIRE_EFFECTIF_1          = 44;
    const CNI_BENEFICIAIRE_EFFECTIF_VERSO_1    = 45;
    const CNI_BENEFICIAIRE_EFFECTIF_2          = 46;
    const CNI_BENEFICIAIRE_EFFECTIF_VERSO_2    = 47;
    const CNI_BENEFICIAIRE_EFFECTIF_3          = 48;
    const CNI_BENEFICIAIRE_EFFECTIF_VERSO_3    = 49;
    const SITUATION_COMPTABLE_INTERMEDIAIRE    = 50;
    const DERNIERS_COMPTES_CONSOLIDES          = 51;
    const PRESENTATION_PROJET                  = 52;
    const DERNIERE_LIASSE_FISCAL_HOLDING       = 53;
    const KBIS_HOLDING                         = 54;
    const PHOTOS_ACTIVITE                      = 55;
    const TRANSFER_CERTIFICATE                 = 56;
    const DEBTS_STATEMENT                      = 57;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=100, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return AttachmentType
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
