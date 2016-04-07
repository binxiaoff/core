<?php

namespace Unilend\librairies\greenPoint;

/**
 * Class greenPointStatus
 * @package Unilend\librairies\greenPoint
 */
class greenPointStatus
{
    CONST NOT_VERIFIED                   = 0;
    CONST OUT_OF_BOUNDS                  = 1;
    CONST FALSIFIED_OR_MINOR             = 2;
    CONST ILLEGIBLE                      = 3;
    CONST VERSO_MISSING                  = 4;
    CONST NAME_SURNAME_INVERSION         = 5;
    CONST INCOHERENT_OTHER_ERROR         = 6;
    CONST EXPIRED                        = 7;
    CONST CONFORM_COHERENT_NOT_QUALIFIED = 8;
    CONST CONFORM_COHERENT_QUALIFIED     = 9;


    public static $aIdControlStatusLabel = array(
        self::NOT_VERIFIED                   => 'Non vérifié',
        self::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un RIB)',
        self::FALSIFIED_OR_MINOR             => 'Falsifiée ou mineur',
        self::ILLEGIBLE                      => 'Illisible / coupée',
        self::VERSO_MISSING                  => 'Verso seul : recto manquant',
        self::NAME_SURNAME_INVERSION         => 'Non cohérent / données : inversion nom - prénom',
        self::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : autre erreur',
        self::EXPIRED                        => 'Expiré',
        self::CONFORM_COHERENT_NOT_QUALIFIED => 'Conforme, cohérent et valide mais non labellisable',
        self::CONFORM_COHERENT_QUALIFIED     => 'Conforme, cohérent et valide + label GREENPOINT IDCONTROL'
    );
    public static $aIbanFlashStatusLabel = array(
        self::NOT_VERIFIED                   => 'Non vérifié',
        self::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un document d\'identité)',
        self::FALSIFIED_OR_MINOR             => 'Falsifiée ou mineur',
        self::ILLEGIBLE                      => 'Illisible / coupée',
        self::VERSO_MISSING                  => 'Banque hors périmètre',
        self::NAME_SURNAME_INVERSION         => 'Non cohérent / données : inversion nom - prénom',
        self::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : autre erreur',
        self::EXPIRED                        => '-',
        self::CONFORM_COHERENT_NOT_QUALIFIED => 'Vérifié sauf prénom du titulaire non vérifié',
        self::CONFORM_COHERENT_QUALIFIED     => 'Conforme, cohérent et valide'
    );
    public static $aAddressControlStatusLabel = array(
        self::NOT_VERIFIED                   => 'Non vérifié',
        self::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un justificatif de domicile)',
        self::FALSIFIED_OR_MINOR             => 'Falsifiée ou mineur',
        self::ILLEGIBLE                      => 'Illisible / coupée',
        self::VERSO_MISSING                  => 'Fournisseur hors périmètre',
        self::NAME_SURNAME_INVERSION         => 'Non cohérent / données : erreur sur le titulaire',
        self::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : erreur sur l\'adresse',
        self::EXPIRED                        => '-',
        self::CONFORM_COHERENT_NOT_QUALIFIED => 'Vérifié sauf prénom du titulaire non vérifié',
        self::CONFORM_COHERENT_QUALIFIED     => 'Conforme, cohérent et valide'
    );
}