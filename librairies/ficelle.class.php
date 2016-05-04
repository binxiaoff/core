<?php

class ficelle
{
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
        $string = strip_tags(utf8_decode($string));
        $string = strtr($string, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyyNn');
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
        $str_trans = array(
            'À' => 'A', 'à' => 'a', 'Á' => 'A', 'á' => 'a', 'Â' => 'A', 'â' => 'a', 'Ã' => 'A', 'ã' => 'a', 'Ä' => 'A',
            'ä' => 'a', 'Å' => 'A', 'å' => 'a', 'Æ' => 'A', 'æ' => 'a', 'Ç' => 'C', 'ç' => 'c', 'È' => 'E', 'è' => 'e',
            'É' => 'E', 'é' => 'e', 'Ê' => 'E', 'ê' => 'e', 'Ë' => 'E', 'ë' => 'e', 'Ì' => 'I', 'ì' => 'i', 'Í' => 'I',
            'í' => 'i', 'Î' => 'I', 'î' => 'i', 'Ï' => 'I', 'ï' => 'i', 'Ñ' => 'N', 'ñ' => 'n', 'Ò' => 'O', 'ò' => 'o',
            'Ó' => 'O', 'ó' => 'o', 'Ô' => 'O', 'ô' => 'o', 'Õ' => 'O', 'õ' => 'o', 'Ö' => 'O', 'ö' => 'o', 'Ø' => 'O',
            'ø' => 'o', 'Œ' => 'OE', 'œ' => 'oe', 'ß' => 'B', 'Ù' => 'U', 'ù' => 'u', 'Ú' => 'U', 'ú' => 'u',
            'Û' => 'U', 'û' => 'u', 'Ü' => 'U', 'ü' => 'u', 'Ý' => 'Y', 'ý' => 'y', 'Ÿ' => 'Y', 'ÿ' => 'y'
        );

        return strtr($string, $str_trans);
    }

    public function stripAccents($string)
    {
        return $this->speCharNoAccent($string);
    }

    // met des majuscules sur les noms composés
    public function majNom($nom)
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

    public function swift_validate($swift)
    {
        if (! preg_match("#^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$#i", $swift)) {
            return false;
        } else {
            return true;
        }
    }

    public function isIBAN($iban)
    {
        $charConvert = array(
            "A" => "10", "B" => "11", "C" => "12", "D" => "13", "E" => "14", "F" => "15", "G" => "16", "H" => "17",
            "I" => "18", "J" => "19", "K" => "20", "L" => "21", "M" => "22", "N" => "23", "O" => "24", "P" => "25",
            "Q" => "26", "R" => "27",
            "S" => "28", "T" => "29", "U" => "30", "V" => "31", "W" => "32", "X" => "33", "Y" => "34", "Z" => "35"
        );
        $iban        = str_replace(" ", "", $iban);
        $iban        = strtoupper($iban);
        $iban        = substr($iban, 4) . substr($iban, 0, 4);
        $iban        = strtr($iban, $charConvert);
        return intval(bcmod($iban, 97)) === 1;
    }

    // convertit une chaine en tableau avec key personalisable (mis en place le 04/07/2014)
    // ex : $string = "1=>toto;2=>tata;5=>damien"
    public function explodeStr2array($string)
    {
        $string = explode(';', $string); // on explose aux points virgules
        $array  = array();
        foreach ($string as $c) {
            $tab       = explode('=>', $c); // maintenant on explose aux fleches
            $k         = $tab[0]; // key
            $val       = $tab[1]; // value
            $array[$k] = $val; // on a notre key et valeur
        }
        return $array;
    }


    // fonction qui check la complexité d'un mot de passe
    // 10 caractères mini / 1 chiffre / 1 caractère spécial
    public function password_bo($mdp)    // $mdp le mot de passe passé en paramètre
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
        /* Au moins 10 caratères, 1 chiffre et 1 caractère spécial */
        $valid = false;
        if ($longueur >= 10 && $point_caracteres == 5 && $point_chiffre == 3) {
            $valid = true;
        }


        return $valid;

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

    // min, maj, caracteres speciaux obligatoire (chiffres obligatoire si true)
    public function password_fo($mdp, $length, $chiffres = false)    // $mdp le mot de passe passé en paramètre
    {
        // On récupère la longueur du mot de passe
        $longueur = strlen($mdp);
        $point = 0;
        $point_min = 0;
        $point_maj = 0;
        $point_caracteres = 0;
        $point_chiffre = 0;

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
        /* Au moins 10 caratères, 1 chiffre et 1 caractère spécial */
        $valid = false;
        if ($chiffres == true) {
            if ($longueur >= $length && $point_caracteres == 5 && $point_chiffre == 3 && $point_maj == 2 && $point_min == 1) {
                $valid = true;
            }
        } else {
            if ($longueur >= $length && $point_maj == 2 && $point_min == 1) {
                $valid = true;
            }
        }


        return $valid;

    }

    // Générateur de token avec une clé unique
    public function genere_token($key)
    {
        $key   = md5($key);
        $time  = time();
        $token = base64_encode($key . '-' . $time);

        return $token;
    }

    // token receptionné
    // key = clé unique
    // temps = durée de validité (en secondes)
    public function verifier_token($token, $key, $temps)
    {
        $valide = false;

        // token
        if ($token != '') {

            $decode = base64_decode($token); // Decode token
            $tab    = explode('-', $decode); // sépare les données

            // dans les temps
            if ($tab[1] >= (time() - $temps)) {
                // key ok
                if ($tab[0] == md5($key)) {
                    $valide = true;
                }
            }
        }

        return $valide;
    }


    // mobile ou pas mobile
    public function is_mobile()
    {
        $SymbianOS  = stripos($_SERVER['HTTP_USER_AGENT'], 'SymbianOS');
        $IEMobile   = stripos($_SERVER['HTTP_USER_AGENT'], 'IEMobile');
        $Googlebot  = stripos($_SERVER['HTTP_USER_AGENT'], 'Googlebot-Mobile');
        $iPod       = stripos($_SERVER['HTTP_USER_AGENT'], 'iPod');
        $iPhone     = stripos($_SERVER['HTTP_USER_AGENT'], 'iPhone');
        $android    = stripos($_SERVER['HTTP_USER_AGENT'], 'Android');
        $webOS      = stripos($_SERVER['HTTP_USER_AGENT'], 'webOS');
        $BlackBerry = stripos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry');

        // Condition
        if ($SymbianOS || $IEMobile || $Googlebot || $iPod || $iPhone || $android || $webOS || $BlackBerry) {
            return true;
        } else {
            return false;
        }
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
        return number_format($fNumber, $iDecimals, ',', ' ');
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
        return (float) str_replace(array(' ', ','), array('', '.'), $sFormatedNumber);
    }

    /**
     * Check whether given mobile phone number is a mobile phone for given country or not
     * @todo intl
     * @param string $sPhoneNumber
     * @param string $sCountry
     * @return bool
     */
    public function isMobilePhoneNumber($sPhoneNumber, $sCountry)
    {
        if ('fr' === $sCountry && 1 === preg_match('/(\+33|0033|0)[6-7][0-9]{8}/', str_replace(array(' ', '.', ','), '', $sPhoneNumber))) {
            return true;
        }
        return false;
    }
}
