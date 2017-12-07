<?php

class ficelle
{
    public static $normalizeChars = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ă' => 'A',
        'Ç' => 'C',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ñ' => 'N', 'Ń' => 'N',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
        'Ș' => 'S', 'Š' => 'S', 'Ț' => 'T',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ý' => 'Y', 'Ÿ' => 'Y', 'Ž' => 'Z',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ă' => 'a',
        'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ñ' => 'n', 'ń' => 'n',
        'ð' => 'o', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
        'ș' => 's', 'š' => 's','ț' => 't',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y', 'ž' => 'z',
        'Þ' => 'B', 'ß' => 'Ss', 'þ' => 'b', 'ƒ' => 'f', 'Ð' => 'Dj'
    ];

    public function generatePassword($nb)
    {
        $liste_chars  = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $maxi         = strlen($liste_chars) - 1;
        $new_password = '';

        for ($i = 1; $i <= $nb; $i++) {
            $new_password .= substr($liste_chars, rand(0, $maxi), 1);
        }

        return $new_password;
    }

    public function generateSlug($string)
    {
        $string = strip_tags($string);
        $string = $this->stripAccents($string);
        $string = strtolower($string); // lower-case the string
        $string = preg_replace('/[ ]/', '-', $string); // replace special characters by score
        $string = preg_replace('/[^a-z0-9-.]/', '', $string); // replace all non-alphanumeric characters by void
        $string = preg_replace('/[-]{2,}/', '-', $string); // replace multi '-' by once
        $string = preg_replace('/[-]{1,}$/', '', $string); // replace end '-' by void
        return $string;
    }

    public function isEmail($value)
    {
        return (false !== filter_var($value, FILTER_VALIDATE_EMAIL));
    }

    // Encode les caracteres speciaux
    public function speChar2HtmlEntities($string)
    {
        $str_trans = array(
            'À' => '&Agrave;', 'à' => '&agrave;', 'Á' => '&Aacute;', 'á' => '&aacute;', 'Â' => '&Acirc;',
            'â' => '&acirc;', 'Ã' => '&Atilde;', 'ã' => '&atilde;', 'Ä' => '&Auml;', 'ä' => '&auml;', 'Å' => '&Aring;',
            'å' => '&aring;', 'Æ' => '&AElig;', 'æ' => '&aelig;', 'Ç' => '&Ccedil;', 'ç' => '&ccedil;', 'Ð' => '&ETH;',
            'ð' => '&eth;', 'È' => '&Egrave;', 'è' => '&egrave;', 'É' => '&Eacute;', 'é' => '&eacute;',
            'Ê' => '&Ecirc;', 'ê' => '&ecirc;', 'Ë' => '&Euml;', 'ë' => '&euml;', 'Ì' => '&Igrave;', 'ì' => '&igrave;',
            'Í' => '&Iacute;', 'í' => '&iacute;', 'Î' => '&Icirc;', 'î' => '&icirc;', 'Ï' => '&Iuml;', 'ï' => '&iuml;',
            'Ñ' => '&Ntilde;', 'ñ' => '&ntilde;', 'Ò' => '&Ograve;', 'ò' => '&ograve;', 'Ó' => '&Oacute;',
            'ó' => '&oacute;', 'Ô' => '&Ocirc;', 'ô' => '&ocirc;', 'Õ' => '&Otilde;', 'õ' => '&otilde;',
            'Ö' => '&Ouml;', 'ö' => '&ouml;', 'Ø' => '&Oslash;', 'ø' => '&oslash;', 'Œ' => '&OElig;', 'œ' => '&oelig;',
            'ß' => '&szlig;', 'Þ' => '&THORN;', 'þ' => '&thorn;', 'Ù' => '&Ugrave;', 'ù' => '&ugrave;',
            'Ú' => '&Uacute;', 'ú' => '&uacute;', 'Û' => '&Ucirc;', 'û' => '&ucirc;', 'Ü' => '&Uuml;', 'ü' => '&uuml;',
            'Ý' => '&Yacute;', 'ý' => '&yacute;', 'Ÿ' => '&Yuml;', 'ÿ' => '&yuml;'
        );
        return strtr($string, $str_trans);
    }

    // Enleve les accents
    public function speCharNoAccent($string)
    {
        return strtr($string, self::$normalizeChars);
    }

    public function stripAccents($string)
    {
        return $this->speCharNoAccent($string);
    }

    // met des majuscules sur les noms composés
    public function majNom($nom)//TODO delete once all create Client are migrated on new entity
    {
        $nom = strtolower($nom);

        $pos = strrpos($nom, '-');
        if ($pos === false) {
            return ucwords($nom);
        } else {
            $tabNom = explode('-', $nom);
            $newNom = '';
            $i      = 0;
            foreach ($tabNom as $nom) {
                $newNom .= ($i == 0 ? '' : '-') . ucwords($nom);
                $i++;
            }
            return $newNom;
        }
    }

    public function str_split_unicode($str, $l = 0)
    {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    //fonction qui check la complexité d'un mot de passe
    public function testpassword($mdp)    // $mdp le mot de passe passé en paramètre
    {
        $point_min        = 0;
        $point_maj        = 0;
        $point            = 0;
        $point_chiffre    = 0;
        $point_caracteres = 0;

        // On récupère la longueur du mot de passe
        $longueur = strlen($mdp);

        // On fait une boucle pour lire chaque lettre
        for ($i = 0; $i < $longueur; $i++) {

            // On sélectionne une à une chaque lettre
            // $i étant à 0 lors du premier passage de la boucle
            $lettre = $mdp[$i];

            if ($lettre >= 'a' && $lettre <= 'z') {
                // On ajoute 1 point pour une minuscule
                $point = $point + 1;

                // On rajoute le bonus pour une minuscule
                $point_min = 1;
            } else {
                if ($lettre >= 'A' && $lettre <= 'Z') {
                    // On ajoute 2 points pour une majuscule
                    $point = $point + 2;

                    // On rajoute le bonus pour une majuscule
                    $point_maj = 2;
                } else {
                    if ($lettre >= '0' && $lettre <= '9') {
                        // On ajoute 3 points pour un chiffre
                        $point = $point + 3;

                        // On rajoute le bonus pour un chiffre
                        $point_chiffre = 3;
                    } else {
                        // On ajoute 5 points pour un caractère autre
                        $point = $point + 5;

                        // On rajoute le bonus pour un caractère autre
                        $point_caracteres = 5;
                    }
                }
            }
        }

        // on vérifie que l'on a bien tout les critères nécessaires pour valider le pass
        /* Au moins 10 caratères, une majuscule & un caractère spécial */
        $valid = false;
        if ($point_min == 1 && $point_maj == 2 && $longueur >= 10 && ($point_caracteres == 5 || $point_chiffre == 3)) {
            $valid = true;
        }


        // Calcul du coefficient points/longueur
        if ($longueur < 1) {
            $longueur = 1;
        }

        $etape1 = $point / $longueur;

        // Calcul du coefficient de la diversité des types de caractères...
        $etape2 = $point_min + $point_maj + $point_chiffre + $point_caracteres;

        // Multiplication du coefficient de diversité avec celui de la longueur
        $resultat = $etape1 * $etape2;

        // Multiplication du résultat par la longueur de la chaîne
        $final = $resultat * $longueur;


        // tableau de retour
        $tab_result = array("score" => $final, "valid" => $valid);


        return $tab_result;

    }

    /**
     * Format numbers to make them readable
     * @todo intl
     * @param float $fNumber
     * @param integer $iDecimals
     * @return string
     */
    public function formatNumber($fNumber, $iDecimals = null)
    {
        if (is_null($iDecimals)) {
            $iDecimals = 2;
        }
        return number_format((float) $fNumber, $iDecimals, ',', ' ');
    }

    /**
     * Clean formated number
     * May come from an input field formated using ficelle::formatNumber method
     * @todo intl
     * @param string $sFormatedNumber
     * @return float
     */
    public function cleanFormatedNumber($sFormatedNumber)
    {
        return (float) str_replace(array(' ', html_entity_decode('&nbsp;'), ','), array('', '', '.'), $sFormatedNumber);
    }

    /**
     * Check whether given mobile phone number is a mobile phone for given country or not
     * @todo intl
     * @param string $sPhoneNumber
     * @param string $sCountry
     * @return bool
     */
    public function isMobilePhoneNumber($sPhoneNumber, $sCountry = 'fr')
    {
        if ('fr' === $sCountry && 1 === preg_match('/(\+33|0033|0)[6-7][0-9]{8}/', str_replace(array(' ', '.', ','), '', $sPhoneNumber))) {
            return true;
        }
        return false;
    }
}
