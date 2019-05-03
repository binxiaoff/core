<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AttachmentType.
 *
 * @ORM\Table(name="attachment_type")
 * @ORM\Entity(repositoryClass="Unilend\Repository\AttachmentTypeRepository")
 */
class AttachmentType
{
    public const CNI_PASSPORTE                        = 1;
    public const JUSTIFICATIF_DOMICILE                = 2;
    public const RIB                                  = 3;
    public const ATTESTATION_HEBERGEMENT_TIERS        = 4;
    public const CNI_PASSPORT_TIERS_HEBERGEANT        = 5;
    public const JUSTIFICATIF_FISCAL                  = 6;
    public const CNI_PASSPORTE_DIRIGEANT              = 7;
    public const KBIS                                 = 8;
    public const DELEGATION_POUVOIR                   = 9;
    public const STATUTS                              = 10;
    public const CNI_PASSPORTE_VERSO                  = 11;
    public const DERNIERE_LIASSE_FISCAL               = 15;
    public const AUTRE1                               = 22;
    public const AUTRE2                               = 23;
    public const AUTRE3                               = 24;
    public const DISPENSE_PRELEVEMENT_2014            = 25;
    public const DISPENSE_PRELEVEMENT_2015            = 26;
    public const DISPENSE_PRELEVEMENT_2016            = 27;
    public const DISPENSE_PRELEVEMENT_2017            = 28;
    public const DELEGATION_POUVOIR_PERSONNES_MORALES = 29;
    public const RELEVE_BANCAIRE_MOIS_N               = 30;
    public const RELEVE_BANCAIRE_MOIS_N_1             = 31;
    public const RELEVE_BANCAIRE_MOIS_N_2             = 32;
    public const PRESENTATION_ENTRERPISE              = 33;
    public const ETAT_ENDETTEMENT                     = 34;
    public const LIASSE_FISCAL_N_1                    = 35;
    public const LIASSE_FISCAL_N_2                    = 36;
    public const RAPPORT_CAC                          = 37;
    public const PREVISIONNEL                         = 38;
    public const BALANCE_CLIENT                       = 39;
    public const BALANCE_FOURNISSEUR                  = 40;
    public const ETAT_PRIVILEGES_NANTISSEMENTS        = 41;
    public const AUTRE4                               = 42;
    public const CGV                                  = 43;
    public const CNI_BENEFICIAIRE_EFFECTIF_1          = 44;
    public const CNI_BENEFICIAIRE_EFFECTIF_VERSO_1    = 45;
    public const CNI_BENEFICIAIRE_EFFECTIF_2          = 46;
    public const CNI_BENEFICIAIRE_EFFECTIF_VERSO_2    = 47;
    public const CNI_BENEFICIAIRE_EFFECTIF_3          = 48;
    public const CNI_BENEFICIAIRE_EFFECTIF_VERSO_3    = 49;
    public const SITUATION_COMPTABLE_INTERMEDIAIRE    = 50;
    public const DERNIERS_COMPTES_CONSOLIDES          = 51;
    public const PRESENTATION_PROJET                  = 52;
    public const DERNIERE_LIASSE_FISCAL_HOLDING       = 53;
    public const KBIS_HOLDING                         = 54;
    public const PHOTOS_ACTIVITE                      = 55;
    public const TRANSFER_CERTIFICATE                 = 56;
    public const DEBTS_STATEMENT                      = 57;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=100)
     */
    private $label;

    /**
     * @var bool
     *
     * @ORM\Column(name="downloadable", type="boolean")
     */
    private $downloadable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->label;
    }

    /**
     * Set label.
     *
     * @param string $label
     *
     * @return AttachmentType
     */
    public function setLabel(string $label): AttachmentType
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Set downloadable.
     *
     * @param bool $downloadable
     *
     * @return AttachmentType
     */
    public function setDownloadable(bool $downloadable): AttachmentType
    {
        $this->downloadable = $downloadable;

        return $this;
    }

    /**
     * Get downloadable.
     *
     * @return bool
     */
    public function getDownloadable(): bool
    {
        return $this->downloadable;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
